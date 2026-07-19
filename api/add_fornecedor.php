<?php
// api/add_fornecedor.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode((string)file_get_contents('php://input'), true) ?? [];

$nome_fantasia = trim((string)($data['nome_fantasia'] ?? ''));

if ($nome_fantasia !== '') {
    try {
        $stmt = $pdo->prepare("INSERT INTO fornecedores (nome_fantasia, razao_social, cnpj_cpf, contato_nome, telefone, email) 
                               VALUES (:nome, :razao, :cnpj, :contato, :tel, :email)");
        $stmt->execute([
            'nome'    => mb_strtoupper($nome_fantasia, 'UTF-8'),
            'razao'   => mb_strtoupper((string)($data['razao_social'] ?? ''), 'UTF-8'),
            'cnpj'    => (string)($data['cnpj_cpf'] ?? ''),
            'contato' => (string)($data['contato_nome'] ?? ''),
            'tel'     => (string)($data['telefone'] ?? ''),
            'email'   => (string)($data['email'] ?? '')
        ]);
        
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nome Fantasia é obrigatório.']);
}
?>