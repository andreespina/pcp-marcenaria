<?php
// api/add_lancamento.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    try {
        // Tratamento rigoroso de campos vazios para evitar erro de Strict Mode no MySQL
        $entidade_id = !empty($data['entidade_id']) ? (int) $data['entidade_id'] : null;
        $entidade_tipo = !empty($data['entidade_tipo']) ? $data['entidade_tipo'] : 'CLIENTE';
        $forma_pagamento = !empty($data['forma_pagamento']) ? mb_strtoupper($data['forma_pagamento'], 'UTF-8') : null;
        $num_parcelas = !empty($data['num_parcelas']) ? (int) $data['num_parcelas'] : 1;
        $valor_parcela = !empty($data['valor_parcela']) ? (float) $data['valor_parcela'] : (float) $data['valor'];
        $data_documento = !empty($data['data_documento']) ? $data['data_documento'] : null;
        $data_vencimento = !empty($data['data_vencimento']) ? $data['data_vencimento'] : null;
        $plano_contas = !empty($data['plano_contas']) ? mb_strtoupper($data['plano_contas'], 'UTF-8') : null;
        $centro_custo = !empty($data['centro_custo']) ? mb_strtoupper($data['centro_custo'], 'UTF-8') : null;
        $tipo_documento = !empty($data['tipo_documento']) ? mb_strtoupper($data['tipo_documento'], 'UTF-8') : null;
        $num_documento = !empty($data['num_documento']) ? mb_strtoupper($data['num_documento'], 'UTF-8') : null;
        $observacao = !empty($data['observacao']) ? $data['observacao'] : '';
        
        $stmt = $pdo->prepare("INSERT INTO financeiro 
            (tipo, descricao, entidade_id, entidade_tipo, valor, data_vencimento, 
             forma_pagamento, num_parcelas, valor_parcela, data_documento, 
             plano_contas, centro_custo, tipo_documento, num_documento, observacao, status) 
            VALUES 
            (:tipo, :desc, :ent_id, :ent_tipo, :valor, :venc, 
             :forma, :parcelas, :vlr_parc, :dt_doc, 
             :plano, :custo, :tipo_doc, :num_doc, :obs, 'PENDENTE')");
        
        $stmt->execute([
            'tipo'      => $data['tipo'],
            'desc'      => mb_strtoupper($data['descricao'], 'UTF-8'),
            'ent_id'    => $entidade_id,
            'ent_tipo'  => $entidade_tipo,
            'valor'     => (float) $data['valor'],
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
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Nenhum dado recebido.']);
}
?>