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

$turmas_disponiveis = [];
$meus_agendamentos = [];
$mensagem_erro = "";
$mensagem_sucesso = "";

try {
    $pdo_agenda = \Conexao::getConexao();
    $id_aluno = $_SESSION['usuario']['id_usuario'];

    // Processar submissão do agendamento
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_agendar'])) {
        $id_turma = $_POST['id_turma'] ?? null;
        $data_escolhida = $_POST['data_agendamento'] ?? null; // Agora recebe apenas YYYY-MM-DD

        if (!empty($id_turma) && !empty($data_escolhida)) {
            
            // 1. Buscar a hora de início oficial desta turma para montar o datetime completo
            $stmtHora = $pdo_agenda->prepare("SELECT COALESCE(h.hora_inicio, '19:00:00') as hora_inicio FROM turma t LEFT JOIN horario_turma h ON t.id_turma = h.id_turma WHERE t.id_turma = ? LIMIT 1");
            $stmtHora->execute([$id_turma]);
            $resHora = $stmtHora->fetch(PDO::FETCH_ASSOC);
            $horaInicio = $resHora['hora_inicio'] ?? '19:00:00';

            // Junta a data escolhida com a hora da turma (Ex: '2026-03-30 19:00:00')
            $data_formatada = $data_escolhida . ' ' . $horaInicio;
            $data_obj = new DateTime($data_formatada);

            // 2. VALIDAR CAPACIDADE DA TURMA (Limite de 20 alunos)
            $stmtCap = $pdo_agenda->prepare("SELECT capacidade FROM turma WHERE id_turma = ?");
            $stmtCap->execute([$id_turma]);
            $turmaInfo = $stmtCap->fetch(PDO::FETCH_ASSOC);
            $capacidadeMax = (int)($turmaInfo['capacidade'] ?? 20);

            $stmtCountTurma = $pdo_agenda->prepare("SELECT COUNT(*) as total FROM agendamento WHERE id_turma = ? AND DATE(data_agendamento) = DATE(?) AND status = 'CONFIRMADO'");
            $stmtCountTurma->execute([$id_turma, $data_formatada]);
            $vagasOcupadas = (int)$stmtCountTurma->fetch()['total'];

            if ($vagasOcupadas >= $capacidadeMax) {
                $mensagem_erro = "Turma lotada! Limite máximo de {$capacidadeMax} alunos atingido para esta sessão.";
            } else {
                
                // 3. VALIDAR LIMITE SEMANAL DO PLANO DO ALUNO
                try {
                    $stmtPlano = $pdo_agenda->prepare("
                        SELECT p.limite_semanal 
                        FROM matricula m 
                        JOIN plano p ON m.id_plano = p.id_plano 
                        WHERE m.id_usuario_aluno = ? AND m.status = 'ATIVO' 
                        LIMIT 1
                    ");
                    $stmtPlano->execute([$id_aluno]);
                    $dadosPlano = $stmtPlano->fetch(PDO::FETCH_ASSOC);

                    if ($dadosPlano && isset($dadosPlano['limite_semanal']) && !is_null($dadosPlano['limite_semanal'])) {
                        $limiteSemanal = (int)$dadosPlano['limite_semanal'];

                        $inicioSemana = clone $data_obj;
                        $inicioSemana->modify('monday this week');
                        $inicioSemana->setTime(0, 0, 0);

                        $fimSemana = clone $data_obj;
                        $fimSemana->modify('sunday this week');
                        $fimSemana->setTime(23, 59, 59);

                        $stmtCountSemana = $pdo_agenda->prepare("
                            SELECT COUNT(*) as total_semana 
                            FROM agendamento 
                            WHERE id_usuario_aluno = ? 
                              AND status = 'CONFIRMADO' 
                              AND data_agendamento BETWEEN ? AND ?
                        ");
                        $stmtCountSemana->execute([
                            $id_aluno, 
                            $inicioSemana->format('Y-m-d H:i:s'), 
                            $fimSemana->format('Y-m-d H:i:s')
                        ]);
                        $totalSemana = (int)$stmtCountSemana->fetch()['total_semana'];

                        if ($totalSemana >= $limiteSemanal) {
                            $mensagem_erro = "Limite semanal atingido! O seu plano permite apenas {$limiteSemanal} treino(s) por semana.";
                        }
                    }
                } catch (Exception $exPlano) {
                    // Ignora se a tabela de planos não estiver ativa
                }

                // 4. INSERIR O AGENDAMENTO COM A HORA AUTOMÁTICA DA TURMA
                if (empty($mensagem_erro)) {
                    $stmtIns = $pdo_agenda->prepare("INSERT INTO agendamento (id_turma, id_usuario_aluno, data_agendamento, status) VALUES (?, ?, ?, 'CONFIRMADO')");
                    if ($stmtIns->execute([$id_turma, $id_aluno, $data_formatada])) {
                        $mensagem_sucesso = "Treino agendado com sucesso no tatame! 🥋";
                    } else {
                        $mensagem_erro = "Erro ao registar o agendamento no banco de dados.";
                    }
                }
            }
        } else {
            $mensagem_erro = "Por favor, selecione a turma e a data do treino.";
        }
    }

    // Consulta flexível para as turmas
    $stmt_t = $pdo_agenda->query("SELECT t.id_turma, t.nome as nome_turma, t.capacidade, 
                                         COALESCE(h.dia_semana, 'Geral') as dia_semana, 
                                         COALESCE(h.hora_inicio, '00:00') as hora_inicio 
                                  FROM turma t 
                                  LEFT JOIN horario_turma h ON t.id_turma = h.id_turma");
    $turmas_disponiveis = $stmt_t->fetchAll(PDO::FETCH_ASSOC);

    // Buscar os agendamentos do aluno logado
    $stmt_meus = $pdo_agenda->prepare("SELECT a.id_agendamento, a.data_agendamento, t.nome as nome_turma 
                                       FROM agendamento a
                                       JOIN turma t ON a.id_turma = t.id_turma
                                       WHERE a.id_usuario_aluno = ? AND a.status = 'CONFIRMADO'
                                       ORDER BY a.data_agendamento ASC");
    $stmt_meus->execute([$_SESSION['usuario']['id_usuario']]);
    $meus_agendamentos = $stmt_meus->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $mensagem_erro = "Erro crítico no sistema: " . $e->getMessage();
    $turmas_disponiveis = [];
    $meus_agendamentos = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Aluno - Dojify</title>
    
    <!-- Bootstrap 5.3.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body style="background-color: var(--bg-body, #f8f9fa);">

    <?php include '../includes/header.php'; ?>

    <main class="container py-4">
        
        <h2 class="text-center mb-2">Painel do Aluno</h2>
        <p class="text-muted text-center mb-4">Bem-vindo(a), <?= htmlspecialchars($_SESSION['usuario']['nome']) ?>! Acompanhe a sua evolução e treinos.</p>

        <!-- Alertas de Feedback -->
        <?php if (!empty($mensagem_sucesso)): ?>
            <div class="alert alert-success text-center fw-bold"><?= $mensagem_sucesso; ?></div>
        <?php endif; ?>
        <?php if (!empty($mensagem_erro)): ?>
            <div class="alert alert-danger text-center fw-bold"><?= $mensagem_erro; ?></div>
        <?php endif; ?>

        <!-- Grelha de Cartões do Aluno -->
        <div class="row g-4 justify-content-center mb-4">
            
            <!-- Cartão 1: Minha Graduação -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border p-3 text-center">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h3 class="h5 card-title mb-2">🥋 Minha Graduação</h3>
                            <p class="text-dark fw-bold fs-4 mb-2">Faixa Branca</p>
                            <p class="text-muted small mb-4">Próxima avaliação em breve.</p>
                        </div>
                        <span class="badge bg-secondary">Iniciante</span>
                    </div>
                </div>
            </div>

            <!-- Cartão 2: Gamificação & XP -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border p-3 text-center">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h3 class="h5 card-title mb-2">⭐ XP & Nível</h3>
                            <p class="text-muted small mb-2">Nível 1 (150 / 300 XP)</p>
                            <div class="progress mb-3" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 50%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <span class="text-muted small">Continue a treinar para subir de nível!</span>
                    </div>
                </div>
            </div>

            <!-- Cartão 3: Estado da Matrícula -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border p-3 text-center">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h3 class="h5 card-title mb-2">📋 Matrícula</h3>
                            <p class="mb-2"><span class="badge bg-success">Ativo</span></p>
                            <p class="text-muted small mb-4">Plano Mensal Padrão</p>
                        </div>
                        <span class="text-muted small">Tudo em dia com a academia.</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- CARTÃO DE AGENDAMENTO (Com input type="date" simples) -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-12">
                <div class="card shadow-sm border p-4">
                    <h3 class="h5 card-title mb-3 text-uppercase fw-bold text-danger">📅 Agendamento de Treinos</h3>
                    <p class="text-muted small mb-4">Selecione uma turma e a data pretendida. A hora do treino é aplicada automaticamente conforme a escala da turma (Máximo de 20 alunos por sessão).</p>

                    <div class="row g-4">
                        <!-- Formulário de Nova Marcação -->
                        <div class="col-md-5">
                            <form method="POST" action="">
                                <input type="hidden" name="acao_agendar" value="1">
                                
                                <div class="mb-3">
                                    <label for="id_turma" class="form-label small fw-bold">Selecionar Turma</label>
                                    <select class="form-select" id="id_turma" name="id_turma" required>
                                        <option value="">-- Escolha a turma --</option>
                                        <?php foreach ($turmas_disponiveis as $t): ?>
                                            <option value="<?= $t['id_turma']; ?>">
                                                <?= htmlspecialchars($t['nome_turma']); ?> (<?= $t['dia_semana']; ?> às <?= $t['hora_inicio']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="data_agendamento" class="form-label small fw-bold">Data do Treino</label>
                                    <!-- Alterado de datetime-local para date simples -->
                                    <input type="date" class="form-control" id="data_agendamento" name="data_agendamento" required>
                                </div>

                                <button type="submit" class="btn btn-danger w-100 fw-bold">Confirmar Agendamento</button>
                            </form>
                        </div>

                        <!-- Tabela de Agendamentos Ativos do Aluno -->
                        <div class="col-md-7">
                            <h6 class="fw-bold text-muted text-uppercase small mb-3">Os Seus Próximos Treinos Marcados</h6>
                            <div class="table-responsive">
                                <?php if (empty($meus_agendamentos)): ?>
                                    <p class="text-muted small fst-italic">Ainda não tem nenhum treino agendado para as próximas sessões.</p>
                                <?php else: ?>
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead>
                                            <tr class="text-muted small">
                                                <th>TURMA</th>
                                                <th>DATA E HORA</th>
                                                <th>ESTADO</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($meus_agendamentos as $ag): ?>
                                                <tr>
                                                    <td class="fw-bold"><?= htmlspecialchars($ag['nome_turma']); ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($ag['data_agendamento'])); ?></td>
                                                    <td><span class="badge bg-success">Confirmado</span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </main>

    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>