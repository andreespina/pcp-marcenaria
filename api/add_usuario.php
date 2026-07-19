<?php
// api/add_usuario.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode((string)file_get_contents('php://input'), true) ?? [];

$login = trim((string)($data['usuario'] ?? ''));
$senha = (string)($data['senha'] ?? '');

if ($login !== '' && $senha !== '') {
    try {
        // Valida se o usuário de login já existe
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmtCheck->execute([$login]);
        if($stmtCheck->fetch()) {
            http_response_code(409); // Conflict
            echo json_encode(['success' => false, 'error' => 'Este login já está sendo utilizado por outro funcionário.']);
            exit;
        }

        $nome = mb_strtoupper((string)($data['nome_completo'] ?? ''), 'UTF-8');
        $setor = mb_strtoupper((string)($data['setor'] ?? ''), 'UTF-8');
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $role = (string)($data['role'] ?? 'USER');
        $permissoes = $data['permissoes'] ?? [];
        $json_permissoes = json_encode($permissoes, JSON_UNESCAPED_UNICODE);

        $stmt = $pdo->prepare("INSERT INTO usuarios (nome_completo, setor, usuario, senha, role, permissoes) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $setor, $login, $senha_hash, $role, $json_permissoes]);

        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Campos obrigatórios ausentes.']);
}
?>