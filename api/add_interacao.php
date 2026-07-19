<?php
// api/add_interacao.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode((string)file_get_contents('php://input'), true) ?? [];

$cliente_id = (int)($data['cliente_id'] ?? 0);
$observacao = trim((string)($data['observacao'] ?? ''));

if ($cliente_id > 0 && $observacao !== '') {
    try {
        // Armadura Definitiva: Garante que a tabela existe antes de tentar inserir
        $pdo->exec("CREATE TABLE IF NOT EXISTS clientes_interacoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cliente_id INT NOT NULL,
            usuario_nome VARCHAR(100) NOT NULL,
            tipo_interacao VARCHAR(50) DEFAULT 'ANOTAÇÃO',
            observacao TEXT NOT NULL,
            data_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Busca o nome do usuário logado na sessão com fallback limpo
        $usuario = (string)($_SESSION['usuario_nome'] ?? 'Sistema');
        $tipo = strtoupper((string)($data['tipo'] ?? 'ANOTAÇÃO'));

        $stmt = $pdo->prepare("INSERT INTO clientes_interacoes (cliente_id, usuario_nome, tipo_interacao, observacao) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $cliente_id,
            $usuario,
            $tipo,
            $observacao
        ]);

        echo json_encode(['success' => true]);

    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados incompletos fornecidos.']);
}
?>