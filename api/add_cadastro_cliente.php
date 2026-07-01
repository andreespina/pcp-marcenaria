<?php
// api/add_cadastro_cliente.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'));

$nome = isset($data->nome_contrato) ? trim($data->nome_contrato) : '';
$codigo = isset($data->codigo_cliente) ? trim($data->codigo_cliente) : '';

if (!empty($nome)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO clientes_cadastro 
            (codigo_cliente, nome_contrato, cpf_cnpj, telefone, whatsapp, email, endereco, numero_lote, quadra, bairro, condominio, complemento, cidade, cep, observacao, arquiteto_nome, arquiteto_whatsapp, arquiteto_email) 
            VALUES (:codigo, :nome, :cpf, :tel, :wpp, :email, :end, :num, :qd, :bairro, :cond, :comp, :cid, :cep, :obs, :arq_nome, :arq_wpp, :arq_email)");
        
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
            'arq_email'=> isset($data->arquiteto_email) ? $data->arquiteto_email : ''
        ]);
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Nome do cliente é obrigatório.']); }
$pdo = null;
?>