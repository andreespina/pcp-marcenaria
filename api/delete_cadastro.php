<?php
// api/delete_cadastro.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');

if ($_SESSION['usuario_role'] !== 'ADMIN') {
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM cadastros_base WHERE id = ?");
        $stmt->execute([(int)$data['id']]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID não fornecido.']);
}
?>