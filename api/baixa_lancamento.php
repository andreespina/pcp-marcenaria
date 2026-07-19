<?php
// api/baixa_lancamento.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');

$data = json_decode((string)file_get_contents('php://input'));

$id = (int)($data->id ?? 0);
$data_pagamento = (string)($data->data_pagamento ?? '');

if ($id > 0 && $data_pagamento !== '') {
    try {
        $stmt = $pdo->prepare("UPDATE financeiro SET status = 'PAGO', data_pagamento = :data_pagamento WHERE id = :id");
        $stmt->execute([
            'id' => $id,
            'data_pagamento' => $data_pagamento
        ]);

        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID ou data de pagamento não fornecidos.']);
}
?>