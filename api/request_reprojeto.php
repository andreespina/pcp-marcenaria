<?php
// api/request_reprojeto.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['id']) && isset($data['nova_data'])) {
    try {
        // Incrementa +1 na revisão, atualiza a data e volta a fase para o projetista (PROJETO_3D)
        $stmt = $pdo->prepare("UPDATE comercial_leads 
                               SET revisao = revisao + 1, 
                                   data_apresentacao = :dt, 
                                   fase = 'PROJETO_3D' 
                               WHERE id = :id");
        $stmt->execute([
            'dt' => $data['nova_data'],
            'id' => $data['id']
        ]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos.']);
}
?>