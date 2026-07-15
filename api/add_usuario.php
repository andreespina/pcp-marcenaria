<?php
// api/add_usuario.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if ($data && !empty($data['usuario']) && !empty($data['senha'])) {
    try {
        // Valida se o usuário de login já existe
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmtCheck->execute([$data['usuario']]);
        if($stmtCheck->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Este login já está sendo utilizado por outro funcionário.']);
            exit;
        }

        $nome = mb_strtoupper($data['nome_completo'], 'UTF-8');
        $setor = mb_strtoupper($data['setor'], 'UTF-8');
        $login = $data['usuario'];
        $senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT);
        $role = $data['role'];
        $json_permissoes = json_encode($data['permissoes']);

        $stmt = $pdo->prepare("INSERT INTO usuarios (nome_completo, setor, usuario, senha, role, permissoes) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $setor, $login, $senha_hash, $role, $json_permissoes]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Campos obrigatórios ausentes.']);
}
?>