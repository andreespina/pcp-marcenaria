<?php
// api/add_lead.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode((string)file_get_contents('php://input'), true) ?? [];

if (!empty($data)) {
    try {
        $pdo->beginTransaction();

        $cliente_id = (string)($data['cliente_id'] ?? '');
        $cliente_nome_input = (string)($data['cliente_nome'] ?? '');
        $cliente_nome = mb_strtoupper($cliente_nome_input, 'UTF-8');
        $telefone = (string)($data['telefone'] ?? '');

        if ($cliente_id === 'NOVO' && $cliente_nome !== '') {
            $stmtCli = $pdo->prepare("INSERT INTO clientes_cadastro (nome_contrato, telefone) VALUES (?, ?)");
            $stmtCli->execute([$cliente_nome, $telefone]);
            $cliente_id = $pdo->lastInsertId();
        } elseif ($cliente_id !== 'NOVO') {
            $stmtName = $pdo->prepare("SELECT nome_contrato FROM clientes_cadastro WHERE id = ?");
            $stmtName->execute([$cliente_id]);
            $cliente_nome = $stmtName->fetchColumn() ?: $cliente_nome;
        }

        $origem = !empty($data['origem']) ? mb_strtoupper((string)$data['origem'], 'UTF-8') : 'OUTROS';
        $arq = !empty($data['arquiteto_nome']) ? mb_strtoupper((string)$data['arquiteto_nome'], 'UTF-8') : '';
        $proj = !empty($data['projetista_responsavel']) ? mb_strtoupper((string)$data['projetista_responsavel'], 'UTF-8') : '';
        $amb = !empty($data['ambientes']) ? mb_strtoupper((string)$data['ambientes'], 'UTF-8') : '';
        $valor = (float)($data['valor_estimado'] ?? 0.00);
        $prob = (int)($data['probabilidade'] ?? 50);
        $obs = (string)($data['observacao'] ?? '');
        $memorial = !empty($data['memorial_descritivo']) ? mb_strtoupper((string)$data['memorial_descritivo'], 'UTF-8') : 'PRA FAZER';
        
        $dt_apres = !empty($data['data_apresentacao']) ? (string)$data['data_apresentacao'] : null;
        $apres_realizada = !empty($data['apresentacao_realizada']) ? 1 : 0;
        $dt_inicio = !empty($data['data_inicio_projeto']) ? (string)$data['data_inicio_projeto'] : null;
        $prazo_dias = (int)($data['prazo_projeto_dias'] ?? 0);
        $dt_entrega = !empty($data['data_entrega_projeto']) ? (string)$data['data_entrega_projeto'] : null;

        // Lógica do Histórico de Reuniões
        $hist_reunioes = [];
        if ($apres_realizada === 1 && $dt_apres !== null) {
            $hist_reunioes[] = ['data' => $dt_apres, 'registro' => date('Y-m-d H:i:s')];
        }
        $hist_reunioes_json = json_encode($hist_reunioes, JSON_UNESCAPED_UNICODE);

        $stmt = $pdo->prepare("INSERT INTO comercial_leads 
            (cliente_id, cliente_nome, telefone, origem, arquiteto_nome, projetista_responsavel, ambientes, valor_estimado, probabilidade, data_apresentacao, apresentacao_realizada, data_inicio_projeto, prazo_projeto_dias, data_entrega_projeto, observacao, memorial_descritivo, historico_reunioes) 
            VALUES (:cid, :nome, :tel, :origem, :arq, :proj, :amb, :valor, :prob, :dt_apres, :apres_realizada, :dt_ini, :prazo, :dt_entrega, :obs, :memorial, :hist_reu)");
        
        $stmt->execute([
            'cid'      => $cliente_id,
            'nome'     => $cliente_nome,
            'tel'      => $telefone,
            'origem'   => $origem,
            'arq'      => $arq,
            'proj'     => $proj,
            'amb'      => $amb,
            'valor'    => $valor,
            'prob'     => $prob,
            'dt_apres' => $dt_apres,
            'apres_realizada' => $apres_realizada,
            'dt_ini'   => $dt_inicio,
            'prazo'    => $prazo_dias,
            'dt_entrega'=> $dt_entrega,
            'obs'      => $obs,
            'memorial' => $memorial,
            'hist_reu' => $hist_reunioes_json
        ]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (\Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nenhum dado recebido.']);
}
?>