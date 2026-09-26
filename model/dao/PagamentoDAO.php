<?php

require_once __DIR__ . '/Conexao.php';
require_once __DIR__ . '/../dto/PagamentoDTO.php';

class PagamentoDAO {
    private PDO $conexao;

    public function __construct() {
        $this->conexao = \Conexao::getConexao();
    }

    public function listarPagamentosDaAcademia(int $idAcademia): array {
        $sql = "SELECT p.id_pagamento, p.valor, p.data_vencimento, p.data_pagamento, p.status, p.forma_pagamento,
                       pl.nome_plano, u.nome AS aluno_nome
                FROM pagamento p
                INNER JOIN plano pl ON p.id_plano_matricula = pl.id_plano
                INNER JOIN usuario u ON pl.id_usuario_aluno = u.id_usuario
                WHERE u.id_academia = :id_academia
                AND u.perfil_id = 4
                ORDER BY p.data_vencimento ASC";
                
        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id_academia', $idAcademia, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registarRecebimento(int $idPagamento, string $formaPagamento): bool {
        try {
            $sql = "UPDATE pagamento 
                    SET status = 'PAGO', data_pagamento = CURDATE(), forma_pagamento = :forma 
                    WHERE id_pagamento = :id";
                    
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':forma', $formaPagamento);
            $stmt->bindValue(':id', $idPagamento, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // ==========================================================
    // MÉTODO NOVO 1: NOTIFICAÇÕES (Sino do Topo)
    // ==========================================================
    public function obterNotificacoesFinanceiras(int $idAcademia): array {
        try {
            // Contagem e soma total de pagamentos atrasados
            $sqlAtrasados = "SELECT COUNT(*) as total_qtd, COALESCE(SUM(p.valor), 0) as total_valor
                             FROM pagamento p
                             INNER JOIN plano pl ON p.id_plano_matricula = pl.id_plano
                             INNER JOIN usuario u ON pl.id_usuario_aluno = u.id_usuario
                             WHERE u.id_academia = :id_academia
                               AND (p.status = 'ATRASADO' OR (p.status = 'PENDENTE' AND p.data_vencimento < CURDATE()))";

            $stmt = $this->conexao->prepare($sqlAtrasados);
            $stmt->bindValue(':id_academia', $idAcademia, PDO::PARAM_INT);
            $stmt->execute();
            $resAtrasados = $stmt->fetch(PDO::FETCH_ASSOC);

            // Lista os últimos 5 alunos inadimplentes para o menu suspenso
            $sqlLista = "SELECT p.id_pagamento, p.valor, p.data_vencimento, u.nome AS aluno_nome
                         FROM pagamento p
                         INNER JOIN plano pl ON p.id_plano_matricula = pl.id_plano
                         INNER JOIN usuario u ON pl.id_usuario_aluno = u.id_usuario
                         WHERE u.id_academia = :id_academia
                           AND (p.status = 'ATRASADO' OR (p.status = 'PENDENTE' AND p.data_vencimento < CURDATE()))
                         ORDER BY p.data_vencimento ASC
                         LIMIT 5";

            $stmtLista = $this->conexao->prepare($sqlLista);
            $stmtLista->bindValue(':id_academia', $idAcademia, PDO::PARAM_INT);
            $stmtLista->execute();
            $listaAtrasados = $stmtLista->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total_atrasados' => (int)($resAtrasados['total_qtd'] ?? 0),
                'valor_atrasados' => (float)($resAtrasados['total_valor'] ?? 0),
                'lista'           => $listaAtrasados
            ];
        } catch (PDOException $e) {
            return [
                'total_atrasados' => 0,
                'valor_atrasados' => 0,
                'lista'           => []
            ];
        }
    }

    // ==========================================================
    // MÉTODO NOVO 2: EMISSÃO DE RELATÓRIO FINANCEIRO
    // ==========================================================
    public function relatorioPagamentos(int $idAcademia, string $dataInicio, string $dataFim, string $status): array {
        $sql = "SELECT p.id_pagamento, p.valor, p.data_vencimento, p.data_pagamento, p.status, p.forma_pagamento,
                       pl.nome_plano, u.nome AS aluno_nome
                FROM pagamento p
                INNER JOIN plano pl ON p.id_plano_matricula = pl.id_plano
                INNER JOIN usuario u ON pl.id_usuario_aluno = u.id_usuario
                WHERE u.id_academia = :id_academia";
        
        // 1. FILTRO DE DATAS ABRANGENTE: 
        // Apanha o registo se ele VENCEU neste mês OU se foi PAGO neste mês
        $sql .= " AND (
                    (DATE(p.data_vencimento) >= :data_inicio AND DATE(p.data_vencimento) <= :data_fim)
                    OR 
                    (DATE(p.data_pagamento) >= :data_inicio AND DATE(p.data_pagamento) <= :data_fim)
                  )";
        
        // 2. FILTRO DE ESTADO (TODOS JUNTOS OU SEPARADOS):
        // Se não for 'TODOS', adiciona o filtro específico (PAGO, PENDENTE ou ATRASADO)
        if ($status !== 'TODOS') {
            $sql .= " AND UPPER(p.status) = :status";
        }
        
        $sql .= " ORDER BY p.data_vencimento ASC";
        
        $stmt = $this->conexao->prepare($sql);
        
        // Atribuição de valores
        $stmt->bindValue(':id_academia', $idAcademia, PDO::PARAM_INT);
        $stmt->bindValue(':data_inicio', $dataInicio);
        $stmt->bindValue(':data_fim', $dataFim);
        
        if ($status !== 'TODOS') {
            $stmt->bindValue(':status', strtoupper($status));
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} // <-- Fim da classe PagamentoDAO
?>