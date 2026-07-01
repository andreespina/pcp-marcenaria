<?php
// api/edit_assistencia.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'));

$id = isset($data->id) ? (int)$data->id : 0;
$cliente = isset($data->cliente) ? trim($data->cliente) : '';

if ($id > 0 && !empty($cliente)) {
    try {
        $stmt = $pdo->prepare("UPDATE assistencias_tecnicas SET 
            cliente = :cliente, obs_assistencia = :obs, endereco = :endereco, numero_lote = :numero, 
            quadra = :quadra, bairro = :bairro, condominio = :condominio, complemento = :complemento, 
            cidade = :cidade, cep = :cep, tel_fixo = :tel_fixo, tel_cel = :tel_cel 
            WHERE id = :id");
        
        $stmt->execute([
            'cliente'     => $cliente,
            'obs'         => isset($data->observacao) ? $data->observacao : null,
            'endereco'    => isset($data->endereco) ? $data->endereco : null,
            'numero'      => isset($data->numero_lote) ? $data->numero_lote : null,
            'quadra'      => isset($data->quadra) ? $data->quadra : null,
            'bairro'      => isset($data->bairro) ? $data->bairro : null,
            'condominio'  => isset($data->condominio) ? $data->condominio : null,
            'complemento' => isset($data->complemento) ? $data->complemento : null,
            'cidade'      => isset($data->cidade) ? $data->cidade : null,
            'cep'         => isset($data->cep) ? $data->cep : null,
            'tel_fixo'    => isset($data->tel_fixo) ? $data->tel_fixo : null,
            'tel_cel'     => isset($data->tel_cel) ? $data->tel_cel : null,
            'id'          => $id
        ]);
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400); echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
}
$pdo = null;
?>