<?php
//  api/delete_item_almox.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'));
if (isset($data->id)) {
    try {
        $stmt = $pdo->prepare("DELETE FROM almoxarifado WHERE id = :id");
        $stmt->execute(['id' => (int)$data->id]);
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) { http_response_code(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
} else { http_response_code(400); echo json_encode(['success' => false]); }
$pdo = null;
?>