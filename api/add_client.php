<?php
// api/add_client.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$json = file_get_contents('php://input');
$data = json_decode($json);

$cliente = isset($data->cliente) ? trim($data->cliente) : '';

if (!empty($cliente)) {
    // --- MOTOR DE BUSCA DE ID ---
    $cliente_id = null;
    $nome_limpo = trim(preg_replace('/^\[.*?\]\s*/', '', $cliente));
    
    $stmtCli = $pdo->prepare("SELECT id FROM clientes_cadastro WHERE TRIM(UPPER(nome_contrato)) = UPPER(?) LIMIT 1");
    $stmtCli->execute([$nome_limpo]);
    if ($cli = $stmtCli->fetch()) {
        $cliente_id = $cli['id'];
    } else {
        if (preg_match('/^\[(.*?)\]/', $cliente, $matches)) {
            $codigo_tag = trim($matches[1]);
            $stmtTag = $pdo->prepare("SELECT id FROM clientes_cadastro WHERE codigo_cliente = ? LIMIT 1");
            $stmtTag->execute([$codigo_tag]);
            if ($cliTag = $stmtTag->fetch()) {
                $cliente_id = $cliTag['id'];
            } else {
                $possible_id = (int) preg_replace('/[^0-9]/', '', $codigo_tag);
                if ($possible_id > 0) {
                    $stmtId = $pdo->prepare("SELECT id FROM clientes_cadastro WHERE id = ? LIMIT 1");
                    $stmtId->execute([$possible_id]);
                    if ($cliId = $stmtId->fetch()) $cliente_id = $cliId['id'];
                }
            }
        }
    }
    // -----------------------------

    $data_limite = !empty($data->data_limite) ? $data->data_limite : null;
    $observacao  = !empty($data->observacao) ? trim($data->observacao) : null;
    $status      = isset($data->status) ? $data->status : 'desenvolvimento';
    
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
    
    $situacao_obra = isset($data->situacao_obra) ? $data->situacao_obra : 'NORMAL';

    try {
        $stmt = $pdo->prepare("INSERT INTO projetos_pcp 
            (cliente_id, cliente, data_limite, observacao, status, promob, projeto_executivo, corte_furacao, lista_compras, lista_ferragens, checklist_respondido, checklist_link, medicao_agendada, medicao_data, equipe_instalacao, data_inicio_instalacao, data_fim_instalacao, situacao_obra) 
            VALUES (:cliente_id, :cliente, :data_limite, :observacao, :status, :promob, :projeto_executivo, :corte_furacao, :lista_compras, :lista_ferragens, :checklist_respondido, :checklist_link, :medicao_agendada, :medicao_data, :equipe_instalacao, :data_inicio_instalacao, :data_fim_instalacao, :situacao_obra)");
        
        $stmt->execute([
            'cliente_id'             => $cliente_id,
            'cliente'                => $cliente,
            'data_limite'            => $data_limite,
            'observacao'             => $observacao,
            'status'                 => $status,
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
            'situacao_obra'          => $situacao_obra
        ]);

        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    $pdo = null;
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'O nome do cliente é obrigatório.']);
}
?>