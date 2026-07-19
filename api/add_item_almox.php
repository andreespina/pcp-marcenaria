<?php
// api/add_item_almox.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';
header('Content-Type: application/json');

$data = json_decode((string)file_get_contents('php://input'));

$nome = trim((string)($data->nome_item ?? ''));

if ($nome !== '') {
    try {
        $stmt = $pdo->prepare("INSERT INTO almoxarifado (nome_item, categoria, quantidade, quantidade_minima, unidade_medida, observacao) VALUES (:nome, :cat, :qtd, :qmin, :und, :obs)");
        $stmt->execute([
            'nome' => $nome,
            'cat'  => (string)($data->categoria ?? 'GERAL'),
            'qtd'  => (float)($data->quantidade ?? 0),
            'qmin' => (float)($data->quantidade_minima ?? 0),
            'und'  => (string)($data->unidade_medida ?? 'UN'),
            'obs'  => (string)($data->observacao ?? '')
        ]);
        
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500); 
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else { 
    http_response_code(400); 
    echo json_encode(['success' => false, 'error' => 'Nome do item obrigatório.']); 
}
$pdo = null;
?>