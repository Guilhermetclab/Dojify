<?php
// controller/AgendamentoController.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Caminho para o ficheiro de conexão (ajuste se necessário conforme a localização real do seu ficheiro)
$caminhoConexao = __DIR__ . '/../model/dao/conexao.php'; 
if (!file_exists($caminhoConexao)) {
    $caminhoConexao = __DIR__ . '/../../model/dao/conexao.php';
}
require_once $caminhoConexao;

// Obtém a instância PDO através da classe Conexao
try {
    $pdo = \Conexao::getConexao();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro de conexão: ' . $e->getMessage()]);
    exit;
}

$acao = $_GET['acao'] ?? '';
// ... restante código do controlador ...