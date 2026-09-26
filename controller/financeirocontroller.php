<?php
// controller/FinanceiroController.php

session_start();

require_once __DIR__ . '/../model/dao/PagamentoDAO.php';

class FinanceiroController {
    private \PagamentoDAO $dao;

    public function __construct() {
        $this->dao = new \PagamentoDAO();
    }

    public function processarRequisicao(): void {
        if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['perfil_id'] !== 2) {
            header('Location: ../view/login.php?erro=acesso_negado');
            exit;
        }

        $acao = $_REQUEST['acao'] ?? '';

        switch ($acao) {
            case 'baixar_pagamento':
                $this->baixarPagamento();
                break;
            default:
                header('Location: ../view/gerente/home_gerente.php');
                exit;
        }
    }

    private function baixarPagamento(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $idPagamento = filter_input(INPUT_POST, 'id_pagamento', FILTER_VALIDATE_INT);
        $formaPagamento = trim($_POST['forma_pagamento'] ?? 'DINHEIRO');

        if ($idPagamento && $this->dao->registarRecebimento($idPagamento, $formaPagamento)) {
            header('Location: ../view/gerente/listar_pagamentos.php?sucesso=recebimento_registado');
        } else {
            header('Location: ../view/gerente/listar_pagamentos.php?erro=falha_recebimento');
        }
        exit;
    }
}

if (isset($_REQUEST['acao'])) {
    $controller = new FinanceiroController();
    $controller->processarRequisicao();
}
?>