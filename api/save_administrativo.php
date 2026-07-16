<?php
// api/save_administrativo.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && !empty($data['id'])) {
    try {
        $pdo->beginTransaction();

        // LÓGICA DE INTELIGÊNCIA: Descobrir o ID oficial do cliente
        $entidade_id = null;
        $nome_limpo = trim(preg_replace('/^\[.*?\]\s*/', '', $data['cliente_nome']));
        
        $stmtCli = $pdo->prepare("SELECT id FROM clientes_cadastro WHERE TRIM(UPPER(nome_contrato)) = UPPER(?) LIMIT 1");
        $stmtCli->execute([$nome_limpo]);
        if ($cli = $stmtCli->fetch()) {
            $entidade_id = $cli['id'];
        } else {
            // Se o nome falhar, tenta achar pelo Código ou ID
            if (preg_match('/^\[(.*?)\]/', $data['cliente_nome'], $matches)) {
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
            'status_c'    => $data['status_contrato'],
            'status_f'    => $data['status_financeiro'],
            'nf'          => !empty($data['numero_nf']) ? mb_strtoupper($data['numero_nf'], 'UTF-8') : null,
            'c_mdf'       => empty($data['custos']['mdf']) ? 0 : $data['custos']['mdf'],
            'c_ferragens' => empty($data['custos']['ferragens']) ? 0 : $data['custos']['ferragens'],
            'c_comissao'  => empty($data['custos']['comissao']) ? 0 : $data['custos']['comissao'],
            'c_outros'    => empty($data['custos']['outros']) ? 0 : $data['custos']['outros'],
            'id'          => $data['id']
        ]);

        // 2. Integração com a tabela Financeiro
        if (!empty($data['parcelas']) && count($data['parcelas']) > 0) {
            $stmtFin = $pdo->prepare("INSERT INTO financeiro 
                (tipo, descricao, categoria, cliente_fornecedor, entidade_id, entidade_tipo, valor, data_vencimento, status) 
                VALUES 
                ('RECEITA', :descricao, 'Receita de Venda', :cliente, :entidade_id, 'CLIENTE', :valor, :data_vencimento, 'PENDENTE')
            ");

            foreach ($data['parcelas'] as $p) {
                $desc = $p['desc'] . " - CONTRATO " . mb_strtoupper($nome_limpo, 'UTF-8');
                $stmtFin->execute([
                    'descricao' => mb_strtoupper($desc, 'UTF-8'),
                    'cliente' => mb_strtoupper($data['cliente_nome'], 'UTF-8'),
                    'entidade_id' => $entidade_id,
                    'valor' => $p['valor'],
                    'data_vencimento' => $p['data']
                ]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID não fornecido.']);
}
?>