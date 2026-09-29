<?php
// controller/LoginController.php

// Inclui as dependências necessárias
require_once '../model/dao/Conexao.php';
require_once '../model/dao/LoginDAO.php';

// Inicia a sessão para gerir o login
// Verifica se já não está iniciada para evitar warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

// Instancia o controlador
$loginController = new LoginController();

switch ($acao) {
    case 'logar':
        $loginController->logar();
        break;
    case 'logout':
        $loginController->logout();
        break;
    default:
        // Se não houver ação, redireciona para o login
        header('Location: ../view/login.php');
        exit;
}

class LoginController {

    public function logar() {
        // Recebe os dados do formulário via POST e blinda contra XSS
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $senha = $_POST['senha'] ?? '';

        if (empty($email) || empty($senha)) {
            header('Location: ../view/login.php?erro=campos_vazios');
            exit;
        }

        // Instancia o DAO
        $loginDAO = new LoginDAO();
        
        $usuario = $loginDAO->autenticar($email, $senha);

        if ($usuario) {
            // 1. Variáveis planas de sessão
            $_SESSION['id_usuario']   = $usuario['id_usuario'];
            $_SESSION['nome_usuario'] = $usuario['nome'];
            $_SESSION['perfil_id']    = $usuario['perfil_id'];
            $_SESSION['id_academia']  = $usuario['id_academia'];
            $_SESSION['logado']       = true;

            // 2. Array de sessão completo (exigido pelo home_admin.php)
            $_SESSION['usuario']      = $usuario;

            // Atualiza o registo de ultimo_login na base de dados
            $loginDAO->atualizarUltimoLogin($usuario['id_usuario']);

            // Redirecionamento Inteligente baseado no perfil_id
            switch ($usuario['perfil_id']) {
                case 1: // Administrador Master
                    header('Location: ../view/admin/home_admin.php');
                    break;
                case 2: // Gerente
                    header('Location: ../view/gerente/home_gerente.php');
                    break;
                case 3: // Professor
                    header('Location: ../view/professor/home_professor.php');
                    break;
                case 4: // Aluno
                    header('Location: ../view/aluno/home_aluno.php');
                    break;
                default:
                    // Perfil desconhecido, logout por segurança
                    $this->logout();
                    break;
            }
            exit;

        } else {
            // Autenticação falhou (credenciais incorretas ou status inativo)
            header('Location: ../view/login.php?erro=credenciais_incorretas');
            exit;
        }
    }

   public function logout() {
        // Destroi a sessão e redireciona para a tela de login
        session_unset();
        session_destroy();
        header('Location: ../view/login.php');
        exit;
    }
}
?>