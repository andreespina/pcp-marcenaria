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

    $funil = [
        'CONTATO'    => ['titulo' => 'Novo Contato', 'cor' => 'border-gray-500', 'bg' => 'bg-gray-100 dark:bg-gray-800/50', 'leads' => []],
        'BRIEFING'   => ['titulo' => 'Reunião / Briefing', 'cor' => 'border-blue-500', 'bg' => 'bg-blue-50 dark:bg-[#1c2333]/50', 'leads' => []],
        'PROJETO_3D' => ['titulo' => 'Desenvolvimento 3D', 'cor' => 'border-indigo-500', 'bg' => 'bg-indigo-50 dark:bg-[#1c2333]/50', 'leads' => []],
        'ORCAMENTO'  => ['titulo' => 'Em Orçamento', 'cor' => 'border-amber-500', 'bg' => 'bg-amber-50 dark:bg-[#1c2333]/50', 'leads' => []],
        'PAUSADO'    => ['titulo' => 'Pausados', 'cor' => 'border-purple-500', 'bg' => 'bg-purple-50 dark:bg-[#2e1f3d]/40', 'leads' => []],
        'FECHADO'    => ['titulo' => 'Venda Fechada', 'cor' => 'border-emerald-500', 'bg' => 'bg-emerald-50 dark:bg-[#15231d]/50', 'leads' => []],
        'PERDIDO'    => ['titulo' => 'Perdido', 'cor' => 'border-red-500', 'bg' => 'bg-red-50 dark:bg-red-900/20', 'leads' => []]
    ];

    $total_projetos = count($leads);
    $fechados_ano = 0; $cancelados = 0; $para_inicio = 0;
    $em_andamento = 0; $finalizados = 0; $para_orcamento = 0; $projetos_memorial = 0; 

    $proximas_apresentacoes = []; $projetos_atraso = [];
    $hoje = date('Y-m-d'); $ano_atual = date('Y');

    foreach ($leads as $l) {
        $fase = $l['fase'];
        if (array_key_exists($fase, $funil)) {
            $funil[$fase]['leads'][] = $l;
            
            if ($fase === 'FECHADO') {
                $finalizados++;
                if (!empty($l['data_fechamento']) && date('Y', strtotime($l['data_fechamento'])) == $ano_atual) $fechados_ano++;
            } elseif ($fase === 'PERDIDO') { $cancelados++;
            } elseif ($fase === 'CONTATO') { $para_inicio++;
            } elseif (in_array($fase, ['BRIEFING', 'PROJETO_3D'])) { $em_andamento++;
            } elseif ($fase === 'ORCAMENTO') { $para_orcamento++; }

            if ($fase !== 'PERDIDO' && $fase !== 'PAUSADO' && in_array($l['memorial_descritivo'], ['PRA FAZER', 'PROJETANDO'])) {
                $projetos_memorial++;
            }

            if(!empty($l['data_apresentacao']) && $fase != 'FECHADO' && $fase != 'PERDIDO' && $fase != 'PAUSADO') {
                if ($l['data_apresentacao'] >= $hoje) $proximas_apresentacoes[] = $l;
                else $projetos_atraso[] = $l;
            }
        }
    }
    
    usort($proximas_apresentacoes, function($a, $b) { return strtotime($a['data_apresentacao']) - strtotime($b['data_apresentacao']); });
    usort($projetos_atraso, function($a, $b) { return strtotime($a['data_apresentacao']) - strtotime($b['data_apresentacao']); });
    
} catch (\PDOException $e) { die("Erro: " . $e->getMessage()); }

function jsSafe($val) { return htmlspecialchars(json_encode($val), ENT_QUOTES, 'UTF-8'); }
function corMemorial($status) {
    if ($status === 'FEITO') return 'text-green-600 dark:text-green-500';
    if ($status === 'PROJETANDO') return 'text-yellow-600 dark:text-yellow-500';
    return 'text-red-500 dark:text-red-400';
}

$page_title = 'COMERCIAL & CRM';
$page_subtitle = 'SBG Móveis & Design';
$main_class = 'flex-1'; 
$menu_button_text = 'MENU';
$page_actions = '
<button onclick="abrirModalLead()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
    NOVO LEAD
</button>';

$head_extras = '
<style>
    .dark body { background-color: #1a1e2b !important; }
    .kanban-col::-webkit-scrollbar { width: 6px; }
    .kanban-col::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
    .dark .kanban-col::-webkit-scrollbar-thumb { background-color: #3f4865; }
    .kanban-col::-webkit-scrollbar-track { background: transparent; }
    .sortable-ghost { opacity: 0.3; background-color: #f1f5f9; border: 2px dashed #94a3b8; }
    .dark .sortable-ghost { background-color: #2a3142; border-color: #4b5563; }
    .app-container { height: calc(100vh - 120px); display: flex; flex-direction: column; }
    .box-total { border-color: #4b5563; } .box-fechados { border-color: #2563eb; } .box-cancelados { border-color: #6366f1; } .box-inicio { border-color: #10b981; } .box-andamento { border-color: #ef4444; } .box-finalizados { border-color: #eab308; } .box-orcamento { border-color: #db2777; } .box-memorial { border-color: #f97316; }
</style>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>';

require_once 'includes/header.php';
?>

<div class="app-container gap-6">
    <div class="flex flex-col xl:flex-row gap-6 shrink-0">
        
        <!-- DASHBOARD -->
        <div class="flex-1 bg-white dark:bg-[#222736] rounded-lg border border-gray-200 dark:border-[#2a3142] shadow-sm p-4 transition-colors duration-300">
            <h2 class="text-blue-700 dark:text-blue-400 font-bold mb-4 flex items-center text-lg tracking-wide">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Dashboard
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 dark:bg-transparent border border-gray-300 dark:border-[#4b5563] rounded p-4 shadow-sm transition-colors duration-300"><p class="text-[11px] font-bold text-gray-500 dark:text-[#9ca3af] uppercase mb-1 tracking-wider">Total de Projetos</p><p class="text-3xl font-black text-gray-700 dark:text-[#93c5fd]"><?= $total_projetos ?></p></div>
                <div class="bg-blue-50 dark:bg-transparent border border-blue-300 dark:border-[#1e3a8a] rounded p-4 shadow-sm transition-colors duration-300"><p class="text-[11px] font-bold text-blue-600 dark:text-[#60a5fa] uppercase mb-1 tracking-wider">Projetos Fechados</p><p class="text-3xl font-black text-blue-600 dark:text-[#93c5fd]"><?= $fechados_ano ?></p></div>
                <div class="bg-indigo-50 dark:bg-transparent border border-indigo-300 dark:border-[#312e81] rounded p-4 shadow-sm transition-colors duration-300"><p class="text-[11px] font-bold text-indigo-600 dark:text-[#818cf8] uppercase mb-1 tracking-wider">Projetos Cancelados</p><p class="text-3xl font-black text-indigo-600 dark:text-[#a5b4fc]"><?= $cancelados ?></p></div>
                <div class="bg-emerald-50 dark:bg-transparent border border-emerald-300 dark:border-[#064e3b] rounded p-4 shadow-sm transition-colors duration-300"><p class="text-[11px] font-bold text-emerald-600 dark:text-[#34d399] uppercase mb-1 tracking-wider">Projetos Para Início</p><p class="text-3xl font-black text-emerald-600 dark:text-[#6ee7b7]"><?= $para_inicio ?></p></div>
                
                <div class="bg-red-50 dark:bg-transparent border border-red-300 dark:border-[#7f1d1d] rounded p-4 shadow-sm transition-colors duration-300"><p class="text-[11px] font-bold text-red-600 dark:text-[#f87171] uppercase mb-1 tracking-wider">Projetos em Andamento</p><p class="text-3xl font-black text-red-600 dark:text-[#fca5a5]"><?= $em_andamento ?></p></div>
                <div class="bg-yellow-50 dark:bg-transparent border border-yellow-300 dark:border-[#78350f] rounded p-4 shadow-sm transition-colors duration-300"><p class="text-[11px] font-bold text-yellow-600 dark:text-[#fbbf24] uppercase mb-1 tracking-wider">Projetos Finalizados</p><p class="text-3xl font-black text-yellow-600 dark:text-[#fcd34d]"><?= $finalizados ?></p></div>
                <div class="bg-pink-50 dark:bg-transparent border border-pink-300 dark:border-[#831843] rounded p-4 shadow-sm transition-colors duration-300"><p class="text-[11px] font-bold text-pink-600 dark:text-[#f472b6] uppercase mb-1 tracking-wider">Projetos para Orçamento</p><p class="text-3xl font-black text-pink-600 dark:text-[#f9a8d4]"><?= $para_orcamento ?></p></div>
                <div class="bg-orange-50 dark:bg-transparent border border-orange-300 dark:border-[#7c2d12] rounded p-4 shadow-sm transition-colors duration-300"><p class="text-[11px] font-bold text-orange-600 dark:text-[#fb923c] uppercase mb-1 tracking-wider">Projetos Memorial</p><p class="text-3xl font-black text-orange-600 dark:text-[#fdba74]"><?= $projetos_memorial ?></p></div>
            </div>
        </div>

        <!-- SIDEBAR -->
        <div class="w-full xl:w-96 flex flex-col gap-4">
            <div class="bg-white dark:bg-[#222736] border border-gray-200 dark:border-[#2a3142] rounded-lg shadow-sm p-4 flex-1 transition-colors duration-300">
                <span class="bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white text-xs font-bold px-3 py-1 inline-block mb-3 rounded-sm shadow-sm border border-gray-200 dark:border-gray-600">Próximas Apresentações</span>
                <div class="space-y-3 overflow-y-auto max-h-[140px] pr-2 kanban-col">
                    <?php if(empty($proximas_apresentacoes)): ?>
                        <p class="text-xs text-gray-500 italic">Nenhuma apresentação agendada.</p>
                    <?php endif; ?>
                    <?php foreach($proximas_apresentacoes as $ap): ?>
                        <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-2">
                            <div>
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase"><?= htmlspecialchars($ap['nome_cadastrado'] ?: $ap['cliente_nome']) ?></p>
                                <p class="text-[10px] text-gray-500 uppercase mt-0.5"><?= $funil[$ap['fase']]['titulo'] ?></p>
                            </div>
                            <span class="text-xs text-red-600 dark:text-red-400 font-bold"><?= date('d/m/Y', strtotime($ap['data_apresentacao'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bg-white dark:bg-[#222736] border border-red-200 dark:border-red-900/50 rounded-lg shadow-sm p-4 flex-1 transition-colors duration-300">
                <span class="bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold px-3 py-1 inline-block mb-3 rounded-sm shadow-sm border border-red-200 dark:border-red-800">Projetos em Atraso</span>
                <div class="space-y-3 overflow-y-auto max-h-[140px] pr-2 kanban-col">
                    <?php if(empty($projetos_atraso)): ?>
                        <p class="text-xs text-gray-500 italic">Nenhum projeto em atraso.</p>
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

    <!-- LINHA INFERIOR: KANBAN -->
    <div class="flex gap-4 overflow-x-auto pb-2 flex-1 min-h-0 items-start mt-2">
        <?php foreach ($funil as $fase_chave => $col): ?>
            <div class="bg-white dark:bg-[#222736] border border-gray-200 dark:border-[#2a3142] rounded flex flex-col min-w-[300px] max-w-[330px] flex-1 h-full shadow-sm transition-colors duration-300">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 <?= $col['cor'] ?> border-t-4 flex justify-between items-center bg-gray-50 dark:bg-gray-800 rounded-t transition-colors duration-300">
                    <h2 class="text-sm font-bold text-gray-800 dark:text-gray-100"><?= $col['titulo'] ?></h2>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400 font-bold"><?= count($col['leads']) ?></span>
                </div>
                
                <div id="fase-<?= $fase_chave ?>" data-fase="<?= $fase_chave ?>" class="kanban-col flex-1 p-3 overflow-y-auto space-y-3 min-h-[150px] <?= $col['bg'] ?> transition-colors duration-300">
                    <?php foreach ($col['leads'] as $l): 
                        $is_atrasado = (!empty($l['data_apresentacao']) && $l['data_apresentacao'] < $hoje && !in_array($fase_chave, ['FECHADO', 'PERDIDO', 'PAUSADO']));
                        
                        // Define a cor da borda baseado no status (Atrasado > Reprojeto > Normal)
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
                            if ($hoje > $data_limite) {
                                $sla_tag = '<span class="text-[9px] bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 px-1.5 py-0.5 rounded font-bold border border-red-200 dark:border-red-800 ml-2 animate-pulse">ATRASADO</span>';
                            } else {
                                $sla_tag = '<span class="text-[9px] bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 px-1.5 py-0.5 rounded font-bold border border-green-200 dark:border-green-800 ml-2">NO PRAZO</span>';
                            }
                        }
                    ?>
                        <div class="bg-white dark:bg-gray-800 border <?= $card_border ?> rounded p-3 cursor-grab transition-colors duration-200" data-id="<?= $l['id'] ?>">
                            
                            <!-- Cabeçalho do Card (Datas, Origem e AÇÕES) -->
                            <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-2 mb-2">
                                <span class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">Entrada: <?= date('d/m/y', strtotime($l['data_entrada'])) ?></span>
                                <div class="flex items-center">
                                    <span class="text-[9px] text-gray-800 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 px-1 font-bold rounded uppercase mr-2"><?= $l['origem'] ?></span>
                                    
                                    <!-- Editar -->
                                    <button onclick='editarLead(<?= json_encode($l, JSON_UNESCAPED_UNICODE) ?>)' class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400" title="Editar">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>
                                    
                                    <!-- NOVO: Solicitar Reprojeto -->
                                    <?php if(!in_array($fase_chave, ['FECHADO', 'PERDIDO', 'PAUSADO'])): ?>
                                    <button onclick='abrirModalReprojeto(<?= $l['id'] ?>)' class="text-gray-400 hover:text-orange-500 dark:hover:text-orange-400 ml-1.5" title="Solicitar Reprojeto">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    </button>
                                    <?php endif; ?>

                                    <!-- Excluir -->
                                    <button onclick='excluirLead(<?= $l['id'] ?>)' class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 ml-1.5" title="Excluir / Ocultar">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </div>
                            
                            <h4 class="font-bold text-xs text-blue-700 dark:text-blue-400 uppercase leading-snug mb-1 flex items-center">
                                <?= htmlspecialchars($l['nome_cadastrado'] ?: $l['cliente_nome']) ?>
                                <?= $sla_tag ?>
                                <!-- SELO DE REVISÃO -->
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

                            <?php if($l['projetista_responsavel']): ?>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">Proj: <span class="text-gray-800 dark:text-gray-200 font-semibold uppercase"><?= htmlspecialchars($l['projetista_responsavel']) ?></span></p>
                            <?php endif; ?>
                            <?php if($l['ambientes']): ?>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5 mb-2">Ambs: <span class="text-gray-800 dark:text-gray-200 font-semibold uppercase"><?= htmlspecialchars($l['ambientes']) ?></span></p>
                            <?php endif; ?>
                            
                            <?php if($l['observacao']): ?>
                                <p class="text-[10px] text-yellow-600 dark:text-yellow-500 italic mb-2 line-clamp-2 mt-2">Obs: <?= htmlspecialchars($l['observacao']) ?></p>
                            <?php endif; ?>
                            
                            <div class="text-[10px] text-gray-600 dark:text-gray-400 space-y-1.5 pt-2 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex justify-between">
                                    <span>Probabilidade:</span> 
                                    <span class="font-bold <?= $l['probabilidade']>70?'text-green-600 dark:text-green-500':'text-orange-500' ?>"><?= $l['probabilidade'] ?>%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Memorial:</span> 
                                    <span class="font-bold <?= corMemorial($l['memorial_descritivo']) ?>"><?= $l['memorial_descritivo'] ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Vlr. Estimado:</span> 
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">R$ <?= number_format($l['valor_estimado'], 2, ',', '.') ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Apresentação:</span> 
                                    <span class="font-bold <?= $is_atrasado ? 'text-red-500' : 'text-blue-600 dark:text-blue-400' ?>">
                                        <?= $l['data_apresentacao'] ? date('d/m/Y', strtotime($l['data_apresentacao'])) : 'NÃO AGENDADO' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL DE REPROJETO                             -->
<!-- ============================================== -->
<div id="modalReprojeto" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-[70] hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalReprojetoConteudo">
        <h3 class="text-lg font-bold text-orange-600 dark:text-orange-400 mb-4 uppercase flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            Solicitar Reprojeto
        </h3>
        <p class="text-xs text-gray-600 dark:text-gray-400 mb-4">O projeto retornará para a fase de <strong>Desenvolvimento 3D</strong> e ganhará uma tag de Revisão.</p>
        
        <form id="formReprojeto" onsubmit="salvarReprojeto(event)">
            <input type="hidden" id="reprojeto_lead_id">
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nova Data de Apresentação</label>
                <input type="date" id="reprojeto_data" required class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-orange-500">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="fecharModalReprojeto()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded font-bold transition shadow-sm">Confirmar</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- OUTROS MODAIS (Lead e Motivo Pausa) Mantidos   -->
<!-- ============================================== -->
<div id="modalLead" class="fixed inset-0 bg-black bg-opacity-60 dark:bg-opacity-70 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300 max-h-[90vh] overflow-y-auto" id="modalLeadConteudo">
        <div class="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
            <h3 id="modalTitulo" class="text-lg font-bold text-blue-700 dark:text-blue-400">Detalhes do Lead</h3>
            <button type="button" onclick="fecharModalLead()" class="text-gray-400 hover:text-gray-800 dark:hover:text-white text-2xl font-bold">&times;</button>
        </div>
        
        <form id="formLead" onsubmit="salvarLead(event)">
            <input type="hidden" id="lead_id" name="id">
            
            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded mb-4 border border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Vincular Cliente (Base de Dados)</label>
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Início do Projeto (Data)</label>
                        <input type="date" id="lead_inicio_projeto" name="data_inicio_projeto" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-blue-300 dark:border-blue-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Prazo para Entrega (Dias)</label>
                        <input type="number" id="lead_prazo_dias" name="prazo_projeto_dias" placeholder="Ex: 5" min="0" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-blue-300 dark:border-blue-600 text-gray-800 dark:text-white rounded font-bold text-blue-600 dark:text-blue-400 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Origem do Lead</label>
                    <select id="lead_origem" name="origem" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase focus:ring-2 focus:ring-blue-500">
                        <option value="INSTAGRAM">Instagram</option>
                        <option value="INDICACAO">Indicação</option>
                        <option value="ARQUITETO">Arquiteto(a)</option>
                        <option value="SHOWROOM">Visita Loja</option>
                        <option value="OUTROS">Outros</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Arquiteto Parceiro</label>
                    <input type="text" id="lead_arquiteto" name="arquiteto_nome" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Projetista Responsável</label>
                    <input type="text" id="lead_projetista" name="projetista_responsavel" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Ambientes</label>
                    <input type="text" id="lead_ambientes" name="ambientes" placeholder="Ex: Cozinha, Quarto..." class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Memorial Descritivo</label>
                    <select id="lead_memorial" name="memorial_descritivo" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase focus:ring-2 focus:ring-orange-500">
                        <option value="PRA FAZER">Pra Fazer</option>
                        <option value="PROJETANDO">Projetando</option>
                        <option value="FEITO">Feito</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Data da Apresentação</label>
                    <input type="date" id="lead_apresentacao" name="data_apresentacao" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded">
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
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Observações do Comercial</label>
                    <textarea id="lead_obs" name="observacao" rows="2" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded"></textarea>
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
                <select id="motivo_selecao" required class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase focus:ring-2 focus:ring-red-500 font-bold">
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
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Detalhes Adicionais (Opcional)</label>
                <textarea id="motivo_detalhes" rows="3" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded" placeholder="Descreva brevemente o que aconteceu com este cliente..."></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="cancelarMotivo()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition font-medium">Cancelar Mudança</button>
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded font-bold transition shadow-sm">Confirmar Status</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/comercial.js?v=<?= time() ?>"></script>
<?php require_once 'includes/footer.php'; ?>