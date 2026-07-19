<?php
// api/edit_recado.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';
header('Content-Type: application/json');

// Conversão limpa para Objeto JSON
$data = json_decode((string)file_get_contents('php://input'));

$id = (int)($data->id ?? 0);
$mensagem = trim((string)($data->mensagem ?? ''));

if ($id > 0 && $mensagem !== '') {
    try {
        $stmt = $pdo->prepare("UPDATE recados SET 
            data_recado = :data_recado, 
            de_quem = :de_quem, 
            para_quem = :para_quem, 
            setor = :setor, 
            prioridade = :prioridade, 
            mensagem = :mensagem 
            WHERE id = :id");
            
        $stmt->execute([
            'data_recado' => (string)($data->data_recado ?? ''),
            'de_quem'     => trim((string)($data->de_quem ?? '')),
            'para_quem'   => trim((string)($data->para_quem ?? '')),
            'setor'       => trim((string)($data->setor ?? '')),
            'prioridade'  => (string)($data->prioridade ?? 'BAIXA'),
            'mensagem'    => $mensagem,
            'id'          => $id
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