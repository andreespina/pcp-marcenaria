<?php
// api/change_password.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$json = file_get_contents('php://input');
$data = json_decode($json);

$nova_senha = isset($data->nova_senha) ? trim($data->nova_senha) : '';

if (!empty($nova_senha)) {
    try {
        // Criptografa a nova senha com hash seguro
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        // Atualiza a senha apenas do usuário que está logado atualmente
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = :senha WHERE id = :id");
        $stmt->execute([
            'senha' => $senha_hash,
            'id'    => $_SESSION['usuario_id']
        ]);

        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    $pdo = null; // Libera memória
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'A nova senha não pode estar vazia.']);
}
?>