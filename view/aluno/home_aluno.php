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

$calendarioSemanal = [];
$meus_agendamentos = [];
$mensagem_erro = "";
$mensagem_sucesso = "";
$is_primeiro_acesso = false;
$id_aluno = $_SESSION['usuario']['id_usuario'];

// Variáveis padrão para o contrato
$c_nome = $c_cpf = $c_data_nasc = $c_sexo = $c_responsavel = $c_cpf_resp = $c_email = $c_tel = $c_endereco = $c_cidade = $c_estado = "---";
$c_luta = "Artes Marciais / Ver agenda"; 
$c_plano = "Não especificado";
$c_infoMedica = $c_especial = $c_obs = "---";

try {
    $pdo_agenda = \Conexao::getConexao();

    // ==============================================================================
    // 1. VERIFICAR PRIMEIRO ACESSO E BUSCAR DADOS PARA O CONTRATO
    // ==============================================================================
    try {
        $stmt_check = $pdo_agenda->prepare("SELECT primeiro_acesso FROM usuario WHERE id_usuario = ?");
        $stmt_check->execute([$id_aluno]);
        $user_data = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data && (int)$user_data['primeiro_acesso'] === 1) {
            $is_primeiro_acesso = true;
            
            // Buscar dados baseados no seu SQL exato
            $stmt_aluno = $pdo_agenda->prepare("
                SELECT u.nome, u.email, u.cpf, u.data_nascimento, u.telefone, 
                       u.responsavel, u.observacao, p.nome_plano
                FROM usuario u
                LEFT JOIN plano p ON u.id_usuario = p.id_usuario_aluno AND p.status = 'ATIVO'
                WHERE u.id_usuario = ?
                LIMIT 1
            ");
            $stmt_aluno->execute([$id_aluno]);
            $dados_contrato = $stmt_aluno->fetch(PDO::FETCH_ASSOC);

            if ($dados_contrato) {
                $c_nome = $dados_contrato['nome'] ?: "Não informado";
                $c_email = $dados_contrato['email'] ?: "Não informado";
                
                // Formatar CPF
                $cpf_limpo = $dados_contrato['cpf'];
                $c_cpf = (strlen($cpf_limpo) == 11) ? preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cpf_limpo) : "Não informado";
                
                $c_data_nasc = $dados_contrato['data_nascimento'] ? date('d/m/Y', strtotime($dados_contrato['data_nascimento'])) : "Não informado";
                $c_tel = $dados_contrato['telefone'] ?: "Não informado";
                $c_responsavel = $dados_contrato['responsavel'] ?: "O próprio";
                $c_obs = $dados_contrato['observacao'] ?: "Nenhuma observação registrada";
                $c_plano = $dados_contrato['nome_plano'] ?: "Plano Base";
                $c_infoMedica = "Vide observações gerais: " . $c_obs;
                $c_especial = "Não";
            }
        }
    } catch (Exception $e) {
        $is_primeiro_acesso = false;
    }

    // ==============================================================================
    // 2. PROCESSAR O FORMULÁRIO DE PRIMEIRO ACESSO (CONTRATO E SENHA)
    // ==============================================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_aceite_contrato'])) {
        $senha_nova = $_POST['senha_nova'] ?? '';
        $senha_confirma = $_POST['senha_confirma'] ?? '';
        $aceite = isset($_POST['ciente2']) ? true : false;

        if (!$aceite) {
            $mensagem_erro = "Precisa de ler e aceitar o contrato da academia para continuar.";
        } elseif (empty($senha_nova) || strlen($senha_nova) < 6) {
            $mensagem_erro = "A nova senha deve ter pelo menos 6 caracteres.";
        } elseif ($senha_nova !== $senha_confirma) {
            $mensagem_erro = "As senhas não coincidem. Tente novamente.";
        } else {
            // Criptografa a nova senha
            $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
            
            // No seu SQL, a senha fica na tabela 'login' e o primeiro acesso na 'usuario'
            $stmt_senha = $pdo_agenda->prepare("UPDATE login SET senha_hash = ? WHERE id_usuario = ?");
            $stmt_acesso = $pdo_agenda->prepare("UPDATE usuario SET primeiro_acesso = 0 WHERE id_usuario = ?");
            
            if ($stmt_senha->execute([$senha_hash, $id_aluno]) && $stmt_acesso->execute([$id_aluno])) {
                // Redireciona para atualizar a sessão
                header("Location: home_aluno.php");
                exit;
            } else {
                $mensagem_erro = "Erro ao guardar as alterações.";
            }
        }
    }

    // ==============================================================================
    // 3. SE NÃO FOR PRIMEIRO ACESSO, CARREGA O PAINEL NORMAL
    // ==============================================================================
    if (!$is_primeiro_acesso) {
        
        // Cancelamento
        if (isset($_GET['cancelar'])) {
            $id_agendamento = $_GET['cancelar'];
            $stmtCancel = $pdo_agenda->prepare("UPDATE agendamento SET status = 'CANCELADO' WHERE id_agendamento = ? AND id_usuario_aluno = ?");
            if ($stmtCancel->execute([$id_agendamento, $id_aluno])) {
                $mensagem_sucesso = "Agendamento cancelado com sucesso.";
            }
        }

        // Agendamento Rápido
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_agendar_semana'])) {
            $id_turma = $_POST['id_turma'] ?? null;
            $data_escolhida = $_POST['data_escolhida'] ?? null; 
            $hora_aula = $_POST['hora_aula'] ?? '19:00:00';

            if (!empty($id_turma) && !empty($data_escolhida)) {
                $data_formatada = $data_escolhida . ' ' . $hora_aula;
                
                // Validação de Capacidade (20 alunos)
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
                    $stmtIns = $pdo_agenda->prepare("INSERT INTO agendamento (id_turma, id_usuario_aluno, data_agendamento, status) VALUES (?, ?, ?, 'CONFIRMADO')");
                    if ($stmtIns->execute([$id_turma, $id_aluno, $data_formatada])) {
                        $mensagem_sucesso = "Treino agendado com sucesso no tatame! 🥋";
                    } else {
                        $mensagem_erro = "Erro ao registar o agendamento.";
                    }
                }
            }
        }

        // Buscar turmas (Garante compatibilidade com seu LEFT JOIN em horario_turma)
        $stmt_t = $pdo_agenda->query("SELECT t.id_turma, t.nome as nome_turma, t.capacidade, 
                                             h.dia_semana, h.hora_inicio 
                                      FROM turma t 
                                      LEFT JOIN horario_turma h ON t.id_turma = h.id_turma 
                                      WHERE t.status = 'ATIVA'");
        $turmas_brutas = $stmt_t->fetchAll(PDO::FETCH_ASSOC);

        // Montar calendário
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

        // Buscar agendamentos do aluno
        $stmt_meus = $pdo_agenda->prepare("SELECT a.id_agendamento, a.data_agendamento, t.nome as nome_turma 
                                           FROM agendamento a
                                           JOIN turma t ON a.id_turma = t.id_turma
                                           WHERE a.id_usuario_aluno = ? AND a.status = 'CONFIRMADO'
                                           ORDER BY a.data_agendamento ASC");
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
    <!-- Bootstrap 5.3.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>

<body style="background-color: var(--bg-body, #f8f9fa);">

    <!-- Header escondido na hora de imprimir (d-print-none) -->
    <div class="d-print-none">
        <?php include '../includes/header.php'; ?>
    </div>

    <main class="container py-4">
        
        <?php if ($is_primeiro_acesso): ?>
            <!-- ========================================================= -->
            <!-- TELA DE PRIMEIRO ACESSO (CONTRATO + NOVA SENHA) -->
            <!-- ========================================================= -->
            <div class="row justify-content-center d-print-block">
                <div class="col-lg-10">
                    <!-- O border-0 e shadow-none na hora de imprimir ajudam o layout de papel -->
                    <div class="card shadow border-0 rounded-3">
                        <div class="card-header bg-danger text-white text-center py-3 d-print-none">
                            <h4 class="mb-0 fw-bold">🥋 Bem-vindo à Dojify! Acesso Inicial</h4>
                        </div>
                        <div class="card-body p-4">
                            <p class="text-center text-muted mb-4 d-print-none">Para libertar o seu painel de agendamentos, confirme os seus dados no contrato abaixo, assinale a caixa de aceite e defina a sua nova senha pessoal.</p>

                            <?php if (!empty($mensagem_erro)): ?>
                                <div class="alert alert-danger py-2 text-center fw-bold d-print-none"><?= $mensagem_erro; ?></div>
                            <?php endif; ?>

                            <!-- ================= ÁREA DO CONTRATO ================= -->
                            <!-- CSS embutido no style para evitar a tag <style>. No print, o script remove o scroll -->
                            <div id="area-impressao" class="p-4 mb-4" style="border: 1px solid #dee2e6; border-radius: 0.375rem; background-color: #ffffff; max-height: 450px; overflow-y: auto; color: #333; font-size: 0.9rem; line-height: 1.6;">
                                
                                <div class="text-center mb-4">
                                    <h2 class="fw-bold" style="color: #b30000; font-size: 1.5rem; border-bottom: 2px solid #eee; padding-bottom: 10px;">Contrato de Prestação de Serviços de Aulas de Artes Marciais</h2>
                                </div>
                                
                                <h3 class="fw-bold mt-4 mb-2" style="color: #b30000; font-size: 1.1rem; border-bottom: 1px solid #eee;">Dados Aluno / Contratante</h3>
                                <div class="row mb-2" style="border-bottom: 1px dashed #eee; padding-bottom: 5px;">
                                    <div class="col-6"><strong>Nome do aluno(a):</strong> <?= htmlspecialchars($c_nome) ?></div>
                                    <div class="col-6"><strong>CPF:</strong> <?= htmlspecialchars($c_cpf) ?></div>
                                </div>
                                <div class="row mb-2" style="border-bottom: 1px dashed #eee; padding-bottom: 5px;">
                                    <div class="col-6"><strong>Data de nascimento:</strong> <?= htmlspecialchars($c_data_nasc) ?></div>
                                    <div class="col-6"><strong>Sexo:</strong> <?= htmlspecialchars($c_sexo) ?></div>
                                </div>
                                <div class="row mb-2" style="border-bottom: 1px dashed #eee; padding-bottom: 5px;">
                                    <div class="col-6"><strong>Nome do Responsável:</strong> <?= htmlspecialchars($c_responsavel) ?></div>
                                    <div class="col-6"><strong>CPF do Responsável:</strong> <?= htmlspecialchars($c_cpf_resp) ?></div>
                                </div>
                                <div class="row mb-2" style="border-bottom: 1px dashed #eee; padding-bottom: 5px;">
                                    <div class="col-6"><strong>E-mail:</strong> <?= htmlspecialchars($c_email) ?></div>
                                    <div class="col-6"><strong>Telefone:</strong> <?= htmlspecialchars($c_tel) ?></div>
                                </div>
                                <div class="row mb-2" style="border-bottom: 1px dashed #eee; padding-bottom: 5px;">
                                    <div class="col-6"><strong>Endereço:</strong> <?= htmlspecialchars($c_endereco) ?></div>
                                    <div class="col-6"><strong>Cidade:</strong> <?= htmlspecialchars($c_cidade) ?> / <?= htmlspecialchars($c_estado) ?></div>
                                </div>

                                <h3 class="fw-bold mt-4 mb-2" style="color: #b30000; font-size: 1.1rem; border-bottom: 1px solid #eee;">Modalidade e Plano</h3>
                                <p class="mb-1"><strong>Modalidade:</strong> <?= htmlspecialchars($c_luta) ?></p>
                                <p><strong>Frequência e Plano:</strong> <?= htmlspecialchars($c_plano) ?></p>

                                <h3 class="fw-bold mt-4 mb-2" style="color: #b30000; font-size: 1.1rem; border-bottom: 1px solid #eee;">Informações Adicionais</h3>
                                <p class="mb-1"><strong>Informações médicas a serem consideradas?</strong> <?= htmlspecialchars($c_infoMedica) ?></p>
                                <p class="mb-1"><strong>O aluno necessita de alguma adaptação especial?</strong> <?= htmlspecialchars($c_especial) ?></p>
                                <p><strong>Observações gerais:</strong> <?= htmlspecialchars($c_obs) ?></p>

                                <h3 class="fw-bold mt-4 mb-2" style="color: #b30000; font-size: 1.1rem; border-bottom: 1px solid #eee;">Dados do Contratado</h3>
                                <p class="mb-1"><strong>Contratado:</strong> A TOKKA – Escola de Lutas</p>
                                <p class="mb-1"><strong>CNPJ:</strong> 66.790.246/0001-12</p>
                                <p class="mb-1"><strong>Endereço:</strong> QNM 08 Conjunto B Lote 34</p>
                                <p class="mb-1"><strong>Telefone:</strong> (61) 99869-3504</p>
                                <p><strong>Instrutor responsável:</strong> Klevisson Araújo</p>

                                <h3 class="fw-bold mt-4 mb-2" style="color: #b30000; font-size: 1.1rem; border-bottom: 1px solid #eee;">Termos e Cláusulas</h3>
                                <p>As partes acima identificadas têm, entre si, justo e acertado o presente contrato de prestação de serviços de aulas de artes marciais, que se regerá pelas cláusulas e condições a seguir:</p>
                                
                                <p><strong>CLÁUSULA 1 — DO OBJETO</strong><br>
                                1.1 O presente contrato tem como objeto a prestação de serviços de aulas de artes marciais na modalidade contratada, observada a frequência escolhida pelo(a) CONTRATANTE, em dias e horários previamente disponibilizados pelo CONTRATADO.</p>
                                
                                <p><strong>CLÁUSULA 2 — DO VALOR E FORMA DE PAGAMENTO</strong><br>
                                2.1 O CONTRATANTE pagará ao CONTRATADO o valor correspondente ao plano selecionado, conforme indicado neste instrumento, a serem pagos até o dia 10 (dez) de cada mês, via dinheiro, pix ou transferência bancária.<br>
                                2.2 O inadimplemento poderá acarretar a suspensão da participação do CONTRATANTE nas aulas até a regularização dos valores pendentes.</p>
                                
                                <p><strong>CLÁUSULA 3 — DAS RESPONSABILIDADES</strong><br>
                                3.1 O CONTRATADO compromete-se a ministrar as aulas de acordo com as técnicas próprias da modalidade, observando as normas de segurança e zelando pelo adequado estado de conservação dos equipamentos e instalações.<br>
                                3.2 O CONTRATANTE compromete-se a respeitar as normas internas, regras de vestimenta, higiene, segurança e conduta estabelecidas pela academia.<br>
                                3.3 O CONTRATANTE declara estar apto à prática de atividades físicas e ciente de que a prática de artes marciais envolve esforço físico e riscos inerentes, responsabilizando-se pelas informações de saúde prestadas.<br>
                                3.4 O CONTRATANTE assume responsabilidade por eventuais problemas de saúde ou lesões decorrentes da prática das atividades, isentando o CONTRATADO de responsabilidade nos casos de imprudência, descumprimento das orientações recebidas ou existência de condições médicas não informadas previamente.<br>
                                3.5 Caso o CONTRATANTE seja o responsável legal por menor de idade, declara, para todos os fins de direito, que assume, em nome do menor, integral responsabilidade pelos riscos decorrentes da prática das atividades contratadas, estendendo-se a isenção de responsabilidade aqui prevista ao menor sob sua guarda.</p>
                                
                                <p><strong>CLÁUSULA 4 — DAS FALTAS E REPOSIÇÕES</strong><br>
                                4.1 O não comparecimento do CONTRATANTE às aulas não gera direito à reposição, desconto, compensação ou reembolso de valores.</p>
                                
                                <p><strong>CLÁUSULA 5 — DA DURAÇÃO DO CONTRATO</strong><br>
                                5.1 O presente contrato terá duração mínima de 3 (três) meses, contados a partir da data de sua assinatura.</p>
                                
                                <p><strong>CLÁUSULA 6 — DA RESCISÃO</strong><br>
                                O presente contrato poderá ser rescindido:<br>
                                6.1 Por qualquer das partes, mediante aviso prévio de 30 (trinta) dias.<br>
                                6.2 Em caso de inadimplemento ou descumprimento de cláusulas contratuais.<br>
                                6.3 O CONTRATADO poderá rescindir imediatamente o presente contrato em caso de conduta agressiva, desrespeito às normas internas, comportamento inadequado ou atitudes que coloquem em risco os demais alunos, professores ou colaboradores.<br>
                                6.4 O CONTRATADO não está obrigado à devolução dos valores pagos.</p>
                                
                                <p><strong>CLÁUSULA 7 — DO USO DE IMAGEM</strong><br>
                                7.1 O CONTRATANTE ou o RESPONSÁVEL LEGAL (no caso de aluno menor de 18 anos) autoriza, de forma gratuita e por prazo indeterminado, o uso de sua imagem e/ou do menor, capturados em fotos e vídeos durante as atividades da TOKKA – Escola de Lutas.<br>
                                7.2 A autorização é concedida para fins de divulgação e publicidade da academia, em todos os meios de comunicação, digitais ou impressos, incluindo redes sociais, sem que disso resulte qualquer obrigação de indenização ou compensação financeira.</p>

                                <p><strong>CLÁUSULA 8 — DAS DISPOSIÇÕES FINAIS</strong><br>
                                8.1 Este contrato é firmado em duas vias de igual teor e forma, assinadas pelas partes para que produza seus efeitos legais.</p>

                                <br>
                                <p class="text-center"><strong>Brasília - DF, <?= date("d/m/Y"); ?>.</strong></p>
                                <br><br>
                                <div class="d-flex justify-content-between text-center mt-4">
                                    <div style="width: 45%;">
                                        <hr style="border: 1px solid #000;">
                                        ASSINATURA CONTRATANTE
                                    </div>
                                    <div style="width: 45%;">
                                        <hr style="border: 1px solid #000;">
                                        ASSINATURA CONTRATADO
                                    </div>
                                </div>
                            </div>
                            <!-- ================= FIM DA ÁREA DO CONTRATO ================= -->

                            <!-- O form tem d-print-none para ocultar botões ao imprimir. O onsubmit tira o max-height antes de imprimir! -->
                            <form method="POST" action="" class="d-print-none" onsubmit="document.getElementById('area-impressao').style.maxHeight='none'; document.getElementById('area-impressao').style.overflow='visible'; window.print(); return true;">
                                <input type="hidden" name="acao_aceite_contrato" value="1">
                                
                                <div class="form-check mb-4 bg-light p-3 border rounded">
                                    <input class="form-check-input ms-1 me-2 border-danger" type="checkbox" name="ciente2" id="ciente2" required>
                                    <label class="form-check-label fw-bold text-dark" for="ciente2" style="font-size: 0.95rem;">
                                        Declaro que li, compreendi e aceito integralmente todos os termos e condições do contrato acima.
                                    </label>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-danger">Definir Nova Senha Definitiva</label>
                                        <input type="password" name="senha_nova" class="form-control border-danger" required placeholder="Mínimo 6 caracteres">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-danger">Confirmar Senha</label>
                                        <input type="password" name="senha_confirma" class="form-control border-danger" required placeholder="Repita a senha">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-danger w-100 fw-bold py-3 fs-5 shadow-sm">Assinar Contrato e Entrar no Painel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- ========================================================= -->
            <!-- PAINEL NORMAL DE AGENDAMENTOS (CALENDÁRIO HORIZONTAL LADO A LADO) -->
            <!-- ========================================================= -->
            
            <h2 class="text-center mb-2">Painel do Aluno</h2>
            <p class="text-muted text-center mb-4">Bem-vindo(a), <?= htmlspecialchars($_SESSION['usuario']['nome']) ?>! Acompanhe a sua evolução e treinos.</p>

            <?php if (!empty($mensagem_sucesso)): ?>
                <div class="alert alert-success text-center py-2"><?= $mensagem_sucesso; ?></div>
            <?php endif; ?>
            <?php if (!empty($mensagem_erro)): ?>
                <div class="alert alert-danger text-center py-2"><?= $mensagem_erro; ?></div>
            <?php endif; ?>

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

            <!-- CALENDÁRIO SEMANAL COMPACTO (Lado a Lado) -->
            <div class="card shadow-sm border p-3 mb-4">
                <h5 class="text-uppercase fw-bold text-danger mb-1" style="font-size: 1rem;">📅 Agenda Semanal de Treinos</h5>
                <p class="text-muted small mb-3">Escolha a sua turma e clique em agendar no dia respetivo.</p>

                <!-- Scroll nativo do Bootstrap sem CSS injetado -->
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
                                            
                                            <!-- Form anulando os estilos usando classes nativas do Bootstrap -->
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
                                            <a href="home_aluno.php?cancelar=<?= $ag['id_agendamento']; ?>" class="btn btn-outline-danger btn-sm py-0 px-2" style="font-size: 0.75rem;" onclick="return confirm('Deseja cancelar este agendamento?')">Cancelar</a>
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

    <!-- Footer escondido na hora de imprimir -->
    <div class="d-print-none">
        <?php include '../includes/footer.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>