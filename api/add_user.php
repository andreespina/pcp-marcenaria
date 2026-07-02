<?php
// api/add_user.php
require_once '../includes/auth.php';
// Garante que APENAS administradores ou pessoas com permissão 'usuarios' possam criar contas
protegerAPI('usuarios');

require_once '../config/conexao.php';
require_once '../includes/logger.php'; // Adicionamos nosso Logger

$data = json_decode(file_get_contents('php://input'), true);

// Sintaxe tradicional compatível com versões anteriores do PHP
$usuario = isset($data['usuario']) ? trim($data['usuario']) : '';
$senha = isset($data['senha']) ? $data['senha'] : '';
$role = isset($data['role']) ? $data['role'] : 'USER';
$permissoes = isset($data['permissoes']) ? $data['permissoes'] : [];

if (empty($usuario) || empty($senha)) {
    echo json_encode(['success' => false, 'error' => 'Preencha todos os campos.']);
    exit;
}

try {
    // Verifica se o usuário já existe
    $check = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = ?");
    $check->execute([$usuario]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Usuário já cadastrado.']);
        exit;
    }

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $permissoes_json = json_encode($permissoes);

    $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, senha, role, permissoes) VALUES (?, ?, ?, ?)");
    $stmt->execute([$usuario, $senha_hash, $role, $permissoes_json]);
    
    $novo_id = $pdo->lastInsertId();

    // SUCESSO! Vamos registrar no log de auditoria
    registrarLog($pdo, 'CREATE', 'usuarios', $novo_id, "Cadastrou um novo usuário ($usuario) com nível $role");

    echo json_encode(['success' => true]);

} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar no banco de dados.']);
}
?>