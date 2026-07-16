<?php
// api/add_administrativo_manual.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && !empty($data['cliente_id']) && isset($data['valor'])) {
    try {
        $stmtCli = $pdo->prepare("SELECT codigo_cliente, nome_contrato FROM clientes_cadastro WHERE id = ?");
        $stmtCli->execute([$data['cliente_id']]);
        $cli = $stmtCli->fetch(PDO::FETCH_ASSOC);

        if (!$cli) {
            echo json_encode(['success' => false, 'error' => 'Cliente não encontrado.']);
            exit;
        }

        $codigo = !empty($cli['codigo_cliente']) ? $cli['codigo_cliente'] : 'CLI-' . $data['cliente_id'];
        $nome_formatado = "[" . $codigo . "] " . mb_strtoupper($cli['nome_contrato'], 'UTF-8');
        $valor = is_numeric($data['valor']) ? (float)$data['valor'] : 0.00;

        // Insere salvando o cliente_id agora
        $stmt = $pdo->prepare("INSERT INTO administrativo_contratos 
            (lead_id, cliente_id, cliente_nome, valor, status_contrato, status_financeiro) 
            VALUES (NULL, :cli_id, :cliente_nome, :valor, :status_c, :status_f)");
        
        $stmt->execute([
            'cli_id'       => $data['cliente_id'],
            'cliente_nome' => $nome_formatado,
            'valor'        => $valor,
            'status_c'     => $data['status_contrato'],
            'status_f'     => $data['status_financeiro']
        ]);

        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados incompletos.']);
}
?>