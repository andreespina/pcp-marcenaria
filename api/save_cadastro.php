<?php
// api/save_cadastro.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');

if (($_SESSION['usuario_role'] ?? '') !== 'ADMIN') {
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

// PHP 8: file_get_contents convertido para string para prevenir erros no json_decode
$data = json_decode((string)file_get_contents('php://input'), true);

$tipo = trim((string)($data['tipo'] ?? ''));
$nome = trim((string)($data['nome'] ?? ''));

if ($tipo !== '' && $nome !== '') {
    try {
        $tipo_upper = strtoupper($tipo);
        $nome_upper = mb_strtoupper($nome, 'UTF-8');

        $stmt = $pdo->prepare("INSERT INTO cadastros_base (tipo, nome) VALUES (?, ?)");
        $stmt->execute([$tipo_upper, $nome_upper]);

        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos.']);
}
?>