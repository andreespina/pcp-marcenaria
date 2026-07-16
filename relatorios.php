<?php
// relatorios.php
require_once 'includes/auth.php';
protegerPagina();
require_once 'config/conexao.php';

try {
    // 1. DADOS DE PROJETOS
    $stmt_proj = $pdo->query("SELECT * FROM projetos_pcp ORDER BY data_limite ASC, id DESC");
    $projetos = $stmt_proj->fetchAll(PDO::FETCH_ASSOC);

    // 2. DADOS DE ASSISTÊNCIAS
    $stmt_ast = $pdo->query("SELECT * FROM assistencias_tecnicas ORDER BY data_solicitacao DESC");
    $assistencias = $stmt_ast->fetchAll(PDO::FETCH_ASSOC);

    // 3. DADOS DE ALMOXARIFADO
    $stmt_almox = $pdo->query("SELECT * FROM almoxarifado ORDER BY categoria ASC, nome_item ASC");
    $almoxarifado = $stmt_almox->fetchAll(PDO::FETCH_ASSOC);

    // 4. DADOS DE CLIENTES
    $stmt_cli = $pdo->query("SELECT * FROM clientes_cadastro ORDER BY nome_contrato ASC");
    $clientes = $stmt_cli->fetchAll(PDO::FETCH_ASSOC);

    // 5. DADOS DO ADMINISTRATIVO (CONTRATOS E CUSTOS)
    $stmt_adm = $pdo->query("SELECT * FROM administrativo_contratos ORDER BY id DESC");
    $administrativo = $stmt_adm->fetchAll(PDO::FETCH_ASSOC);

    // 6. DADOS DO FINANCEIRO (FLUXO DE CAIXA)
    $stmt_fin = $pdo->query("SELECT * FROM financeiro ORDER BY data_vencimento DESC");
    $financeiro = $stmt_fin->fetchAll(PDO::FETCH_ASSOC);
    
    // 7. CÁLCULO DE LEAD TIME MÉDIO DE PRODUÇÃO
    $stmt_lead = $pdo->query("SELECT projeto_id, status_anterior, status_novo, data_mudanca FROM historico_projetos ORDER BY data_mudanca ASC");
    $logs = $stmt_lead->fetchAll(PDO::FETCH_ASSOC);
    
    $tempos_producao = [];
    $entradas_producao = [];
    
    foreach ($logs as $log) {
        if ($log['status_novo'] == 'producao') {
            $entradas_producao[$log['projeto_id']] = strtotime($log['data_mudanca']);
        }
        if ($log['status_anterior'] == 'producao' && isset($entradas_producao[$log['projeto_id']])) {
            $saida = strtotime($log['data_mudanca']);
            $diferenca_dias = ($saida - $entradas_producao[$log['projeto_id']]) / (60 * 60 * 24);
            $tempos_producao[] = $diferenca_dias;
            unset($entradas_producao[$log['projeto_id']]); 
        }
    }
    
    $lead_time_medio = count($tempos_producao) > 0 ? round(array_sum($tempos_producao) / count($tempos_producao), 1) : 0;

    // 8. DADOS DO CRM (Tentativa de busca genérica para evitar erros)
    $crm_leads = [];
    try {
        // Se a sua tabela do CRM tiver outro nome, basta alterar "comercial_leads" abaixo
        $stmt_crm = $pdo->query("SELECT * FROM comercial_leads ORDER BY id DESC");
        $crm_leads = $stmt_crm->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) { /* Tabela pode não existir com este nome exato, ignoramos suavemente */ }


    // ==========================================
    // CÁLCULOS E ESTATÍSTICAS GERAIS
    // ==========================================
    
    // PROJETOS
    $proj_total = count($projetos);
    $proj_atrasados = 0; $proj_concluidos = 0; $proj_andamento = 0;
    foreach($projetos as $p) {
        if ($p['status'] === 'atrasou') { $proj_atrasados++; }
        elseif ($p['status'] === 'assistencia') { $proj_concluidos++; }
        else { $proj_andamento++; }
    }

    // ASSISTÊNCIAS
    $ast_total = count($assistencias);
    $ast_pendentes = 0; $ast_agendadas = 0; $ast_resolvidas = 0;
    $ast_garantia = 0; $ast_faturadas = 0; $valor_faturado = 0;
    foreach($assistencias as $a) {
        if ($a['status'] === 'pendente') $ast_pendentes++;
        elseif ($a['status'] === 'agendada') $ast_agendadas++;
        elseif ($a['status'] === 'concluida') $ast_resolvidas++;
        
        if (isset($a['tipo_cobranca']) && $a['tipo_cobranca'] === 'FATURADA') {
            $ast_faturadas++;
            $valor_faturado += (float)$a['valor_cobrado'];
        } else {
            $ast_garantia++;
        }
    }

    // ALMOXARIFADO & CLIENTES
    $almox_total = count($almoxarifado);
    $almox_critico = 0; $almox_ok = 0;
    foreach($almoxarifado as $al) {
        if ($al['quantidade'] <= $al['quantidade_minima']) { $almox_critico++; } else { $almox_ok++; }
    }
    $cli_total = count($clientes);

    // ADMINISTRATIVO (LUCRATIVIDADE)
    $adm_vendas_total = 0; $adm_custos_total = 0;
    $adm_contratos_assinados = 0; $adm_contratos_pendentes = 0;
    foreach($administrativo as $ad) {
        if ($ad['status_contrato'] === 'ASSINADO') $adm_contratos_assinados++;
        else $adm_contratos_pendentes++;

        $adm_vendas_total += (float)$ad['valor'];
        $adm_custos_total += (float)$ad['custo_mdf'] + (float)$ad['custo_ferragens'] + (float)$ad['custo_comissao'] + (float)$ad['custo_outros'];
    }
    $adm_lucro_liquido = $adm_vendas_total - $adm_custos_total;
    $adm_margem_geral = $adm_vendas_total > 0 ? ($adm_lucro_liquido / $adm_vendas_total) * 100 : 0;

    // FINANCEIRO (FLUXO)
    $fin_rec_pagas = 0; $fin_rec_pendentes = 0;
    $fin_desp_pagas = 0; $fin_desp_pendentes = 0;
    foreach($financeiro as $f) {
        if($f['tipo'] == 'RECEITA') {
            if($f['status'] == 'PAGO') $fin_rec_pagas += $f['valor'];
            else $fin_rec_pendentes += $f['valor'];
        } else {
            if($f['status'] == 'PAGO') $fin_desp_pagas += $f['valor'];
            else $fin_desp_pendentes += $f['valor'];
        }
    }
    $fin_saldo_real = $fin_rec_pagas - $fin_desp_pagas;
    $fin_saldo_previsto = ($fin_rec_pagas + $fin_rec_pendentes) - ($fin_desp_pagas + $fin_desp_pendentes);

    // CRM
    $crm_total = count($crm_leads);
    $crm_ganhos = 0; $crm_perdidos = 0; $crm_andamento = 0;
    foreach($crm_leads as $l) {
        if(strtoupper($l['status']) == 'GANHO' || strtoupper($l['status']) == 'FECHADO') $crm_ganhos++;
        elseif(strtoupper($l['status']) == 'PERDIDO') $crm_perdidos++;
        else $crm_andamento++;
    }
    $crm_conversao = $crm_total > 0 ? ($crm_ganhos / $crm_total) * 100 : 0;

} catch (\PDOException $e) {
    die("Erro na consulta do banco de dados: " . $e->getMessage());
}

// Funções Auxiliares
function formatarData($data) {
    if (!$data) return '-';
    return date('d/m/Y', strtotime($data));
}

function nomeStatus($status) {
    $nomes = [
        'instalacao'      => 'Em Instalação',
        'expedicao'       => 'Em Expedição',
        'producao'        => 'Em Produção',
        'desenvolvimento' => 'Desenv. PCP',
        'atrasou'         => 'Obra Atrasou',
        'assistencia'     => 'Concluído',
        'pendente'        => 'Pendente',
        'agendada'        => 'Agendada',
        'concluida'       => 'Baixada'
    ];
    return isset($nomes[$status]) ? $nomes[$status] : strtoupper($status);
}

// Configurações do Header
$page_title = 'BUSINESS INTELLIGENCE (BI)';
$page_subtitle = 'Métricas e Análises do ERP';
$main_class = 'flex-1'; 
$menu_button_text = 'MENU';
$page_actions = '
<button onclick="imprimirRelatorio()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg> IMPRIMIR ABA ATUAL
</button>';

// Estilo exclusivo para impressão
$head_extras = '
<style>
    .dark body { background-color: #1a1e2b !important; }
    .table-container { max-height: calc(100vh - 350px); overflow-y: auto; }
    .table-container::-webkit-scrollbar { width: 6px; }
    .table-container::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
    .dark .table-container::-webkit-scrollbar-thumb { background-color: #4b5563; }
    
    @media print {
        body { background: white !important; color: black !important; }
        .no-print { display: none !important; }
        .tab-content.hidden { display: none !important; }
        .tab-content { display: block !important; }
        .shadow-sm { box-shadow: none !important; border: 1px solid #ddd !important; }
        * { transition: none !important; }
        .table-container { max-height: none !important; overflow: visible !important; }
    }
</style>
';

require_once 'includes/header.php';
?>

<div class="flex flex-col gap-4">

    <div class="border-b border-gray-200 dark:border-[#2a3142] no-print">
        <ul class="flex flex-nowrap overflow-x-auto text-sm font-medium text-center scrollbar-thin" role="tablist">
            <li class="mr-1" role="presentation">
                <button id="btn_geral" onclick="mudarAba('geral')" class="tab-btn active px-4 py-3 border-b-2 text-blue-600 border-blue-600 dark:text-blue-400 dark:border-blue-400 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                    Geral
                </button>
            </li>
            <li class="mr-1" role="presentation">
                <button id="btn_administrativo" onclick="mudarAba('administrativo')" class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-600 dark:text-gray-400 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                    Adm (Lucros)
                </button>
            </li>
            <li class="mr-1" role="presentation">
                <button id="btn_financeiro" onclick="mudarAba('financeiro')" class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-600 dark:text-gray-400 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                    Financeiro
                </button>
            </li>
            <li class="mr-1" role="presentation">
                <button id="btn_projetos" onclick="mudarAba('projetos')" class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-600 dark:text-gray-400 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                    PCP & Produção
                </button>
            </li>
            <li class="mr-1" role="presentation">
                <button id="btn_assistencias" onclick="mudarAba('assistencias')" class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-600 dark:text-gray-400 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                    Assistências
                </button>
            </li>
            <li class="mr-1" role="presentation">
                <button id="btn_almoxarifado" onclick="mudarAba('almoxarifado')" class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-600 dark:text-gray-400 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                    Almoxarifado
                </button>
            </li>
            <li class="mr-1" role="presentation">
                <button id="btn_crm" onclick="mudarAba('crm')" class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-600 dark:text-gray-400 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                    CRM
                </button>
            </li>
            <li role="presentation">
                <button id="btn_clientes" onclick="mudarAba('clientes')" class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-600 dark:text-gray-400 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                    Clientes
                </button>
            </li>
        </ul>
    </div>

    <div id="conteudo_geral" class="tab-content">
        <div class="mb-4">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 flex items-center">
                <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Visão 360º da Marcenaria
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Métricas consolidadas de todos os departamentos.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            
            <div class="bg-gradient-to-br from-emerald-500 to-teal-700 p-5 rounded-lg shadow-md text-white flex flex-col justify-between">
                <div>
                    <p class="text-[11px] font-bold text-emerald-100 uppercase tracking-wide">Lucro Líquido Histórico (Adm)</p>
                    <p class="text-3xl font-black mt-1">R$ <?= number_format($adm_lucro_liquido, 2, ',', '.') ?></p>
                </div>
                <div class="mt-4 flex justify-between items-end border-t border-emerald-400/50 pt-2">
                    <span class="text-xs font-bold text-emerald-100">Margem Geral</span>
                    <span class="text-lg font-bold"><?= number_format($adm_margem_geral, 1, ',', '.') ?>%</span>
                </div>
            </div>

            <div class="bg-gradient-to-br from-[#1e3a8a] to-blue-800 p-5 rounded-lg shadow-md text-white flex flex-col justify-between">
                <div>
                    <p class="text-[11px] font-bold text-blue-200 uppercase tracking-wide">Saldo Atual em Caixa (Fin)</p>
                    <p class="text-3xl font-black mt-1">R$ <?= number_format($fin_saldo_real, 2, ',', '.') ?></p>
                </div>
                <div class="mt-4 flex justify-between items-end border-t border-blue-700/50 pt-2">
                    <span class="text-xs font-bold text-blue-200">Saldo Previsto</span>
                    <span class="text-lg font-bold">R$ <?= number_format($fin_saldo_previsto, 2, ',', '.') ?></span>
                </div>
            </div>

            <div class="bg-white dark:bg-[#222736] p-5 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex items-center">
                <div class="p-3 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Projetos em Andamento</p>
                    <p class="text-2xl font-black text-gray-800 dark:text-white mt-0.5"><?= $proj_andamento ?></p>
                </div>
            </div>
            
            <div class="bg-white dark:bg-[#222736] p-5 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex items-center">
                <div class="p-3 rounded-full bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Obras Atrasadas</p>
                    <p class="text-2xl font-black text-red-600 dark:text-red-400 mt-0.5"><?= $proj_atrasados ?></p>
                </div>
            </div>

            <div class="bg-white dark:bg-[#222736] p-5 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex items-center">
                <div class="p-3 rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Assistências Abertas</p>
                    <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-0.5"><?= $ast_pendentes + $ast_agendadas ?></p>
                </div>
            </div>

            <div class="bg-white dark:bg-[#222736] p-5 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex items-center">
                <div class="p-3 rounded-full bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Faturado em Assist.</p>
                    <p class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-0.5">R$ <?= number_format($valor_faturado, 2, ',', '.') ?></p>
                </div>
            </div>

            <div class="bg-white dark:bg-[#222736] p-5 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex items-center">
                <div class="p-3 rounded-full bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Itens Estoque Crítico</p>
                    <p class="text-2xl font-black text-orange-600 dark:text-orange-400 mt-0.5"><?= $almox_critico ?></p>
                </div>
            </div>

            <div class="bg-white dark:bg-[#222736] p-5 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex items-center">
                <div class="p-3 rounded-full bg-pink-50 dark:bg-pink-900/20 text-pink-600 dark:text-pink-400 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total de Clientes</p>
                    <p class="text-2xl font-black text-pink-600 dark:text-pink-400 mt-0.5"><?= $cli_total ?></p>
                </div>
            </div>
        </div>
    </div>

    <div id="conteudo_administrativo" class="tab-content hidden">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142]">
                <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Volume Bruto Negociado</p>
                <p class="text-2xl font-black text-gray-800 dark:text-white mt-0.5">R$ <?= number_format($adm_vendas_total, 2, ',', '.') ?></p>
            </div>
            <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-red-200 dark:border-red-900/30">
                <p class="text-[10px] font-bold text-red-600 dark:text-red-400 uppercase tracking-wide">Custos Totais (Chapas, Ferr...)</p>
                <p class="text-2xl font-black text-red-600 dark:text-red-400 mt-0.5">R$ <?= number_format($adm_custos_total, 2, ',', '.') ?></p>
            </div>
            <div class="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-lg shadow-sm border border-emerald-200 dark:border-emerald-800/50">
                <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Lucro Líquido Global</p>
                <p class="text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-0.5">R$ <?= number_format($adm_lucro_liquido, 2, ',', '.') ?></p>
            </div>
            <div class="bg-teal-50 dark:bg-teal-900/20 p-4 rounded-lg shadow-sm border border-teal-200 dark:border-teal-800/50">
                <p class="text-[10px] font-bold text-teal-600 dark:text-teal-400 uppercase tracking-wide">Margem de Lucro Média</p>
                <p class="text-2xl font-black text-teal-700 dark:text-teal-400 mt-0.5"><?= number_format($adm_margem_geral, 1, ',', '.') ?> %</p>
            </div>
        </div>

        <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] overflow-hidden">
            <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 no-print">
                <div class="relative w-full md:w-1/3">
                    <input type="text" onkeyup="filtrarTabela('busca_adm', 'tr-adm')" id="busca_adm" placeholder="Filtrar contrato ou cliente..." class="w-full px-3 py-1.5 pl-8 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
            <div class="overflow-x-auto table-container">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 sticky top-0 font-bold">
                        <tr>
                            <th class="px-6 py-3">Contrato / Cliente</th>
                            <th class="px-6 py-3 text-right">Venda (R$)</th>
                            <th class="px-6 py-3 text-right">Custos (R$)</th>
                            <th class="px-6 py-3 text-right">Lucro (R$)</th>
                            <th class="px-6 py-3 text-center">Margem</th>
                            <th class="px-6 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($administrativo)): ?>
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">Nenhum contrato registado.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($administrativo as $ad): 
                            $c_total = $ad['custo_mdf'] + $ad['custo_ferragens'] + $ad['custo_comissao'] + $ad['custo_outros'];
                            $lucro = $ad['valor'] - $c_total;
                            $margem = $ad['valor'] > 0 ? ($lucro / $ad['valor']) * 100 : 0;
                            $cor_lucro = $lucro >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
                        ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors text-gray-700 dark:text-gray-200 tr-adm">
                                <td class="px-6 py-3 font-bold uppercase text-gray-800 dark:text-gray-100 td-busca"><?= htmlspecialchars($ad['cliente_nome']) ?></td>
                                <td class="px-6 py-3 font-black text-right text-gray-800 dark:text-gray-200 td-busca"><?= number_format($ad['valor'], 2, ',', '.') ?></td>
                                <td class="px-6 py-3 font-medium text-right text-red-500 dark:text-red-400 td-busca"><?= number_format($c_total, 2, ',', '.') ?></td>
                                <td class="px-6 py-3 font-black text-right <?= $cor_lucro ?> td-busca"><?= number_format($lucro, 2, ',', '.') ?></td>
                                <td class="px-6 py-3 font-bold text-center <?= $cor_lucro ?> td-busca"><?= number_format($margem, 1, ',', '.') ?>%</td>
                                <td class="px-6 py-3 text-center td-busca"><span class="px-2 py-1 rounded text-[10px] font-bold uppercase <?= $ad['status_contrato'] === 'ASSINADO' ? 'border border-green-200 bg-green-50 text-green-700 dark:bg-green-900/30 dark:border-green-800 dark:text-green-400' : 'border border-red-200 bg-red-50 text-red-700 dark:bg-red-900/30 dark:border-red-800 dark:text-red-400' ?>"><?= $ad['status_contrato'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="conteudo_financeiro" class="tab-content hidden">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-green-200 dark:border-green-900/30">
                <p class="text-[10px] font-bold text-green-600 dark:text-green-400 uppercase tracking-wide">Receitas Realizadas</p>
                <p class="text-2xl font-black text-green-700 dark:text-green-400 mt-0.5">R$ <?= number_format($fin_rec_pagas, 2, ',', '.') ?></p>
            </div>
            <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-red-200 dark:border-red-900/30">
                <p class="text-[10px] font-bold text-red-600 dark:text-red-400 uppercase tracking-wide">Despesas Pagas</p>
                <p class="text-2xl font-black text-red-700 dark:text-red-400 mt-0.5">R$ <?= number_format($fin_desp_pagas, 2, ',', '.') ?></p>
            </div>
            <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-blue-200 dark:border-blue-900/30">
                <p class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wide">A Receber (Pendente)</p>
                <p class="text-2xl font-black text-blue-700 dark:text-blue-400 mt-0.5">R$ <?= number_format($fin_rec_pendentes, 2, ',', '.') ?></p>
            </div>
            <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-orange-200 dark:border-orange-900/30">
                <p class="text-[10px] font-bold text-orange-600 dark:text-orange-400 uppercase tracking-wide">A Pagar (Pendente)</p>
                <p class="text-2xl font-black text-orange-700 dark:text-orange-400 mt-0.5">R$ <?= number_format($fin_desp_pendentes, 2, ',', '.') ?></p>
            </div>
        </div>

        <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] overflow-hidden">
            <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 no-print">
                <div class="relative w-full md:w-1/3">
                    <input type="text" onkeyup="filtrarTabela('busca_fin', 'tr-fin')" id="busca_fin" placeholder="Filtrar lançamentos..." class="w-full px-3 py-1.5 pl-8 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
            <div class="overflow-x-auto table-container">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 sticky top-0 font-bold">
                        <tr>
                            <th class="px-6 py-3">Data Venc.</th>
                            <th class="px-6 py-3">Tipo / Categoria</th>
                            <th class="px-6 py-3">Descrição (Entidade)</th>
                            <th class="px-6 py-3 text-right">Valor (R$)</th>
                            <th class="px-6 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($financeiro)): ?>
                            <tr><td colspan=\"5\" class="px-6 py-8 text-center text-gray-500 italic">Nenhum lançamento registado.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($financeiro as $fin): 
                            $is_rec = $fin['tipo'] === 'RECEITA';
                            $cor_valor = $is_rec ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
                            $sinal = $is_rec ? '+' : '-';
                        ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors text-gray-700 dark:text-gray-200 tr-fin">
                                <td class="px-6 py-3 font-medium td-busca"><?= formatarData($fin['data_vencimento']) ?></td>
                                <td class="px-6 py-3 font-bold uppercase td-busca <?= $is_rec ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>"><?= $fin['tipo'] ?><br><span class="text-[10px] text-gray-500 font-normal"><?= htmlspecialchars($fin['categoria']) ?></span></td>
                                <td class="px-6 py-3 font-bold uppercase text-gray-800 dark:text-gray-100 td-busca"><?= htmlspecialchars($fin['descricao']) ?></td>
                                <td class="px-6 py-3 font-black text-right <?= $cor_valor ?> td-busca"><?= $sinal ?> <?= number_format($fin['valor'], 2, ',', '.') ?></td>
                                <td class="px-6 py-3 text-center td-busca"><span class="px-2 py-1 rounded text-[10px] font-bold uppercase <?= $fin['status'] === 'PAGO' ? 'border border-green-200 bg-green-50 text-green-700 dark:bg-green-900/30 dark:border-green-800 dark:text-green-400' : 'border border-yellow-200 bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:border-yellow-800 dark:text-yellow-400' ?>"><?= $fin['status'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="conteudo_projetos" class="tab-content hidden">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
            <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142]">
                <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total de Projetos</p>
                <p class="text-2xl font-black text-gray-800 dark:text-white mt-0.5"><?= $proj_total ?></p>
            </div>
            <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-blue-200 dark:border-blue-800/50">
                <p class="text-[10px] font-bold text-blue-500 dark:text-blue-400 uppercase tracking-wide">Em Andamento</p>
                <p class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-0.5"><?= $proj_andamento ?></p>
            </div>
            <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-green-200 dark:border-green-800/50">
                <p class="text-[10px] font-bold text-green-500 dark:text-green-400 uppercase tracking-wide">Concluídos</p>
                <p class="text-2xl font-black text-green-600 dark:text-green-400 mt-0.5"><?= $proj_concluidos ?></p>
            </div>
            <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-red-200 dark:border-red-800/50">
                <p class="text-[10px] font-bold text-red-500 dark:text-red-400 uppercase tracking-wide">Atrasados</p>
                <p class="text-2xl font-black text-red-600 dark:text-red-400 mt-0.5"><?= $proj_atrasados ?></p>
            </div>
            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg shadow-sm border border-indigo-200 dark:border-indigo-800/50">
                <p class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wide">Lead Time (Produção)</p>
                <p class="text-2xl font-black text-indigo-700 dark:text-indigo-300 mt-0.5"><?= $lead_time_medio ?> Dias</p>
            </div>
        </div>

        <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] overflow-hidden">
            <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 no-print">
                <div class="relative w-full md:w-1/3">
                    <input type="text" onkeyup="filtrarTabela('busca_proj', 'tr-proj')" id="busca_proj" placeholder="Pesquisar projetos..." class="w-full px-3 py-1.5 pl-8 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
            <div class="overflow-x-auto table-container">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 sticky top-0 font-bold">
                        <tr>
                            <th class="px-6 py-3">ID</th>
                            <th class="px-6 py-3">Cliente</th>
                            <th class="px-6 py-3">Fase Atual</th>
                            <th class="px-6 py-3">Data Limite</th>
                            <th class="px-6 py-3">Equipe de Instalação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($projetos)): ?>
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">Nenhum projeto.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($projetos as $p): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors text-gray-700 dark:text-gray-200 tr-proj">
                                <td class="px-6 py-3 font-bold text-gray-500 dark:text-gray-400 td-busca">#<?= $p['id'] ?></td>
                                <td class="px-6 py-3 font-bold uppercase text-gray-800 dark:text-gray-100 td-busca"><?= htmlspecialchars($p['cliente']) ?></td>
                                <td class="px-6 py-3 td-busca"><span class="px-2 py-1 rounded text-[10px] font-bold uppercase border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700"><?= nomeStatus($p['status']) ?></span></td>
                                <td class="px-6 py-3 font-medium td-busca"><?= formatarData($p['data_limite']) ?></td>
                                <td class="px-6 py-3 uppercase text-xs text-indigo-600 dark:text-indigo-400 font-semibold td-busca"><?= htmlspecialchars($p['equipe_instalacao']) ?: '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="conteudo_assistencias" class="tab-content hidden">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-amber-200 dark:border-amber-800/50">
                <p class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wide">Total Solicitadas</p>
                <p class="text-2xl font-black text-amber-700 dark:text-amber-400 mt-0.5"><?= $ast_total ?></p>
            </div>
            <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-emerald-200 dark:border-emerald-800/50">
                <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Garantia (Custo 0)</p>
                <p class="text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-0.5"><?= $ast_garantia ?></p>
            </div>
            <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-purple-200 dark:border-purple-800/50">
                <p class="text-[10px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wide">Qtd Faturadas</p>
                <p class="text-2xl font-black text-purple-700 dark:text-purple-400 mt-0.5"><?= $ast_faturadas ?></p>
            </div>
            <div class="bg-purple-600 p-4 rounded-lg shadow-sm border border-purple-800 text-white flex flex-col justify-center">
                <p class="text-[10px] font-bold text-purple-200 uppercase tracking-wide">Total Recebido</p>
                <p class="text-2xl font-black mt-0.5">R$ <?= number_format($valor_faturado, 2, ',', '.') ?></p>
            </div>
        </div>

        <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] overflow-hidden">
            <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 no-print">
                <div class="relative w-full md:w-1/3">
                    <input type="text" onkeyup="filtrarTabela('busca_ast', 'tr-ast')" id="busca_ast" placeholder="Pesquisar assistência..." class="w-full px-3 py-1.5 pl-8 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-amber-500 transition-colors">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
            <div class="overflow-x-auto table-container">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 sticky top-0 font-bold">
                        <tr>
                            <th class="px-6 py-3">OS #</th>
                            <th class="px-6 py-3">Cliente</th>
                            <th class="px-6 py-3">Data Solicitação</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Tipo</th>
                            <th class="px-6 py-3">Valor (R$)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($assistencias)): ?>
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">Nenhuma assistência.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($assistencias as $a): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors text-gray-700 dark:text-gray-200 tr-ast">
                                <td class="px-6 py-3 font-bold text-gray-500 dark:text-gray-400 td-busca">AST #<?= $a['id'] ?></td>
                                <td class="px-6 py-3 font-bold uppercase td-busca"><?= htmlspecialchars($a['cliente']) ?></td>
                                <td class="px-6 py-3 text-xs text-gray-500 dark:text-gray-400 td-busca"><?= formatarData($a['data_solicitacao']) ?></td>
                                <td class="px-6 py-3 td-busca"><span class="px-2 py-1 rounded text-[10px] font-bold uppercase border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700"><?= nomeStatus($a['status']) ?></span></td>
                                
                                <td class="px-6 py-3 font-bold text-[11px] td-busca <?= (isset($a['tipo_cobranca']) && $a['tipo_cobranca'] === 'FATURADA') ? 'text-purple-600 dark:text-purple-400' : 'text-emerald-600 dark:text-emerald-400' ?>">
                                    <?= !empty($a['tipo_cobranca']) ? $a['tipo_cobranca'] : 'GARANTIA' ?>
                                </td>
                                <td class="px-6 py-3 font-medium td-busca">
                                    <?= (isset($a['tipo_cobranca']) && $a['tipo_cobranca'] === 'FATURADA') ? 'R$ ' . number_format((float)$a['valor_cobrado'], 2, ',', '.') : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="conteudo_almoxarifado" class="tab-content hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142]">
                <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total de Itens Cadastrados</p>
                <p class="text-2xl font-black text-gray-800 dark:text-white mt-0.5"><?= $almox_total ?></p>
            </div>
            <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-green-200 dark:border-green-800/50">
                <p class="text-[10px] font-bold text-green-500 dark:text-green-400 uppercase tracking-wide">Estoque Saudável</p>
                <p class="text-2xl font-black text-green-600 dark:text-green-400 mt-0.5"><?= $almox_ok ?></p>
            </div>
            <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-red-200 dark:border-red-800/50">
                <p class="text-[10px] font-bold text-red-500 dark:text-red-400 uppercase tracking-wide">Crítico / Em Falta</p>
                <p class="text-2xl font-black text-red-600 dark:text-red-400 mt-0.5"><?= $almox_critico ?></p>
            </div>
        </div>

        <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] overflow-hidden">
            <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 no-print">
                <div class="relative w-full md:w-1/3">
                    <input type="text" onkeyup="filtrarTabela('busca_almox', 'tr-almox')" id="busca_almox" placeholder="Pesquisar material..." class="w-full px-3 py-1.5 pl-8 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-teal-500 transition-colors">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
            <div class="overflow-x-auto table-container">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 sticky top-0 font-bold">
                        <tr>
                            <th class="px-6 py-3">Item / Material</th>
                            <th class="px-6 py-3">Categoria</th>
                            <th class="px-6 py-3">Estoque Atual</th>
                            <th class="px-6 py-3">Mínimo de Segurança</th>
                            <th class="px-6 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($almoxarifado)): ?>
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">Nenhum item.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($almoxarifado as $al): 
                            $critico = ($al['quantidade'] <= $al['quantidade_minima']);
                        ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors text-gray-700 dark:text-gray-200 tr-almox">
                                <td class="px-6 py-3 font-bold uppercase text-gray-800 dark:text-gray-100 td-busca"><?= htmlspecialchars($al['nome_item']) ?></td>
                                <td class="px-6 py-3 text-xs uppercase text-gray-500 dark:text-gray-400 td-busca"><?= htmlspecialchars($al['categoria']) ?></td>
                                <td class="px-6 py-3 font-black td-busca <?= $critico ? 'text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-gray-200' ?>"><?= (float)$al['quantidade'] ?> <?= $al['unidade_medida'] ?></td>
                                <td class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400 td-busca"><?= (float)$al['quantidade_minima'] ?></td>
                                <td class="px-6 py-3 text-center">
                                    <?php if($critico): ?>
                                        <span class="bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 px-2 py-1 rounded text-[10px] font-bold border border-red-200 dark:border-red-800/50 uppercase">COMPRAR</span>
                                    <?php else: ?>
                                        <span class="bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400 px-2 py-1 rounded text-[10px] font-bold border border-green-200 dark:border-green-800/50 uppercase">SAUDÁVEL</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="conteudo_crm" class="tab-content hidden">
        <?php if(empty($crm_leads)): ?>
            <div class="bg-white dark:bg-[#222736] p-8 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] text-center">
                <div class="text-4xl mb-4">📈</div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-2">Módulo CRM não encontrado ou vazio</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Certifique-se de que a tabela do comercial se chama <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">comercial_leads</code> ou adicione os seus primeiros leads no funil de vendas para gerar estatísticas aqui.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142]">
                    <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total de Leads Gerados</p>
                    <p class="text-2xl font-black text-gray-800 dark:text-white mt-0.5"><?= $crm_total ?></p>
                </div>
                <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-emerald-200 dark:border-emerald-900/30">
                    <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Contratos Fechados</p>
                    <p class="text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-0.5"><?= $crm_ganhos ?></p>
                </div>
                <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-red-200 dark:border-red-900/30">
                    <p class="text-[10px] font-bold text-red-600 dark:text-red-400 uppercase tracking-wide">Vendas Perdidas</p>
                    <p class="text-2xl font-black text-red-700 dark:text-red-400 mt-0.5"><?= $crm_perdidos ?></p>
                </div>
                <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-blue-200 dark:border-blue-900/30">
                    <p class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wide">Taxa de Conversão Real</p>
                    <p class="text-2xl font-black text-blue-700 dark:text-blue-400 mt-0.5"><?= number_format($crm_conversao, 1, ',', '.') ?>%</p>
                </div>
            </div>

            <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] overflow-hidden">
                <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 no-print">
                    <div class="relative w-full md:w-1/3">
                        <input type="text" onkeyup="filtrarTabela('busca_crm', 'tr-crm')" id="busca_crm" placeholder="Pesquisar prospecto..." class="w-full px-3 py-1.5 pl-8 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 transition-colors">
                        <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
                <div class="overflow-x-auto table-container">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 sticky top-0 font-bold">
                            <tr>
                                <th class="px-6 py-3">Data Lead</th>
                                <th class="px-6 py-3">Nome do Prospecto</th>
                                <th class="px-6 py-3 text-right">Valor Estimado</th>
                                <th class="px-6 py-3 text-center">Status no Funil</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($crm_leads as $l): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors text-gray-700 dark:text-gray-200 tr-crm">
                                    <td class="px-6 py-3 text-xs td-busca"><?= formatarData(isset($l['data_criacao']) ? $l['data_criacao'] : null) ?></td>
                                    <td class="px-6 py-3 font-bold uppercase td-busca"><?= htmlspecialchars(isset($l['nome_lead']) ? $l['nome_lead'] : (isset($l['cliente']) ? $l['cliente'] : '-')) ?></td>
                                    <td class="px-6 py-3 font-medium text-right td-busca">R$ <?= isset($l['valor_estimado']) ? number_format((float)$l['valor_estimado'], 2, ',', '.') : '0,00' ?></td>
                                    <td class="px-6 py-3 text-center td-busca"><span class="px-2 py-1 rounded text-[10px] font-bold uppercase border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700"><?= htmlspecialchars($l['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div id="conteudo_clientes" class="tab-content hidden">
        <div class="mb-4 bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-pink-200 dark:border-pink-800/50 flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold text-pink-600 dark:text-pink-400 uppercase tracking-wide">Base Oficial de Clientes</p>
                <p class="text-2xl font-black text-pink-700 dark:text-pink-300 mt-0.5"><?= $cli_total ?> Contratos Cadastrados</p>
            </div>
            <div class="text-5xl opacity-20">🏢</div>
        </div>

        <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] overflow-hidden">
            <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 no-print">
                <div class="relative w-full md:w-1/3">
                    <input type="text" onkeyup="filtrarTabela('busca_cli', 'tr-cli')" id="busca_cli" placeholder="Pesquisar cliente..." class="w-full px-3 py-1.5 pl-8 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-pink-500 transition-colors">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
            <div class="overflow-x-auto table-container">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 sticky top-0 font-bold">
                        <tr>
                            <th class="px-6 py-3">Código</th>
                            <th class="px-6 py-3">Nome / Contrato</th>
                            <th class="px-6 py-3">Telefone Principal</th>
                            <th class="px-6 py-3">Cidade</th>
                            <th class="px-6 py-3">Arquiteto(a)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($clientes)): ?>
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">Nenhum cliente cadastrado.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($clientes as $c): 
                            $codigo = !empty($c['codigo_cliente']) ? $c['codigo_cliente'] : "CLI-" . str_pad($c['id'], 2, "0", STR_PAD_LEFT);
                        ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors text-gray-700 dark:text-gray-200 tr-cli">
                                <td class="px-6 py-3 font-bold text-blue-600 dark:text-blue-400 text-[11px] td-busca">[<?= htmlspecialchars($codigo) ?>]</td>
                                <td class="px-6 py-3 font-bold uppercase text-gray-800 dark:text-gray-100 td-busca"><?= htmlspecialchars($c['nome_contrato']) ?></td>
                                <td class="px-6 py-3 font-medium td-busca"><?= htmlspecialchars($c['telefone'] ?: $c['whatsapp']) ?: '-' ?></td>
                                <td class="px-6 py-3 uppercase text-xs td-busca"><?= htmlspecialchars($c['cidade']) ?: '-' ?></td>
                                <td class="px-6 py-3 uppercase text-xs text-indigo-600 dark:text-indigo-400 font-semibold td-busca"><?= htmlspecialchars($c['arquiteto_nome']) ?: '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/relatorios.js?v=<?= time() ?>"></script>

<?php require_once 'includes/footer.php'; ?>