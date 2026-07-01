<?php
// api/concluir_assistencia.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$json = file_get_contents('php://input');
$data = json_decode($json);

if (isset($data->id)) {
    $id = (int) $data->id;
    $tecnico = isset($data->tecnico) ? trim($data->tecnico) : null;
    $data_atendimento = !empty($data->data_atendimento) ? $data->data_atendimento : null;
    $resolvido = isset($data->resolvido) ? $data->resolvido : 'NAO';
    $obs = isset($data->observacao) ? trim($data->observacao) : null;

    try {
        // Atualiza a tabela nova e já muda o status se estiver resolvido!
        $stmt = $pdo->prepare("UPDATE assistencias_tecnicas SET 
                                tecnico_assistencia = :tecnico,
                                data_assistencia = :data_atendimento,
                                resolvido_assistencia = :resolvido,
                                obs_assistencia = :obs,
                                status = IF(:resolvido = 'SIM', 'concluida', status)
                               WHERE id = :id");
        
        $stmt->execute([
            'tecnico' => $tecnico,
            'data_atendimento' => $data_atendimento,
            'resolvido' => $resolvido,
            'obs' => $obs,
            'id' => $id
        ]);

        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    $pdo = null;
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID inválido ou não fornecido.']);
}
?>