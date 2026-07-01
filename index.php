<?php
// index.php
require_once 'includes/auth.php';
protegerPagina();
require_once 'config/conexao.php'; 

// --- FUNÇÃO PARA CALCULAR DIAS ÚTEIS ---
function calcularDiasUteis($data_inicial, $data_final) {
    if (!$data_inicial || !$data_final) return null;
    $inicio = new DateTime($data_inicial);
    $fim = new DateTime($data_final);
    if ($inicio > $fim) return 0; // Prevenção de erro
    
    $diasUteis = 0;
    while ($inicio <= $fim) {
        // 'N' retorna 1 (segunda) até 7 (domingo)
        if ($inicio->format('N') < 6) {
            $diasUteis++;
        }
        $inicio->modify('+1 day');
    }
    return $diasUteis;
}
// ---------------------------------------

$titulos_colunas = [
    'instalacao'      => ['titulo' => 'INSTALAÇÃO', 'cor' => 'border-emerald-500', 'data_label' => 'Finalização:'],
    'expedicao'       => ['titulo' => 'EXPEDIÇÃO', 'cor' => 'border-indigo-500', 'data_label' => 'Entrega:'],
    'producao'        => ['titulo' => 'PRODUÇÃO', 'cor' => 'border-blue-500', 'data_label' => 'Entrega:'],
    'desenvolvimento' => ['titulo' => 'DESENV. PCP', 'cor' => 'border-gray-500', 'data_label' => 'Entrega:'],
    'atrasou'         => ['titulo' => 'OBRA ATRASOU', 'cor' => 'border-red-500 text-red-700 dark:text-red-400', 'data_label' => '']
];

try {
    $stmt = $pdo->query("SELECT p.*, c.codigo_cliente, c.id as id_cadastro 
                         FROM projetos_pcp p 
                         LEFT JOIN clientes_cadastro c ON p.cliente = c.nome_contrato 
                         ORDER BY p.data_limite ASC");
    $projetos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt_lista_cli = $pdo->query("SELECT id, codigo_cliente, nome_contrato FROM clientes_cadastro ORDER BY nome_contrato ASC");
    $lista_clientes_oficial = $stmt_lista_cli->fetchAll(PDO::FETCH_ASSOC);

    $total_assistencias_abertas = 0;
    try {
        $stmt_ast = $pdo->query("SELECT COUNT(*) FROM assistencias_tecnicas WHERE resolvido_assistencia != 'SIM'");
        $total_assistencias_abertas = $stmt_ast->fetchColumn();
    } catch (\PDOException $e) {}

    $total_clientes = 0;
    try {
        $stmt_cli = $pdo->query("SELECT COUNT(*) FROM clientes_cadastro");
        $total_clientes = $stmt_cli->fetchColumn();
    } catch (\PDOException $e) {}

    $estoque_critico = [];
    $total_critico = 0;
    try {
        $stmt_almox = $pdo->query("SELECT nome_item, quantidade, unidade_medida FROM almoxarifado WHERE quantidade <= quantidade_minima ORDER BY nome_item ASC LIMIT 5");
        $estoque_critico = $stmt_almox->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt_almox_count = $pdo->query("SELECT COUNT(*) FROM almoxarifado WHERE quantidade <= quantidade_minima");
        $total_critico = $stmt_almox_count->fetchColumn();
    } catch (\PDOException $e) {}

    $colunas = [
        'instalacao'      => [], 'expedicao'       => [], 'producao'        => [],
        'desenvolvimento' => [], 'atrasou'         => []
    ];
    $proximas_entregas = [];

    foreach ($projetos as $p) {
        if ($p['status'] === 'assistencia') continue; 
        if (array_key_exists($p['status'], $colunas)) { $colunas[$p['status']][] = $p; }
        if (!empty($p['data_limite']) && in_array($p['status'], ['desenvolvimento', 'producao', 'expedicao'])) {
            if (count($proximas_entregas) < 5) { $proximas_entregas[] = $p; }
        }
    }
} catch (\PDOException $e) {
    die("Erro na consulta: " . $e->getMessage());
}

function formatarData($data) {
    if (!$data) return ''; return date('d/m/Y', strtotime($data));
}
function corChecklist($valor) {
    if ($valor === 'SIM') return 'text-green-600 dark:text-green-400 font-bold';
    if ($valor === 'PROJETANDO' || $valor === 'FAZENDO') return 'text-yellow-600 dark:text-yellow-400 font-bold';
    return 'text-gray-500 dark:text-gray-400 font-medium';
}
function jsSafe($val) {
    if ($val === null) $val = ''; return htmlspecialchars(json_encode($val), ENT_QUOTES, 'UTF-8');
}

// ---- VARIÁVEIS PARA O HEADER.PHP ----
$page_title = 'PCP';
$page_subtitle = 'SBG Móveis & Design';
$main_class = 'flex-1';
$menu_button_text = 'MENU';
$menu_button_class = 'bg-[#1e3a8a] dark:bg-blue-600 hover:bg-blue-800 dark:hover:bg-blue-500 text-white';

$head_extras = '
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<style>
    .kanban-column::-webkit-scrollbar { width: 4px; }
    .kanban-column::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
    .dark .kanban-column::-webkit-scrollbar-thumb { background-color: #475569; }
    .sortable-ghost { opacity: 0.4; background-color: #e2e8f0; border: 2px dashed #94a3b8; }
    .dark .sortable-ghost { background-color: #334155; border-color: #64748b; }
</style>';

$menu_extras = '
<button onclick="abrirModalNovo(); fecharMenu()" class="w-full text-left px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center border-t border-gray-100 dark:border-gray-700 transition-colors">
    <svg class="w-4 h-4 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>PROJETO
</button>
<button onclick="abrirModalUsuario(); fecharMenu()" class="w-full text-left px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center border-t border-gray-100 dark:border-gray-700 transition-colors">
    <svg class="w-4 h-4 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>ADD USER
</button>
<button onclick="abrirModalSenha(); fecharMenu()" class="w-full text-left px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center border-t border-gray-100 dark:border-gray-700 transition-colors">
    <svg class="w-4 h-4 mr-2 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>ALTERAR SENHA
</button>';

require_once 'includes/header.php';
// -------------------------------------
?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
    <div class="lg:col-span-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5 transition-colors duration-300 flex flex-col">
        <h2 class="text-lg font-bold text-[#1e3a8a] dark:text-blue-400 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            Resumo da Produção
        </h2>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded border border-gray-200 dark:border-gray-600">
                <div class="text-gray-500 dark:text-gray-400 text-xs font-bold uppercase">Desenv. PCP</div>
                <div class="text-2xl font-black text-[#1e3a8a] dark:text-blue-300"><?= count($colunas['desenvolvimento']) ?></div>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded border border-blue-100 dark:border-blue-800">
                <div class="text-blue-600 dark:text-blue-400 text-xs font-bold uppercase">Produção</div>
                <div class="text-2xl font-black text-blue-700 dark:text-blue-300"><?= count($colunas['producao']) ?></div>
            </div>
            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded border border-indigo-100 dark:border-indigo-800">
                <div class="text-indigo-600 dark:text-indigo-400 text-xs font-bold uppercase">Expedição</div>
                <div class="text-2xl font-black text-indigo-700 dark:text-indigo-300"><?= count($colunas['expedicao']) ?></div>
            </div>
            <div class="bg-emerald-50 dark:bg-emerald-900/20 p-3 rounded border border-emerald-100 dark:border-emerald-800">
                <div class="text-emerald-600 dark:text-emerald-400 text-xs font-bold uppercase">Instalação</div>
                <div class="text-2xl font-black text-emerald-700 dark:text-emerald-300"><?= count($colunas['instalacao']) ?></div>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 p-3 rounded border border-red-100 dark:border-red-800">
                <div class="text-red-600 dark:text-red-400 text-xs font-bold uppercase">Obra Atrasou</div>
                <div class="text-2xl font-black text-red-700 dark:text-red-300"><?= count($colunas['atrasou']) ?></div>
            </div>
            <div class="bg-amber-50 dark:bg-amber-900/20 p-3 rounded border border-amber-100 dark:border-amber-800">
                <div class="text-amber-600 dark:text-amber-400 text-xs font-bold uppercase">Ass. Técnicas</div>
                <div class="text-2xl font-black text-amber-700 dark:text-amber-300"><?= $total_assistencias_abertas ?></div>
            </div>
            <div class="bg-pink-50 dark:bg-pink-900/20 p-3 rounded border border-pink-100 dark:border-pink-800">
                <div class="text-pink-600 dark:text-pink-400 text-xs font-bold uppercase">Clientes Base</div>
                <div class="text-2xl font-black text-pink-700 dark:text-pink-300"><?= $total_clientes ?></div>
            </div>
            <div class="bg-orange-50 dark:bg-orange-900/20 p-3 rounded border border-orange-100 dark:border-orange-800">
                <div class="text-orange-600 dark:text-orange-400 text-xs font-bold uppercase">Itens em Falta</div>
                <div class="text-2xl font-black text-orange-700 dark:text-orange-300"><?= $total_critico ?></div>
            </div>
        </div>

        <div class="mt-auto pt-5 border-t border-gray-100 dark:border-gray-700">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-sm font-bold text-red-600 dark:text-red-500 flex items-center uppercase tracking-wide">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    Aprendizado
                </h3>
                <a href="https://www.youtube.com/@SBGMoveis" target="_blank" class="text-xs font-bold text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 transition-colors">Ver todos &rarr;</a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="https://www.youtube.com/watch?v=ncoj3qCjSCU&list=PLBz_Y0IEvnqLmaLTOfalWoYrQrCD6kj4K&index=2" target="_blank" class="group relative rounded-lg overflow-hidden shadow-sm border border-gray-200 dark:border-gray-700 aspect-video bg-gray-200 dark:bg-gray-800 transition-all hover:shadow-md block">
                    <img src="assets/images/playlist01.jpg" alt="Vídeo SBG Manuais de Instalação" onerror="this.src='https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=500&q=60'" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute inset-0 bg-black/30 group-hover:bg-black/50 transition-colors flex items-center justify-center">
                        <svg class="w-12 h-12 text-white opacity-80 group-hover:opacity-100 group-hover:scale-110 transition-all drop-shadow-lg" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/90 to-transparent p-3 pt-8">
                        <p class="text-white text-xs font-bold truncate">Manuais de Instalação</p>
                    </div>
                </a>
                
                <a href="https://www.youtube.com/watch?v=paEV_BdMRlg&t=15s" target="_blank" class="group relative rounded-lg overflow-hidden shadow-sm border border-gray-200 dark:border-gray-700 aspect-video bg-gray-200 dark:bg-gray-800 transition-all hover:shadow-md block">
                    <img src="assets/images/playlist02.png" alt="Vídeo SBG Projetando com Promob" onerror="this.src='https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=500&q=60'" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute inset-0 bg-black/30 group-hover:bg-black/50 transition-colors flex items-center justify-center">
                        <svg class="w-12 h-12 text-white opacity-80 group-hover:opacity-100 group-hover:scale-110 transition-all drop-shadow-lg" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/90 to-transparent p-3 pt-8">
                        <p class="text-white text-xs font-bold truncate">Projetando com Promob</p>
                    </div>
                </a>
                
                <a href="https://www.youtube.com/watch?v=b5bfG3YF4cg&t=1s" target="_blank" class="group relative rounded-lg overflow-hidden shadow-sm border border-gray-200 dark:border-gray-700 aspect-video bg-gray-200 dark:bg-gray-800 transition-all hover:shadow-md block">
                    <img src="assets/images/playlist03.png" alt="Vídeo SBG PCP Express + Promob" onerror="this.src='https://images.unsplash.com/photo-1524758631624-e2822e304c36?w=500&q=60'" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute inset-0 bg-black/30 group-hover:bg-black/50 transition-colors flex items-center justify-center">
                        <svg class="w-12 h-12 text-white opacity-80 group-hover:opacity-100 group-hover:scale-110 transition-all drop-shadow-lg" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/90 to-transparent p-3 pt-8">
                        <p class="text-white text-xs font-bold truncate">PCP Express + Promob</p>
                    </div>
                </a>
            </div>
        </div>
        </div>

    <div class="lg:col-span-1 flex flex-col space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5 transition-colors duration-300 flex-1">
            <h2 class="text-lg font-bold text-red-600 dark:text-red-400 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Próximas Entregas
            </h2>
            <ul class="space-y-3">
                <?php foreach ($proximas_entregas as $entrega): ?>
                    <li class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-2 last:border-0 last:pb-0">
                        <div>
                            <p class="font-bold text-sm text-gray-800 dark:text-gray-200 uppercase"><?= htmlspecialchars($entrega['cliente']) ?></p>
                            <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400"><?= $titulos_colunas[$entrega['status']]['titulo'] ?></p>
                        </div>
                        <span class="text-sm font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 px-2 py-1 rounded">
                            <?= formatarData($entrega['data_limite']) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($proximas_entregas)): ?>
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic mt-2">Nenhuma entrega em fluxo.</p>
                <?php endif; ?>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-orange-200 dark:border-orange-800 p-5 transition-colors duration-300">
            <h2 class="text-md font-bold text-orange-600 dark:text-orange-400 mb-3 flex items-center uppercase tracking-wide">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                Alerta de Estoque
            </h2>
            <ul class="space-y-2">
                <?php foreach ($estoque_critico as $item): ?>
                    <li class="flex justify-between items-center text-sm border-b border-gray-100 dark:border-gray-700 pb-1 last:border-0 last:pb-0">
                        <span class="text-gray-700 dark:text-gray-300 font-semibold truncate w-3/4 pr-2" title="<?= htmlspecialchars($item['nome_item']) ?>"><?= htmlspecialchars($item['nome_item']) ?></span>
                        <span class="text-red-600 dark:text-red-400 font-bold whitespace-nowrap"><?= (float)$item['quantidade'] ?> <span class="text-[10px] text-gray-500"><?= $item['unidade_medida'] ?></span></span>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($estoque_critico)): ?>
                    <p class="text-sm text-green-600 dark:text-green-400 italic font-semibold">Estoque Saudável. Tudo OK!</p>
                <?php else: ?>
                    <li class="pt-2 text-center">
                        <a href="almoxarifado.php" class="text-xs text-blue-500 hover:text-blue-700 font-bold hover:underline">Ver tudo no Almoxarifado &rarr;</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
    <?php foreach ($colunas as $status_chave => $lista_projetos): $conf = $titulos_colunas[$status_chave]; ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-3 flex flex-col h-[500px] transition-colors duration-300">
            <h2 class="text-sm font-bold uppercase tracking-wider border-b-2 <?= $conf['cor'] ?> pb-2 mb-3 dark:text-gray-100"><?= $conf['titulo'] ?></h2>
            <div id="col-<?= $status_chave ?>" data-status="<?= $status_chave ?>" class="kanban-column flex-1 overflow-y-auto space-y-2 pr-1">
                
                <?php foreach ($lista_projetos as $p): ?>
                    <?php 
                        $chk_resp = isset($p['checklist_respondido']) ? $p['checklist_respondido'] : 'NAO';
                        $chk_link = isset($p['checklist_link']) ? $p['checklist_link'] : '';
                        $med_agen = isset($p['medicao_agendada']) ? $p['medicao_agendada'] : 'NAO';
                        $med_data = isset($p['medicao_data']) ? $p['medicao_data'] : '';
                        
                        $equipe_inst = isset($p['equipe_instalacao']) ? $p['equipe_instalacao'] : '';
                        $dt_ini_inst = isset($p['data_inicio_instalacao']) ? $p['data_inicio_instalacao'] : '';
                        $dt_fim_inst = isset($p['data_fim_instalacao']) ? $p['data_fim_instalacao'] : '';
                        $dias_uteis = calcularDiasUteis($dt_ini_inst, $dt_fim_inst);

                        // Nova variável: Projeto Executivo
                        $proj_exec = isset($p['projeto_executivo']) ? $p['projeto_executivo'] : 'PARA FAZER';
                    ?>

                    <div class="bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 p-3 rounded shadow-sm cursor-grab active:cursor-grabbing transition-all duration-200" data-id="<?= $p['id'] ?>">
                        <div class="flex justify-between items-start mb-1">
                            <div class="flex items-center space-x-1.5">
                                <span class="text-[10px] font-bold text-blue-600 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/50 px-1.5 py-0.5 rounded">ID #<?= $p['id'] ?></span>
                                
                                <button onclick='imprimirFicha(<?= $p['id'] ?>, <?= jsSafe($p['cliente']) ?>, <?= jsSafe($conf['titulo']) ?>, <?= jsSafe($p['data_limite']) ?>, <?= jsSafe($p['observacao']) ?>, <?= jsSafe($p['promob']) ?>, <?= jsSafe($p['corte_furacao']) ?>, <?= jsSafe($p['lista_compras']) ?>, <?= jsSafe($p['lista_ferragens']) ?>, <?= jsSafe($chk_resp) ?>, <?= jsSafe($med_agen) ?>, <?= jsSafe($med_data) ?>, <?= jsSafe($equipe_inst) ?>, <?= jsSafe($dt_ini_inst) ?>, <?= jsSafe($dt_fim_inst) ?>, <?= jsSafe($dias_uteis) ?>, <?= jsSafe($proj_exec) ?>)' class="text-gray-400 hover:text-gray-800 dark:hover:text-gray-100 transition-colors text-xs px-1" title="Imprimir Ordem de Serviço">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                </button>

                                <?php if ($status_chave === 'instalacao'): ?>
                                    <button onclick='abrirModalNovaAssistencia(event, <?= $p['id'] ?>, <?= jsSafe($p['cliente']) ?>)' class="text-amber-500 hover:text-amber-600 dark:hover:text-amber-400 transition-colors text-xs px-1" title="Abrir Chamado de Assistência">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    </button>
                                <?php endif; ?>

                                <button onclick='abrirModalEdicao(event, <?= $p['id'] ?>, <?= jsSafe($p['cliente']) ?>, <?= jsSafe($p['data_limite']) ?>, <?= jsSafe($p['observacao']) ?>, <?= jsSafe($p['promob']) ?>, <?= jsSafe($p['corte_furacao']) ?>, <?= jsSafe($p['lista_compras']) ?>, <?= jsSafe($p['lista_ferragens']) ?>, <?= jsSafe($chk_resp) ?>, <?= jsSafe($chk_link) ?>, <?= jsSafe($med_agen) ?>, <?= jsSafe($med_data) ?>, <?= jsSafe($equipe_inst) ?>, <?= jsSafe($dt_ini_inst) ?>, <?= jsSafe($dt_fim_inst) ?>, <?= jsSafe($proj_exec) ?>)' class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors text-xs px-1" title="Editar Projeto">&#9998;</button>
                                
                                <button onclick="deletarCliente(event, <?= $p['id'] ?>)" class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors text-sm px-1" title="Apagar Projeto">&times;</button>
                            </div>
                        </div>

                        <?php if($conf['data_label'] && $p['data_limite']): ?>
                            <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400"><?= $conf['data_label'] ?> <?= formatarData($p['data_limite']) ?></span>
                        <?php endif; ?>
                        
                        <?php 
                            $codigo_exibicao = '';
                                if (!empty($p['codigo_cliente'])) {
                                    $codigo_exibicao = $p['codigo_cliente'];
                                } elseif (!empty($p['id_cadastro'])) {
                                    $codigo_exibicao = "CLI-" . str_pad($p['id_cadastro'], 2, "0", STR_PAD_LEFT);
                                }
                        ?>
                        <p class="font-bold text-gray-800 dark:text-gray-100 uppercase text-xs mt-1 flex items-center">
                            <?php if($codigo_exibicao): ?>
                                <span class="text-blue-600 dark:text-blue-400 font-black mr-1.5">[<?= htmlspecialchars($codigo_exibicao) ?>]</span>
                            <?php endif; ?>
                            <?= htmlspecialchars($p['cliente']) ?>
                        </p>
                        
                        <?php if ($p['observacao']): ?>
                            <p class="text-[10px] mt-1 italic <?= $status_chave === 'atrasou' ? 'text-gray-600 dark:text-gray-400' : 'text-amber-600 dark:text-amber-400' ?>">
                                <?= $status_chave === 'atrasou' ? 'Motivo:' : 'Obs:' ?> <?= htmlspecialchars($p['observacao']) ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($equipe_inst || $dias_uteis !== null): ?>
                            <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600 text-[10px]">
                                <?php if ($equipe_inst): ?>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-gray-500 dark:text-gray-400">Equipe Resp:</span>
                                        <span class="font-bold text-indigo-600 dark:text-indigo-400 uppercase"><?= htmlspecialchars($equipe_inst) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($dias_uteis !== null): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-500 dark:text-gray-400">Previsão:</span>
                                        <span class="font-bold text-gray-700 dark:text-gray-300">
                                            <?= formatarData($dt_ini_inst) ?> a <?= formatarData($dt_fim_inst) ?> 
                                            <span class="text-indigo-600 dark:text-indigo-400">(<?= $dias_uteis ?> dias úteis)</span>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($status_chave === 'desenvolvimento' || $status_chave === 'producao'): ?>
                            <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600 text-[10px] grid grid-cols-1 gap-1">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-gray-500 dark:text-gray-400">Checklist Obra:</span> 
                                    <span class="flex items-center">
                                        <span class="<?= corChecklist($chk_resp) ?>"><?= $chk_resp ?></span>
                                        <?php if ($chk_resp === 'SIM' && !empty($p['checklist_link'])): ?>
                                            <a href="<?= htmlspecialchars($p['checklist_link']) ?>" target="_blank" class="text-blue-500 hover:text-blue-700 ml-1" title="Ver Link">&#128279;</a>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center mb-2 border-b border-gray-100 dark:border-gray-600 pb-1">
                                    <span class="text-gray-500 dark:text-gray-400">Medição:</span> 
                                    <span class="flex items-center">
                                        <span class="<?= corChecklist($med_agen) ?>"><?= $med_agen ?></span>
                                        <?php if ($med_agen === 'SIM' && !empty($p['medicao_data'])): ?>
                                            <span class="text-gray-400 ml-1 border-l border-gray-300 dark:border-gray-600 pl-1"><?= formatarData($p['medicao_data']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center"><span class="text-gray-500 dark:text-gray-400">Projeto Promob:</span> <span class="<?= corChecklist($p['promob']) ?>"><?= $p['promob'] ?></span></div>
                                <div class="flex justify-between items-center"><span class="text-gray-500 dark:text-gray-400">Proj. Executivo:</span> <span class="<?= corChecklist($proj_exec) ?>"><?= $proj_exec ?></span></div>
                                <div class="flex justify-between items-center"><span class="text-gray-500 dark:text-gray-400">Corte:</span> <span class="<?= corChecklist($p['corte_furacao']) ?>"><?= $p['corte_furacao'] ?></span></div>
                                <div class="flex justify-between items-center"><span class="text-gray-500 dark:text-gray-400">Compras:</span> <span class="<?= corChecklist($p['lista_compras']) ?>"><?= $p['lista_compras'] ?></span></div>
                                <div class="flex justify-between items-center"><span class="text-gray-500 dark:text-gray-400">Ferragens:</span> <span class="<?= corChecklist($p['lista_ferragens']) ?>"><?= $p['lista_ferragens'] ?></span></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div id="modalNovo" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300 max-h-[90vh] overflow-y-auto" id="modalNovoConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
            <h3 class="text-lg font-bold text-[#1e3a8a] dark:text-blue-400">Cadastrar Novo Projeto</h3>
            <button onclick="fecharModalNovo()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">&times;</button>
        </div>
        
        <form id="formNovo" onsubmit="salvarNovoServidor(event)">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Selecionar Cliente Oficial</label>
                    <input type="text" id="search_novo_cliente" onkeyup="filtrarSelect('search_novo_cliente', 'novo_cliente')" placeholder="Pesquisar cliente..." autocomplete="off" class="w-full px-3 py-1.5 mb-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
                    
                    <select id="novo_cliente" required size="4" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase scrollbar-thin">
                        <?php foreach ($lista_clientes_oficial as $cli): 
                            $codigo_cli = !empty($cli['codigo_cliente']) ? $cli['codigo_cliente'] : "CLI-" . str_pad($cli['id'], 2, "0", STR_PAD_LEFT); 
                        ?>
                        <option value="<?= htmlspecialchars($cli['nome_contrato']) ?>" class="p-1 border-b border-gray-100 dark:border-gray-700 hover:bg-blue-50 dark:hover:bg-gray-600">
                            [<?= htmlspecialchars($codigo_cli) ?>] - <?= htmlspecialchars($cli['nome_contrato']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Status Inicial</label>
                    <select id="novo_status" name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
                        <option value="instalacao">INSTALAÇÃO</option>
                        <option value="expedicao">EXPEDIÇÃO</option>
                        <option value="producao">PRODUÇÃO</option>
                        <option value="desenvolvimento" selected>DESENV. PCP</option>
                        <option value="atrasou">OBRA ATRASOU</option>
                    </select>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded border border-gray-200 dark:border-gray-600 mb-4">
                <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 border-b border-gray-300 dark:border-gray-600 pb-1">Pré-Produção e PCP</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Checklist Respondido?</label>
                        <select id="novo_checklist" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                            <option value="NAO">NÃO</option>
                            <option value="SIM">SIM</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Link do Checklist (Opcional)</label>
                        <input type="url" id="novo_checklist_link" placeholder="https://..." class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Medição Agendada?</label>
                        <select id="novo_medicao" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                            <option value="NAO">NÃO</option>
                            <option value="SIM">SIM</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Data da Medição (Opcional)</label>
                        <input type="date" id="novo_medicao_data" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                    </div>

                    <div class="col-span-1 md:col-span-2 border-b border-gray-200 dark:border-gray-600 my-1"></div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Projeto Promob</label>
                        <select id="novo_promob" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                            <option value="PARA FAZER">PARA FAZER</option>
                            <option value="PROJETANDO">PROJETANDO</option>
                            <option value="SIM">SIM (Concluído)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Projeto Executivo</label>
                        <select id="novo_executivo" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                            <option value="PARA FAZER">PARA FAZER</option>
                            <option value="FAZENDO">FAZENDO</option>
                            <option value="SIM">SIM (Concluído)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Corte e Furação</label>
                        <select id="novo_corte" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                            <option value="PARA ENVIAR">PARA ENVIAR</option>
                            <option value="SIM">SIM (Enviado)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Lista de Compras</label>
                        <select id="novo_compras" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                            <option value="PARA ENVIAR">PARA ENVIAR</option>
                            <option value="SIM">SIM (Enviada)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Lista de Ferragens</label>
                        <select id="novo_ferragens" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                            <option value="PARA ENVIAR">PARA ENVIAR</option>
                            <option value="SIM">SIM (Enviada)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded border border-indigo-100 dark:border-indigo-800 mb-4">
                <h4 class="text-sm font-bold text-indigo-700 dark:text-indigo-400 mb-3 border-b border-indigo-200 dark:border-indigo-700 pb-1">Planejamento de Instalação</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Equipe Responsável</label>
                        <input type="text" id="novo_equipe" placeholder="Ex: Equipe A" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Data Início Instalação</label>
                        <input type="date" id="novo_dt_ini_inst" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Data Fim Instalação</label>
                        <input type="date" id="novo_dt_fim_inst" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Data Limite / Entrega do Projeto</label>
                <input type="date" id="novo_data_limite" name="data_limite" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Observações</label>
                <textarea id="novo_observacao" name="observacao" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div class="flex justify-end space-x-3 border-t dark:border-gray-700 pt-4">
                <button type="button" onclick="fecharModalNovo()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded font-bold transition shadow-sm">Cadastrar Projeto</button>
            </div>
        </form>
    </div>
</div>

<div id="modalEdicao" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300 max-h-[90vh] overflow-y-auto" id="modalConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
            <h3 class="text-lg font-bold text-[#1e3a8a] dark:text-blue-400">Editar Informações <span id="labelIdProjeto" class="text-gray-400 dark:text-gray-500 text-sm"></span></h3>
            <button onclick="fecharModalEdicao()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">&times;</button>
        </div>
        
        <form id="formEdicao" onsubmit="salvarEdicaoServidor(event)">
            <input type="hidden" id="edit_id" name="id">

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Selecionar Cliente Oficial</label>
                <input type="text" id="search_edit_cliente" onkeyup="filtrarSelect('search_edit_cliente', 'edit_cliente')" placeholder="Pesquisar cliente..." autocomplete="off" class="w-full px-3 py-1.5 mb-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
                <select id="edit_cliente" required size="4" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase scrollbar-thin">
                    <?php foreach ($lista_clientes_oficial as $cli): 
                        $codigo_cli = !empty($cli['codigo_cliente']) ? $cli['codigo_cliente'] : "CLI-" . str_pad($cli['id'], 2, "0", STR_PAD_LEFT); 
                    ?>
                    <option value="<?= htmlspecialchars($cli['nome_contrato']) ?>" class="p-1 border-b border-gray-100 dark:border-gray-700 hover:bg-blue-50 dark:hover:bg-gray-600">
                        [<?= htmlspecialchars($codigo_cli) ?>] - <?= htmlspecialchars($cli['nome_contrato']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded border border-gray-200 dark:border-gray-600 mb-4">
                <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 border-b border-gray-300 dark:border-gray-600 pb-1">Pré-Produção e PCP</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Checklist Respondido?</label>
                        <select id="edit_checklist" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                            <option value="NAO">NÃO</option>
                            <option value="SIM">SIM</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Link do Checklist (Opcional)</label>
                        <input type="url" id="edit_checklist_link" placeholder="https://..." class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Medição Agendada?</label>
                        <select id="edit_medicao" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                            <option value="NAO">NÃO</option>
                            <option value="SIM">SIM</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Data da Medição (Opcional)</label>
                        <input type="date" id="edit_medicao_data" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                    </div>

                    <div class="col-span-1 md:col-span-2 border-b border-gray-200 dark:border-gray-600 my-1"></div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Projeto Promob</label>
                        <select id="edit_promob" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                            <option value="PARA FAZER">PARA FAZER</option>
                            <option value="PROJETANDO">PROJETANDO</option>
                            <option value="SIM">SIM (Concluído)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Projeto Executivo</label>
                        <select id="edit_executivo" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                            <option value="PARA FAZER">PARA FAZER</option>
                            <option value="FAZENDO">FAZENDO</option>
                            <option value="SIM">SIM (Concluído)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Corte e Furação</label>
                        <select id="edit_corte" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                            <option value="PARA ENVIAR">PARA ENVIAR</option>
                            <option value="SIM">SIM (Enviado)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Lista de Compras</label>
                        <select id="edit_compras" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                            <option value="PARA ENVIAR">PARA ENVIAR</option>
                            <option value="SIM">SIM (Enviada)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Lista de Ferragens</label>
                        <select id="edit_ferragens" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                            <option value="PARA ENVIAR">PARA ENVIAR</option>
                            <option value="SIM">SIM (Enviada)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded border border-indigo-100 dark:border-indigo-800 mb-4">
                <h4 class="text-sm font-bold text-indigo-700 dark:text-indigo-400 mb-3 border-b border-indigo-200 dark:border-indigo-700 pb-1">Planejamento de Instalação</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Equipe Responsável</label>
                        <input type="text" id="edit_equipe" placeholder="Ex: Equipe A" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Data Início Instalação</label>
                        <input type="date" id="edit_dt_ini_inst" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Data Fim Instalação</label>
                        <input type="date" id="edit_dt_fim_inst" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Data Limite / Entrega do Projeto</label>
                <input type="date" id="edit_data_limite" name="data_limite" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Observações</label>
                <textarea id="edit_observacao" name="observacao" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div class="flex justify-end space-x-3 border-t dark:border-gray-700 pt-4">
                <button type="button" onclick="fecharModalEdicao()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-[#1e3a8a] dark:bg-blue-600 hover:bg-blue-800 dark:hover:bg-blue-500 text-white rounded font-bold transition shadow-sm">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<div id="modalUsuario" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalUsuarioConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
            <h3 class="text-lg font-bold text-[#1e3a8a] dark:text-blue-400">Cadastrar Novo Usuário</h3>
            <button onclick="fecharModalUsuario()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">&times;</button>
        </div>
        <form id="formUsuario" onsubmit="salvarUsuarioServidor(event)">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Login do Usuário</label>
                <input type="text" id="novo_login" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Criar Senha</label>
                <input type="password" id="novo_senha" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex justify-end space-x-3 border-t dark:border-gray-700 pt-4">
                <button type="button" onclick="fecharModalUsuario()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded font-bold transition shadow-sm">Cadastrar</button>
            </div>
        </form>
    </div>
</div>

<div id="modalSenha" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalSenhaConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-yellow-600 pb-2">
            <h3 class="text-lg font-bold text-yellow-600 dark:text-yellow-400">Alterar Minha Senha</h3>
            <button onclick="fecharModalSenha()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">&times;</button>
        </div>
        <form id="formSenha" onsubmit="salvarSenhaServidor(event)">
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nova Senha</label>
                <input type="password" id="nova_senha_input" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-yellow-500">
            </div>
            <div class="flex justify-end space-x-3 border-t dark:border-gray-700 pt-4">
                <button type="button" onclick="fecharModalSenha()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded font-bold transition shadow-sm">Atualizar</button>
            </div>
        </form>
    </div>
</div>

<div id="modalNovaAssistencia" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalNovaAssistenciaConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
            <h3 class="text-lg font-bold text-amber-600 dark:text-amber-400">Abrir Chamado de Assistência</h3>
            <button onclick="fecharModalNovaAssistencia()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">&times;</button>
        </div>
        <form id="formNovaAssistencia" onsubmit="salvarNovaAssistencia(event)">
            <input type="hidden" id="na_projeto_id">
            <input type="hidden" id="na_cliente_nome">
            <div class="mb-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Cliente:</p>
                <p id="label_na_cliente" class="font-bold text-gray-800 dark:text-gray-100 uppercase"></p>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Qual o defeito ou problema relatado?</label>
                <textarea id="na_observacao" rows="3" required placeholder="Descreva o que aconteceu..." class="w-full px-3 py-2 border border-amber-300 dark:border-amber-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-amber-500 bg-amber-50 dark:bg-gray-700"></textarea>
            </div>
            <div class="flex justify-end space-x-3 border-t dark:border-gray-700 pt-4">
                <button type="button" onclick="fecharModalNovaAssistencia()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded font-bold transition shadow-sm">Abrir Assistência</button>
            </div>
        </form>
    </div>
</div>

<script>
    // JS PARA O FILTRO NATIVO DOS CLIENTES NOS MODAIS
    function filtrarSelect(inputId, selectId) {
        let filter = document.getElementById(inputId).value.toUpperCase();
        let select = document.getElementById(selectId);
        let options = select.getElementsByTagName('option');
        for (let i = 0; i < options.length; i++) {
            let txtValue = options[i].textContent || options[i].innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                options[i].style.display = "";
            } else {
                options[i].style.display = "none";
            }
        }
    }

    // DRAG AND DROP
    document.addEventListener('DOMContentLoaded', () => {
        const columns = document.querySelectorAll('.kanban-column');
        columns.forEach(col => {
            new Sortable(col, {
                group: 'pcp_shared_group', animation: 180, ghostClass: 'sortable-ghost',
                onEnd: async function (evt) { await atualizarStatusNoServidor(evt.item.getAttribute('data-id'), evt.to.getAttribute('data-status')); },
            });
        });
    });

    async function atualizarStatusNoServidor(id, status) {
        try { await fetch('api/update_status.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id, status: status }) }); } catch (error) { console.error('Erro:', error); }
    }

    async function deletarCliente(event, id) {
        event.stopPropagation();
        if (!confirm(`Deseja apagar o registro ID #${id}?`)) return;
        const cardElement = document.querySelector(`[data-id="${id}"]`);
        try {
            const response = await fetch('api/delete_client.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) });
            const result = await response.json();
            if (result.success) { cardElement.style.opacity = '0'; cardElement.style.transform = 'scale(0.9)'; setTimeout(() => cardElement.remove(), 200); } 
        } catch (error) { alert('Erro de rede.'); }
    }

    // IMPRIMIR FICHA ATUALIZADA
    function imprimirFicha(id, cliente, statusAtual, dataLimite, observacao, promob, corte, compras, ferragens, chkResp, medAgen, medData, equipe, dtIni, dtFim, diasUteis, projExec) {
        let dataFormatada = 'Não informada'; if (dataLimite) { const partes = dataLimite.split('-'); if (partes.length === 3) dataFormatada = `${partes[2]}/${partes[1]}/${partes[0]}`; }
        let medDataFormatada = ''; if (medData) { const p = medData.split('-'); if (p.length === 3) medDataFormatada = ` (${p[2]}/${p[1]}/${p[0]})`; }
        let infoInstalacao = '';
        if (equipe || diasUteis !== null) {
            infoInstalacao = `<div class="checklist" style="margin-top: 15px;"><h3 class="check-title">PLANEJAMENTO DE INSTALAÇÃO</h3><div class="grid"><div class="col"><div class="check-item"><span>Equipe:</span> <strong style="text-transform: uppercase;">${equipe || '-'}</strong></div></div><div class="col"><div class="check-item"><span>Previsão:</span> <strong>${dtIni ? dtIni.split('-').reverse().join('/') : '-'} até ${dtFim ? dtFim.split('-').reverse().join('/') : '-'} (${diasUteis || 0} dias úteis)</strong></div></div></div></div>`;
        }

        const html = `<!DOCTYPE html><html><head><title>Ficha - #${id}</title><style>
        @media print { @page { margin: 0; } body { padding: 1.5cm; } }
        body { font-family: Arial, sans-serif; padding: 20px; color: #000; margin: 0; } .container { max-width: 800px; margin: 0 auto; border: 2px solid #000; padding: 20px; border-radius: 8px; } 
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .info-label { font-weight: bold; font-size: 12px; color: #333; text-transform: uppercase; border-bottom: 1px solid #ccc; padding-bottom: 3px; } .info-value { font-size: 16px; margin-top: 4px; padding: 6px 0; } .grid { display: flex; flex-wrap: wrap; gap: 20px; } .col { flex: 1; min-width: 45%; } .checklist { margin-top: 20px; border-top: 2px solid #000; padding-top: 15px; } .check-title { background: #eee; padding: 5px; border: 1px solid #ccc; font-size: 16px; text-align: center;} .check-item { display: flex; justify-content: space-between; border-bottom: 1px dashed #aaa; padding: 8px 0; font-size: 14px; } .box-obs { min-height: 80px; border: 1px solid #aaa; padding: 10px; font-style: italic; background: #fafafa; } .footer { text-align: center; font-size: 11px; color: #666; margin-top: 30px; }</style></head><body><div class="container">
        <div class="header"><img src="assets/images/sbg_oficial.png" alt="SBG" style="max-width: 150px; height: auto;" onerror="this.style.display='none';"><div style="text-align: right;"><h1 style="margin:0; font-size: 24px;">FICHA DE PRODUÇÃO - PCP</h1><p style="margin:5px 0 0 0; font-size: 14px;">Ordem de Serviço #${id}</p></div></div>
        <div style="margin-top:15px;"><div class="info-label">Nome do Cliente</div><div class="info-value" style="font-size: 22px; font-weight: bold; text-transform: uppercase;">${cliente}</div></div><div class="grid"><div class="col"><div class="info-label">Data Limite</div><div class="info-value"><b>${dataFormatada}</b></div></div><div class="col"><div class="info-label">Status Atual</div><div class="info-value"><b>${statusAtual}</b></div></div></div><div style="margin-top:15px;"><div class="info-label">Observações Gerais</div><div class="info-value box-obs">${observacao || 'Nenhuma observação.'}</div></div><div class="checklist"><h3 class="check-title">VERIFICAÇÃO DE PRÉ-PRODUÇÃO E PROJETOS</h3><div class="grid"><div class="col"><div class="check-item"><span>Checklist Obra:</span> <strong>${chkResp}</strong></div><div class="check-item"><span>Medição Obra:</span> <strong>${medAgen} ${medDataFormatada}</strong></div><div class="check-item"><span>Projeto Promob:</span> <strong>${promob}</strong></div><div class="check-item"><span>Projeto Executivo:</span> <strong>${projExec}</strong></div></div><div class="col"><div class="check-item"><span>Corte/Furação:</span> <strong>${corte}</strong></div><div class="check-item"><span>Lista Compras:</span> <strong>${compras}</strong></div><div class="check-item"><span>Lista Ferragens:</span> <strong>${ferragens}</strong></div></div></div></div>${infoInstalacao}<div class="footer">Impresso via PCP AESPINA em ${new Date().toLocaleString('pt-BR')}</div></div></body></html>`;
        const janelaPrint = window.open('', '_blank', 'width=800,height=600'); janelaPrint.document.write(html); janelaPrint.document.close(); janelaPrint.focus(); setTimeout(() => { janelaPrint.print(); janelaPrint.close(); }, 500);
    }

    // CONTROLADORES DE MODAIS
    const modalNovo = document.getElementById('modalNovo'); const modalNovoConteudo = document.getElementById('modalNovoConteudo');
    function abrirModalNovo() { 
        document.getElementById('search_novo_cliente').value = '';
        filtrarSelect('search_novo_cliente', 'novo_cliente');
        modalNovo.classList.remove('hidden'); setTimeout(() => { modalNovo.classList.remove('opacity-0'); modalNovoConteudo.classList.remove('scale-95'); }, 10); 
    }
    function fecharModalNovo() { modalNovo.classList.add('opacity-0'); modalNovoConteudo.classList.add('scale-95'); setTimeout(() => { modalNovo.classList.add('hidden'); document.getElementById('formNovo').reset(); }, 300); }
    
    async function salvarNovoServidor(event) {
        event.preventDefault(); 
        const payload = {
            cliente: document.getElementById('novo_cliente').value, status: document.getElementById('novo_status').value, data_limite: document.getElementById('novo_data_limite').value,
            observacao: document.getElementById('novo_observacao').value, promob: document.getElementById('novo_promob').value, 
            projeto_executivo: document.getElementById('novo_executivo').value, corte_furacao: document.getElementById('novo_corte').value,
            lista_compras: document.getElementById('novo_compras').value, lista_ferragens: document.getElementById('novo_ferragens').value,
            checklist_respondido: document.getElementById('novo_checklist').value, checklist_link: document.getElementById('novo_checklist_link').value,
            medicao_agendada: document.getElementById('novo_medicao').value, medicao_data: document.getElementById('novo_medicao_data').value,
            equipe_instalacao: document.getElementById('novo_equipe').value, data_inicio_instalacao: document.getElementById('novo_dt_ini_inst').value, data_fim_instalacao: document.getElementById('novo_dt_fim_inst').value
        };
        try {
            const response = await fetch('api/add_client.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const result = await response.json();
            if (result.success) { fecharModalNovo(); window.location.reload(); } else { alert('Erro: ' + result.error); }
        } catch (error) { alert('Erro de rede.'); }
    }

    const modalEdicao = document.getElementById('modalEdicao'); const modalConteudo = document.getElementById('modalConteudo');
    function abrirModalEdicao(event, id, cliente, dataLimite, observacao, promob, corte, compras, ferragens, chkResp, chkLink, medAgen, medData, equipe, dtIni, dtFim, projExec) {
        event.stopPropagation();
        
        document.getElementById('search_edit_cliente').value = '';
        filtrarSelect('search_edit_cliente', 'edit_cliente');
        
        document.getElementById('edit_id').value = id; document.getElementById('edit_cliente').value = cliente; document.getElementById('edit_data_limite').value = dataLimite ? dataLimite : ''; document.getElementById('edit_observacao').value = observacao ? observacao : '';
        document.getElementById('edit_promob').value = promob || 'PARA FAZER'; 
        document.getElementById('edit_executivo').value = projExec || 'PARA FAZER';
        document.getElementById('edit_corte').value = corte || 'PARA ENVIAR'; document.getElementById('edit_compras').value = compras || 'PARA ENVIAR'; document.getElementById('edit_ferragens').value = ferragens || 'PARA ENVIAR';
        document.getElementById('edit_checklist').value = chkResp || 'NAO'; document.getElementById('edit_checklist_link').value = chkLink || ''; document.getElementById('edit_medicao').value = medAgen || 'NAO'; document.getElementById('edit_medicao_data').value = medData || '';
        
        document.getElementById('edit_equipe').value = equipe || '';
        document.getElementById('edit_dt_ini_inst').value = dtIni || '';
        document.getElementById('edit_dt_fim_inst').value = dtFim || '';

        document.getElementById('labelIdProjeto').innerText = `(ID #${id})`;
        modalEdicao.classList.remove('hidden'); setTimeout(() => { modalEdicao.classList.remove('opacity-0'); modalConteudo.classList.remove('scale-95'); }, 10);
    }
    function fecharModalEdicao() { modalEdicao.classList.add('opacity-0'); modalConteudo.classList.add('scale-95'); setTimeout(() => { modalEdicao.classList.add('hidden'); document.getElementById('formEdicao').reset(); }, 300); }
    
    async function salvarEdicaoServidor(event) {
        event.preventDefault(); 
        const payload = {
            id: document.getElementById('edit_id').value, cliente: document.getElementById('edit_cliente').value, data_limite: document.getElementById('edit_data_limite').value,
            observacao: document.getElementById('edit_observacao').value, promob: document.getElementById('edit_promob').value, 
            projeto_executivo: document.getElementById('edit_executivo').value, corte_furacao: document.getElementById('edit_corte').value,
            lista_compras: document.getElementById('edit_compras').value, lista_ferragens: document.getElementById('edit_ferragens').value,
            checklist_respondido: document.getElementById('edit_checklist').value, checklist_link: document.getElementById('edit_checklist_link').value,
            medicao_agendada: document.getElementById('edit_medicao').value, medicao_data: document.getElementById('edit_medicao_data').value,
            equipe_instalacao: document.getElementById('edit_equipe').value, data_inicio_instalacao: document.getElementById('edit_dt_ini_inst').value, data_fim_instalacao: document.getElementById('edit_dt_fim_inst').value
        };
        try {
            const response = await fetch('api/edit_client.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const result = await response.json();
            if (result.success) { fecharModalEdicao(); window.location.reload(); } else { alert('Erro: ' + result.error); }
        } catch (error) { alert('Erro de rede.'); }
    }

    const modalUsuario = document.getElementById('modalUsuario'); function abrirModalUsuario() { modalUsuario.classList.remove('hidden'); setTimeout(() => { modalUsuario.classList.remove('opacity-0'); document.getElementById('modalUsuarioConteudo').classList.remove('scale-95'); }, 10); }
    function fecharModalUsuario() { modalUsuario.classList.add('opacity-0'); document.getElementById('modalUsuarioConteudo').classList.add('scale-95'); setTimeout(() => { modalUsuario.classList.add('hidden'); document.getElementById('formUsuario').reset(); }, 300); }
    async function salvarUsuarioServidor(event) { event.preventDefault(); const payload = { usuario: document.getElementById('novo_login').value, senha: document.getElementById('novo_senha').value }; try { const response = await fetch('api/add_user.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) }); const result = await response.json(); if (result.success) { alert('Usuário cadastrado!'); fecharModalUsuario(); } else { alert('Erro: ' + result.error); } } catch (error) { alert('Erro de rede.'); } }

    const modalSenha = document.getElementById('modalSenha'); function abrirModalSenha() { modalSenha.classList.remove('hidden'); setTimeout(() => { modalSenha.classList.remove('opacity-0'); document.getElementById('modalSenhaConteudo').classList.remove('scale-95'); }, 10); }
    function fecharModalSenha() { modalSenha.classList.add('opacity-0'); document.getElementById('modalSenhaConteudo').classList.add('scale-95'); setTimeout(() => { modalSenha.classList.add('hidden'); document.getElementById('formSenha').reset(); }, 300); }
    async function salvarSenhaServidor(event) { event.preventDefault(); const payload = { nova_senha: document.getElementById('nova_senha_input').value }; try { const response = await fetch('api/change_password.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) }); const result = await response.json(); if (result.success) { alert('Senha alterada!'); fecharModalSenha(); } else { alert('Erro: ' + result.error); } } catch (error) { alert('Erro de rede.'); } }

    const modalNA = document.getElementById('modalNovaAssistencia'); function abrirModalNovaAssistencia(event, projeto_id, cliente) { event.stopPropagation(); document.getElementById('na_projeto_id').value = projeto_id; document.getElementById('na_cliente_nome').value = cliente; document.getElementById('label_na_cliente').innerText = cliente + ` (Projeto #${projeto_id})`; modalNA.classList.remove('hidden'); setTimeout(() => { modalNA.classList.remove('opacity-0'); document.getElementById('modalNovaAssistenciaConteudo').classList.remove('scale-95'); }, 10); }
    function fecharModalNovaAssistencia() { modalNA.classList.add('opacity-0'); document.getElementById('modalNovaAssistenciaConteudo').classList.add('scale-95'); setTimeout(() => { modalNA.classList.add('hidden'); document.getElementById('formNovaAssistencia').reset(); }, 300); }
    async function salvarNovaAssistencia(event) { event.preventDefault(); const payload = { projeto_id: document.getElementById('na_projeto_id').value, cliente: document.getElementById('na_cliente_nome').value, observacao: document.getElementById('na_observacao').value }; try { const response = await fetch('api/nova_assistencia.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) }); const result = await response.json(); if (result.success) { alert('Assistência registrada!'); fecharModalNovaAssistencia(); window.location.reload(); } else { alert('Erro: ' + result.error); } } catch (error) { alert('Erro de rede.'); } }

    modalNovo.addEventListener('click', (e) => { if (e.target === modalNovo) fecharModalNovo(); });
    modalEdicao.addEventListener('click', (e) => { if (e.target === modalEdicao) fecharModalEdicao(); });
    modalUsuario.addEventListener('click', (e) => { if (e.target === modalUsuario) fecharModalUsuario(); });
    modalSenha.addEventListener('click', (e) => { if (e.target === modalSenha) fecharModalSenha(); });
    modalNA.addEventListener('click', (e) => { if (e.target === modalNA) fecharModalNovaAssistencia(); });
</script>

<?php require_once 'includes/footer.php'; ?>