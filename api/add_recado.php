<?php
// api/add_recado.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';
header('Content-Type: application/json');

$data = json_decode((string)file_get_contents('php://input'));

$mensagem = trim((string)($data->mensagem ?? ''));
$de_quem = trim((string)($data->de_quem ?? ''));
$para_quem = trim((string)($data->para_quem ?? ''));

if ($mensagem !== '' && $de_quem !== '' && $para_quem !== '') {
    try {
        $stmt = $pdo->prepare("INSERT INTO recados (data_recado, de_quem, para_quem, setor, prioridade, mensagem) 
                               VALUES (:data_recado, :de_quem, :para_quem, :setor, :prioridade, :mensagem)");
        $stmt->execute([
            'data_recado' => (string)($data->data_recado ?? ''),
            'de_quem'     => $de_quem,
            'para_quem'   => $para_quem,
            'setor'       => trim((string)($data->setor ?? '')),
            'prioridade'  => (string)($data->prioridade ?? 'BAIXA'),
            'mensagem'    => $mensagem
        ]);
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500); 
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400); 
    echo json_encode(['success' => false, 'error' => 'Preencha todos os campos obrigatórios.']);
}
$pdo = null;
?>