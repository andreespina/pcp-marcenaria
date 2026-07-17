<?php
// api/delete_lead_permanente.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['id'])) {
    try {
        // Comando DELETE apaga o lead da base de dados sem volta
        $stmt = $pdo->prepare("DELETE FROM comercial_leads WHERE id = :id");
        $stmt->execute(['id' => $data['id']]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID não informado.']);
}
?>