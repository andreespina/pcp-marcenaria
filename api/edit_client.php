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

        // 1. Verifica se o projeto está vinculado a um Lead do Comercial
        $stmtCheck = $pdo->prepare("SELECT lead_id FROM projetos_pcp WHERE id = ?");
        $stmtCheck->execute([$id]);
        $projetoAtual = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        $cliente_nome_final = $cliente; // Nome padrão enviado pelo formulário do PCP

        // 2. Se tiver vínculo, FORÇA o nome correto [CÓDIGO] NOME para não desvincular
        if ($projetoAtual && !empty($projetoAtual['lead_id'])) {
            $stmtLead = $pdo->prepare("
                SELECT cl.cliente_nome, c.codigo_cliente 
                FROM comercial_leads cl
                LEFT JOIN clientes_cadastro c ON cl.cliente_id = c.id
                WHERE cl.id = ?
            ");
            $stmtLead->execute([$projetoAtual['lead_id']]);
            $lead = $stmtLead->fetch(PDO::FETCH_ASSOC);
            
            if ($lead) {
                $codigo = !empty($lead['codigo_cliente']) ? $lead['codigo_cliente'] : 'CLI-' . $projetoAtual['lead_id'];
                $cliente_nome_final = "[" . $codigo . "] " . mb_strtoupper($lead['cliente_nome'], 'UTF-8');
            }
        }

        // 3. Executa o UPDATE com os dados completos
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
            'cliente'                => $cliente_nome_final,
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