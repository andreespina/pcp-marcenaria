<?php
// api/delete_lead_soft.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['id'])) {
    try {
        // Marcamos ativo = 0 em vez de fazer um DELETE FROM
        $stmt = $pdo->prepare("UPDATE comercial_leads SET ativo = 0 WHERE id = :id");
        $stmt->execute(['id' => $data['id']]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID não informado.']);
}
?>