<?php
// api/edit_client.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$json = file_get_contents('php://input');
$data = json_decode($json);

$id = isset($data->id) ? (int)$data->id : 0;
$cliente = isset($data->cliente) ? trim($data->cliente) : '';

if ($id > 0 && !empty($cliente)) {
    $data_limite = !empty($data->data_limite) ? $data->data_limite : null;
    $observacao  = !empty($data->observacao) ? trim($data->observacao) : null;
    
    $promob            = isset($data->promob) ? $data->promob : 'PARA FAZER';
    $projeto_executivo = isset($data->projeto_executivo) ? $data->projeto_executivo : 'PARA FAZER';
    $corte_furacao     = isset($data->corte_furacao) ? $data->corte_furacao : 'PARA ENVIAR';
    $lista_compras     = isset($data->lista_compras) ? $data->lista_compras : 'PARA ENVIAR';
    $lista_ferragens   = isset($data->lista_ferragens) ? $data->lista_ferragens : 'PARA ENVIAR';

    $checklist_respondido = isset($data->checklist_respondido) ? $data->checklist_respondido : 'NAO';
    $checklist_link       = isset($data->checklist_link) ? trim($data->checklist_link) : null;
    $medicao_agendada     = isset($data->medicao_agendada) ? $data->medicao_agendada : 'NAO';
    $medicao_data         = !empty($data->medicao_data) ? $data->medicao_data : null;

    $equipe_instalacao      = !empty($data->equipe_instalacao) ? trim($data->equipe_instalacao) : null;
    $data_inicio_instalacao = !empty($data->data_inicio_instalacao) ? $data->data_inicio_instalacao : null;
    $data_fim_instalacao    = !empty($data->data_fim_instalacao) ? $data->data_fim_instalacao : null;

    try {
        $pdo->beginTransaction();

        $cliente_final = mb_strtoupper($cliente, 'UTF-8');

        // Se o nome não tiver os colchetes [ ], significa que veio do Dropdown. 
        // Vamos procurar o Código dele na tabela central para manter a padronização!
        if (!preg_match('/^\[.*?\]/', $cliente_final)) {
            $stmtFind = $pdo->prepare("SELECT codigo_cliente FROM clientes_cadastro WHERE UPPER(nome_contrato) = ? LIMIT 1");
            $stmtFind->execute([$cliente_final]);
            $cad = $stmtFind->fetch(PDO::FETCH_ASSOC);
            
            if ($cad && !empty($cad['codigo_cliente'])) {
                $cliente_final = "[" . $cad['codigo_cliente'] . "] " . $cliente_final;
            }
        }

        $stmt = $pdo->prepare("UPDATE projetos_pcp SET 
            cliente = :cliente, 
            data_limite = :data_limite, 
            observacao = :observacao, 
            promob = :promob, 
            projeto_executivo = :projeto_executivo,
            corte_furacao = :corte_furacao, 
            lista_compras = :lista_compras, 
            lista_ferragens = :lista_ferragens,
            checklist_respondido = :checklist_respondido,
            checklist_link = :checklist_link,
            medicao_agendada = :medicao_agendada,
            medicao_data = :medicao_data,
            equipe_instalacao = :equipe_instalacao,
            data_inicio_instalacao = :data_inicio_instalacao,
            data_fim_instalacao = :data_fim_instalacao
            WHERE id = :id");
        
        $stmt->execute([
            'cliente'                => $cliente_final,
            'data_limite'            => $data_limite,
            'observacao'             => $observacao,
            'promob'                 => $promob,
            'projeto_executivo'      => $projeto_executivo,
            'corte_furacao'          => $corte_furacao,
            'lista_compras'          => $lista_compras,
            'lista_ferragens'        => $lista_ferragens,
            'checklist_respondido'   => $checklist_respondido,
            'checklist_link'         => $checklist_link,
            'medicao_agendada'       => $medicao_agendada,
            'medicao_data'           => $medicao_data,
            'equipe_instalacao'      => $equipe_instalacao,
            'data_inicio_instalacao' => $data_inicio_instalacao,
            'data_fim_instalacao'    => $data_fim_instalacao,
            'id'                     => $id
        ]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    $pdo = null;
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID e Nome do cliente são obrigatórios.']);
}
?>