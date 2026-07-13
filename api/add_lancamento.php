<?php
// api/add_lancamento.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json);

if (isset($data->tipo) && isset($data->valor) && isset($data->descricao) && isset($data->data_vencimento)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO financeiro (tipo, valor, descricao, cliente_fornecedor, categoria, data_vencimento, observacao, status) 
                               VALUES (:tipo, :valor, :descricao, :cliente, :categoria, :vencimento, :observacao, 'PENDENTE')");
        
        $stmt->execute([
            'tipo'       => $data->tipo,
            'valor'      => (float) $data->valor,
            'descricao'  => mb_strtoupper($data->descricao, 'UTF-8'),
            'cliente'    => mb_strtoupper($data->cliente_fornecedor ?? '', 'UTF-8'),
            'categoria'  => mb_strtoupper($data->categoria ?? '', 'UTF-8'),
            'vencimento' => $data->data_vencimento,
            'observacao' => $data->observacao ?? ''
        ]);

        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados incompletos para o lançamento.']);
}
?>