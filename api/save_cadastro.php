<?php
// api/save_cadastro.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');

if ($_SESSION['usuario_role'] !== 'ADMIN') {
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data['tipo']) && !empty($data['nome'])) {
    try {
        $tipo = strtoupper(trim($data['tipo']));
        $nome = mb_strtoupper(trim($data['nome']), 'UTF-8');

        $stmt = $pdo->prepare("INSERT INTO cadastros_base (tipo, nome) VALUES (?, ?)");
        $stmt->execute([$tipo, $nome]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos.']);
}
?>