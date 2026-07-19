<?php
// api/edit_item_almox.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';
header('Content-Type: application/json');

$data = json_decode((string)file_get_contents('php://input'));
$id = (int)($data->id ?? 0);
$nome_item = trim((string)($data->nome_item ?? ''));

if ($id > 0 && $nome_item !== '') {
    try {
        $stmt = $pdo->prepare("UPDATE almoxarifado SET nome_item = :nome, categoria = :cat, quantidade = :qtd, quantidade_minima = :qmin, unidade_medida = :und, observacao = :obs WHERE id = :id");
        $stmt->execute([
            'nome' => $nome_item, 
            'cat' => (string)($data->categoria ?? ''), 
            'qtd' => (float)($data->quantidade ?? 0),
            'qmin' => (float)($data->quantidade_minima ?? 0), 
            'und' => (string)($data->unidade_medida ?? ''), 
            'obs' => (string)($data->observacao ?? ''), 
            'id' => $id
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
$pdo = null;
?>