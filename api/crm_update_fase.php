<?php
// api/crm_update_fase.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$data = json_decode((string)file_get_contents('php://input'), true) ?? [];

$id = (int)($data['id'] ?? 0);
$fase = (string)($data['fase'] ?? '');

if ($id > 0 && $fase !== '') {
    try {
        // 1. GARANTIA DE ESTRUTURA: Força a existência da tabela para evitar falhas silenciosas de banco de dados
        $pdo->exec("CREATE TABLE IF NOT EXISTS administrativo_contratos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lead_id INT NOT NULL,
            cliente_nome VARCHAR(255) NOT NULL,
            valor DECIMAL(10,2) DEFAULT 0.00,
            status_contrato VARCHAR(50) DEFAULT 'PENDENTE',
            status_financeiro VARCHAR(50) DEFAULT 'A FATURAR',
            numero_nf VARCHAR(50) NULL,
            custo_mdf DECIMAL(10,2) DEFAULT 0.00,
            custo_ferragens DECIMAL(10,2) DEFAULT 0.00,
            custo_comissao DECIMAL(10,2) DEFAULT 0.00,
            custo_outros DECIMAL(10,2) DEFAULT 0.00,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->beginTransaction();

        // Busca informações completas do Lead
        $stmtLead = $pdo->prepare("
            SELECT cl.*, c.codigo_cliente, c.nome_contrato 
            FROM comercial_leads cl
            LEFT JOIN clientes_cadastro c ON cl.cliente_id = c.id
            WHERE cl.id = :id
        ");
        $stmtLead->execute(['id' => $id]);
        $lead = $stmtLead->fetch(PDO::FETCH_ASSOC);

        if (!$lead) { 
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Lead não encontrado.']); 
            exit; 
        }
        
        // Bloqueia execução fantasma
        if (($lead['fase'] ?? '') === $fase) { 
            $pdo->rollBack(); 
            echo json_encode(['success' => true]); 
            exit; 
        }

        $dt_fechamento = ($fase === 'FECHADO') ? date('Y-m-d') : null;
        $motivo = null;
        
        if (in_array($fase, ['PAUSADO', 'PERDIDO']) && !empty($data['motivo'])) {
            $motivo = mb_strtoupper((string)$data['motivo'], 'UTF-8');
        }

        // Atualiza a fase no Comercial
        $stmt = $pdo->prepare("UPDATE comercial_leads SET fase = :fase, data_fechamento = :dt, motivo_status = :motivo WHERE id = :id");
        $stmt->execute(['fase' => $fase, 'dt' => $dt_fechamento, 'motivo' => $motivo, 'id' => $id]);

        // ROTINA EXCLUSIVA DE VENDA FECHADA
        if ($fase === 'FECHADO') {
            $nome_base = !empty($lead['nome_contrato']) ? $lead['nome_contrato'] : ($lead['cliente_nome'] ?? 'CLIENTE DESCONHECIDO');
            $nome_base = mb_strtoupper(trim($nome_base), 'UTF-8');
            
            $codigo = !empty($lead['codigo_cliente']) ? $lead['codigo_cliente'] : 'CLI-' . $id;
            $nome_com_codigo = "[" . $codigo . "] " . $nome_base;
            
            // 2. CONVERSÃO SEGURA DE DADOS: Impede que MySQL trave ao receber valores vazios
            $valorProjeto = (float)($lead['valor_estimado'] ?? 0.00);

            // INTEGRAÇÃO PCP
            if (!empty($data['gerarPCP'])) {
                $stmtCheckPCP = $pdo->prepare("SELECT id FROM projetos_pcp WHERE lead_id = :lead_id ORDER BY id ASC");
                $stmtCheckPCP->execute(['lead_id' => $id]);
                $rows = $stmtCheckPCP->fetchAll(PDO::FETCH_ASSOC);

                if (count($rows) > 0) {
                    $idPrincipal = $rows[0]['id'];
                    $stmtUpd = $pdo->prepare("UPDATE projetos_pcp SET cliente = :cliente WHERE id = :id");
                    $stmtUpd->execute(['cliente' => $nome_com_codigo, 'id' => $idPrincipal]);
                    
                    for ($i = 1; $i < count($rows); $i++) {
                        $pdo->prepare("DELETE FROM projetos_pcp WHERE id = ?")->execute([$rows[$i]['id']]);
                    }
                } else {
                    $obs_texto = "Obra Comercial. Vlr: R$ " . number_format($valorProjeto, 2, ',', '.') . " | Obs: " . ($lead['observacao'] ?? '');
                    $stmtPCP = $pdo->prepare("INSERT INTO projetos_pcp (lead_id, cliente, status, observacao) VALUES (:lead_id, :cliente, 'desenvolvimento', :obs)");
                    $stmtPCP->execute([
                        'lead_id' => $id,
                        'cliente' => $nome_com_codigo,
                        'obs' => $obs_texto
                    ]);
                }
            }

            // INTEGRAÇÃO ADMINISTRATIVO / FINANCEIRO
            $stmtCheckAdmin = $pdo->prepare("SELECT id FROM administrativo_contratos WHERE lead_id = :lead_id");
            $stmtCheckAdmin->execute(['lead_id' => $id]);
            $adminCad = $stmtCheckAdmin->fetch(PDO::FETCH_ASSOC);
            
            if (!$adminCad) {
                // Criação limpa
                $stmtAdmin = $pdo->prepare("INSERT INTO administrativo_contratos (lead_id, cliente_nome, valor) VALUES (:lead_id, :cliente_nome, :valor)");
                $stmtAdmin->execute([
                    'lead_id' => $id,
                    'cliente_nome' => $nome_com_codigo,
                    'valor' => $valorProjeto
                ]);
            } else {
                // Se já existir, atualiza o nome e o valor para garantir a sincronia de dados
                $stmtAdminUpd = $pdo->prepare("UPDATE administrativo_contratos SET cliente_nome = :cliente_nome, valor = :valor WHERE id = :id");
                $stmtAdminUpd->execute([
                    'cliente_nome' => $nome_com_codigo,
                    'valor' => $valorProjeto,
                    'id' => $adminCad['id']
                ]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (\Throwable $e) { 
        // 3. LOG COMPLETO: Agora captura falhas do PDO e falhas de tipo (TypeErrors)
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados inválidos recebidos.']);
}
?>