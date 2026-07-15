<?php
// api/add_lead.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    try {
        $pdo->beginTransaction();

        $cliente_id = $data['cliente_id'];
        $cliente_nome = mb_strtoupper($data['cliente_nome'], 'UTF-8');
        $telefone = isset($data['telefone']) ? $data['telefone'] : '';

        if ($cliente_id === 'NOVO' && !empty($cliente_nome)) {
            $stmtCli = $pdo->prepare("INSERT INTO clientes_cadastro (nome_contrato, telefone) VALUES (?, ?)");
            $stmtCli->execute([$cliente_nome, $telefone]);
            $cliente_id = $pdo->lastInsertId();
        } elseif ($cliente_id !== 'NOVO') {
            $stmtName = $pdo->prepare("SELECT nome_contrato FROM clientes_cadastro WHERE id = ?");
            $stmtName->execute([$cliente_id]);
            $cliente_nome = $stmtName->fetchColumn();
        }

        $origem = !empty($data['origem']) ? mb_strtoupper($data['origem'], 'UTF-8') : 'OUTROS';
        $arq = !empty($data['arquiteto_nome']) ? mb_strtoupper($data['arquiteto_nome'], 'UTF-8') : '';
        $proj = !empty($data['projetista_responsavel']) ? mb_strtoupper($data['projetista_responsavel'], 'UTF-8') : '';
        $amb = !empty($data['ambientes']) ? mb_strtoupper($data['ambientes'], 'UTF-8') : '';
        $valor = !empty($data['valor_estimado']) ? (float) $data['valor_estimado'] : 0.00;
        $prob = !empty($data['probabilidade']) ? (int) $data['probabilidade'] : 50;
        $obs = !empty($data['observacao']) ? $data['observacao'] : '';
        $memorial = !empty($data['memorial_descritivo']) ? mb_strtoupper($data['memorial_descritivo'], 'UTF-8') : 'PRA FAZER';
        
        // SLA, Apresentação e Baixas
        $dt_apres = !empty($data['data_apresentacao']) ? $data['data_apresentacao'] : null;
        $apres_realizada = !empty($data['apresentacao_realizada']) ? 1 : 0;
        $dt_inicio = !empty($data['data_inicio_projeto']) ? $data['data_inicio_projeto'] : null;
        $prazo_dias = !empty($data['prazo_projeto_dias']) ? (int) $data['prazo_projeto_dias'] : 0;
        $dt_entrega = !empty($data['data_entrega_projeto']) ? $data['data_entrega_projeto'] : null;

        $stmt = $pdo->prepare("INSERT INTO comercial_leads 
            (cliente_id, cliente_nome, telefone, origem, arquiteto_nome, projetista_responsavel, ambientes, valor_estimado, probabilidade, data_apresentacao, apresentacao_realizada, data_inicio_projeto, prazo_projeto_dias, data_entrega_projeto, observacao, memorial_descritivo) 
            VALUES (:cid, :nome, :tel, :origem, :arq, :proj, :amb, :valor, :prob, :dt_apres, :apres_realizada, :dt_ini, :prazo, :dt_entrega, :obs, :memorial)");
        
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
            'memorial' => $memorial
        ]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>