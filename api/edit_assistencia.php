<?php
// api/edit_assistencia.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';
header('Content-Type: application/json');

$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if (strpos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    $data = $_POST;
}

$id = isset($data['id']) ? (int)$data['id'] : 0;

if ($id > 0 && !empty($data['cliente'])) {
    try {
        $cliente_raw = trim($data['cliente']);

        // --- MOTOR DE BUSCA DE ID ---
        $cliente_id = null;
        $nome_limpo = trim(preg_replace('/^\[.*?\]\s*/', '', $cliente_raw));
        
        $stmtCli = $pdo->prepare("SELECT id FROM clientes_cadastro WHERE TRIM(UPPER(nome_contrato)) = UPPER(?) LIMIT 1");
        $stmtCli->execute([$nome_limpo]);
        if ($cli = $stmtCli->fetch()) {
            $cliente_id = $cli['id'];
        } else {
            if (preg_match('/^\[(.*?)\]/', $cliente_raw, $matches)) {
                $codigo_tag = trim($matches[1]);
                $stmtTag = $pdo->prepare("SELECT id FROM clientes_cadastro WHERE codigo_cliente = ? LIMIT 1");
                $stmtTag->execute([$codigo_tag]);
                if ($cliTag = $stmtTag->fetch()) {
                    $cliente_id = $cliTag['id'];
                } else {
                    $possible_id = (int) preg_replace('/[^0-9]/', '', $codigo_tag);
                    if ($possible_id > 0) {
                        $stmtId = $pdo->prepare("SELECT id FROM clientes_cadastro WHERE id = ? LIMIT 1");
                        $stmtId->execute([$possible_id]);
                        if ($cliId = $stmtId->fetch()) $cliente_id = $cliId['id'];
                    }
                }
            }
        }
        // -----------------------------

        $tipo_cobranca = isset($data['tipo_cobranca']) ? $data['tipo_cobranca'] : 'GARANTIA';
        $val = isset($data['valor_cobrado']) ? str_replace(',', '.', $data['valor_cobrado']) : '';
        $valor_cobrado = ($val !== '') ? (float)$val : null;
        $forma_pagamento = !empty($data['forma_pagamento']) ? $data['forma_pagamento'] : null;

        // Upload
        $comprovante_path = null;
        if (isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/comprovantes/';
            if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }
            $fileName = time() . '_' . basename($_FILES['comprovante']['name']);
            if (@move_uploaded_file($_FILES['comprovante']['tmp_name'], $uploadDir . $fileName)) {
                $comprovante_path = 'uploads/comprovantes/' . $fileName;
            }
        }

        $query = "UPDATE assistencias_tecnicas SET 
            cliente_id = :cliente_id,
            cliente = :cliente,
            obs_assistencia = :observacao,
            endereco = :endereco,
            numero_lote = :numero_lote,
            quadra = :quadra,
            bairro = :bairro,
            condominio = :condominio,
            complemento = :complemento,
            cidade = :cidade,
            cep = :cep,
            tel_fixo = :tel_fixo,
            tel_cel = :tel_cel,
            tipo_cobranca = :tipo_cobranca,
            valor_cobrado = :valor_cobrado,
            forma_pagamento = :forma_pagamento";
            
        $params = [
            'cliente_id' => $cliente_id,
            'cliente' => $cliente_raw,
            'observacao' => isset($data['observacao']) ? trim($data['observacao']) : null,
            'endereco' => isset($data['endereco']) ? trim($data['endereco']) : null,
            'numero_lote' => isset($data['numero_lote']) ? trim($data['numero_lote']) : null,
            'quadra' => isset($data['quadra']) ? trim($data['quadra']) : null,
            'bairro' => isset($data['bairro']) ? trim($data['bairro']) : null,
            'condominio' => isset($data['condominio']) ? trim($data['condominio']) : null,
            'complemento' => isset($data['complemento']) ? trim($data['complemento']) : null,
            'cidade' => isset($data['cidade']) ? trim($data['cidade']) : null,
            'cep' => isset($data['cep']) ? trim($data['cep']) : null,
            'tel_fixo' => isset($data['tel_fixo']) ? trim($data['tel_fixo']) : null,
            'tel_cel' => isset($data['tel_cel']) ? trim($data['tel_cel']) : null,
            'tipo_cobranca' => $tipo_cobranca,
            'valor_cobrado' => $valor_cobrado,
            'forma_pagamento' => $forma_pagamento,
            'id' => $id
        ];

        if ($comprovante_path !== null) {
            $query .= ", comprovante_file = :comprovante_file";
            $params['comprovante_file'] = $comprovante_path;
        }

        $query .= " WHERE id = :id";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        echo json_encode(['success' => true]);
        
    } catch (\PDOException $e) {
        http_response_code(500); 
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400); echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
}
$pdo = null;
?>