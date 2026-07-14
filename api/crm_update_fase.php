<?php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['id']) && isset($data['fase'])) {
    try {
        $pdo->beginTransaction();

        // 1. Atualiza fase no funil
        $dt_fechamento = ($data['fase'] === 'FECHADO') ? date('Y-m-d') : null;
        $stmt = $pdo->prepare("UPDATE comercial_leads SET fase = :fase, data_fechamento = :dt WHERE id = :id");
        $stmt->execute(['fase' => $data['fase'], 'dt' => $dt_fechamento, 'id' => $data['id']]);

        // 2. Disparos se a venda foi fechada
        if ($data['fase'] === 'FECHADO') {
            $stmtLead = $pdo->prepare("SELECT * FROM comercial_leads WHERE id = :id");
            $stmtLead->execute(['id' => $data['id']]);
            $lead = $stmtLead->fetch(PDO::FETCH_ASSOC);

            // Gera projeto no PCP se solicitado
            if (!empty($data['gerarPCP']) && $data['gerarPCP'] == true) {
                $stmtPCP = $pdo->prepare("INSERT INTO projetos_pcp (cliente, status, observacao) VALUES (:cliente, 'desenvolvimento', :obs)");
                $stmtPCP->execute([
                    'cliente' => mb_strtoupper($lead['cliente_nome'], 'UTF-8'),
                    'obs' => "Obra oriunda do Comercial. Faturado em: " . date('d/m/Y')
                ]);
            }

            // Notifica Administrativo
            $stmtAdmin = $pdo->prepare("INSERT INTO administrativo_contratos (lead_id, cliente_nome, valor) VALUES (:lead_id, :cliente_nome, :valor)");
            $stmtAdmin->execute([
                'lead_id' => $lead['id'],
                'cliente_nome' => mb_strtoupper($lead['cliente_nome'], 'UTF-8'),
                'valor' => $lead['valor_estimado']
            ]);
        }

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>