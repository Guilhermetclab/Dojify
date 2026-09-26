<?php
// view/includes/sidebar.php
?>
<aside class="sidebar" style="width: 250px; background-color: var(--bg-card); border-right: 1px solid var(--border-color); padding: 20px; min-height: calc(100vh - 70px); float: left; margin-right: 20px;">
    <h3 style="font-size: 1rem; color: var(--text-secondary); margin-bottom: 16px; text-transform: uppercase;">Menu Principal</h3>
    <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; gap: 10px;">
        <li>
            <a href="../gerente/home_gerente.php" class="btn" style="width: 100%; justify-content: flex-start; background-color: transparent; color: var(--text-primary) !important; border: 1px solid var(--border-color);">
                🏠 Início
            </a>
        </li>
        <li>
            <a href="../gerente/listar_usuarios.php" class="btn" style="width: 100%; justify-content: flex-start; background-color: transparent; color: var(--text-primary) !important; border: 1px solid var(--border-color);">
                👥 Alunos & Professores
            </a>
        </li>
        <li>
            <a href="../gerente/listar_planos.php" class="btn" style="width: 100%; justify-content: flex-start; background-color: transparent; color: var(--text-primary) !important; border: 1px solid var(--border-color);">
                📋 Planos
            </a>
        </li>
        <li>
            <a href="../gerente/listar_pagamentos.php" class="btn" style="width: 100%; justify-content: flex-start; background-color: transparent; color: var(--text-primary) !important; border: 1px solid var(--border-color);">
                💰 Financeiro
            </a>
        </li>
        <li>
            <a href="../gerente/listar_modalidade.php" class="btn" style="width: 100%; justify-content: flex-start; background-color: transparent; color: var(--text-primary) !important; border: 1px solid var(--border-color);">
                🥋 Modalidades
            </a>
        </li>
        <li>
            <a href="../turmas/listar_turma.php" class="btn" style="width: 100%; justify-content: flex-start; background-color: transparent; color: var(--text-primary) !important; border: 1px solid var(--border-color);">
                📅 Turmas
            </a>
        </li>
    </ul>
</aside>