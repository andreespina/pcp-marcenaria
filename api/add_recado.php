<?php
// api/add_recado.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'));

if (!empty($data->mensagem) && !empty($data->de_quem) && !empty($data->para_quem)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO recados (data_recado, de_quem, para_quem, setor, prioridade, mensagem) 
                               VALUES (:data_recado, :de_quem, :para_quem, :setor, :prioridade, :mensagem)");
        $stmt->execute([
            'data_recado' => $data->data_recado,
            'de_quem'     => trim($data->de_quem),
            'para_quem'   => trim($data->para_quem),
            'setor'       => trim($data->setor),
            'prioridade'  => $data->prioridade,
            'mensagem'    => trim($data->mensagem)
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