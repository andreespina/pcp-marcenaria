<?php
// api/edit_usuario.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode((string)file_get_contents('php://input'), true);

$id = (int)($data['id'] ?? 0);

if ($id > 0) {
    try {
        $nome = mb_strtoupper((string)($data['nome_completo'] ?? ''), 'UTF-8');
        $setor = mb_strtoupper((string)($data['setor'] ?? ''), 'UTF-8');
        $login = (string)($data['usuario'] ?? '');
        $role = (string)($data['role'] ?? 'USER');
        
        $permissoes = $data['permissoes'] ?? [];
        $json_permissoes = json_encode($permissoes, JSON_UNESCAPED_UNICODE);
        
        $senha = (string)($data['senha'] ?? '');

        if ($senha !== '') {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET nome_completo = ?, setor = ?, usuario = ?, senha = ?, role = ?, permissoes = ? WHERE id = ?");
            $stmt->execute([$nome, $setor, $login, $senha_hash, $role, $json_permissoes, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome_completo = ?, setor = ?, usuario = ?, role = ?, permissoes = ? WHERE id = ?");
            $stmt->execute([$nome, $setor, $login, $role, $json_permissoes, $id]);
        }

        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID inválido.']);
}
?>