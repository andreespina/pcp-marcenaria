<?php
// api/update_assistencia_status.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$json = file_get_contents('php://input');
$data = json_decode($json);

if (isset($data->id) && isset($data->status)) {
    try {
        $stmt = $pdo->prepare("UPDATE assistencias_tecnicas SET status = :status WHERE id = :id");
        $stmt->execute([
            'status' => $data->status,
            'id' => (int)$data->id
        ]);
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
}
?>