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

        // 1. Busca os dados COMPLETOS do Lead + Código Oficial do Cliente
        $stmtLead = $pdo->prepare("
            SELECT cl.*, c.codigo_cliente 
            FROM comercial_leads cl
            LEFT JOIN clientes_cadastro c ON cl.cliente_id = c.id
            WHERE cl.id = :id
        ");
        $stmtLead->execute(['id' => $data['id']]);
        $lead = $stmtLead->fetch(PDO::FETCH_ASSOC);

        if (!$lead) {
            echo json_encode(['success' => false, 'error' => 'Lead não encontrado.']);
            exit;
        }

        // TRAVA DE SEGURANÇA: Se o card JÁ ESTIVER na fase solicitada, ignora o comando fantasma
        if ($lead['fase'] === $data['fase']) {
            $pdo->rollBack();
            echo json_encode(['success' => true]);
            exit;
        }

        $dt_fechamento = ($data['fase'] === 'FECHADO') ? date('Y-m-d') : null;
        $motivo = null;
        if (in_array($data['fase'], ['PAUSADO', 'PERDIDO']) && !empty($data['motivo'])) {
            $motivo = mb_strtoupper($data['motivo'], 'UTF-8');
        }

        // 2. Atualiza o status no funil Comercial
        $stmt = $pdo->prepare("UPDATE comercial_leads SET fase = :fase, data_fechamento = :dt, motivo_status = :motivo WHERE id = :id");
        $stmt->execute([
            'fase' => $data['fase'], 
            'dt' => $dt_fechamento, 
            'motivo' => $motivo,
            'id' => $data['id']
        ]);

        // 3. Integração com outros Setores se a venda for FECHADA
        if ($data['fase'] === 'FECHADO') {
            
            // PADRONIZA O NOME DO CARD: [CÓDIGO] NOME DO CLIENTE
            $codigo = !empty($lead['codigo_cliente']) ? $lead['codigo_cliente'] : 'CLI-' . $lead['id'];
            $nome_formatado = "[" . $codigo . "] " . mb_strtoupper($lead['cliente_nome'], 'UTF-8');

            // INTEGRAÇÃO PCP
            if (!empty($data['gerarPCP']) && $data['gerarPCP'] == true) {
                // Confere se o lead_id já está lá para não duplicar de forma alguma
                $stmtCheckPCP = $pdo->prepare("SELECT id FROM projetos_pcp WHERE lead_id = :lead_id");
                $stmtCheckPCP->execute(['lead_id' => $lead['id']]);
                
                if (!$stmtCheckPCP->fetch()) {
                    $stmtPCP = $pdo->prepare("INSERT INTO projetos_pcp (lead_id, cliente, status, observacao) VALUES (:lead_id, :cliente, 'desenvolvimento', :obs)");
                    $stmtPCP->execute([
                        'lead_id' => $lead['id'],
                        'cliente' => $nome_formatado,
                        'obs' => "Obra Comercial. Vlr: R$ " . number_format($lead['valor_estimado'], 2, ',', '.') . " | Obs: " . $lead['observacao']
                    ]);
                }
            }

            // INTEGRAÇÃO ADMINISTRATIVO
            $stmtCheckAdmin = $pdo->prepare("SELECT id FROM administrativo_contratos WHERE lead_id = :lead_id");
            $stmtCheckAdmin->execute(['lead_id' => $lead['id']]);
            
            if (!$stmtCheckAdmin->fetch()) {
                $stmtAdmin = $pdo->prepare("INSERT INTO administrativo_contratos (lead_id, cliente_nome, valor) VALUES (:lead_id, :cliente_nome, :valor)");
                $stmtAdmin->execute([
                    'lead_id' => $lead['id'],
                    'cliente_nome' => $nome_formatado,
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
} else {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos recebidos.']);
}
?>