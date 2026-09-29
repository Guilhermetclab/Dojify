<?php
// view/aluno/agendar_aula.php
session_start();

// Validação de sessão de aluno (perfil_id = 4)
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['perfil_id'] !== 4) {
    header('Location: ../login.php');
    exit;
}

// Conexão segura usando a classe Conexao
require_once __DIR__ . '/../../model/dao/conexao.php';

$mensagem = "";
$erro = "";

try {
    $pdo = Conexao::getConexao();

    // 1. Processar o formulário quando o aluno clica em agendar
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_turma'], $_POST['data_agendamento'])) {
        $id_turma = $_POST['id_turma'];
        $id_usuario_aluno = $_SESSION['usuario']['id_usuario'];
        $data_agendamento = $_POST['data_agendamento'];

        // Verificar capacidade máxima da turma
        $stmtCap = $pdo->prepare("SELECT capacidade FROM turma WHERE id_turma = ?");
        $stmtCap->execute([$id_turma]);
        $turmaInfo = $stmtCap->fetch(PDO::FETCH_ASSOC);
        $capacidadeMax = (int)($turmaInfo['capacidade'] ?? 20);

        // Contar agendamentos confirmados para esta turma na data escolhida
        $stmtCount = $pdo->prepare("SELECT COUNT(*) as total FROM agendamento WHERE id_turma = ? AND DATE(data_agendamento) = DATE(?) AND status = 'CONFIRMADO'");
        $stmtCount->execute([$id_turma, $data_agendamento]);
        $vagasOcupadas = (int)$stmtCount->fetch()['total'];

        if ($vagasOcupadas >= $capacidadeMax) {
            $erro = "Turma lotada! Atingiu o limite máximo de {$capacidadeMax} combatentes.";
        } else {
            // Inserir agendamento
            $stmtIns = $pdo->prepare("INSERT INTO agendamento (id_turma, id_usuario_aluno, data_agendamento, status) VALUES (?, ?, ?, 'CONFIRMADO')");
            if ($stmtIns->execute([$id_turma, $id_usuario_aluno, $data_agendamento])) {
                $mensagem = "Agendamento confirmado com sucesso no tatame! 🥋";
            } else {
                $erro = "Erro ao efetuar o agendamento.";
            }
        }
    }

    // 2. Buscar turmas ativas e horários para exibir no formulário
    $sqlTurmas = "SELECT t.id_turma, t.nome as nome_turma, t.capacidade, h.dia_semana, h.hora_inicio, h.hora_fim 
                  FROM turma t 
                  JOIN horario_turma h ON t.id_turma = h.id_turma 
                  WHERE t.status = 'ATIVA'";
    $stmtTurmas = $pdo->query($sqlTurmas);
    $turmasDisponiveis = $stmtTurmas->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $erro = "Erro no sistema: " . $e->getMessage();
    $turmasDisponiveis = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Aulas - Dojify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/estilo.css">
    <style>
        body { background-color: #121212; color: #e0e0e0; }
        .card-combate { background-color: #1e1e1e; border: 1px solid #333; }
    </style>
</head>
<body>

    <?php include '../includes/header.php'; ?>

    <main class="container py-5">
        <h2 class="text-center text-uppercase fw-bold mb-3" style="letter-spacing: 1px;">📅 Agendamento de Treinos</h2>
        <p class="text-muted text-center mb-4">Garante o teu lugar no tatame (Limite máximo: 20 alunos por turma).</p>

        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card card-combate shadow-lg p-4 rounded">
                    
                    <?php if (!empty($mensagem)): ?>
                        <div class="alert alert-success text-center fw-bold"><?= $mensagem; ?></div>
                    <?php endif; ?>

                    <?php if (!empty($erro)): ?>
                        <div class="alert alert-danger text-center fw-bold"><?= $erro; ?></div>
                    <?php endif; ?>

                    <?php if (empty($turmasDisponiveis)): ?>
                        <div class="alert alert-warning text-center">De momento, não existem turmas ativas com horários configurados na base de dados.</div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="id_turma" class="form-label fw-bold text-uppercase small text-muted">Selecionar Turma</label>
                                <select class="form-select bg-dark text-white border-secondary" id="id_turma" name="id_turma" required>
                                    <option value="">-- Escolhe a tua turma --</option>
                                    <?php foreach ($turmasDisponiveis as $t): ?>
                                        <option value="<?= $t['id_turma']; ?>">
                                            <?= htmlspecialchars($t['nome_turma']); ?> 
                                            (<?= $t['dia_semana']; ?>s às <?= $t['hora_inicio']; ?> - Capacidade: <?= $t['capacidade']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="data_agendamento" class="form-label fw-bold text-uppercase small text-muted">Data Pretendida para a Aula</label>
                                <input type="datetime-local" class="form-control bg-dark text-white border-secondary" id="data_agendamento" name="data_agendamento" required>
                            </div>

                            <button type="submit" class="btn btn-danger w-100 fw-bold text-uppercase py-2" style="letter-spacing: 1px;">Confirmar Agendamento</button>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>