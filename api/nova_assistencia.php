<?php
// api/nova_assistencia.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'));

$projeto_id = (isset($data->projeto_id) && $data->projeto_id > 0) ? (int)$data->projeto_id : null;
$cliente    = isset($data->cliente) ? trim($data->cliente) : '';
$observacao = isset($data->observacao) ? trim($data->observacao) : '';

if (!empty($cliente)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO assistencias_tecnicas 
            (projeto_id, cliente, data_solicitacao, obs_assistencia, endereco, numero_lote, quadra, bairro, condominio, complemento, cidade, cep, tel_fixo, tel_cel) 
            VALUES (:projeto_id, :cliente, :data_solicitacao, :obs, :endereco, :numero, :quadra, :bairro, :condominio, :complemento, :cidade, :cep, :tel_fixo, :tel_cel)");
        
        $stmt->execute([
            'projeto_id'       => $projeto_id,
            'cliente'          => $cliente,
            'data_solicitacao' => date('Y-m-d'),
            'obs'              => $observacao,
            'endereco'         => isset($data->endereco) ? $data->endereco : null,
            'numero'           => isset($data->numero_lote) ? $data->numero_lote : null,
            'quadra'           => isset($data->quadra) ? $data->quadra : null,
            'bairro'           => isset($data->bairro) ? $data->bairro : null,
            'condominio'       => isset($data->condominio) ? $data->condominio : null,
            'complemento'      => isset($data->complemento) ? $data->complemento : null,
            'cidade'           => isset($data->cidade) ? $data->cidade : null,
            'cep'              => isset($data->cep) ? $data->cep : null,
            'tel_fixo'         => isset($data->tel_fixo) ? $data->tel_fixo : null,
            'tel_cel'          => isset($data->tel_cel) ? $data->tel_cel : null
        ]);
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400); echo json_encode(['success' => false, 'error' => 'O nome do cliente é obrigatório.']);
}
$pdo = null;
?>