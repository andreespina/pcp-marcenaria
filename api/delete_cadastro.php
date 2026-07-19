<?php
// api/delete_cadastro.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');

if (($_SESSION['usuario_role'] ?? '') !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

$data = json_decode((string)file_get_contents('php://input'), true) ?? [];

$id = (int)($data['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM cadastros_base WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID não fornecido.']);
}
?>