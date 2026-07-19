<?php
// api/add_mensagem.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';
header('Content-Type: application/json');

$data = json_decode((string)file_get_contents('php://input'));

$titulo = trim((string)($data->titulo ?? ''));
$mensagem = trim((string)($data->mensagem ?? ''));

if ($titulo !== '' && $mensagem !== '') {
    try {
        $stmt = $pdo->prepare("INSERT INTO mensagens_padroes (titulo, etapa, mensagem) VALUES (:titulo, :etapa, :mensagem)");
        $stmt->execute([
            'titulo' => $titulo,
            'etapa'  => trim((string)($data->etapa ?? '')),
            'mensagem' => $mensagem
        ]);
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500); 
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400); 
    echo json_encode(['success' => false, 'error' => 'Título e Mensagem são obrigatórios.']);
}
?>