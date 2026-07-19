<?php
// api/update_estoque.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode((string)$json);

// PHP 8: Operador de Coalescência Nula para atribuição direta e segura
$id = (int) ($data->id ?? 0);
$qtd = (float) ($data->quantidade ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE almoxarifado SET quantidade = :qtd WHERE id = :id");
        $stmt->execute(['qtd' => $qtd, 'id' => $id]);
        
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500); 
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else { 
    http_response_code(400); 
    echo json_encode(['success' => false, 'error' => 'ID inválido.']); 
}

// Fechamento opcional da conexão
$pdo = null;
?>