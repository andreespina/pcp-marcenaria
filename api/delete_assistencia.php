<?php
// api/delete_assistencia.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');

// Validação extra: Apenas ADMIN deve poder excluir no Backend
if (!isset($_SESSION['usuario_role']) || $_SESSION['usuario_role'] !== 'ADMIN') {
    echo json_encode(['success' => false, 'error' => 'Permissão negada. Apenas administradores podem excluir.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = isset($data['id']) ? intval($data['id']) : 0;

if ($id <= 0) {
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
        echo json_encode(['success' => false, 'error' => 'Assistência não encontrada.']);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(['success' => false, 'error' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>