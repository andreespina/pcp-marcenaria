<?php
// api/request_reprojeto.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode((string)file_get_contents('php://input'), true);

$id = (int)($data['id'] ?? 0);
$nova_data = (string)($data['nova_data'] ?? '');
$motivo = (string)($data['motivo'] ?? '');

if ($id > 0 && $nova_data !== '' && $motivo !== '') {
    try {
        // 1. Busca os dados atuais para não sobrescrever nada
        $stmtSel = $pdo->prepare("SELECT historico_reprojetos, revisao, data_apresentacao FROM comercial_leads WHERE id = ?");
        $stmtSel->execute([$id]);
        $lead = $stmtSel->fetch(PDO::FETCH_ASSOC);

        if (!$lead) {
            echo json_encode(['success' => false, 'error' => 'Lead não encontrado.']);
            exit;
        }

        $historico = [];
        if (!empty($lead['historico_reprojetos'])) {
            $historico = json_decode((string)$lead['historico_reprojetos'], true) ?: [];
        }

        $nova_revisao = (int)($lead['revisao'] ?? 0) + 1;

        // 2. Adiciona o novo evento de reprojeto ao histórico
        $historico[] = [
            'revisao' => $nova_revisao,
            'data' => $nova_data,
            'motivo' => mb_strtoupper($motivo, 'UTF-8'),
            'data_registro' => date('Y-m-d H:i:s')
        ];

        $historico_json = json_encode($historico, JSON_UNESCAPED_UNICODE);

        // 3. Atualiza o banco: sobe a revisão, volta a fase para o 3D e desmarca a apresentação
        $stmt = $pdo->prepare("UPDATE comercial_leads 
                               SET revisao = :rev, 
                                   data_apresentacao = :dt, 
                                   fase = 'PROJETO_3D',
                                   apresentacao_realizada = 0,
                                   historico_reprojetos = :hist
                               WHERE id = :id");
        $stmt->execute([
            'rev' => $nova_revisao,
            'dt' => $nova_data,
            'hist' => $historico_json,
            'id' => $id
        ]);
        
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos.']);
}
?>