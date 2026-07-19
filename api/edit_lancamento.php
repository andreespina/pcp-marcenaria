<?php
// api/edit_lancamento.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode((string)file_get_contents('php://input'), true) ?? [];

$id = (int)($data['id'] ?? 0);

if ($id > 0) {
    try {
        $entidade_id = !empty($data['entidade_id']) ? (int) $data['entidade_id'] : null;
        $entidade_tipo = !empty($data['entidade_tipo']) ? (string)$data['entidade_tipo'] : 'CLIENTE';
        $forma_pagamento = !empty($data['forma_pagamento']) ? mb_strtoupper((string)$data['forma_pagamento'], 'UTF-8') : null;
        $num_parcelas = (int)($data['num_parcelas'] ?? 1);
        $valor = (float)($data['valor'] ?? 0);
        $valor_parcela = !empty($data['valor_parcela']) ? (float)$data['valor_parcela'] : $valor;
        $data_documento = !empty($data['data_documento']) ? (string)$data['data_documento'] : null;
        $data_vencimento = !empty($data['data_vencimento']) ? (string)$data['data_vencimento'] : null;
        $plano_contas = !empty($data['plano_contas']) ? mb_strtoupper((string)$data['plano_contas'], 'UTF-8') : null;
        $centro_custo = !empty($data['centro_custo']) ? mb_strtoupper((string)$data['centro_custo'], 'UTF-8') : null;
        $tipo_documento = !empty($data['tipo_documento']) ? mb_strtoupper((string)$data['tipo_documento'], 'UTF-8') : null;
        $num_documento = !empty($data['num_documento']) ? mb_strtoupper((string)$data['num_documento'], 'UTF-8') : null;
        $observacao = (string)($data['observacao'] ?? '');
        
        $tipo = (string)($data['tipo'] ?? '');
        $descricao = (string)($data['descricao'] ?? '');

        $stmt = $pdo->prepare("UPDATE financeiro SET 
            tipo = :tipo, descricao = :desc, entidade_id = :ent_id, entidade_tipo = :ent_tipo, 
            valor = :valor, data_vencimento = :venc, forma_pagamento = :forma, 
            num_parcelas = :parcelas, valor_parcela = :vlr_parc, data_documento = :dt_doc, 
            plano_contas = :plano, centro_custo = :custo, tipo_documento = :tipo_doc, 
            num_documento = :num_doc, observacao = :obs
            WHERE id = :id");
        
        $stmt->execute([
            'id'        => $id,
            'tipo'      => $tipo,
            'desc'      => mb_strtoupper($descricao, 'UTF-8'),
            'ent_id'    => $entidade_id,
            'ent_tipo'  => $entidade_tipo,
            'valor'     => $valor,
            'venc'      => $data_vencimento,
            'forma'     => $forma_pagamento,
            'parcelas'  => $num_parcelas,
            'vlr_parc'  => $valor_parcela,
            'dt_doc'    => $data_documento,
            'plano'     => $plano_contas,
            'custo'     => $centro_custo,
            'tipo_doc'  => $tipo_documento,
            'num_doc'   => $num_documento,
            'obs'       => $observacao
        ]);

        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID inválido.']);
}
?>