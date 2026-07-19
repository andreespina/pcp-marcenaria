<?php
// api/add_user.php
require_once '../includes/auth.php';
// Garante que APENAS administradores ou pessoas com permissão 'usuarios' possam criar contas
protegerAPI('usuarios');

require_once '../config/conexao.php';
require_once '../includes/logger.php'; // Adicionamos nosso Logger

header('Content-Type: application/json');
$data = json_decode((string)file_get_contents('php://input'), true) ?? [];

// PHP 8: Sintaxe limpa com coalescência nula
$usuario = trim((string)($data['usuario'] ?? ''));
$senha = (string)($data['senha'] ?? '');
$role = (string)($data['role'] ?? 'USER');
$permissoes = $data['permissoes'] ?? [];

if ($usuario === '' || $senha === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Preencha todos os campos.']);
    exit;
}

try {
    // Verifica se o usuário já existe
    $check = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = ?");
    $check->execute([$usuario]);
    if ($check->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Usuário já cadastrado.']);
        exit;
    }

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $permissoes_json = json_encode($permissoes, JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, senha, role, permissoes) VALUES (?, ?, ?, ?)");
    $stmt->execute([$usuario, $senha_hash, $role, $permissoes_json]);
    
    $novo_id = $pdo->lastInsertId();

    // SUCESSO! Vamos registrar no log de auditoria
    registrarLog($pdo, 'CREATE', 'usuarios', $novo_id, "Cadastrou um novo usuário ({$usuario}) com nível {$role}");

    echo json_encode(['success' => true]);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar no banco de dados.']);
}
?>