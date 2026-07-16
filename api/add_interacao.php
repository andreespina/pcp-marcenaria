<?php
// api/add_interacao.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && !empty($data['cliente_id']) && !empty($data['observacao'])) {
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

        // Busca o nome do usuário logado na sessão
        $usuario = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'Sistema';

        $stmt = $pdo->prepare("INSERT INTO clientes_interacoes (cliente_id, usuario_nome, tipo_interacao, observacao) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $data['cliente_id'],
            $usuario,
            strtoupper($data['tipo']),
            trim($data['observacao'])
        ]);

        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados incompletos fornecidos.']);
}
?>