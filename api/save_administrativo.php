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

        // 1. Atualiza os dados administrativos e de custos
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
            'c_mdf' => $data['custos']['mdf'],
            'c_ferragens' => $data['custos']['ferragens'],
            'c_comissao' => $data['custos']['comissao'],
            'c_outros' => $data['custos']['outros'],
            'id' => $data['id']
        ]);

        // 2. Integração com a tabela `financeiro` se existirem parcelas a lançar
        if (!empty($data['parcelas']) && count($data['parcelas']) > 0) {
            
            $stmtFin = $pdo->prepare("INSERT INTO financeiro 
                (tipo, descricao, categoria, cliente_fornecedor, valor, data_vencimento, status, entidade_tipo) 
                VALUES 
                ('RECEITA', :descricao, 'Receita de Venda', :cliente, :valor, :data_vencimento, 'PENDENTE', 'CLIENTE')
            ");

            foreach ($data['parcelas'] as $p) {
                // Descrição amigável: "Parcela 1/3 - Contrato [AE56] ANDRÉ..."
                $desc = $p['desc'] . " - Contrato " . $data['cliente_nome'];

                $stmtFin->execute([
                    'descricao' => mb_strtoupper($desc, 'UTF-8'),
                    'cliente' => mb_strtoupper($data['cliente_nome'], 'UTF-8'),
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