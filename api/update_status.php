<?php
// api/update_status.php
require_once '../includes/auth.php';
protegerAPI(); 

require_once '../config/conexao.php'; 

header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json);

if (isset($data->id) && isset($data->status)) {
    $id = (int) $data->id;
    $novo_status = $data->status;
    
    // Captura o usuário logado que fez a ação (baseado no seu login)
    $usuario_acao = isset($_SESSION['usuario_login']) ? $_SESSION['usuario_login'] : 'SISTEMA';

    try {
        // Inicia a transação para garantir integridade dos dados
        $pdo->beginTransaction();

        // 1. Pegar o status ATUAL (anterior) do projeto antes de mudar
        $stmtAtual = $pdo->prepare("SELECT status FROM projetos_pcp WHERE id = :id");
        $stmtAtual->execute(['id' => $id]);
        $projeto = $stmtAtual->fetch(PDO::FETCH_ASSOC);

        if ($projeto) {
            $status_anterior = $projeto['status'];

            // 2. Só registra a mudança se o status for realmente diferente
            if ($status_anterior !== $novo_status) {
                
                // Atualiza o status do projeto na tabela principal
                $stmtUpdate = $pdo->prepare("UPDATE projetos_pcp SET status = :status WHERE id = :id");
                $stmtUpdate->execute(['status' => $novo_status, 'id' => $id]);

                // Insere o registro de rastreio na tabela de histórico
                $stmtLog = $pdo->prepare("INSERT INTO historico_projetos (projeto_id, status_anterior, status_novo, usuario) 
                                          VALUES (:projeto_id, :status_anterior, :status_novo, :usuario)");
                $stmtLog->execute([
                    'projeto_id'      => $id,
                    'status_anterior' => $status_anterior,
                    'status_novo'     => $novo_status,
                    'usuario'         => $usuario_acao
                ]);
            }
        }

        // Confirma a transação
        $pdo->commit();

        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        // Se der qualquer erro (no update ou no log), desfaz a transação inteira
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
}
?>