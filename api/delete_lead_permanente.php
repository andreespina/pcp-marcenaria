<?php
// api/delete_lead_permanente.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode((string)file_get_contents('php://input'), true) ?? [];

$id = (int)($data['id'] ?? 0);

if ($id > 0) {
    try {
        // Comando DELETE apaga o lead da base de dados sem volta
        $stmt = $pdo->prepare("DELETE FROM comercial_leads WHERE id = :id");
        $stmt->execute(['id' => $id]);

        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID não informado.']);
}
?>