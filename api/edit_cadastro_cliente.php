<?php
// api/edit_cadastro_cliente.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'));

$id = isset($data->id) ? (int)$data->id : 0;
$nome = isset($data->nome_contrato) ? trim($data->nome_contrato) : '';
$codigo = isset($data->codigo_cliente) ? trim($data->codigo_cliente) : '';

if ($id > 0 && !empty($nome)) {
    try {
        $stmt = $pdo->prepare("UPDATE clientes_cadastro SET 
            codigo_cliente = :codigo,
            nome_contrato = :nome,
            cpf_cnpj = :cpf,
            telefone = :tel,
            whatsapp = :wpp,
            email = :email,
            endereco = :end,
            numero_lote = :num,
            quadra = :qd,
            bairro = :bairro,
            condominio = :cond,
            complemento = :comp,
            cidade = :cid,
            cep = :cep,
            observacao = :obs,
            arquiteto_nome = :arq_nome,
            arquiteto_whatsapp = :arq_wpp,
            arquiteto_email = :arq_email
            WHERE id = :id");
        
        $stmt->execute([
            'codigo'   => $codigo,
            'nome'     => $nome,
            'cpf'      => isset($data->cpf_cnpj) ? $data->cpf_cnpj : '',
            'tel'      => isset($data->telefone) ? $data->telefone : '',
            'wpp'      => isset($data->whatsapp) ? $data->whatsapp : '',
            'email'    => isset($data->email) ? $data->email : '',
            'end'      => isset($data->endereco) ? $data->endereco : '',
            'num'      => isset($data->numero_lote) ? $data->numero_lote : '',
            'qd'       => isset($data->quadra) ? $data->quadra : '',
            'bairro'   => isset($data->bairro) ? $data->bairro : '',
            'cond'     => isset($data->condominio) ? $data->condominio : '',
            'comp'     => isset($data->complemento) ? $data->complemento : '',
            'cid'      => isset($data->cidade) ? $data->cidade : '',
            'cep'      => isset($data->cep) ? $data->cep : '',
            'obs'      => isset($data->observacao) ? $data->observacao : '',
            'arq_nome' => isset($data->arquiteto_nome) ? $data->arquiteto_nome : '',
            'arq_wpp'  => isset($data->arquiteto_whatsapp) ? $data->arquiteto_whatsapp : '',
            'arq_email'=> isset($data->arquiteto_email) ? $data->arquiteto_email : '',
            'id'       => $id
        ]);
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else { http_response_code(400); echo json_encode(['success' => false, 'error' => 'ID e Nome do cliente são obrigatórios.']); }
$pdo = null;
?>