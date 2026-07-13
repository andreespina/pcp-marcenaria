<?php
// api/delete_lancamento.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json);

if (isset($data->id)) {
    try {
        $stmt = $pdo->prepare("DELETE FROM financeiro WHERE id = :id");
        $stmt->execute(['id' => (int) $data->id]);

        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID do lançamento não fornecido.']);
}
?>