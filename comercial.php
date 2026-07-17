<?php
// comercial.php
require_once 'includes/auth.php';
protegerPagina(); 
require_once 'config/conexao.php';

try {
    $stmt_cli = $pdo->query("SELECT id, codigo_cliente, nome_contrato FROM clientes_cadastro ORDER BY nome_contrato ASC");
    $clientes_db = $stmt_cli->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT cl.*, c.nome_contrato as nome_cadastrado, c.codigo_cliente 
                         FROM comercial_leads cl 
                         LEFT JOIN clientes_cadastro c ON cl.cliente_id = c.id 
                         WHERE cl.ativo = 1
                         ORDER BY cl.data_entrada DESC");
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // NOVA ORDEM DO FUNIL: Contato -> 3D -> Orçamento -> Apresentacao -> Fechado -> Pausado -> Perdido
    $funil = [
        'CONTATO'      => ['titulo' => 'Novo Contato', 'cor' => 'border-gray-500', 'bg' => 'bg-gray-100 dark:bg-gray-800/50', 'leads' => []],
        'PROJETO_3D'   => ['titulo' => 'Projeto 3D', 'cor' => 'border-indigo-500', 'bg' => 'bg-indigo-50 dark:bg-[#1c2333]/50', 'leads' => []],
        'ORCAMENTO'    => ['titulo' => 'Orçamento', 'cor' => 'border-amber-500', 'bg' => 'bg-amber-50 dark:bg-[#1c2333]/50', 'leads' => []],
        'APRESENTACAO' => ['titulo' => 'Reunião', 'cor' => 'border-blue-500', 'bg' => 'bg-blue-50 dark:bg-[#1c2333]/50', 'leads' => []],
        'FECHADO'      => ['titulo' => 'Venda Fechada', 'cor' => 'border-emerald-500', 'bg' => 'bg-emerald-50 dark:bg-[#15231d]/50', 'leads' => []],
        'PAUSADO'      => ['titulo' => 'Pausados', 'cor' => 'border-purple-500', 'bg' => 'bg-purple-50 dark:bg-[#2e1f3d]/40', 'leads' => []],
        'PERDIDO'      => ['titulo' => 'Perdidos', 'cor' => 'border-red-500', 'bg' => 'bg-red-50 dark:bg-red-900/20', 'leads' => []]
    ];

    $total_projetos = count($leads);
    $finalizados = 0; $cancelados = 0; $para_inicio = 0;
    $em_andamento = 0; $para_orcamento = 0; 

    $proximas_apresentacoes = []; $projetos_atraso = [];
    $eventos_calendario = []; 
    
    $hoje = date('Y-m-d'); $ano_atual = date('Y');

    foreach ($leads as $l) {
        $fase = $l['fase'];
        if (array_key_exists($fase, $funil)) {
            $funil[$fase]['leads'][] = $l;
            
            if ($fase === 'FECHADO') { $finalizados++;
            } elseif ($fase === 'PERDIDO') { $cancelados++;
            } elseif ($fase === 'CONTATO') { $para_inicio++;
            } elseif (in_array($fase, ['APRESENTACAO', 'PROJETO_3D'])) { $em_andamento++;
            } elseif ($fase === 'ORCAMENTO') { $para_orcamento++; }

            if(!empty($l['data_apresentacao']) && $fase != 'FECHADO' && $fase != 'PERDIDO' && $fase != 'PAUSADO' && empty($l['apresentacao_realizada'])) {
                if ($l['data_apresentacao'] >= $hoje) $proximas_apresentacoes[] = $l;
                else $projetos_atraso[] = $l;
                
                $nome_cli = !empty($l['nome_cadastrado']) ? $l['nome_cadastrado'] : $l['cliente_nome'];
                $eventos_calendario[] = [
                    'id' => $l['id'],
                    'title' => $nome_cli . ' (' . $funil[$fase]['titulo'] . ')',
                    'start' => $l['data_apresentacao'],
                    'color' => ($l['data_apresentacao'] < $hoje) ? '#ef4444' : '#3b82f6',
                    'extendedProps' => ['obs' => $l['observacao']]
                ];
            }

            // INÍCIO: Adicionar múltiplas reuniões (Reprojetos) no Calendário
            if (!empty($l['historico_reprojetos'])) {
                $historico_apres = json_decode($l['historico_reprojetos'], true);
                if (is_array($historico_apres)) {
                    foreach ($historico_apres as $h) {
                        $nome_cli = !empty($l['nome_cadastrado']) ? $l['nome_cadastrado'] : $l['cliente_nome'];
                        $eventos_calendario[] = [
                            'id' => $l['id'] . '_rev_' . $h['revisao'],
                            'title' => $nome_cli . ' (REV ' . str_pad($h['revisao'], 2, '0', STR_PAD_LEFT) . ')',
                            'start' => $h['data'],
                            'color' => '#f59e0b', // Laranja
                            'extendedProps' => ['obs' => 'Motivo do Reprojeto: ' . $h['motivo']]
                        ];
                    }
                }
            }
        }
    }
    
    usort($proximas_apresentacoes, function($a, $b) { return strtotime($a['data_apresentacao']) - strtotime($b['data_apresentacao']); });
    usort($projetos_atraso, function($a, $b) { return strtotime($a['data_apresentacao']) - strtotime($b['data_apresentacao']); });
    
    // --- LÓGICA DE DADOS PARA OS GRÁFICOS DO COMERCIAL ---
    $chart_funil_labels = ['Contato', 'Projeto 3D', 'Orçamento', 'Reunião', 'Pausados'];
    $chart_funil_data = [
        count($funil['CONTATO']['leads']),
        count($funil['PROJETO_3D']['leads']),
        count($funil['ORCAMENTO']['leads']),
        count($funil['APRESENTACAO']['leads']),
        count($funil['PAUSADO']['leads'])
    ];

    $meses_nomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    $chart_bar_labels = [];
    $chart_bar_novos = [];
    $chart_bar_fechados = [];

    for ($i = 5; $i >= 0; $i--) {
        $data_alvo = strtotime("-$i months");
        $mes_num = date('m', $data_alvo);
        $ano_num = date('Y', $data_alvo);
        $chart_bar_labels[] = $meses_nomes[(int)$mes_num - 1] . '/' . substr($ano_num, 2);
        
        $stmt_novos = $pdo->prepare("SELECT COUNT(*) FROM comercial_leads WHERE MONTH(data_entrada) = ? AND YEAR(data_entrada) = ? AND ativo = 1");
        $stmt_novos->execute([$mes_num, $ano_num]);
        $chart_bar_novos[] = $stmt_novos->fetchColumn();

        $stmt_fechados = $pdo->prepare("SELECT COUNT(*) FROM comercial_leads WHERE fase = 'FECHADO' AND MONTH(COALESCE(data_fechamento, data_entrada)) = ? AND YEAR(COALESCE(data_fechamento, data_entrada)) = ? AND ativo = 1");
        $stmt_fechados->execute([$mes_num, $ano_num]);
        $chart_bar_fechados[] = $stmt_fechados->fetchColumn();
    }

} catch (\PDOException $e) { die("Erro: " . $e->getMessage()); }

function corMemorial($status) {
    if ($status === 'FEITO') return 'text-green-600 dark:text-green-500';
    if ($status === 'PROJETANDO') return 'text-yellow-600 dark:text-yellow-500';
    return 'text-red-500 dark:text-red-400';
}

$page_title = 'COMERCIAL & CRM';
$page_subtitle = 'SBG Móveis & Design';
$main_class = 'flex-1'; 
$menu_button_text = 'MENU';

$filtro_colunas_html = '';
foreach ($funil as $fase_chave => $col) {
    $filtro_colunas_html .= '
    <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-1 rounded transition-colors">
        <input type="checkbox" checked value="'.$fase_chave.'" onchange="aplicarFiltroColunas()" class="col-filter-cb rounded text-blue-600 border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-blue-500">
        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase select-none">'.$col['titulo'].'</span>
    </label>';
}

$page_actions = '
<div class="flex space-x-2 relative">
    <button onclick="document.getElementById(\'menuFiltros\').classList.toggle(\'hidden\')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center relative z-20">
        <svg class="w-4 h-4 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
        <span class="hidden md:inline">FILTROS</span>
    </button>
    <div id="menuFiltros" class="hidden absolute top-full right-0 lg:left-0 mt-2 w-56 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-2xl z-[60] p-3">
        <h4 class="text-[10px] font-black text-gray-400 dark:text-gray-500 mb-2 uppercase border-b border-gray-100 dark:border-gray-700 pb-1">Exibir Colunas:</h4>
        <div class="space-y-1">
            ' . $filtro_colunas_html . '
        </div>
    </div>
    <button onclick="toggleViewMode()" id="btnToggleView" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 md:px-4 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
        <svg class="w-4 h-4 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        <span class="hidden md:inline">CALENDÁRIO</span>
    </button>
    <button onclick="abrirModalLead()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 md:px-4 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
        <svg class="w-4 h-4 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        <span class="hidden md:inline">NOVO LEAD</span>
    </button>
</div>';

$head_extras = '
<style>
    .app-container { display: flex; flex-direction: column; gap: 1.5rem; width: 100%; }
    .kanban-wrapper { display: flex; flex-direction: column; gap: 1.5rem; width: 100%; }
    
    .kanban-column-container { width: 100%; display: flex; flex-direction: column; min-height: 500px; }
    .kanban-column-container .kanban-col { min-height: 500px; overflow-y: auto; padding-bottom: 2rem; }

    @media (min-width: 1280px) {
        .dark body { background-color: #1a1e2b !important; }
        .app-container { min-height: calc(100vh - 120px); height: auto; }
        .kanban-wrapper { flex-direction: row; overflow-x: auto; flex: 1 1 0%; min-height: 0; align-items: flex-start; padding-bottom: 1.5rem; }
        
        .kanban-column-container { min-width: 270px; max-width: 320px; flex: 1 1 0%; height: 100%; min-height: 500px; display: flex; flex-direction: column; }
        .kanban-column-container .kanban-col { max-height: none; flex: 1 1 auto; min-height: 500px; overflow-y: auto; padding-bottom: 6rem; }
    }
    
    .kanban-col::-webkit-scrollbar { width: 6px; }
    .kanban-col::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
    .dark .kanban-col::-webkit-scrollbar-thumb { background-color: #3f4865; }
    .kanban-col::-webkit-scrollbar-track { background: transparent; }
    
    .sortable-ghost { opacity: 0.3; background-color: #f1f5f9; border: 2px dashed #94a3b8; }
    .dark .sortable-ghost { background-color: #2a3142; border-color: #4b5563; }
    
    .dark .fc-theme-standard td, .dark .fc-theme-standard th, .dark .fc-theme-standard .fc-scrollgrid { border-color: #374151; }
    .dark .fc-view-harness { background-color: #1f2937; color: #fff; }
    .dark .fc-col-header-cell-cushion, .dark .fc-daygrid-day-number { color: #d1d5db; text-decoration: none; }
    .dark .fc-toolbar-title { color: #e5e7eb; }
    .fc-event { cursor: pointer; }
</style>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
';

require_once 'includes/header.php';
?>

<div class="app-container gap-6">
    <div class="flex flex-col xl:flex-row gap-6 shrink-0">
        
        <div class="flex-1 bg-white dark:bg-[#222736] rounded-lg border border-gray-200 dark:border-[#2a3142] shadow-sm p-4 transition-colors duration-300 flex flex-col">
            <h2 class="text-blue-700 dark:text-blue-400 font-bold mb-4 flex items-center text-lg tracking-wide">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Indicadores de Performance
            </h2>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-5">
                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-2.5 shadow-sm text-center">
                    <p class="text-[9px] font-bold text-gray-500 dark:text-gray-400 uppercase mb-0.5 tracking-wider">Total de Projetos</p>
                    <p class="text-xl font-black text-gray-800 dark:text-gray-100"><?= $total_projetos ?></p>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800/50 rounded-lg p-2.5 shadow-sm text-center">
                    <p class="text-[9px] font-bold text-emerald-600 dark:text-emerald-500 uppercase mb-0.5 tracking-wider">Projetos Fechados</p>
                    <p class="text-xl font-black text-emerald-700 dark:text-emerald-400"><?= $finalizados ?></p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800/50 rounded-lg p-2.5 shadow-sm text-center">
                    <p class="text-[9px] font-bold text-red-600 dark:text-red-500 uppercase mb-0.5 tracking-wider">Projetos Cancelados</p>
                    <p class="text-xl font-black text-red-700 dark:text-red-400"><?= $cancelados ?></p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg p-2.5 shadow-sm text-center">
                    <p class="text-[9px] font-bold text-slate-600 dark:text-slate-400 uppercase mb-0.5 tracking-wider">Projetos Para Início</p>
                    <p class="text-xl font-black text-slate-700 dark:text-slate-300"><?= $para_inicio ?></p>
                </div>
                <div class="bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-200 dark:border-indigo-800/50 rounded-lg p-2.5 shadow-sm text-center">
                    <p class="text-[9px] font-bold text-indigo-600 dark:text-indigo-400 uppercase mb-0.5 tracking-wider">Em Andamento</p>
                    <p class="text-xl font-black text-indigo-700 dark:text-indigo-400"><?= $em_andamento ?></p>
                </div>
                <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/50 rounded-lg p-2.5 shadow-sm text-center">
                    <p class="text-[9px] font-bold text-amber-600 dark:text-amber-500 uppercase mb-0.5 tracking-wider">Orçando</p>
                    <p class="text-xl font-black text-amber-700 dark:text-amber-400"><?= $para_orcamento ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 flex-1">
                <div class="bg-gray-50 dark:bg-gray-900/30 p-3 rounded-xl border border-gray-200 dark:border-gray-700 flex flex-col justify-center items-center shadow-inner h-52">
                    <h3 class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest w-full text-center mb-2">Funil de Vendas (Ativos)</h3>
                    <div class="relative w-full h-40 flex justify-center">
                        <canvas id="chartFunil"></canvas>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900/30 p-3 rounded-xl border border-gray-200 dark:border-gray-700 flex flex-col justify-center items-center shadow-inner h-52">
                    <h3 class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest w-full text-center mb-2">Novos Leads vs Vendas (6 Meses)</h3>
                    <div class="relative w-full h-40 flex justify-center">
                        <canvas id="chartEvolucao"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full xl:w-96 flex flex-col gap-4">
            <div class="bg-white dark:bg-[#222736] border border-gray-200 dark:border-[#2a3142] rounded-lg shadow-sm p-4 flex-1 transition-colors duration-300">
                <span class="bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white text-xs font-bold px-3 py-1 inline-block mb-3 rounded-sm shadow-sm border border-gray-200 dark:border-gray-600">Próximas Apresentações / Reuniões</span>
                <div class="space-y-3 overflow-y-auto max-h-[220px] pr-2 kanban-col">
                    <?php if(empty($proximas_apresentacoes)): ?>
                        <p class="text-xs text-gray-500 italic">Nenhuma apresentação agendada.</p>
                    <?php endif; ?>
                    <?php foreach($proximas_apresentacoes as $ap): ?>
                        <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-2">
                            <div>
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase"><?= htmlspecialchars($ap['nome_cadastrado'] ?: $ap['cliente_nome']) ?></p>
                                <p class="text-[10px] text-gray-500 uppercase mt-0.5"><?= $funil[$ap['fase']]['titulo'] ?></p>
                            </div>
                            <span class="text-xs text-blue-600 dark:text-blue-400 font-bold"><?= date('d/m/Y', strtotime($ap['data_apresentacao'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bg-white dark:bg-[#222736] border border-red-200 dark:border-red-900/50 rounded-lg shadow-sm p-4 flex-1 transition-colors duration-300">
                <span class="bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold px-3 py-1 inline-block mb-3 rounded-sm shadow-sm border border-red-200 dark:border-red-800">Projetos em Atraso</span>
                <div class="space-y-3 overflow-y-auto max-h-[140px] pr-2 kanban-col">
                    <?php if(empty($projetos_atraso)): ?>
                        <p class="text-xs text-gray-500 italic">Nenhum projeto em atraso no momento. Ufa!.</p>
                    <?php endif; ?>
                    <?php foreach($projetos_atraso as $pa): ?>
                        <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-2">
                            <div>
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase"><?= htmlspecialchars($pa['nome_cadastrado'] ?: $pa['cliente_nome']) ?></p>
                                <p class="text-[10px] text-gray-500 uppercase mt-0.5"><?= $funil[$pa['fase']]['titulo'] ?></p>
                            </div>
                            <span class="text-[10px] text-white bg-red-600 dark:bg-red-500 px-1.5 py-0.5 rounded font-bold">Atrasado</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="view-kanban" class="kanban-wrapper">
        <?php foreach ($funil as $fase_chave => $col): ?>
            <div id="coluna-container-<?= $fase_chave ?>" class="kanban-column-container bg-white dark:bg-[#222736] border border-gray-200 dark:border-[#2a3142] rounded shadow-sm transition-colors duration-300">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 <?= $col['cor'] ?> border-t-4 flex justify-between items-center bg-gray-50 dark:bg-gray-800 rounded-t transition-colors duration-300">
                    <h2 class="text-sm font-bold text-gray-800 dark:text-gray-100 truncate pr-2" title="<?= $col['titulo'] ?>"><?= $col['titulo'] ?></h2>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400 font-bold bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded-full"><?= count($col['leads']) ?></span>
                </div>
                
                <div id="fase-<?= $fase_chave ?>" data-fase="<?= $fase_chave ?>" class="kanban-col p-3 space-y-3 <?= $col['bg'] ?> transition-colors duration-300">
                    <?php foreach ($col['leads'] as $l): 
                        $is_atrasado = (!empty($l['data_apresentacao']) && $l['data_apresentacao'] < $hoje && !in_array($fase_chave, ['FECHADO', 'PERDIDO', 'PAUSADO']) && empty($l['apresentacao_realizada']));
                        
                        if ($is_atrasado) {
                            $card_border = 'border-red-400 dark:border-red-500 shadow-[0_0_8px_rgba(239,68,68,0.3)]';
                        } elseif (!empty($l['revisao']) && $l['revisao'] > 0) {
                            $card_border = 'border-orange-400 dark:border-orange-500 border-dashed border-2 shadow-sm';
                        } else {
                            $card_border = 'border-gray-200 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-400 shadow-sm';
                        }

                        $sla_tag = '';
                        if (!empty($l['data_inicio_projeto']) && !empty($l['prazo_projeto_dias']) && !in_array($fase_chave, ['FECHADO', 'PERDIDO', 'PAUSADO'])) {
                            $data_limite = date('Y-m-d', strtotime($l['data_inicio_projeto'] . ' + ' . $l['prazo_projeto_dias'] . ' days'));
                            
                            if (!empty($l['data_entrega_projeto'])) {
                                if ($l['data_entrega_projeto'] > $data_limite) {
                                    $sla_tag = '<span class="text-[9px] bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300 px-1.5 py-0.5 rounded font-bold border border-orange-200 dark:border-orange-800 ml-2">ENTREGUE ATRASADO</span>';
                                } else {
                                    $sla_tag = '<span class="text-[9px] bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 px-1.5 py-0.5 rounded font-bold border border-blue-200 dark:border-blue-800 ml-2">ENTREGUE NO PRAZO</span>';
                                }
                            } else {
                                if ($hoje > $data_limite) {
                                    $sla_tag = '<span class="text-[9px] bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 px-1.5 py-0.5 rounded font-bold border border-red-200 dark:border-red-800 ml-2 animate-pulse">ATRASADO</span>';
                                } else {
                                    $sla_tag = '<span class="text-[9px] bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 px-1.5 py-0.5 rounded font-bold border border-green-200 dark:border-green-800 ml-2">NO PRAZO</span>';
                                }
                            }
                        }
                        
                        $nome_formatado = htmlspecialchars(!empty($l['nome_cadastrado']) ? $l['nome_cadastrado'] : $l['cliente_nome'], ENT_QUOTES, 'UTF-8');
                        $obs_formatada = htmlspecialchars(!empty($l['observacao']) ? $l['observacao'] : '', ENT_QUOTES, 'UTF-8');
                    ?>
                        <div class="bg-white dark:bg-gray-800 border <?= $card_border ?> rounded p-3 cursor-grab transition-colors duration-200" data-id="<?= $l['id'] ?>">
                            
                            <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-2 mb-2">
                                <span class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">Entrada: <?= date('d/m/y', strtotime($l['data_entrada'])) ?></span>
                                <div class="flex items-center">
                                    <span class="text-[9px] text-gray-800 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 px-1 font-bold rounded uppercase mr-2"><?= $l['origem'] ?></span>
                                    
                                    <a href="#" onclick="abrirGoogleCalendar('<?= $nome_formatado ?>', '<?= !empty($l['data_apresentacao']) ? $l['data_apresentacao'] : '' ?>', '<?= $obs_formatada ?>'); return false;" class="text-gray-400 hover:text-green-600 dark:hover:text-green-400 mr-2" title="Salvar no Google Agenda">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </a>

                                    <button onclick="abrirEdicaoPorId(<?= $l['id'] ?>)" class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400" title="Editar">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>
                                    
                                    <?php if(!in_array($fase_chave, ['FECHADO', 'PERDIDO', 'PAUSADO'])): ?>
                                    <button onclick='abrirModalReprojeto(<?= $l['id'] ?>)' class="text-gray-400 hover:text-orange-500 dark:hover:text-orange-400 ml-1.5" title="Solicitar Reprojeto">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    </button>
                                    <?php endif; ?>

                                    <!-- Botão Lixeira agora move para Perdidos -->
                                    <button onclick="abrirModalMotivo(<?= $l['id'] ?>, 'PERDIDO')" class="text-gray-400 hover:text-red-500 dark:hover:text-red-400 ml-1.5" title="Mover para Perdidos">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>

                                    <!-- NOVO: Botão Exclusão Permanente (Some se estiver Fechado) -->
                                    <?php if($fase_chave !== 'FECHADO'): ?>
                                    <button onclick="excluirLeadPermanente(<?= $l['id'] ?>)" class="text-gray-400 hover:text-red-800 dark:hover:text-red-600 ml-1.5" title="Excluir Permanentemente">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <h4 class="font-bold text-xs text-blue-700 dark:text-blue-400 uppercase leading-snug mb-1 flex items-center">
                                <?= $nome_formatado ?>
                                <?= $sla_tag ?>
                                <?php if(!empty($l['revisao']) && $l['revisao'] > 0): ?>
                                    <span class="text-[9px] bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300 px-1.5 py-0.5 rounded font-bold border border-orange-200 dark:border-orange-800 uppercase ml-2">REV. <?= str_pad($l['revisao'], 2, '0', STR_PAD_LEFT) ?></span>
                                <?php endif; ?>
                            </h4>
                            
                            <?php if(!empty($l['codigo_cliente'])): ?>
                                <div class="mb-2">
                                    <span class="text-[9px] bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 px-1.5 py-0.5 rounded font-bold border border-blue-200 dark:border-blue-800 uppercase">CÓD: <?= htmlspecialchars($l['codigo_cliente']) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if(in_array($fase_chave, ['PAUSADO', 'PERDIDO']) && !empty($l['motivo_status'])): ?>
                                <div class="mt-2 mb-2 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 p-1.5 rounded">
                                    <p class="text-[9px] font-bold text-red-700 dark:text-red-400 uppercase leading-tight">Motivo: <?= htmlspecialchars($l['motivo_status']) ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if(!empty($l['projetista_responsavel'])): ?>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">Proj: <span class="text-gray-800 dark:text-gray-200 font-semibold uppercase"><?= htmlspecialchars($l['projetista_responsavel']) ?></span></p>
                            <?php endif; ?>
                            <?php if(!empty($l['ambientes'])): ?>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5 mb-2">Ambs: <span class="text-gray-800 dark:text-gray-200 font-semibold uppercase"><?= htmlspecialchars($l['ambientes']) ?></span></p>
                            <?php endif; ?>
                            
                            <div class="text-[10px] text-gray-600 dark:text-gray-400 space-y-1.5 pt-2 border-t border-gray-100 dark:border-gray-700 mt-2">
                                <div class="flex justify-between"><span>Probabilidade:</span> <span class="font-bold <?= $l['probabilidade']>70?'text-green-600 dark:text-green-500':'text-orange-500' ?>"><?= $l['probabilidade'] ?>%</span></div>
                                <div class="flex justify-between"><span>Memorial:</span> <span class="font-bold <?= corMemorial($l['memorial_descritivo']) ?>"><?= $l['memorial_descritivo'] ?></span></div>
                                <div class="flex justify-between"><span>Vlr. Estimado:</span> <span class="font-bold text-emerald-600 dark:text-emerald-400">R$ <?= number_format($l['valor_estimado'], 2, ',', '.') ?></span></div>
                                <div class="flex justify-between items-center">
                                    <span>Apresentação:</span> 
                                    <?php if(!empty($l['apresentacao_realizada'])): ?>
                                        <span class="font-bold text-green-600 dark:text-green-400 line-through decoration-green-600 dark:decoration-green-400"><?= !empty($l['data_apresentacao']) ? date('d/m/Y', strtotime($l['data_apresentacao'])) : '---' ?> &#10003;</span>
                                    <?php else: ?>
                                        <span class="font-bold <?= $is_atrasado ? 'text-red-500' : 'text-blue-600 dark:text-blue-400' ?>"><?= !empty($l['data_apresentacao']) ? date('d/m/Y', strtotime($l['data_apresentacao'])) : 'NÃO AGENDADO' ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="view-calendario" class="hidden flex-1 bg-white dark:bg-[#222736] border border-gray-200 dark:border-[#2a3142] rounded-lg shadow-sm p-4 w-full h-[600px] xl:h-full transition-colors duration-300">
        <div id="calendario_sbg" class="w-full h-full"></div>
    </div>
</div>

<div id="modalLead" class="fixed inset-0 bg-black bg-opacity-60 dark:bg-opacity-70 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300 max-h-[90vh] overflow-y-auto" id="modalLeadConteudo">
        <div class="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
            <h3 id="modalTitulo" class="text-lg font-bold text-blue-700 dark:text-blue-400">Detalhes do Lead</h3>
            <button type="button" onclick="fecharModalLead()" class="text-gray-400 hover:text-gray-800 dark:hover:text-white text-2xl font-bold">&times;</button>
        </div>
        <form id="formLead" onsubmit="salvarLead(event)">
            <input type="hidden" id="lead_id" name="id">
            
            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded mb-4 border border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Vincular Cliente</label>
                <select id="lead_cliente_id" name="cliente_id" onchange="toggleNovoCliente()" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded font-bold uppercase focus:ring-2 focus:ring-blue-500 mb-2">
                    <option value="NOVO" class="text-green-600 dark:text-green-400">++ CADASTRAR NOVO CLIENTE ++</option>
                    <?php foreach($clientes_db as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= !empty($c['codigo_cliente']) ? htmlspecialchars($c['codigo_cliente']) . ' - ' : '' ?><?= htmlspecialchars($c['nome_contrato']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div id="div_novo_cliente" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                    <div>
                        <label class="block text-xs font-semibold text-green-600 dark:text-green-400 mb-1">Nome do Novo Cliente</label>
                        <input type="text" id="lead_nome" name="cliente_nome" placeholder="Digite o nome..." class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-green-600 dark:text-green-400 mb-1">Telefone / WhatsApp</label>
                        <input type="text" id="lead_telefone" name="telefone" placeholder="(11) 99999-9999" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded mb-4 border border-blue-200 dark:border-blue-800">
                <h4 class="text-xs font-bold text-blue-800 dark:text-blue-300 uppercase mb-3 flex items-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    SLA e Prazos do Projeto
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Início do Projeto</label>
                        <input type="date" id="lead_inicio_projeto" name="data_inicio_projeto" class="w-full px-2 py-1.5 bg-white dark:bg-gray-700 border border-blue-300 dark:border-blue-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Prazo (Dias)</label>
                        <input type="number" id="lead_prazo_dias" name="prazo_projeto_dias" placeholder="Ex: 5" min="0" class="w-full px-2 py-1.5 bg-white dark:bg-gray-700 border border-blue-300 dark:border-blue-600 text-gray-800 dark:text-white rounded font-bold text-blue-600 dark:text-blue-400 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-green-700 dark:text-green-400 mb-1">Data de Entrega</label>
                        <input type="date" id="lead_entrega_projeto" name="data_entrega_projeto" class="w-full px-2 py-1.5 bg-white dark:bg-gray-700 border border-green-300 dark:border-green-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="bg-gray-50 dark:bg-gray-900 p-2 border border-gray-200 dark:border-gray-700 rounded flex flex-col justify-center">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Data da Apresentação / Reunião</label>
                    <div class="flex items-center space-x-2">
                        <input type="date" id="lead_apresentacao" name="data_apresentacao" class="w-full px-2 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded">
                        <label class="flex flex-col items-center cursor-pointer ml-2">
                            <input type="checkbox" id="lead_apres_realizada" class="w-5 h-5 text-green-600 rounded cursor-pointer">
                            <span class="text-[9px] font-bold text-green-600 dark:text-green-400 mt-0.5 whitespace-nowrap">REALIZADA?</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Origem do Lead</label>
                    <select id="lead_origem" name="origem" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase focus:ring-2 focus:ring-blue-500">
                        <option value="INSTAGRAM">Instagram</option><option value="INDICACAO">Indicação</option><option value="ARQUITETO">Arquiteto(a)</option><option value="SHOWROOM">Visita Loja</option><option value="OUTROS">Outros</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Projetista Responsável</label>
                    <input type="text" id="lead_projetista" name="projetista_responsavel" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Ambientes</label>
                    <input type="text" id="lead_ambientes" name="ambientes" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Memorial Descritivo</label>
                    <select id="lead_memorial" name="memorial_descritivo" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase">
                        <option value="PRA FAZER">Pra Fazer</option><option value="PROJETANDO">Projetando</option><option value="FEITO">Feito</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Valor Estimado (R$)</label>
                    <input type="number" step="0.01" id="lead_valor" name="valor_estimado" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-green-600 dark:text-green-400 font-bold rounded">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Probabilidade (%)</label>
                    <input type="number" id="lead_prob" name="probabilidade" value="50" min="0" max="100" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-blue-600 dark:text-blue-400 font-bold rounded">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Observações</label>
                    <textarea id="lead_obs" name="observacao" rows="2" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded"></textarea>
                </div>

                <!-- Container de Históricos (Reuniões e Reprojetos) -->
                <div class="md:col-span-2 hidden mt-4 pt-4 border-t border-gray-100 dark:border-gray-700" id="grid_historicos_gerais">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        <!-- Histórico de Reuniões -->
                        <div id="div_historico_reunioes" class="hidden">
                            <label class="block text-sm font-semibold text-green-600 dark:text-green-400 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Reuniões Realizadas (<span id="count_reunioes">0</span>)
                            </label>
                            <div id="lista_historico_reunioes" class="max-h-40 overflow-y-auto bg-green-50 dark:bg-green-900/10 p-3 border border-green-200 dark:border-green-800/50 rounded-lg text-xs space-y-1 scrollbar-thin">
                                <!-- Povoado pelo JS -->
                            </div>
                        </div>

                        <!-- Histórico de Reprojetos -->
                        <div id="div_historico_reprojetos" class="hidden">
                            <label class="block text-sm font-semibold text-orange-600 dark:text-orange-400 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Histórico de Reprojetos (<span id="count_reprojetos">0</span>)
                            </label>
                            <div id="lista_historico_reprojetos" class="max-h-40 overflow-y-auto bg-orange-50 dark:bg-orange-900/10 p-3 border border-orange-200 dark:border-orange-800/50 rounded-lg scrollbar-thin">
                                <!-- Povoado pelo JS -->
                            </div>
                        </div>

                    </div>
                </div>

            </div>
            <div class="flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                <button type="button" onclick="fecharModalLead()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-bold transition shadow-sm">Salvar Informações</button>
            </div>
        </form>
    </div>
</div>

<div id="modalMotivo" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-[60] hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalMotivoConteudo">
        <h3 id="modalMotivoTitulo" class="text-lg font-bold text-red-600 dark:text-red-400 mb-4 uppercase">Informar Motivo</h3>
        <form id="formMotivo" onsubmit="confirmarMotivo(event)">
            <input type="hidden" id="motivo_lead_id">
            <input type="hidden" id="motivo_nova_fase">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Selecione o Motivo Principal</label>
                <select id="motivo_selecao" required class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase font-bold focus:ring-2 focus:ring-red-500">
                    <option value="">Selecione a razão...</option>
                    <option value="PREÇO / ORÇAMENTO ALTO">Preço / Orçamento Alto</option>
                    <option value="FECHOU COM CONCORRENTE">Fechou com Concorrente</option>
                    <option value="CLIENTE SUMIU / PAROU DE RESPONDER">Cliente Sumiu / Parou de Responder</option>
                    <option value="PROJETO ADIADO (FINANCEIRO)">Projeto Adiado (Problemas Financeiros)</option>
                    <option value="PROJETO ADIADO (OBRA ATRASOU)">Projeto Adiado (Obra Atrasou)</option>
                    <option value="INVIABILIDADE TÉCNICA">Inviabilidade Técnica do Projeto</option>
                    <option value="OUTROS">Outros Motivos</option>
                </select>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Detalhes Adicionais</label>
                <textarea id="motivo_detalhes" rows="3" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded"></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="cancelarMotivo()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition font-medium">Cancelar Mudança</button>
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded font-bold transition shadow-sm">Confirmar Status</button>
            </div>
        </form>
    </div>
</div>

<div id="modalReprojeto" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-[70] hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalReprojetoConteudo">
        <h3 class="text-lg font-bold text-orange-600 dark:text-orange-400 mb-4 uppercase flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Solicitar Reprojeto
        </h3>
        <p class="text-xs text-gray-600 dark:text-gray-400 mb-4">O projeto retornará para a fase de Projeto 3D.</p>
        <form id="formReprojeto" onsubmit="salvarReprojeto(event)">
            <input type="hidden" id="reprojeto_lead_id">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nova Data de Apresentação</label>
                <input type="date" id="reprojeto_data" required class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-orange-500">
            </div>
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Motivo / Alterações Solicitadas</label>
                <textarea id="reprojeto_motivo" required rows="3" placeholder="Descreva o que o cliente pediu para alterar..." class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-orange-500"></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="fecharModalReprojeto()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded font-bold transition shadow-sm">Confirmar Retorno</button>
            </div>
        </form>
    </div>
</div>

<script>
    const crmLeadsDados = <?= json_encode($leads, JSON_UNESCAPED_UNICODE) ?>;
    const eventosCalendario = <?= json_encode($eventos_calendario, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="assets/js/comercial.js?v=<?= time() ?>"></script>

<script>
    const corTexto = document.documentElement.classList.contains('dark') ? '#9ca3af' : '#475569';
    const corGrade = document.documentElement.classList.contains('dark') ? '#374151' : '#e2e8f0';

    const ctxFunil = document.getElementById('chartFunil');
    if (ctxFunil) {
        new Chart(ctxFunil.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($chart_funil_labels) ?>,
                datasets: [{
                    data: <?= json_encode($chart_funil_data) ?>,
                    backgroundColor: ['#9ca3af', '#6366f1', '#f59e0b', '#3b82f6', '#a855f7'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'right', labels: { color: corTexto, font: { size: 9, weight: 'bold' }, boxWidth: 10 } }
                }
            }
        });
    }

    const ctxEvo = document.getElementById('chartEvolucao');
    if (ctxEvo) {
        new Chart(ctxEvo.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_bar_labels) ?>,
                datasets: [
                    { label: 'Novos Leads', data: <?= json_encode($chart_bar_novos) ?>, backgroundColor: '#3b82f6', borderRadius: 3 },
                    { label: 'Vendas Fechadas', data: <?= json_encode($chart_bar_fechados) ?>, backgroundColor: '#10b981', borderRadius: 3 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { color: corTexto, font: { size: 9, weight: 'bold' }, boxWidth: 10 } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, color: corTexto, font: { size: 9 } }, grid: { color: corGrade } },
                    x: { ticks: { color: corTexto, font: { size: 9 } }, grid: { display: false } }
                }
            }
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?>