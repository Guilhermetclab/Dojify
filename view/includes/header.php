<?php
// view/includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$notificacoes = ['total_atrasados' => 0, 'valor_atrasados' => 0, 'lista' => []];

// Se for um Gerente logado, busca as notificações financeiras
if (isset($_SESSION['usuario']) && (int)$_SESSION['usuario']['perfil_id'] === 2 && isset($_SESSION['id_academia'])) {
    require_once __DIR__ . '/../../model/dao/PagamentoDAO.php';
    $pagamentoDAO = new \PagamentoDAO();
    $notificacoes = $pagamentoDAO->obterNotificacoesFinanceiras((int)$_SESSION['id_academia']);
}
?>

<header class="navbar">
    <div class="navbar-brand">
        <a href="../gerente/home_gerente.php" class="logo-link">
            <img src="../../assets/img/Dojify_original2.png" alt="Dojify Logo" class="navbar-logo">
            <div>
                <h1>Dojify</h1>
            </div>
        </a>
    </div>

    <div class="navbar-user">

        <!-- ÍCONE DE NOTIFICAÇÕES -->
        <?php if (isset($_SESSION['usuario']) && (int)$_SESSION['usuario']['perfil_id'] === 2): ?>
            <div class="notificacao-container">
                <button type="button" id="btnNotificacao" class="btn-notificacao">
                    🔔
                    <?php if ($notificacoes['total_atrasados'] > 0): ?>
                        <span class="notificacao-badge">
                            <?= $notificacoes['total_atrasados'] ?>
                        </span>
                    <?php endif; ?>
                </button>

                <!-- MENU DROPDOWN DE NOTIFICAÇÕES -->
                <div id="dropdownNotificacoes" class="notificacao-dropdown">

                    <!-- Cabeçalho do Dropdown -->
                    <div class="notificacao-dropdown-header">
                        <strong>Notificações</strong>
                        <span class="notificacao-count-badge">
                            <?= $notificacoes['total_atrasados'] ?> pendências
                        </span>
                    </div>

                    <!-- Lista de Atrasados -->
                    <div class="notificacao-lista">
                        <?php if (empty($notificacoes['lista'])): ?>
                            <p class="notificacao-vazio">
                                Tudo em dia! Nenhuma pendência. 🎉
                            </p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($notificacoes['lista'] as $item): ?>
                                    <li>
                                        <div class="notificacao-item-nome">
                                            <?= htmlspecialchars($item['aluno_nome']) ?>
                                        </div>
                                        <div class="notificacao-item-info">
                                            <span>Venceu: <?= date('d/m/Y', strtotime($item['data_vencimento'])) ?></span>
                                            <strong>R$ <?= number_format($item['valor'], 2, ',', '.') ?></strong>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <!-- Rodapé do Dropdown -->
                    <?php if (!empty($notificacoes['lista'])): ?>
                        <div class="notificacao-dropdown-footer">
                            <a href="../gerente/listar_pagamentos.php" class="btn btn-success">
                                Resolver Pendências
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Dados do Utilizador -->
        <span class="user-greeting">Olá, <strong><?= htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Usuário') ?></strong></span>
        <a href="../gerente/home_gerente.php" class="btn btn-sm btn-outline">Início</a>
        <a href="../../controller/UsuarioController.php?acao=logout" class="btn btn-sm btn-danger">Sair</a>
    </div>
</header>

<!-- SCRIPT PARA ABRIR/FECHAR O DROPDOWN -->

<script src="../../assets/js/main.js"></script>
