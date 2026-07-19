<?php
// api/delete_lancamento.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode((string)file_get_contents('php://input'));

$id = (int)($data->id ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM financeiro WHERE id = :id");
        $stmt->execute(['id' => $id]);

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