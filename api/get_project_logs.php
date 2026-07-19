<?php
// api/get_project_logs.php
require_once '../includes/auth.php';
protegerAPI(); 

require_once '../config/conexao.php'; 

header('Content-Type: application/json');

// Extração limpa via GET
$projeto_id = (int)($_GET['id'] ?? 0);

if ($projeto_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT status_anterior, status_novo, usuario, data_mudanca 
                               FROM historico_projetos 
                               WHERE projeto_id = :projeto_id 
                               ORDER BY data_mudanca DESC");
        $stmt->execute(['projeto_id' => $projeto_id]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'logs' => $logs]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID do projeto não fornecido']);
}
?>