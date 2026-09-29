<?php
// view/turma/cadastrar_turma.php

session_start();

if (
    !isset($_SESSION['usuario']) ||
    $_SESSION['usuario']['perfil_id'] != 2 ||
    !isset($_SESSION['id_academia'])
) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../model/dao/TurmaDAO.php';

$dao = new \TurmaDAO();

$idAcademia = (int) $_SESSION['id_academia'];

$professores = $dao->listarProfessoresPorAcademia($idAcademia);
$modalidades = $dao->listarModalidadesPorAcademia($idAcademia);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastrar Turma - Dojify</title>

    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body>

    <?php include '../includes/header.php'; ?>

    <main class="conteudo">
        <form action="../../controller/TurmaController.php" method="POST">
            <div class="text-center">
                <img src="../../assets/img/dojify_logo1.png" alt="Dojify Logo" style="width: 100px; height: auto; margin-bottom: 12px; filter: grayscale(100%);">
                <h2>Cadastrar Turma</h2>
            </div>

            <input type="hidden" name="acao" value="cadastrar">

            <div class="campo">
                <label for="nome">Nome da turma</label>

                <input
                    type="text"
                    id="nome"
                    name="nome"
                    maxlength="100"
                    required
                >
            </div>

            <div class="campo">
                <label for="id_usuario_professor">Professor</label>

                <select
                    id="id_usuario_professor"
                    name="id_usuario_professor"
                    required
                >
                    <option value="">
                        Selecione o professor
                    </option>

                    <?php foreach ($professores as $professor): ?>

                        <option value="<?= $professor['id_usuario'] ?>">
                            <?= htmlspecialchars($professor['nome']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </div>

            <div class="campo">
                <label for="id_modalidade">Modalidade</label>

                <select
                    id="id_modalidade"
                    name="id_modalidade"
                    required
                >
                    <option value="">
                        Selecione a modalidade
                    </option>

                    <?php foreach ($modalidades as $modalidade): ?>

                        <option value="<?= $modalidade['id_modalidade'] ?>">
                            <?= htmlspecialchars($modalidade['nome']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </div>

            <div class="campo">
                <label for="capacidade">Capacidade</label>

                <input
                    type="number"
                    id="capacidade"
                    name="capacidade"
                    min="1"
                    required
                >
            </div>

            <div class="campo">
                <label for="nivel">Nível</label>

                <select
                    id="nivel"
                    name="nivel"
                    required
                >
                    <option value="">
                        Selecione o nível
                    </option>

                    <option value="INICIANTE">Iniciante</option>
                    <option value="INTERMEDIARIO">Intermediário</option>
                    <option value="AVANCADO">Avançado</option>
                </select>
            </div>

            <div class="campo">
                <label for="status">Status</label>

                <select
                    id="status"
                    name="status"
                    required
                >
                    <option value="ATIVA">Ativa</option>
                    <option value="INATIVA">Inativa</option>
                </select>
            </div>

            <button type="submit">
                Cadastrar
            </button>

            <a href="listar_turma.php">
                Voltar
            </a>

        </form>

    </main>

</body>

</html>

