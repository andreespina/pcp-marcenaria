<?php
// api/edit_lead.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode((string)file_get_contents('php://input'), true) ?? [];

$id = (int)($data['id'] ?? 0);

if ($id > 0) {
    try {
        $cliente_id = (string)($data['cliente_id'] ?? '');
        $telefone = (string)($data['telefone'] ?? '');
        $cliente_nome_input = (string)($data['cliente_nome'] ?? '');

        if($cliente_id === 'NOVO') {
            $stmtCli = $pdo->prepare("INSERT INTO clientes_cadastro (nome_contrato, telefone) VALUES (?, ?)");
            $stmtCli->execute([mb_strtoupper($cliente_nome_input, 'UTF-8'), $telefone]);
            $cliente_id = $pdo->lastInsertId();
            $cliente_nome = mb_strtoupper($cliente_nome_input, 'UTF-8');
        } else {
            $stmtName = $pdo->prepare("SELECT nome_contrato FROM clientes_cadastro WHERE id = ?");
            $stmtName->execute([$cliente_id]);
            $cliente_nome = $stmtName->fetchColumn();
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

        // Inteligência para evitar duplicar a mesma reunião no histórico
        $stmtSel = $pdo->prepare("SELECT historico_reunioes FROM comercial_leads WHERE id = ?");
        $stmtSel->execute([$id]);
        $curr_lead = $stmtSel->fetch(PDO::FETCH_ASSOC);

        $hist_reunioes = [];
        if (!empty($curr_lead['historico_reunioes'])) {
            $hist_reunioes = json_decode((string)$curr_lead['historico_reunioes'], true) ?: [];
        }

        if ($apres_realizada === 1 && !empty($dt_apres)) {
            $existe = false;
            foreach ($hist_reunioes as $hr) {
                if (($hr['data'] ?? '') === $dt_apres) { $existe = true; break; }
            }
            if (!$existe) {
                $hist_reunioes[] = ['data' => $dt_apres, 'registro' => date('Y-m-d H:i:s')];
            }
        }
        $historico_reunioes_json = json_encode($hist_reunioes, JSON_UNESCAPED_UNICODE);

        $stmt = $pdo->prepare("UPDATE comercial_leads SET 
            cliente_id = :cid, cliente_nome = :nome, telefone = :tel, origem = :origem, 
            arquiteto_nome = :arq, projetista_responsavel = :proj, ambientes = :amb, 
            valor_estimado = :valor, probabilidade = :prob, 
            data_apresentacao = :dt_apres, apresentacao_realizada = :apres_realizada, 
            data_inicio_projeto = :dt_ini, prazo_projeto_dias = :prazo, data_entrega_projeto = :dt_entrega, 
            observacao = :obs, memorial_descritivo = :memorial, historico_reunioes = :hist_reu
            WHERE id = :id");
        
        $stmt->execute([
            'id'       => $id,
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
            'hist_reu' => $historico_reunioes_json
        ]);

        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID não fornecido.']);
}
?>