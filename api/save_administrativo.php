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

        // Tratamento seguro para os custos (caso venham vazios)
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
        // Só gera lançamentos se houver parcelas configuradas no modal
        if (!empty($data['parcelas']) && count($data['parcelas']) > 0) {
            
            // Prepara a query exata baseada na estrutura da sua tabela de financeiro
            $stmtFin = $pdo->prepare("INSERT INTO financeiro 
                (tipo, descricao, categoria, cliente_fornecedor, valor, data_vencimento, status, entidade_tipo) 
                VALUES 
                ('RECEITA', :descricao, 'Receita de Venda', :cliente, :valor, :data_vencimento, 'PENDENTE', 'CLIENTE')
            ");

            foreach ($data['parcelas'] as $p) {
                // Limpa o nome do cliente que vem com a TAG [CLI-XX] para ficar mais elegante
                $nome_limpo = preg_replace('/^\[.*?\]\s*/', '', $data['cliente_nome']);

                // Gera a descrição (Ex: "Entrada - CONTRATO ANDRÉ" ou "Parcela 1/3 - CONTRATO ANDRÉ")
                $desc = $p['desc'] . " - CONTRATO " . $nome_limpo;

                $stmtFin->execute([
                    'descricao' => mb_strtoupper($desc, 'UTF-8'),
                    'cliente' => mb_strtoupper($data['cliente_nome'], 'UTF-8'), // Mantém a TAG aqui para a coluna "Entidade"
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
        // Retorna o erro exato do banco de dados caso algo falhe
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID não fornecido.']);
}
?>