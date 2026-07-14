<?php
// api/edit_lead.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['id'])) {
    try {
        $cliente_id = $data['cliente_id'];
        $telefone = isset($data['telefone']) ? $data['telefone'] : '';

        if($cliente_id === 'NOVO') {
            $stmtCli = $pdo->prepare("INSERT INTO clientes_cadastro (nome_contrato, telefone) VALUES (?, ?)");
            $stmtCli->execute([mb_strtoupper($data['cliente_nome'], 'UTF-8'), $telefone]);
            $cliente_id = $pdo->lastInsertId();
            $cliente_nome = mb_strtoupper($data['cliente_nome'], 'UTF-8');
        } else {
            $stmtName = $pdo->prepare("SELECT nome_contrato FROM clientes_cadastro WHERE id = ?");
            $stmtName->execute([$cliente_id]);
            $cliente_nome = $stmtName->fetchColumn();
        }

        // Prevenção de Erros para PHP mais antigos (sem usar '??')
        $origem = !empty($data['origem']) ? mb_strtoupper($data['origem'], 'UTF-8') : 'OUTROS';
        $arq = !empty($data['arquiteto_nome']) ? mb_strtoupper($data['arquiteto_nome'], 'UTF-8') : '';
        $proj = !empty($data['projetista_responsavel']) ? mb_strtoupper($data['projetista_responsavel'], 'UTF-8') : '';
        $amb = !empty($data['ambientes']) ? mb_strtoupper($data['ambientes'], 'UTF-8') : '';
        $valor = !empty($data['valor_estimado']) ? (float) $data['valor_estimado'] : 0.00;
        $prob = !empty($data['probabilidade']) ? (int) $data['probabilidade'] : 50;
        $dt = !empty($data['data_apresentacao']) ? $data['data_apresentacao'] : null;
        $obs = !empty($data['observacao']) ? $data['observacao'] : '';
        $memorial = !empty($data['memorial_descritivo']) ? mb_strtoupper($data['memorial_descritivo'], 'UTF-8') : 'PRA FAZER';

        $stmt = $pdo->prepare("UPDATE comercial_leads SET 
            cliente_id = :cid, 
            cliente_nome = :nome, 
            telefone = :tel,
            origem = :origem, 
            arquiteto_nome = :arq, 
            projetista_responsavel = :proj, 
            ambientes = :amb, 
            valor_estimado = :valor, 
            probabilidade = :prob, 
            data_apresentacao = :dt, 
            observacao = :obs, 
            memorial_descritivo = :memorial
            WHERE id = :id");
        
        $stmt->execute([
            'id'       => $data['id'],
            'cid'      => $cliente_id,
            'nome'     => $cliente_nome,
            'tel'      => $telefone,
            'origem'   => $origem,
            'arq'      => $arq,
            'proj'     => $proj,
            'amb'      => $amb,
            'valor'    => $valor,
            'prob'     => $prob,
            'dt'       => $dt,
            'obs'      => $obs,
            'memorial' => $memorial
        ]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>