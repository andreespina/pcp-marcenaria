<?php
// api/edit_usuario.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['id'])) {
    try {
        $id = $data['id'];
        $nome = mb_strtoupper($data['nome_completo'], 'UTF-8');
        $setor = mb_strtoupper($data['setor'], 'UTF-8');
        $login = $data['usuario'];
        $role = $data['role'];
        $json_permissoes = json_encode($data['permissoes']);

        // Se preencheu a nova senha, atualiza o hash. Caso contrário, não mexe na senha atual.
        if (!empty($data['senha'])) {
            $senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET nome_completo = ?, setor = ?, usuario = ?, senha = ?, role = ?, permissoes = ? WHERE id = ?");
            $stmt->execute([$nome, $setor, $login, $senha_hash, $role, $json_permissoes, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome_completo = ?, setor = ?, usuario = ?, role = ?, permissoes = ? WHERE id = ?");
            $stmt->execute([$nome, $setor, $login, $role, $json_permissoes, $id]);
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>