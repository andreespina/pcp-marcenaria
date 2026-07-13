<?php
// api/add_fornecedor.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    try {
        $stmt = $pdo->prepare("INSERT INTO fornecedores (nome_fantasia, razao_social, cnpj_cpf, contato_nome, telefone, email) 
                               VALUES (:nome, :razao, :cnpj, :contato, :tel, :email)");
        $stmt->execute([
            'nome'    => mb_strtoupper($data['nome_fantasia']),
            'razao'   => mb_strtoupper($data['razao_social']),
            'cnpj'    => $data['cnpj_cpf'],
            'contato' => $data['contato_nome'],
            'tel'     => $data['telefone'],
            'email'   => $data['email']
        ]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>