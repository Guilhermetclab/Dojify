<?php
// aba relatorio com css inline
// view/gerente/relatorio_financeiro.php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil_id'] != 2 || !isset($_SESSION['id_academia'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../model/dao/PagamentoDAO.php';

$dao = new \PagamentoDAO();
$idAcademia = (int) $_SESSION['id_academia'];
$dadosRelatorio = [];

// Valores padrão para os filtros
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01'); // 1º dia do mês atual
$dataFim = $_GET['data_fim'] ?? date('Y-m-t'); // Último dia do mês atual
$status = $_GET['status'] ?? 'TODOS';
$totalValor = 0;

// Se o formulário de filtro foi submetido, busca os dados
if (isset($_GET['filtrar'])) {
    $dadosRelatorio = $dao->relatorioPagamentos($idAcademia, $dataInicio, $dataFim, $status);
    foreach ($dadosRelatorio as $pag) {
        $totalValor += $pag['valor'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Financeiro - Dojify</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
    <style>
        /* Regras exclusivas para quando o utilizador imprimir a página ou gerar PDF */
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; padding: 0; margin: 0; }
            .conteudo { box-shadow: none; border: none; padding: 0; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ccc; padding: 8px; font-size: 12px; }
            th { background-color: #f1f5f9 !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            span[style*="background-color"] { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <?php include '../includes/header.php'; ?>
    </div>

    <main class="container conteudo" style="background-color: var(--bg-card); padding: 24px; border-radius: 8px; margin-top: 24px;">
        
        <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin: 0;">Emissão de Relatório Financeiro</h2>
            <a href="listar_pagamentos.php" class="btn" style="background-color: var(--border-color); color: var(--text-primary) !important;">⬅ Voltar</a>
        </div>

        <!-- Formulário de Filtros (Não aparece na impressão) -->
        <form method="GET" class="no-print" style="max-width: 100%; display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; margin-bottom: 24px; padding: 16px; background: var(--bg-hover); border-radius: 8px;">
            <div style="flex: 1; min-width: 150px;">
                <label for="data_inicio">Data Início</label>
                <input type="date" name="data_inicio" id="data_inicio" value="<?= htmlspecialchars($dataInicio) ?>" required style="margin-top: 4px; width: 100%;">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label for="data_fim">Data Fim</label>
                <input type="date" name="data_fim" id="data_fim" value="<?= htmlspecialchars($dataFim) ?>" required style="margin-top: 4px; width: 100%;">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label for="status">Estado</label>
                <select name="status" id="status" style="margin-top: 4px; width: 100%;">
                    <option value="TODOS" <?= $status === 'TODOS' ? 'selected' : '' ?>>Todos</option>
                    <option value="PAGO" <?= $status === 'PAGO' ? 'selected' : '' ?>>Pagos</option>
                    <option value="PENDENTE" <?= $status === 'PENDENTE' ? 'selected' : '' ?>>Pendentes</option>
                    <option value="ATRASADO" <?= $status === 'ATRASADO' ? 'selected' : '' ?>>Atrasados</option>
                </select>
            </div>
            <div style="flex: 0 0 auto;">
                <button type="submit" name="filtrar" value="1" class="btn btn-info" style="margin-top: 0; height: 42px; width: auto !important; padding: 0 24px;">Gerar</button>
            </div>
        </form>

        <!-- Área do Relatório (Aparece na impressão) -->
        <?php if (isset($_GET['filtrar'])): ?>
            
            <div style="text-align: center; margin-bottom: 24px;">
                <h3 style="margin-bottom: 4px;">Relatório de Mensalidades - Dojify</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">
                    Período: <?= date('d/m/Y', strtotime($dataInicio)) ?> a <?= date('d/m/Y', strtotime($dataFim)) ?> | 
                    Estado: <?= $status === 'TODOS' ? 'Geral' : ucfirst(strtolower($status)) ?>
                </p>
            </div>

            <?php if (empty($dadosRelatorio)): ?>
                <p class="text-center text-muted">Nenhum registo encontrado para este período.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Plano</th>
                            <th>Vencimento</th>
                            <th>Valor (R$)</th>
                            <th>Estado</th>
                            <th>Forma de Pag.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dadosRelatorio as $pag): ?>
                            <?php 
                                // Definir cores do badge conforme o estado
                                $statusUpper = strtoupper($pag['status']);
                                if ($statusUpper === 'PAGO') {
                                    $bgBadge = '#d1fae5';
                                    $txtBadge = '#065f46';
                                } elseif ($statusUpper === 'PENDENTE') {
                                    $bgBadge = '#fef3c7';
                                    $txtBadge = '#92400e';
                                } elseif ($statusUpper === 'ATRASADO') {
                                    $bgBadge = '#fee2e2';
                                    $txtBadge = '#991b1b';
                                } else {
                                    $bgBadge = '#e2e8f0';
                                    $txtBadge = '#475569';
                                }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($pag['aluno_nome']) ?></td>
                                <td><?= htmlspecialchars($pag['nome_plano']) ?></td>
                                <td><?= date('d/m/Y', strtotime($pag['data_vencimento'])) ?></td>
                                <td><?= number_format($pag['valor'], 2, ',', '.') ?></td>
                                <td>
                                    <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; background-color: <?= $bgBadge ?>; color: <?= $txtBadge ?>;">
                                        <?= htmlspecialchars($pag['status']) ?>
                                    </span>
                                </td>
                                <td><?= $pag['forma_pagamento'] ? htmlspecialchars($pag['forma_pagamento']) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="text-align: right; margin-top: 20px; padding-top: 16px; border-top: 2px solid var(--border-color);">
                    <h3 style="margin: 0;">Total: R$ <?= number_format($totalValor, 2, ',', '.') ?></h3>
                </div>

                <div class="no-print" style="text-align: center; margin-top: 32px;">
                    <button onclick="window.print()" class="btn btn-success" style="width: auto; padding: 10px 24px; font-size: 1rem;">🖨️ Imprimir / Guardar PDF</button>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </main>
</body>
</html>