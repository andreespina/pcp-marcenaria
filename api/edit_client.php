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
    // --- MOTOR DE BUSCA DE ID ---
    $cliente_id = null;
    $nome_limpo = trim(preg_replace('/^\[.*?\]\s*/', '', $cliente));
    
    $stmtCli = $pdo->prepare("SELECT id, codigo_cliente FROM clientes_cadastro WHERE TRIM(UPPER(nome_contrato)) = UPPER(?) LIMIT 1");
    $stmtCli->execute([$nome_limpo]);
    if ($cli = $stmtCli->fetch()) {
        $cliente_id = $cli['id'];
        $cliente_codigo = $cli['codigo_cliente'];
    } else {
        if (preg_match('/^\[(.*?)\]/', $cliente, $matches)) {
            $codigo_tag = trim($matches[1]);
            $stmtTag = $pdo->prepare("SELECT id, codigo_cliente FROM clientes_cadastro WHERE codigo_cliente = ? LIMIT 1");
            $stmtTag->execute([$codigo_tag]);
            if ($cliTag = $stmtTag->fetch()) {
                $cliente_id = $cliTag['id'];
                $cliente_codigo = $cliTag['codigo_cliente'];
            } else {
                $possible_id = (int) preg_replace('/[^0-9]/', '', $codigo_tag);
                if ($possible_id > 0) {
                    $stmtId = $pdo->prepare("SELECT id, codigo_cliente FROM clientes_cadastro WHERE id = ? LIMIT 1");
                    $stmtId->execute([$possible_id]);
                    if ($cliId = $stmtId->fetch()) {
                        $cliente_id = $cliId['id'];
                        $cliente_codigo = $cliId['codigo_cliente'];
                    }
                }
            }
        }
    }
    // -----------------------------

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

        // Se encontramos o cliente e o nome no input veio sem a Tag, nós adicionamos a Tag para ficar padronizado na visualização!
        if ($cliente_id && !preg_match('/^\[.*?\]/', $cliente_final)) {
            $codigo_usar = !empty($cliente_codigo) ? $cliente_codigo : "CLI-" . str_pad($cliente_id, 2, "0", STR_PAD_LEFT);
            $cliente_final = "[" . $codigo_usar . "] " . mb_strtoupper($nome_limpo, 'UTF-8');
        }

        $stmt = $pdo->prepare("UPDATE projetos_pcp SET 
            cliente_id = :cliente_id,
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
            'cliente_id'             => $cliente_id,
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