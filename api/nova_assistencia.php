<?php
// api/nova_assistencia.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';
header('Content-Type: application/json');

$contentType = trim($_SERVER["CONTENT_TYPE"] ?? '');
if (strpos($contentType, 'application/json') !== false) {
    $data = json_decode((string)file_get_contents('php://input'), true) ?? [];
} else {
    $data = $_POST;
}

$cliente = trim((string)($data['cliente'] ?? ''));
$observacao = trim((string)($data['observacao'] ?? ''));

if ($cliente !== '' && $observacao !== '') {
    try {
        $pdo->beginTransaction(); // Inicia a transação segura

        $projeto_id = !empty($data['projeto_id']) ? (int)$data['projeto_id'] : null;
        
        // --- MOTOR DE BUSCA DE ID ---
        $cliente_id = null;
        $nome_limpo = trim(preg_replace('/^\[.*?\]\s*/', '', $cliente));
        
        $stmtCli = $pdo->prepare("SELECT id FROM clientes_cadastro WHERE TRIM(UPPER(nome_contrato)) = UPPER(?) LIMIT 1");
        $stmtCli->execute([$nome_limpo]);
        if ($cli = $stmtCli->fetch()) {
            $cliente_id = $cli['id'];
        } else {
            if (preg_match('/^\[(.*?)\]/', $cliente, $matches)) {
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

        // Extração segura utilizando ?? em vez de isset()
        $endereco = !empty($data['endereco']) ? trim($data['endereco']) : null;
        $numero_lote = !empty($data['numero_lote']) ? trim($data['numero_lote']) : null;
        $quadra = !empty($data['quadra']) ? trim($data['quadra']) : null;
        $bairro = !empty($data['bairro']) ? trim($data['bairro']) : null;
        $condominio = !empty($data['condominio']) ? trim($data['condominio']) : null;
        $complemento = !empty($data['complemento']) ? trim($data['complemento']) : null;
        $cidade = !empty($data['cidade']) ? trim($data['cidade']) : null;
        $cep = !empty($data['cep']) ? trim($data['cep']) : null;
        $tel_fixo = !empty($data['tel_fixo']) ? trim($data['tel_fixo']) : null;
        $tel_cel = !empty($data['tel_cel']) ? trim($data['tel_cel']) : null;

        // Faturamento
        $tipo_cobranca = !empty($data['tipo_cobranca']) ? $data['tipo_cobranca'] : 'GARANTIA';
        $val = str_replace(',', '.', (string)($data['valor_cobrado'] ?? ''));
        $valor_cobrado = ($val !== '') ? (float)$val : null;
        $forma_pagamento = !empty($data['forma_pagamento']) ? $data['forma_pagamento'] : null;
        
        // Upload - Fim do uso de @ (Error Control Operator)
        $comprovante_path = null;
        if (isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/comprovantes/';
            if (!is_dir($uploadDir)) { 
                mkdir($uploadDir, 0777, true); 
            }
            $fileName = time() . '_' . basename($_FILES['comprovante']['name']);
            if (move_uploaded_file($_FILES['comprovante']['tmp_name'], $uploadDir . $fileName)) {
                $comprovante_path = 'uploads/comprovantes/' . $fileName;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO assistencias_tecnicas (cliente_id, projeto_id, cliente, status, resolvido_assistencia, data_solicitacao, obs_assistencia, endereco, numero_lote, quadra, bairro, condominio, complemento, cidade, cep, tel_fixo, tel_cel, tipo_cobranca, valor_cobrado, forma_pagamento, comprovante_file) VALUES (:cliente_id, :projeto_id, :cliente, 'pendente', 'NAO', CURDATE(), :observacao, :endereco, :numero_lote, :quadra, :bairro, :condominio, :complemento, :cidade, :cep, :tel_fixo, :tel_cel, :tipo_cobranca, :valor_cobrado, :forma_pagamento, :comprovante_file)");
        
        $stmt->execute([
            'cliente_id' => $cliente_id,
            'projeto_id' => $projeto_id,
            'cliente' => $cliente,
            'observacao' => $observacao,
            'endereco' => $endereco,
            'numero_lote' => $numero_lote,
            'quadra' => $quadra,
            'bairro' => $bairro,
            'condominio' => $condominio,
            'complemento' => $complemento,
            'cidade' => $cidade,
            'cep' => $cep,
            'tel_fixo' => $tel_fixo,
            'tel_cel' => $tel_cel,
            'tipo_cobranca' => $tipo_cobranca,
            'valor_cobrado' => $valor_cobrado,
            'forma_pagamento' => $forma_pagamento,
            'comprovante_file' => $comprovante_path
        ]);
        
        $assistencia_id = $pdo->lastInsertId();

        // --- INTEGRAÇÃO FINANCEIRA ---
        if ($tipo_cobranca === 'FATURADA' && $valor_cobrado > 0) {
            $descFin = "ASSISTÊNCIA #" . $assistencia_id . " - " . mb_strtoupper($nome_limpo, 'UTF-8');
            $stmtFin = $pdo->prepare("INSERT INTO financeiro (tipo, descricao, categoria, cliente_fornecedor, entidade_id, entidade_tipo, valor, data_vencimento, status) VALUES ('RECEITA', ?, 'Assistência Técnica', ?, ?, 'CLIENTE', ?, CURDATE(), 'PENDENTE')");
            $stmtFin->execute([$descFin, mb_strtoupper($cliente, 'UTF-8'), $cliente_id, $valor_cobrado]);
        }
        // ------------------------------

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        http_response_code(500); 
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400); 
    echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
}
$pdo = null;
?>