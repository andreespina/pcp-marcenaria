<?php
// api/delete_client.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode((string)file_get_contents('php://input'));

$id = (int)($data->id ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM projetos_pcp WHERE id = :id");
        $stmt->execute(['id' => $id]);

        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID inválido ou não fornecido.']);
}
?>