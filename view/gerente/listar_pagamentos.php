<?php
// view/gerente/listar_pagamentos.php

session_start();

if (
    !isset($_SESSION['usuario']) ||
    $_SESSION['usuario']['perfil_id'] != 2 ||
    !isset($_SESSION['id_academia'])
) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../model/dao/PagamentoDAO.php';

$dao = new \PagamentoDAO();
$idAcademia = (int) $_SESSION['id_academia'];
$pagamentos = $dao->listarPagamentosDaAcademia($idAcademia);
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão Financeira - Dojify</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>
<body>

    <?php include '../includes/header.php'; ?>

    <main class="container">
        <main class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin: 0;">Gestão de Pagamentos</h2>
            <div style="display: flex; gap: 10px;">
                <a href="relatorio_financeiro.php" class="btn btn-info" style="color: white !important;">📊 Emitir Relatório</a>
                <a href="home_gerente.php" class="btn" style="background-color: var(--border-color); color: var(--text-primary) !important;">⬅ Voltar ao Painel</a>
            </div>
        </div>
        
        <p class="text-center text-muted" style="margin-bottom: 24px;">Controlo das mensalidades dos alunos.</p>

        <?php
        $toastTipo = null;
        $toastTexto = '';
        if (isset($_GET['sucesso'])) {
            $toastTipo = 'sucesso';
            $toastTexto = 'Recebimento registado com sucesso!';
        } elseif (isset($_GET['erro'])) {
            $toastTipo = 'erro';
            $toastTexto = 'Não foi possível registar o recebimento. Tente novamente.';
        }
        ?>

        <?php if ($toastTipo): ?>
            <div id="toast" class="toast toast--<?= $toastTipo ?>"><?= htmlspecialchars($toastTexto) ?></div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const toast = document.getElementById('toast');
                    if (!toast) return;
                    requestAnimationFrame(() => toast.classList.add('toast-show'));
                    setTimeout(() => {
                        toast.classList.remove('toast-show');
                        setTimeout(() => toast.remove(), 300);
                    }, 4000);
                });
            </script>
        <?php endif; ?>

        <?php if (empty($pagamentos)): ?>
            <p class="text-center text-muted">Nenhum pagamento registado nesta academia.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>Plano</th>
                        <th>Vencimento</th>
                        <th>Valor (R$)</th>
                        <th>Estado</th>
                        <th>Ações / Registo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagamentos as $pag): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($pag['aluno_nome']) ?></strong></td>
                            <td><?= htmlspecialchars($pag['nome_plano']) ?></td>
                            <td><?= date('d/m/Y', strtotime($pag['data_vencimento'])) ?></td>
                            <td><?= number_format($pag['valor'], 2, ',', '.') ?></td>
                            
                            <td>
                                <?php if ($pag['status'] === 'PAGO'): ?>
                                    <span style="color: #059669; font-weight: 600;">Pago em <?= date('d/m/y', strtotime($pag['data_pagamento'])) ?></span>
                                    <br><small class="text-muted"><?= htmlspecialchars($pag['forma_pagamento']) ?></small>
                                <?php elseif ($pag['status'] === 'ATRASADO' || (strtotime($pag['data_vencimento']) < time() && $pag['status'] === 'PENDENTE')): ?>
                                    <span style="color: #dc2626; font-weight: 600;">Atrasado</span>
                                <?php else: ?>
                                    <span style="color: #d97706; font-weight: 600;">Pendente</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($pag['status'] !== 'PAGO'): ?>
                                    <form action="../../controller/FinanceiroController.php" method="POST" style="padding: 0; margin: 0; box-shadow: none; border: none; background: transparent; display: flex; gap: 8px; align-items: center; width: auto;">
                                        
                                        <input type="hidden" name="acao" value="baixar_pagamento">
                                        <input type="hidden" name="id_pagamento" value="<?= $pag['id_pagamento'] ?>">
                                        
                                        <select name="forma_pagamento" style="padding: 4px; width: 110px; font-size: 0.8rem; margin: 0;" required>
                                            <option value="PIX">PIX</option>
                                            <option value="CARTAO">Cartão</option>
                                            <option value="DINHEIRO">Dinheiro</option>
                                        </select>
                                        
                                        <button type="submit" class="btn btn-sm btn-success" style="margin: 0; padding: 6px 12px; width: auto;" onclick="return confirm('Confirmar o recebimento deste pagamento?');">
                                            Receber
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.85rem;">Fechado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>

    <?php include '../includes/footer.php'; ?>

</body>
</html>