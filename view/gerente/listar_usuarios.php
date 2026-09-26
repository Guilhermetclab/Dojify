<?php
// view/gerente/listar_usuarios.php
session_start();

// Verifica se é Gerente (perfil_id = 2)
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['perfil_id'] !== 2) {
    header('Location: ../login.php?erro=acesso_negado');
    exit;
}

require_once __DIR__ . '/../../model/dao/UsuarioDAO.php';

$idAcademia = $_SESSION['usuario']['id_academia'];
$idGerenteLogado = $_SESSION['usuario']['id_usuario'] ?? $_SESSION['usuario']['id'] ?? 0;

$usuarioDAO = new UsuarioDAO();
$usuarios = $usuarioDAO->listarPorAcademia($idAcademia);

$gerentes = [];
$professores = [];
$alunos = [];

foreach ($usuarios as $u) {
    $perfil = strtolower($u['perfil_nome'] ?? '');
    $perfilId = (int)($u['perfil_id'] ?? 0);

    if ($perfilId === 2 || str_contains($perfil, 'gerente')) {
        $gerentes[] = $u;
    } 
    elseif ($perfilId === 3 || str_contains($perfil, 'professor') || str_contains($perfil, 'instrutor')) {
        $professores[] = $u;
    } 
    else {
        $alunos[] = $u;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Alunos e Professores - Dojify</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>
<body>    
    <?php include '../includes/header.php'; ?> 
    <div class="container">
        <h2>Gestão de Utilizadores</h2>
        <p class="text-center text-muted" style="margin-bottom: 24px;">Administração de professores e alunos registados na sua academia.</p>

        <!-- Barra de Pesquisa a aproveitar as classes nativas do design system -->
        <div class="busca-container">
            <div class="busca-form">
                <input type="text" id="inputPesquisa" placeholder="Pesquisar por nome ou e-mail..." class="busca-input">
            </div>
        </div>


        <!-- SECÇÃO DE GERENTES -->
        <h3 style="margin: 24px 0 12px 0;">Gerente</h3>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Perfil</th>
                    <th>E-mail</th>
                    <th>Telefone</th>
                    <th>Estado</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($gerentes)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Nenhum gerente registado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($gerentes as $u): ?>
                        <?php $isPropraConta = ((int)$u['id_usuario'] === (int)$idGerenteLogado); ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($u['nome']); ?></strong>
                                <?php if ($isPropraConta): ?>
                                    <span class="text-muted">(Sua conta)</span>
                                <?php endif; ?>
                            </td>
                            <td><span><?php echo htmlspecialchars($u['perfil_nome'] ?? 'Gerente'); ?></span></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['telefone']); ?></td>
                            <td>
                                <?php if ($u['status'] === 'ATIVO'): ?>
                                    <span>Ativo</span>
                                <?php elseif ($u['status'] === 'SUSPENSO'): ?>
                                    <span>Suspenso</span>
                                <?php else: ?>
                                    <span>Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isPropraConta): ?>
                                    <span class="text-muted">Não aplicável</span>
                                <?php else: ?>
                                    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                        <?php if ($u['status'] !== 'ATIVO'): ?>
                                            <a href="../../controller/UsuarioController.php?acao=alternar_status&id=<?php echo $u['id_usuario']; ?>&status=ATIVO" class="btn btn-sm btn-success">Ativar</a>
                                        <?php endif; ?>
                                        <?php if ($u['status'] !== 'SUSPENSO'): ?>
                                            <a href="../../controller/UsuarioController.php?acao=alternar_status&id=<?php echo $u['id_usuario']; ?>&status=SUSPENSO" class="btn btn-sm btn-warning">Suspender</a>
                                        <?php endif; ?>
                                        <?php if ($u['status'] !== 'INATIVO'): ?>
                                            <a href="../../controller/UsuarioController.php?acao=alternar_status&id=<?php echo $u['id_usuario']; ?>&status=INATIVO" class="btn btn-sm btn-danger">Inativar</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- SECÇÃO DE PROFESSORES -->
        <h3 style="margin: 32px 0 12px 0;">Professores</h3>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Perfil</th>
                    <th>E-mail</th>
                    <th>Telefone</th>
                    <th>Estado</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($professores)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Nenhum professor registado nesta academia.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($professores as $u): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($u['nome']); ?></strong></td>
                            <td><span><?php echo htmlspecialchars($u['perfil_nome'] ?? 'Professor'); ?></span></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['telefone']); ?></td>
                            <td>
                                <?php if ($u['status'] === 'ATIVO'): ?>
                                    <span>Ativo</span>
                                <?php elseif ($u['status'] === 'SUSPENSO'): ?>
                                    <span>Suspenso</span>
                                <?php else: ?>
                                    <span>Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    <!-- Botão de Editar Professor -->
                                    <a href="editar_professor.php?id=<?php echo $u['id_usuario']; ?>" class="btn btn-sm btn-info">Editar</a>

                                    <?php if ($u['status'] !== 'ATIVO'): ?>
                                        <a href="../../controller/UsuarioController.php?acao=alternar_status&id=<?php echo $u['id_usuario']; ?>&status=ATIVO" class="btn btn-sm btn-success">Ativar</a>
                                    <?php endif; ?>
                                    <?php if ($u['status'] !== 'SUSPENSO'): ?>
                                        <a href="../../controller/UsuarioController.php?acao=alternar_status&id=<?php echo $u['id_usuario']; ?>&status=SUSPENSO" class="btn btn-sm btn-warning">Suspender</a>
                                    <?php endif; ?>
                                    <?php if ($u['status'] !== 'INATIVO'): ?>
                                        <a href="../../controller/UsuarioController.php?acao=alternar_status&id=<?php echo $u['id_usuario']; ?>&status=INATIVO" class="btn btn-sm btn-danger">Inativar</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- SECÇÃO DE ALUNOS -->
        <h3 style="margin: 32px 0 12px 0;">Alunos</h3>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Perfil</th>
                    <th>E-mail</th>
                    <th>Telefone</th>
                    <th>Estado</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($alunos)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Nenhum aluno registado nesta academia.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($alunos as $u): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($u['nome']); ?></strong>
                                <?php if (!empty($u['responsavel'])): ?>
                                    <br><span class="text-muted" style="font-size: 0.75rem;">Resp: <?php echo htmlspecialchars($u['responsavel']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span><?php echo htmlspecialchars($u['perfil_nome'] ?? 'Aluno'); ?></span></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['telefone']); ?></td>
                            <td>
                                <?php if ($u['status'] === 'ATIVO'): ?>
                                    <span>Ativo</span>
                                <?php elseif ($u['status'] === 'SUSPENSO'): ?>
                                    <span>Suspenso</span>
                                <?php else: ?>
                                    <span>Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    <!-- Botão de Editar Aluno -->
                                    <a href="editar_aluno.php?id=<?php echo $u['id_usuario']; ?>" class="btn btn-sm btn-info">Editar</a>

                                    <?php if ($u['status'] !== 'ATIVO'): ?>
                                        <a href="../../controller/UsuarioController.php?acao=alternar_status&id=<?php echo $u['id_usuario']; ?>&status=ATIVO" class="btn btn-sm btn-success">Ativar</a>
                                    <?php endif; ?>
                                    <?php if ($u['status'] !== 'SUSPENSO'): ?>
                                        <a href="../../controller/UsuarioController.php?acao=alternar_status&id=<?php echo $u['id_usuario']; ?>&status=SUSPENSO" class="btn btn-sm btn-warning">Suspender</a>
                                    <?php endif; ?>
                                    <?php if ($u['status'] !== 'INATIVO'): ?>
                                        <a href="../../controller/UsuarioController.php?acao=alternar_status&id=<?php echo $u['id_usuario']; ?>&status=INATIVO" class="btn btn-sm btn-danger">Inativar</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

    <!-- Script de Pesquisa em Tempo Real -->
    <script>
    document.getElementById('inputPesquisa').addEventListener('keyup', function() {
        const termo = this.value.toLowerCase().trim();
        const tabelas = document.querySelectorAll('table');

        tabelas.forEach(tabela => {
            const linhas = tabela.querySelectorAll('tbody tr');
            
            linhas.forEach(linha => {
                if (linha.cells.length === 1) return; 

                const textoLinha = linha.textContent.toLowerCase();
                
                if (textoLinha.includes(termo)) {
                    linha.style.display = ''; 
                } else {
                    linha.style.display = 'none'; 
                }
            });
        });
    });
    </script>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Dojify. Todos os direitos reservados.</p>
    </footer>
</body>
</html>