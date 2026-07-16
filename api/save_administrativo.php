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

        // 1. Atualiza os dados administrativos e de custos na tabela
        $stmt = $pdo->prepare("UPDATE administrativo_contratos SET 
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
            'status_c' => $data['status_contrato'],
            'status_f' => $data['status_financeiro'],
            'nf' => !empty($data['numero_nf']) ? mb_strtoupper($data['numero_nf'], 'UTF-8') : null,
            'c_mdf' => empty($data['custos']['mdf']) ? 0 : $data['custos']['mdf'],
            'c_ferragens' => empty($data['custos']['ferragens']) ? 0 : $data['custos']['ferragens'],
            'c_comissao' => empty($data['custos']['comissao']) ? 0 : $data['custos']['comissao'],
            'c_outros' => empty($data['custos']['outros']) ? 0 : $data['custos']['outros'],
            'id' => $data['id']
        ]);

        // 2. Integração automática com a tabela `financeiro`
        if (!empty($data['parcelas']) && count($data['parcelas']) > 0) {
            
            $entidade_id = null;
            // Remove a TAG (Ex: [CLI-08]) e deixa só o nome limpo
            $nome_limpo = trim(preg_replace('/^\[.*?\]\s*/', '', $data['cliente_nome']));
            
            // PRIORIDADE 1: Busca exata pelo Nome do Contrato
            $stmtCli = $pdo->prepare("SELECT id FROM clientes_cadastro WHERE TRIM(UPPER(nome_contrato)) = UPPER(?) LIMIT 1");
            $stmtCli->execute([$nome_limpo]);
            $cli = $stmtCli->fetch(PDO::FETCH_ASSOC);
            
            if ($cli) {
                $entidade_id = $cli['id'];
            } else {
                // PRIORIDADE 2: Se o nome falhar, busca pela TAG de Código
                $codigo_tag = '';
                if (preg_match('/^\[(.*?)\]/', $data['cliente_nome'], $matches)) {
                    $codigo_tag = trim($matches[1]);
                }

                if (!empty($codigo_tag)) {
                    // Busca exata por quem tem ESSE código
                    $stmtTag = $pdo->prepare("SELECT id FROM clientes_cadastro WHERE codigo_cliente = ? LIMIT 1");
                    $stmtTag->execute([$codigo_tag]);
                    $cliTag = $stmtTag->fetch(PDO::FETCH_ASSOC);

                    if ($cliTag) {
                        $entidade_id = $cliTag['id'];
                    } else {
                        // PRIORIDADE 3: Tenta extrair apenas o número como fallback (ex: CLI-08 vira ID 8)
                        $possible_id = (int) preg_replace('/[^0-9]/', '', $codigo_tag);
                        if ($possible_id > 0) {
                            $stmtId = $pdo->prepare("SELECT id FROM clientes_cadastro WHERE id = ? LIMIT 1");
                            $stmtId->execute([$possible_id]);
                            $cliId = $stmtId->fetch(PDO::FETCH_ASSOC);
                            if ($cliId) {
                                $entidade_id = $cliId['id'];
                            }
                        }
                    }
                }
            }

            // Insere no Financeiro enviando o 'entidade_id' corretamente validado
            $stmtFin = $pdo->prepare("INSERT INTO financeiro 
                (tipo, descricao, categoria, cliente_fornecedor, entidade_id, entidade_tipo, valor, data_vencimento, status) 
                VALUES 
                ('RECEITA', :descricao, 'Receita de Venda', :cliente, :entidade_id, 'CLIENTE', :valor, :data_vencimento, 'PENDENTE')
            ");

            foreach ($data['parcelas'] as $p) {
                // Gera a descrição (Ex: "Entrada - CONTRATO LUANA")
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