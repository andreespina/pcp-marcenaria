<?php
// api/edit_item_almox.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'));

$id = isset($data->id) ? (int)$data->id : 0;
if ($id > 0 && !empty($data->nome_item)) {
    try {
        $stmt = $pdo->prepare("UPDATE almoxarifado SET nome_item = :nome, categoria = :cat, quantidade = :qtd, quantidade_minima = :qmin, unidade_medida = :und, observacao = :obs WHERE id = :id");
        $stmt->execute([
            'nome' => trim($data->nome_item), 'cat' => $data->categoria, 'qtd' => (float)$data->quantidade,
            'qmin' => (float)$data->quantidade_minima, 'und' => $data->unidade_medida, 'obs' => $data->observacao, 'id' => $id
        ]);
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Dados inválidos.']); }
$pdo = null;
?>