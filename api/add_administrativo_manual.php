<?php
// api/add_administrativo_manual.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode((string)file_get_contents('php://input'), true) ?? [];

$cliente_id = (int)($data['cliente_id'] ?? 0);

if ($cliente_id > 0) {
    try {
        $stmtCli = $pdo->prepare("SELECT codigo_cliente, nome_contrato FROM clientes_cadastro WHERE id = ?");
        $stmtCli->execute([$cliente_id]);
        $cli = $stmtCli->fetch(PDO::FETCH_ASSOC);

        if (!$cli) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Cliente não encontrado.']);
            exit;
        }

        $codigo = !empty($cli['codigo_cliente']) ? $cli['codigo_cliente'] : 'CLI-' . $cliente_id;
        $nome_formatado = "[" . $codigo . "] " . mb_strtoupper($cli['nome_contrato'], 'UTF-8');
        
        $valor = (float)($data['valor'] ?? 0);
        $status_contrato = (string)($data['status_contrato'] ?? 'PENDENTE');
        $status_financeiro = (string)($data['status_financeiro'] ?? 'A FATURAR');

        // Insere salvando o cliente_id agora
        $stmt = $pdo->prepare("INSERT INTO administrativo_contratos 
            (lead_id, cliente_id, cliente_nome, valor, status_contrato, status_financeiro) 
            VALUES (NULL, :cli_id, :cliente_nome, :valor, :status_c, :status_f)");
        
        $stmt->execute([
            'cli_id'       => $cliente_id,
            'cliente_nome' => $nome_formatado,
            'valor'        => $valor,
            'status_c'     => $status_contrato,
            'status_f'     => $status_financeiro
        ]);

        echo json_encode(['success' => true]);

    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados incompletos (Cliente ID ausente).']);
}
?>