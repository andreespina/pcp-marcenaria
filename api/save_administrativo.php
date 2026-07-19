<?php
// api/save_administrativo.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$json = file_get_contents('php://input');
$data = json_decode((string)$json, true);

// Checagem e extração limpa das variáveis base
$id = (int)($data['id'] ?? 0);
$cliente_nome = (string)($data['cliente_nome'] ?? '');

if ($id > 0) {
    try {
        $pdo->beginTransaction();

        // LÓGICA DE INTELIGÊNCIA: Descobrir o ID oficial do cliente
        $entidade_id = null;
        $nome_limpo = trim(preg_replace('/^\[.*?\]\s*/', '', $cliente_nome));
        
        $stmtCli = $pdo->prepare("SELECT id FROM clientes_cadastro WHERE TRIM(UPPER(nome_contrato)) = UPPER(?) LIMIT 1");
        $stmtCli->execute([$nome_limpo]);
        if ($cli = $stmtCli->fetch()) {
            $entidade_id = $cli['id'];
        } else {
            // Se o nome falhar, tenta achar pelo Código ou ID
            if (preg_match('/^\[(.*?)\]/', $cliente_nome, $matches)) {
                $codigo_tag = trim($matches[1]);
                $stmtTag = $pdo->prepare("SELECT id FROM clientes_cadastro WHERE codigo_cliente = ? LIMIT 1");
                $stmtTag->execute([$codigo_tag]);
                if ($cliTag = $stmtTag->fetch()) {
                    $entidade_id = $cliTag['id'];
                } else {
                    $possible_id = (int) preg_replace('/[^0-9]/', '', $codigo_tag);
                    if ($possible_id > 0) {
                        $stmtId = $pdo->prepare("SELECT id FROM clientes_cadastro WHERE id = ? LIMIT 1");
                        $stmtId->execute([$possible_id]);
                        if ($cliId = $stmtId->fetch()) $entidade_id = $cliId['id'];
                    }
                }
            }
        }

        // PHP 8: Coalescência nula evita 'Undefined array key' em arrays filhos (nested arrays)
        $c_mdf       = (float)($data['custos']['mdf'] ?? 0);
        $c_ferragens = (float)($data['custos']['ferragens'] ?? 0);
        $c_comissao  = (float)($data['custos']['comissao'] ?? 0);
        $c_outros    = (float)($data['custos']['outros'] ?? 0);

        // 1. Atualiza os dados administrativos e AGORA SALVA O CLIENTE_ID
        $stmt = $pdo->prepare("UPDATE administrativo_contratos SET 
            cliente_id = :cliente_id,
            status_contrato = :status_c,
            status_financeiro = :status_f,
            numero_nf = :nf,
            custo_mdf = :c_mdf,
            custo_ferragens = :c_ferragens,
            custo_comissao = :c_comissao,
            custo_outros = :c_outros
            WHERE id = :id
        ");

        $stmt->execute([
            'cliente_id'  => $entidade_id,
            'status_c'    => (string)($data['status_contrato'] ?? 'PENDENTE'),
            'status_f'    => (string)($data['status_financeiro'] ?? 'A FATURAR'),
            'nf'          => !empty($data['numero_nf']) ? mb_strtoupper($data['numero_nf'], 'UTF-8') : null,
            'c_mdf'       => $c_mdf,
            'c_ferragens' => $c_ferragens,
            'c_comissao'  => $c_comissao,
            'c_outros'    => $c_outros,
            'id'          => $id
        ]);

        // 2. Integração com a tabela Financeiro
        if (!empty($data['parcelas']) && is_array($data['parcelas'])) {
            $stmtFin = $pdo->prepare("INSERT INTO financeiro 
                (tipo, descricao, categoria, cliente_fornecedor, entidade_id, entidade_tipo, valor, data_vencimento, status) 
                VALUES 
                ('RECEITA', :descricao, 'Receita de Venda', :cliente, :entidade_id, 'CLIENTE', :valor, :data_vencimento, 'PENDENTE')
            ");

            foreach ($data['parcelas'] as $p) {
                $desc_parcela = (string)($p['desc'] ?? '');
                $valor_parcela = (float)($p['valor'] ?? 0);
                $data_vencimento = (string)($p['data'] ?? '');

                $desc = $desc_parcela . " - CONTRATO " . mb_strtoupper($nome_limpo, 'UTF-8');
                
                $stmtFin->execute([
                    'descricao' => mb_strtoupper($desc, 'UTF-8'),
                    'cliente' => mb_strtoupper($cliente_nome, 'UTF-8'),
                    'entidade_id' => $entidade_id,
                    'valor' => $valor_parcela,
                    'data_vencimento' => $data_vencimento
                ]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (\PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID não fornecido.']);
}
?>