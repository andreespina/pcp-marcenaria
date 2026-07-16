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
    
    // 5. CÁLCULO DE LEAD TIME MÉDIO DE PRODUÇÃO (NOVO)
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
            unset($entradas_producao[$log['projeto_id']]); // Remove para não duplicar caso o card volte
        }
    }
    
    // Calcula a média arredondada
    $lead_time_medio = count($tempos_producao) > 0 ? round(array_sum($tempos_producao) / count($tempos_producao), 1) : 0;

    // ==========================================
    // CÁLCULOS E ESTATÍSTICAS
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

    // ALMOXARIFADO
    $almox_total = count($almoxarifado);
    $almox_critico = 0; $almox_ok = 0;
    foreach($almoxarifado as $al) {
        if ($al['quantidade'] <= $al['quantidade_minima']) { $almox_critico++; }
        else { $almox_ok++; }
    }

    // CLIENTES
    $cli_total = count($clientes);

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
$page_title = 'CENTRAL DE RELATÓRIOS';
$page_subtitle = 'Métricas e Análises da Produção';
$main_class = 'flex-1'; 
$menu_button_text = 'MENU';
$page_actions = '
<button onclick="imprimirRelatorio()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg> IMPRIMIR ABA ATUAL
</button>';

// Estilo exclusivo para esconder botões e menus durante a impressão e scrollbars
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

    <details class="group bg-purple-50 dark:bg-purple-900/10 border border-purple-200 dark:border-purple-800 rounded-lg shadow-sm transition-colors duration-300 no-print">
        <summary class="cursor-pointer p-4 font-bold text-sm text-purple-800 dark:text-purple-400 flex items-center justify-between select-none uppercase tracking-wide">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Guia Rápido: Relatórios Gerenciais
            </div>
            <svg class="w-5 h-5 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </summary>
        <div class="p-4 pt-0 mt-2 border-t border-purple-200 dark:border-purple-800">
            <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-2 ml-1 mt-3">
                <li class="flex items-start">
                    <span class="mr-2">📊</span>
                    <span><strong>Navegação:</strong> Navegue pelas abas abaixo para ver as métricas específicas de Projetos, Assistências, Almoxarifado e Clientes.</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">🔍</span>
                    <span><strong>Pesquisa:</strong> Cada aba (exceto a visão geral) possui uma barra de busca para filtrar rapidamente a tabela antes de analisar os dados.</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">🖨️</span>
                    <span><strong>Impressão:</strong> Clique no botão superior direito "IMPRIMIR ABA ATUAL". O sistema organizará os dados em formato limpo para PDF ou Papel, imprimindo apenas o que você filtrou.</span>
                </li>
            </ul>
        </div>
    </details>

    <div class="border-b border-gray-200 dark:border-[#2a3142] no-print">
        <ul class="flex flex-nowrap overflow-x-auto text-sm font-medium text-center" role="tablist">
            <li class="mr-2" role="presentation">
                <button id="btn_geral" onclick="mudarAba('geral')" class="tab-btn active px-4 py-3 border-b-2 text-blue-600 border-blue-600 dark:text-blue-400 dark:border-blue-400 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                    Resumo Geral
                </button>
            </li>
            <li class="mr-2" role="presentation">
                <button id="btn_projetos" onclick="mudarAba('projetos')" class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                    Projetos & Lead Time
                </button>
            </li>
            <li class="mr-2" role="presentation">
                <button id="btn_assistencias" onclick="mudarAba('assistencias')" class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                    Assistências & Faturamento
                </button>
            </li>
            <li class="mr-2" role="presentation">
                <button id="btn_almoxarifado" onclick="mudarAba('almoxarifado')" class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                    Estoque (Almoxarifado)
                </button>
            </li>
            <li role="presentation">
                <button id="btn_clientes" onclick="mudarAba('clientes')" class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                    Base de Clientes
                </button>
            </li>
        </ul>
    </div>

    <div id="conteudo_geral" class="tab-content">
        <div class="mb-4">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 flex items-center">
                <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Visão Geral da Operação
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Métricas consolidadas de todos os departamentos.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
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
                    <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Faturado (Assistências)</p>
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