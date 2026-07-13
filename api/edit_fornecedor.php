<?php
// api/edit_fornecedor.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE fornecedores SET nome_fantasia=:nome, razao_social=:razao, cnpj_cpf=:cnpj, contato_nome=:contato, telefone=:tel, email=:email WHERE id=:id");
        $stmt->execute([
            'id'      => $data['id'],
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