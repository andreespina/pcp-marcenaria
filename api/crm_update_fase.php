<?php
// api/crm_update_fase.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['id']) && isset($data['fase'])) {
    try {
        $pdo->beginTransaction();

        $stmtLead = $pdo->prepare("
            SELECT cl.*, c.codigo_cliente, c.nome_contrato 
            FROM comercial_leads cl
            LEFT JOIN clientes_cadastro c ON cl.cliente_id = c.id
            WHERE cl.id = :id
        ");
        $stmtLead->execute(['id' => $data['id']]);
        $lead = $stmtLead->fetch(PDO::FETCH_ASSOC);

        if (!$lead) { echo json_encode(['success' => false, 'error' => 'Lead não encontrado.']); exit; }
        if ($lead['fase'] === $data['fase']) { $pdo->rollBack(); echo json_encode(['success' => true]); exit; }

        $dt_fechamento = ($data['fase'] === 'FECHADO') ? date('Y-m-d') : null;
        $motivo = null;
        if (in_array($data['fase'], ['PAUSADO', 'PERDIDO']) && !empty($data['motivo'])) {
            $motivo = mb_strtoupper($data['motivo'], 'UTF-8');
        }

        $stmt = $pdo->prepare("UPDATE comercial_leads SET fase = :fase, data_fechamento = :dt, motivo_status = :motivo WHERE id = :id");
        $stmt->execute(['fase' => $data['fase'], 'dt' => $dt_fechamento, 'motivo' => $motivo, 'id' => $data['id']]);

        // INTEGRAÇÃO NA VENDA FECHADA
        if ($data['fase'] === 'FECHADO') {
            // Usa o NOME OFICIAL do cadastro para não dar erro no Select do PCP
            $nome_base = !empty($lead['nome_contrato']) ? $lead['nome_contrato'] : $lead['cliente_nome'];
            $nome_base = mb_strtoupper(trim($nome_base), 'UTF-8');
            
            $codigo = !empty($lead['codigo_cliente']) ? $lead['codigo_cliente'] : 'CLI-' . $lead['id'];
            $nome_com_codigo = "[" . $codigo . "] " . $nome_base;

            if (!empty($data['gerarPCP']) && $data['gerarPCP'] == true) {
                // Aspirador de pó anti-duplicação
                $stmtCheckPCP = $pdo->prepare("SELECT id FROM projetos_pcp WHERE lead_id = :lead_id ORDER BY id ASC");
                $stmtCheckPCP->execute(['lead_id' => $lead['id']]);
                $rows = $stmtCheckPCP->fetchAll(PDO::FETCH_ASSOC);

                if (count($rows) > 0) {
                    $idPrincipal = $rows[0]['id'];
                    $stmtUpd = $pdo->prepare("UPDATE projetos_pcp SET cliente = :cliente WHERE id = :id");
                    $stmtUpd->execute(['cliente' => $nome_com_codigo, 'id' => $idPrincipal]);
                    
                    for ($i = 1; $i < count($rows); $i++) {
                        $pdo->prepare("DELETE FROM projetos_pcp WHERE id = ?")->execute([$rows[$i]['id']]);
                    }
                } else {
                    $stmtPCP = $pdo->prepare("INSERT INTO projetos_pcp (lead_id, cliente, status, observacao) VALUES (:lead_id, :cliente, 'desenvolvimento', :obs)");
                    $stmtPCP->execute([
                        'lead_id' => $lead['id'],
                        'cliente' => $nome_com_codigo,
                        'obs' => "Obra Comercial. Vlr: R$ " . number_format($lead['valor_estimado'], 2, ',', '.') . " | Obs: " . $lead['observacao']
                    ]);
                }
            }

            // Integração com o Administrativo
            $stmtCheckAdmin = $pdo->prepare("SELECT id FROM administrativo_contratos WHERE lead_id = :lead_id");
            $stmtCheckAdmin->execute(['lead_id' => $lead['id']]);
            if (!$stmtCheckAdmin->fetch()) {
                $stmtAdmin = $pdo->prepare("INSERT INTO administrativo_contratos (lead_id, cliente_nome, valor) VALUES (:lead_id, :cliente_nome, :valor)");
                $stmtAdmin->execute([
                    'lead_id' => $lead['id'],
                    'cliente_nome' => $nome_com_codigo,
                    'valor' => $lead['valor_estimado']
                ]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>