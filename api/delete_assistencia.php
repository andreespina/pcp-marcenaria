<?php
// api/delete_assistencia.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');

// Validação extra e segura para o PHP 8
if (($_SESSION['usuario_role'] ?? '') !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permissão negada. Apenas administradores podem excluir.']);
    exit;
}

$data = json_decode((string)file_get_contents("php://input"), true) ?? [];
$id = (int)($data['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID inválido.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Apaga a Assistência
    $stmt = $pdo->prepare("DELETE FROM assistencias_tecnicas WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        // 2. Apaga do Financeiro qualquer cobrança PENDENTE gerada por esta assistência
        $descLike = "ASSISTÊNCIA #" . $id . " - %";
        $stmtDel = $pdo->prepare("DELETE FROM financeiro WHERE descricao LIKE ? AND status = 'PENDENTE'");
        $stmtDel->execute([$descLike]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } else {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Assistência não encontrada.']);
    }
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>