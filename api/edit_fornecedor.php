<?php
// api/edit_fornecedor.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode((string)file_get_contents('php://input'), true) ?? [];
$id = (int)($data['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE fornecedores SET nome_fantasia=:nome, razao_social=:razao, cnpj_cpf=:cnpj, contato_nome=:contato, telefone=:tel, email=:email WHERE id=:id");
        $stmt->execute([
            'id'      => $id,
            'nome'    => mb_strtoupper((string)($data['nome_fantasia'] ?? ''), 'UTF-8'),
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
    echo json_encode(['success' => false, 'error' => 'ID inválido.']);
}
?>