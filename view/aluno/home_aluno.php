<?php
// view/aluno/home_aluno.php
session_start();

if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['perfil_id'] !== 4) {
    header('Location: ../login.php?erro=acesso_negado');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Aluno - Dojify</title>
    
    <!-- Bootstrap 5.3.3 CDN (Igual ao painel do gerente) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body style="background-color: var(--bg-body, #f8f9fa);">

    <?php include '../includes/header.php'; ?>

    <main class="container py-4">
        
        <h2 class="text-center mb-2">Painel do Aluno</h2>
        <p class="text-muted text-center mb-5">Bem-vindo(a), <?= htmlspecialchars($_SESSION['usuario']['nome']) ?>! Acompanhe a sua evolução e treinos.</p>

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
                            <!-- Barra de progresso do Bootstrap -->
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
    </main>

    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>