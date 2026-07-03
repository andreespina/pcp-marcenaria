<?php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'));
$id = isset($data->id) ? (int)$data->id : 0;

if ($id > 0 && !empty($data->titulo) && !empty($data->mensagem)) {
    try {
        $stmt = $pdo->prepare("UPDATE mensagens_padroes SET titulo = :titulo, etapa = :etapa, mensagem = :mensagem WHERE id = :id");
        $stmt->execute([
            'titulo' => trim($data->titulo),
            'etapa'  => trim($data->etapa),
            'mensagem' => trim($data->mensagem),
            'id' => $id
        ]);
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400); echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
}
?>