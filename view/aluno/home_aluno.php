<?php
// view/aluno/home_aluno.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// cspell:disable-next-line
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['perfil_id'] !== 4) {
    header('Location: ../login.php?erro=acesso_negado');
    exit;
}

require_once __DIR__ . '/../../model/dao/conexao.php';

$id_aluno = $_SESSION['usuario']['id_usuario'];
$mensagem_erro = "";
$mensagem_sucesso = "";
$is_primeiro_acesso = false;

try {
    $pdo_agenda = \Conexao::getConexao();

    // 1. VERIFICAR SE É O PRIMEIRO ACESSO DO ALUNO
    // (Garante que a coluna primeiro_acesso existe. Se der erro, assumimos que não é primeiro acesso para não bloquear o sistema)
    try {
        $stmt_check = $pdo_agenda->prepare("SELECT primeiro_acesso FROM usuario WHERE id_usuario = ?");
        $stmt_check->execute([$id_aluno]);
        $user_data = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data && (int)$user_data['primeiro_acesso'] === 1) {
            $is_primeiro_acesso = true;
        }
    } catch (Exception $e) {
        $is_primeiro_acesso = false; // Se a coluna não existir, passa direto
    }

    // 2. PROCESSAR O FORMULÁRIO DE PRIMEIRO ACESSO (CONTRATO E SENHA)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_aceite_contrato'])) {
        $senha_nova = $_POST['senha_nova'] ?? '';
        $senha_confirma = $_POST['senha_confirma'] ?? '';
        $aceite = isset($_POST['aceite_contrato']) ? true : false;

        if (!$aceite) {
            $mensagem_erro = "Precisa de ler e aceitar o contrato da academia para continuar.";
        } elseif (empty($senha_nova) || strlen($senha_nova) < 6) {
            $mensagem_erro = "A nova senha deve ter pelo menos 6 caracteres.";
        } elseif ($senha_nova !== $senha_confirma) {
            $mensagem_erro = "As senhas não coincidem. Tente novamente.";
        } else {
            // Tudo certo! Criptografar nova senha e remover o status de primeiro acesso
            $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
            
            $stmt_update = $pdo_agenda->prepare("UPDATE usuario SET senha = ?, primeiro_acesso = 0 WHERE id_usuario = ?");
            if ($stmt_update->execute([$senha_hash, $id_aluno])) {
                // Sucesso! Atualiza a página para carregar o painel normal
                header("Location: home_aluno.php");
                exit;
            } else {
                $mensagem_erro = "Erro ao guardar as alterações. Tente novamente.";
            }
        }
    }

    // ==============================================================================
    // SE NÃO FOR PRIMEIRO ACESSO, CARREGA OS DADOS NORMAIS DO PAINEL DE AGENDAMENTO
    // ==============================================================================
    if (!$is_primeiro_acesso) {
        
        // Processar cancelamento
        if (isset($_GET['cancelar'])) {
            $id_agendamento = $_GET['cancelar'];
            $stmtCancel = $pdo_agenda->prepare("UPDATE agendamento SET status = 'CANCELADO' WHERE id_agendamento = ? AND id_usuario_aluno = ?");
            if ($stmtCancel->execute([$id_agendamento, $id_aluno])) {
                $mensagem_sucesso = "Agendamento cancelado com sucesso.";
            }
        }

        // Processar agendamento rápido
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_agendar_semana'])) {
            $id_turma = $_POST['id_turma'] ?? null;
            $data_escolhida = $_POST['data_escolhida'] ?? null; 
            $hora_aula = $_POST['hora_aula'] ?? '19:00:00';

            if (!empty($id_turma) && !empty($data_escolhida)) {
                $data_formatada = $data_escolhida . ' ' . $hora_aula;
                $data_obj = new DateTime($data_formatada);

                $stmtCap = $pdo_agenda->prepare("SELECT capacidade FROM turma WHERE id_turma = ?");
                $stmtCap->execute([$id_turma]);
                $turmaInfo = $stmtCap->fetch(PDO::FETCH_ASSOC);
                $capacidadeMax = (int)($turmaInfo['capacidade'] ?? 20);

                $stmtCountTurma = $pdo_agenda->prepare("SELECT COUNT(*) as total FROM agendamento WHERE id_turma = ? AND DATE(data_agendamento) = DATE(?) AND status = 'CONFIRMADO'");
                $stmtCountTurma->execute([$id_turma, $data_formatada]);
                $vagasOcupadas = (int)$stmtCountTurma->fetch()['total'];

                if ($vagasOcupadas >= $capacidadeMax) {
                    $mensagem_erro = "Turma lotada! Limite de {$capacidadeMax} alunos atingido.";
                } else {
                    try {
                        $stmtPlano = $pdo_agenda->prepare("SELECT p.limite_semanal FROM matricula m JOIN plano p ON m.id_plano = p.id_plano WHERE m.id_usuario_aluno = ? AND m.status = 'ATIVO' LIMIT 1");
                        $stmtPlano->execute([$id_aluno]);
                        $dadosPlano = $stmtPlano->fetch(PDO::FETCH_ASSOC);

                        if ($dadosPlano && isset($dadosPlano['limite_semanal']) && !is_null($dadosPlano['limite_semanal'])) {
                            $limiteSemanal = (int)$dadosPlano['limite_semanal'];
                            $inicioSemana = clone $data_obj; $inicioSemana->modify('monday this week')->setTime(0, 0, 0);
                            $fimSemana = clone $data_obj; $fimSemana->modify('sunday this week')->setTime(23, 59, 59);

                            $stmtCountSemana = $pdo_agenda->prepare("SELECT COUNT(*) as total_semana FROM agendamento WHERE id_usuario_aluno = ? AND status = 'CONFIRMADO' AND data_agendamento BETWEEN ? AND ?");
                            $stmtCountSemana->execute([$id_aluno, $inicioSemana->format('Y-m-d H:i:s'), $fimSemana->format('Y-m-d H:i:s')]);
                            $totalSemana = (int)$stmtCountSemana->fetch()['total_semana'];

                            if ($totalSemana >= $limiteSemanal) {
                                $mensagem_erro = "Limite semanal atingido! O seu plano permite apenas {$limiteSemanal} treino(s) por semana.";
                            }
                        }
                    } catch (Exception $exPlano) {}

                    if (empty($mensagem_erro)) {
                        $stmtIns = $pdo_agenda->prepare("INSERT INTO agendamento (id_turma, id_usuario_aluno, data_agendamento, status) VALUES (?, ?, ?, 'CONFIRMADO')");
                        if ($stmtIns->execute([$id_turma, $id_aluno, $data_formatada])) {
                            $mensagem_sucesso = "Treino agendado com sucesso no tatame! 🥋";
                        } else {
                            $mensagem_erro = "Erro ao registar o agendamento.";
                        }
                    }
                }
            }
        }

        // Buscar turmas
        $stmt_t = $pdo_agenda->query("SELECT t.id_turma, t.nome as nome_turma, t.capacidade, h.dia_semana, h.hora_inicio FROM turma t LEFT JOIN horario_turma h ON t.id_turma = h.id_turma WHERE t.status = 'ATIVA'");
        $turmas_brutas = $stmt_t->fetchAll(PDO::FETCH_ASSOC);

        $calendarioSemanal = [];
        $hoje = new DateTime();
        
        for ($i = 1; $i <= 7; $i++) {
            $diaLoop = clone $hoje;
            $diaLoop->modify('monday this week');
            $diaLoop->modify('+' . ($i - 1) . ' days');
            
            $nomeDiaPt = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][(int)$diaLoop->format('w')];
            
            $calendarioSemanal[$i] = [
                'nome_dia' => $nomeDiaPt,
                'data_iso' => $diaLoop->format('Y-m-d'),
                'data_exibicao' => $diaLoop->format('d/m'),
                'aulas' => []
            ];

            foreach ($turmas_brutas as $turma) {
                $diaTurma = trim(ucfirst(strtolower($turma['dia_semana'] ?? '')));
                if (empty($diaTurma) || $diaTurma === 'Geral' || stripos($diaTurma, $nomeDiaPt) !== false) {
                    $turma['hora_inicio'] = $turma['hora_inicio'] ?? '19:00:00';
                    $calendarioSemanal[$i]['aulas'][] = $turma;
                }
            }
        }

        // Buscar agendamentos
        $stmt_meus = $pdo_agenda->prepare("SELECT a.id_agendamento, a.data_agendamento, t.nome as nome_turma FROM agendamento a JOIN turma t ON a.id_turma = t.id_turma WHERE a.id_usuario_aluno = ? AND a.status = 'CONFIRMADO' ORDER BY a.data_agendamento ASC");
        $stmt_meus->execute([$id_aluno]);
        $meus_agendamentos = $stmt_meus->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    $mensagem_erro = "Erro no sistema: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Aluno - Dojify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body style="background-color: var(--bg-body, #f8f9fa);">

    <?php include '../includes/header.php'; ?>

    <main class="container py-4">
        
        <?php if ($is_primeiro_acesso): ?>
            <!-- ========================================================= -->
            <!-- TELA DE PRIMEIRO ACESSO (CONTRATO + NOVA SENHA) -->
            <!-- ========================================================= -->
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow border-0 rounded-3">
                        <div class="card-header bg-danger text-white text-center py-3">
                            <h4 class="mb-0 fw-bold">🥋 Bem-vindo à Dojify!</h4>
                        </div>
                        <div class="card-body p-4">
                            <p class="text-center text-muted mb-4">Para liberar o seu painel e começar a agendar treinos, precisamos que defina a sua nova senha de acesso e aceite os termos da academia.</p>

                            <?php if (!empty($mensagem_erro)): ?>
                                <div class="alert alert-danger py-2 text-center fw-bold"><?= $mensagem_erro; ?></div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <input type="hidden" name="acao_aceite_contrato" value="1">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Definir Nova Senha</label>
                                    <input type="password" name="senha_nova" class="form-control bg-light" required placeholder="Mínimo 6 caracteres">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-bold small">Confirmar Nova Senha</label>
                                    <input type="password" name="senha_confirma" class="form-control bg-light" required placeholder="Repita a senha">
                                </div>

                                <div class="border rounded p-3 mb-3 bg-light" style="max-height: 150px; overflow-y: auto; font-size: 0.85rem;">
                                    <strong class="d-block mb-2">Termo de Responsabilidade e Matrícula</strong>
                                    Declaro para os devidos fins que estou em plenas condições de saúde física e mental para a prática de artes marciais. Concordo em respeitar as regras do Dojô, os mestres e os meus colegas de treino. Estou ciente das políticas de mensalidade e cancelamento da academia.
                                    <br><br>
                                    <em>* Este é um contrato padrão. Ao assinalar a caixa abaixo, concorda digitalmente com os termos impostos pela academia.</em>
                                </div>

                                <div class="form-check mb-4">
                                    <input class="form-check-input border-secondary" type="checkbox" name="aceite_contrato" id="aceite_contrato" required>
                                    <label class="form-check-label small fw-bold text-dark" for="aceite_contrato">
                                        Li e aceito os termos do contrato da academia.
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-danger w-100 fw-bold py-2">Confirmar e Entrar no Painel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- ========================================================= -->
            <!-- PAINEL NORMAL (CARTÕES E CALENDÁRIO) -->
            <!-- ========================================================= -->
            <h2 class="text-center mb-2">Painel do Aluno</h2>
            <p class="text-muted text-center mb-4">Bem-vindo(a), <?= htmlspecialchars($_SESSION['usuario']['nome']) ?>! Acompanhe a sua evolução e treinos.</p>

            <!-- Alertas -->
            <?php if (!empty($mensagem_sucesso)): ?>
                <div class="alert alert-success text-center py-2"><?= $mensagem_sucesso; ?></div>
            <?php endif; ?>
            <?php if (!empty($mensagem_erro)): ?>
                <div class="alert alert-danger text-center py-2"><?= $mensagem_erro; ?></div>
            <?php endif; ?>

            <!-- Cartões Originais -->
            <div class="row g-3 justify-content-center mb-4">
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border p-2 text-center">
                        <h6 class="text-dark fw-bold mb-1">🥋 Graduação</h6>
                        <p class="text-dark fw-bold mb-0">Faixa Branca</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border p-2 text-center">
                        <h6 class="text-dark fw-bold mb-1">⭐ XP & Nível</h6>
                        <p class="text-muted small mb-0">Nível 1 (150 / 300 XP)</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border p-2 text-center">
                        <h6 class="text-dark fw-bold mb-1">📋 Matrícula</h6>
                        <p class="mb-0"><span class="badge bg-success">Ativo</span></p>
                    </div>
                </div>
            </div>

            <!-- CALENDÁRIO SEMANAL COMPACTO -->
            <div class="card shadow-sm border p-3 mb-4">
                <h5 class="text-uppercase fw-bold text-danger mb-1" style="font-size: 1rem;">📅 Agenda Semanal de Treinos</h5>
                <p class="text-muted small mb-3">Escolha a sua turma e clique em agendar no dia respetivo.</p>

                <div class="d-flex overflow-auto pb-2" style="gap: 10px;">
                    <?php foreach ($calendarioSemanal as $dia): ?>
                        <div class="shadow-sm border border-secondary text-white rounded" style="flex: 0 0 135px; background-color: #1a1a1a;">
                            <div class="text-white fw-bold text-center p-1" style="background-color: #b30000; font-size: 0.8rem; border-radius: 5px 5px 0 0;">
                                <?= $dia['nome_dia']; ?><br>
                                <span class="fw-normal" style="font-size: 0.7rem;"><?= $dia['data_exibicao']; ?></span>
                            </div>
                            <div class="p-2">
                                <?php if (empty($dia['aulas'])): ?>
                                    <p class="text-muted text-center small fst-italic my-3" style="font-size: 0.75rem;">Sem aulas</p>
                                <?php else: ?>
                                    <?php foreach ($dia['aulas'] as $aula): ?>
                                        <div class="bg-black p-2 rounded mb-2 border border-secondary text-center">
                                            <span class="text-warning d-block fw-bold" style="font-size: 0.75rem;"><?= htmlspecialchars($aula['nome_turma']); ?></span>
                                            <span class="text-white d-block mb-2" style="font-size: 0.7rem;">⏰ <?= date('H:i', strtotime($aula['hora_inicio'])); ?></span>
                                            
                                            <form method="POST" action="" class="d-block m-0 p-0 bg-transparent border-0 shadow-none">
                                                <input type="hidden" name="acao_agendar_semana" value="1">
                                                <input type="hidden" name="id_turma" value="<?= $aula['id_turma']; ?>">
                                                <input type="hidden" name="data_escolhida" value="<?= $dia['data_iso']; ?>">
                                                <input type="hidden" name="hora_aula" value="<?= $aula['hora_inicio']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold border-0" style="font-size: 0.75rem; padding: 4px 0;">Agendar</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- TABELA DE TREINOS AGENDADOS -->
            <div class="card shadow-sm border p-3">
                <h6 class="fw-bold text-muted text-uppercase small mb-2">📌 Os Seus Treinos Marcados</h6>
                <div class="table-responsive">
                    <?php if (empty($meus_agendamentos)): ?>
                        <p class="text-muted small fst-italic mb-0">Ainda não tem nenhum treino agendado.</p>
                    <?php else: ?>
                        <table class="table table-sm table-hover align-middle mb-0 small">
                            <thead>
                                <tr class="text-muted">
                                    <th>TURMA</th>
                                    <th>DATA E HORA</th>
                                    <th>ESTADO</th>
                                    <th class="text-end">AÇÃO</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($meus_agendamentos as $ag): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($ag['nome_turma']); ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($ag['data_agendamento'])); ?></td>
                                        <td><span class="badge bg-success">Confirmado</span></td>
                                        <td class="text-end">
                                            <a href="home_aluno.php?cancelar=<?= $ag['id_agendamento']; ?>" class="btn btn-outline-danger btn-sm py-0 px-2" style="font-size: 0.75rem;" onclick="return confirm('Deseja cancelar?')">Cancelar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>

    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>