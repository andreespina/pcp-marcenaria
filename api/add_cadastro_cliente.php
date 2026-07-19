<?php
// api/add_cadastro_cliente.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';
header('Content-Type: application/json');

$data = json_decode((string)file_get_contents('php://input'));

$nome = trim((string)($data->nome_contrato ?? ''));
$codigo = trim((string)($data->codigo_cliente ?? ''));

if ($nome !== '') {
    try {
        $stmt = $pdo->prepare("INSERT INTO clientes_cadastro 
            (codigo_cliente, nome_contrato, cpf_cnpj, telefone, whatsapp, email, endereco, numero_lote, quadra, bairro, condominio, complemento, cidade, cep, observacao, arquiteto_nome, arquiteto_whatsapp, arquiteto_email) 
            VALUES (:codigo, :nome, :cpf, :tel, :wpp, :email, :end, :num, :qd, :bairro, :cond, :comp, :cid, :cep, :obs, :arq_nome, :arq_wpp, :arq_email)");
        
        $stmt->execute([
            'codigo'   => $codigo,
            'nome'     => $nome,
            'cpf'      => (string)($data->cpf_cnpj ?? ''),
            'tel'      => (string)($data->telefone ?? ''),
            'wpp'      => (string)($data->whatsapp ?? ''),
            'email'    => (string)($data->email ?? ''),
            'end'      => (string)($data->endereco ?? ''),
            'num'      => (string)($data->numero_lote ?? ''),
            'qd'       => (string)($data->quadra ?? ''),
            'bairro'   => (string)($data->bairro ?? ''),
            'cond'     => (string)($data->condominio ?? ''),
            'comp'     => (string)($data->complemento ?? ''),
            'cid'      => (string)($data->cidade ?? ''),
            'cep'      => (string)($data->cep ?? ''),
            'obs'      => (string)($data->observacao ?? ''),
            'arq_nome' => (string)($data->arquiteto_nome ?? ''),
            'arq_wpp'  => (string)($data->arquiteto_whatsapp ?? ''),
            'arq_email'=> (string)($data->arquiteto_email ?? '')
        ]);
        
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500); 
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else { 
    http_response_code(400); 
    echo json_encode(['success' => false, 'error' => 'Nome do cliente é obrigatório.']); 
}
$pdo = null;
?>