<?php
// index.php
require_once 'includes/auth.php';
protegerPagina();

require_once 'config/conexao.php';

// --- FUNÇÃO PARA CALCULAR DIAS ÚTEIS COM TIPAGEM (PHP 8) ---
function calcularDiasUteis(?string $data_inicial, ?string $data_final): ?int {
    if (!$data_inicial || !$data_final) return null;
    
    $inicio = new DateTime($data_inicial);
    $fim = new DateTime($data_final);
    
    if ($inicio > $fim) return 0;
    
    $diasUteis = 0;
    while ($inicio <= $fim) {
        if ($inicio->format('N') < 6) { $diasUteis++; }
        $inicio->modify('+1 day');
    }
    return $diasUteis;
}
// ---------------------------------------

$titulos_colunas = [
    'desenvolvimento' => ['titulo' => 'DESENV. PCP', 'cor' => 'border-gray-500', 'data_label' => 'Entrega:'],
    'producao'        => ['titulo' => 'PRODUÇÃO', 'cor' => 'border-blue-500', 'data_label' => 'Entrega:'],
    'expedicao'       => ['titulo' => 'EXPEDIÇÃO', 'cor' => 'border-indigo-500', 'data_label' => 'Entrega:'],
    'instalacao'      => ['titulo' => 'INSTALAÇÃO', 'cor' => 'border-emerald-500', 'data_label' => 'Finalização:'],
    'entregue'        => ['titulo' => 'ENTREGUES', 'cor' => 'border-purple-500', 'data_label' => 'Finalizado:']
];

try {
    $stmt = $pdo->query("SELECT p.*, c.codigo_cliente, c.id as id_cadastro 
                         FROM projetos_pcp p 
                         LEFT JOIN clientes_cadastro c ON p.cliente = c.nome_contrato 
                         ORDER BY p.data_limite ASC");
    $projetos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt_lista_cli = $pdo->query("SELECT id, codigo_cliente, nome_contrato FROM clientes_cadastro ORDER BY nome_contrato ASC");
    $lista_clientes_oficial = $stmt_lista_cli->fetchAll(PDO::FETCH_ASSOC);

    // --- NOVO: Buscando Cadastros Base para o PCP ---
    $stmt_equipes = $pdo->query("SELECT nome FROM cadastros_base WHERE tipo = 'EQUIPE_MONTAGEM' ORDER BY nome ASC");
    $equipes_montagem = $stmt_equipes->fetchAll(PDO::FETCH_COLUMN);
    // ------------------------------------------------

    $total_assistencias_abertas = 0;
    try {
        $stmt_ast = $pdo->query("SELECT COUNT(*) FROM assistencias_tecnicas WHERE resolvido_assistencia != 'SIM'");
        $total_assistencias_abertas = (int)$stmt_ast->fetchColumn();
    } catch (\PDOException) {} // Removido o $e inativo

    $total_clientes = 0;
    try {
        $stmt_cli = $pdo->query("SELECT COUNT(*) FROM clientes_cadastro");
        $total_clientes = (int)$stmt_cli->fetchColumn();
    } catch (\PDOException) {} // Removido o $e inativo

    $estoque_critico = [];
    $total_critico = 0;
    try {
        $stmt_almox = $pdo->query("SELECT nome_item, quantidade, unidade_medida FROM almoxarifado WHERE quantidade <= quantidade_minima ORDER BY nome_item ASC LIMIT 5");
        $estoque_critico = $stmt_almox->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt_almox_count = $pdo->query("SELECT COUNT(*) FROM almoxarifado WHERE quantidade <= quantidade_minima");
        $total_critico = (int)$stmt_almox_count->fetchColumn();
    } catch (\PDOException) {} // Removido o $e inativo

    $colunas = [
        'desenvolvimento' => [], 'producao' => [], 'expedicao' => [], 'instalacao' => [], 'entregue' => []
    ];

    $proximas_entregas = [];

    foreach ($projetos as $p) {
        $status_banco = $p['status'] ?? '';
        if ($status_banco === 'assistencia') continue; 
        
        // Proteção e atribuição de status
        $status_atual = array_key_exists($status_banco, $colunas) ? $status_banco : 'instalacao';
        
        // Lógica: Se for entregue, verifica se está dentro dos últimos 6 meses
        if ($status_atual === 'entregue') {
            // Refatorado com encadeamento de coalescência nula (PHP 8)
            $data_ref = $p['data_fim_instalacao'] ?? ($p['data_limite'] ?? date('Y-m-d'));
            $seis_meses_atras = date('Y-m-d', strtotime('-6 months'));
            
            if ($data_ref >= $seis_meses_atras) {
                $colunas['entregue'][] = $p;
            }
        } else {
            $colunas[$status_atual][] = $p;
            
            if (!empty($p['data_limite']) && in_array($status_atual, ['desenvolvimento', 'producao', 'expedicao'])) {
                if (count($proximas_entregas) < 5) { $proximas_entregas[] = $p; }
            }
        }
    }

    // --- LÓGICA DE DADOS PARA OS GRÁFICOS ---
    $chart_rosca_labels = ['Desenv. PCP', 'Produção', 'Expedição', 'Instalação', 'Entregues (6m)'];
    $chart_rosca_data = [
        count($colunas['desenvolvimento']),
        count($colunas['producao']),
        count($colunas['expedicao']),
        count($colunas['instalacao']),
        count($colunas['entregue'])
    ];

    $meses_nomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    $chart_bar_labels = [];
    $chart_bar_projetos = [];
    $chart_bar_assistencias = [];

    // Loop para preencher os últimos 6 meses retroativamente
    for ($i = 5; $i >= 0; $i--) {
        $data_alvo = strtotime("-$i months");
        $mes_num = date('m', $data_alvo);
        $ano_num = date('Y', $data_alvo);

        $chart_bar_labels[] = $meses_nomes[(int)$mes_num - 1] . '/' . substr($ano_num, 2);
        
        $stmt_proj_mes = $pdo->prepare("SELECT COUNT(*) FROM projetos_pcp WHERE status = 'assistencia' AND MONTH(data_limite) = ? AND YEAR(data_limite) = ?");
        $stmt_proj_mes->execute([$mes_num, $ano_num]);
        $chart_bar_projetos[] = (int)$stmt_proj_mes->fetchColumn();

        $stmt_ast_mes = $pdo->prepare("SELECT COUNT(*) FROM assistencias_tecnicas WHERE MONTH(data_solicitacao) = ? AND YEAR(data_solicitacao) = ?");
        $stmt_ast_mes->execute([$mes_num, $ano_num]);
        $chart_bar_assistencias[] = (int)$stmt_ast_mes->fetchColumn();
    }
    
    // Fallback caso a array de projetos estivesse vazia
    $entregues_este_mes = end($chart_bar_projetos) ?: 0;

} catch (\PDOException $e) {
    die("Erro na consulta: " . $e->getMessage());
}

function formatarData(?string $data): string {
    return $data ? date('d/m/Y', strtotime($data)) : '';
}

function corChecklist(?string $valor): string {
    return match($valor) {
        'SIM' => 'text-green-600 dark:text-green-400 font-bold',
        'PROJETANDO', 'FAZENDO' => 'text-yellow-600 dark:text-yellow-400 font-bold',
        default => 'text-gray-500 dark:text-gray-400 font-medium'
    };
}

function jsSafe(mixed $val): string {
    return htmlspecialchars(json_encode($val ?? ''), ENT_QUOTES, 'UTF-8');
}

$page_title = 'PAINEL DE CONTROLE';
$page_subtitle = 'MoodLAR Projetos Moveleiros';
$main_class = 'flex-1'; 
$menu_button_text = 'MENU';
$menu_button_class = 'bg-[#1e3a8a] dark:bg-blue-600 hover:bg-blue-800 dark:hover:bg-blue-500 text-white';

$head_extras = '
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .kanban-column::-webkit-scrollbar { width: 4px; }
    .kanban-column::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
    .dark .kanban-column::-webkit-scrollbar-thumb { background-color: #475569; }
    .sortable-ghost { opacity: 0.4; background-color: #e2e8f0; border: 2px dashed #94a3b8; }
    .dark .sortable-ghost { background-color: #334155; border-color: #64748b; }
</style>';

$role = $_SESSION['usuario_role'] ?? 'USER';
$permissoes = $_SESSION['usuario_permissoes'] ?? [];

require_once 'includes/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
    
    <div class="lg:col-span-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5 transition-colors duration-300 flex flex-col">
        <h2 class="text-lg font-bold text-[#1e3a8a] dark:text-blue-400 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            Resumo da Produção
        </h2>
        
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
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
            <div class="bg-purple-50 dark:bg-purple-900/20 p-3 rounded border border-purple-100 dark:border-purple-800">
                <div class="text-purple-600 dark:text-purple-400 text-[10px] sm:text-xs font-bold uppercase">Entregues (6m)</div>
                <div class="text-2xl font-black text-purple-700 dark:text-purple-300"><?= count($colunas['entregue']) ?></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 flex-1">
            <div class="bg-gray-50 dark:bg-gray-700/30 p-4 rounded-xl border border-gray-200 dark:border-gray-700 flex flex-col justify-center items-center shadow-inner">
                <h3 class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest w-full text-center mb-4">Volume Atual por Fase</h3>
                <div class="relative w-full h-44 flex justify-center">
                    <canvas id="chartRosca"></canvas>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/30 p-4 rounded-xl border border-gray-200 dark:border-gray-700 flex flex-col justify-center shadow-inner">
                <h3 class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest w-full text-center mb-4">Desempenho (Últimos 6 Meses)</h3>
                <div class="relative w-full h-44">
                    <canvas id="chartBarras"></canvas>
                </div>
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
        
        <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] p-5 transition-colors duration-300">
            <h2 class="text-md font-bold text-gray-800 dark:text-gray-200 mb-4 flex items-center uppercase tracking-wide">
                <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                Ações Rápidas
            </h2>
            <div class="grid grid-cols-2 gap-3">
                <?php if ($role === 'ADMIN' || in_array('projetos', $permissoes)): ?>
                <button onclick="abrirModalNovo()" class="bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-lg text-[11px] font-bold flex flex-col items-center justify-center transition-colors shadow-sm tracking-wide">
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    NOVO PROJETO
                </button>
                <a href="calendario.php" class="bg-indigo-500 hover:bg-indigo-600 text-white p-3 rounded-lg text-[11px] font-bold flex flex-col items-center justify-center transition-colors shadow-sm tracking-wide text-center">
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    CALENDÁRIO
                </a>
                <?php endif; ?>
                
                <a href="impressoes.php" class="col-span-2 bg-emerald-600 hover:bg-emerald-700 text-white p-2.5 rounded-lg text-xs font-bold flex items-center justify-center transition-colors shadow-sm mt-1 uppercase tracking-wide">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    IMPRESSÕES RÁPIDAS
                </a>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5 transition-colors duration-300 flex-1">
            <h2 class="text-lg font-bold text-red-600 dark:text-red-400 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Próximas Entregas
            </h2>
            <ul class="space-y-3">
                <?php foreach ($proximas_entregas as $entrega): ?>
                    <li class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-2 last:border-0 last:pb-0">
                        <div>
                            <p class="font-bold text-sm text-gray-800 dark:text-gray-200 uppercase"><?= preg_replace('/^(\[.*?\])/', '<span class="text-blue-600 dark:text-blue-400 font-black mr-1">$1</span>', htmlspecialchars($entrega['cliente'])) ?></p>
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
                        // PHP 8: Troca do isset e operador ternário pela coalescência nula ??
                        $chk_resp = $p['checklist_respondido'] ?? 'NAO';
                        $chk_link = $p['checklist_link'] ?? '';
                        $med_agen = $p['medicao_agendada'] ?? 'NAO';
                        $med_data = $p['medicao_data'] ?? '';
                        
                        $equipe_inst = $p['equipe_instalacao'] ?? '';
                        $dt_ini_inst = $p['data_inicio_instalacao'] ?? '';
                        $dt_fim_inst = $p['data_fim_instalacao'] ?? '';
                        $dias_uteis = calcularDiasUteis($dt_ini_inst, $dt_fim_inst);
                        $proj_exec = $p['projeto_executivo'] ?? 'PARA FAZER';
                        $situacao_obra = $p['situacao_obra'] ?? 'NORMAL';
                        
                        // Lógica de Atraso
                        $is_atrasado = false;
                        $hoje = date('Y-m-d');
                        if (!empty($p['data_fim_instalacao']) && $p['data_fim_instalacao'] < $hoje) {
                            $is_atrasado = true;
                        } elseif (!empty($p['data_limite']) && $p['data_limite'] < $hoje) {
                            $is_atrasado = true;
                        }

                        // Lógica de Cor do Card
                        $card_bg = 'bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600';
                        $card_border = 'border-gray-200 dark:border-gray-600';

                        if ($status_chave === 'instalacao') {
                            if ($situacao_obra === 'PAUSADA') {
                                $card_border = 'border-orange-400 dark:border-orange-500 shadow-[0_0_8px_rgba(249,115,22,0.3)]';
                                $card_bg = 'bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 dark:hover:bg-orange-900/30';
                            } elseif ($is_atrasado) {
                                $card_border = 'border-red-400 dark:border-red-500 shadow-[0_0_8px_rgba(239,68,68,0.3)]';
                                $card_bg = 'bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30';
                            }
                        } elseif ($status_chave === 'entregue') {
                            $card_border = 'border-purple-300 dark:border-purple-700 opacity-70 hover:opacity-100';
                            $card_bg = 'bg-purple-50/50 dark:bg-purple-900/10 hover:bg-purple-100 dark:hover:bg-purple-900/30';
                        }
                    ?>
                    <div class="<?= $card_bg ?> border <?= $card_border ?> p-3 rounded shadow-sm cursor-grab active:cursor-grabbing transition-all duration-200" data-id="<?= $p['id'] ?>">
                        <div class="flex justify-between items-start mb-1">
                            <div class="flex items-center space-x-1.5 w-full justify-end">
                                <button onclick='imprimirFicha(<?= $p['id'] ?>, <?= jsSafe($p['cliente']) ?>, <?= jsSafe($conf['titulo']) ?>, <?= jsSafe($p['data_limite']) ?>, <?= jsSafe($p['observacao']) ?>, <?= jsSafe($p['promob']) ?>, <?= jsSafe($p['corte_furacao']) ?>, <?= jsSafe($p['lista_compras']) ?>, <?= jsSafe($p['lista_ferragens']) ?>, <?= jsSafe($chk_resp) ?>, <?= jsSafe($med_agen) ?>, <?= jsSafe($med_data) ?>, <?= jsSafe($equipe_inst) ?>, <?= jsSafe($dt_ini_inst) ?>, <?= jsSafe($dt_fim_inst) ?>, <?= jsSafe($dias_uteis) ?>, <?= jsSafe($proj_exec) ?>)' class="text-gray-400 hover:text-gray-800 dark:hover:text-gray-100 transition-colors text-xs px-1" title="Imprimir Ordem de Serviço">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                </button>
                                <?php if ($status_chave === 'instalacao' || $status_chave === 'entregue'): ?>
                                    <button onclick='abrirModalNovaAssistencia(event, <?= $p['id'] ?>, <?= jsSafe($p['cliente']) ?>)' class="text-amber-500 hover:text-amber-600 dark:hover:text-amber-400 transition-colors text-xs px-1" title="Abrir Chamado de Assistência">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    </button>
                                <?php endif; ?>
                                <button onclick='abrirModalEdicao(event, <?= $p['id'] ?>, <?= jsSafe($p['cliente']) ?>, <?= jsSafe($p['data_limite']) ?>, <?= jsSafe($p['observacao']) ?>, <?= jsSafe($p['promob']) ?>, <?= jsSafe($p['corte_furacao']) ?>, <?= jsSafe($p['lista_compras']) ?>, <?= jsSafe($p['lista_ferragens']) ?>, <?= jsSafe($chk_resp) ?>, <?= jsSafe($chk_link) ?>, <?= jsSafe($med_agen) ?>, <?= jsSafe($med_data) ?>, <?= jsSafe($equipe_inst) ?>, <?= jsSafe($dt_ini_inst) ?>, <?= jsSafe($dt_fim_inst) ?>, <?= jsSafe($proj_exec) ?>, <?= jsSafe($situacao_obra) ?>)' class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors text-xs px-1" title="Editar Projeto">&#9998;</button>
                                
                                <?php if ($role === 'ADMIN'): ?>
                                    <button onclick="deletarCliente(event, <?= $p['id'] ?>)" class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors text-sm px-1" title="Apagar Projeto">&times;</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if($conf['data_label'] && !empty($p['data_limite'])): ?>
                            <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400"><?= $conf['data_label'] ?> <?= formatarData($p['data_limite']) ?></span>
                        <?php endif; ?>
                        
                        <p class="font-bold text-gray-800 dark:text-gray-100 uppercase text-xs mt-1 flex items-center">
                            <?= preg_replace('/^(\[.*?\])/', '<span class="text-blue-600 dark:text-blue-400 font-black mr-1.5">$1</span>', htmlspecialchars($p['cliente'] ?? '')) ?>
                        </p>

                        <?php if ($status_chave === 'entregue'): ?>
                            <div class="mt-2 mb-1 flex space-x-2">
                                <span class="bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-400 px-1.5 py-0.5 rounded text-[9px] font-bold border border-purple-200 dark:border-purple-800 uppercase">✅ CONCLUÍDO</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($status_chave === 'instalacao'): ?>
                            <div class="mt-2 mb-1 flex space-x-2">
                                <?php if ($situacao_obra === 'PAUSADA'): ?>
                                    <span class="bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-400 px-1.5 py-0.5 rounded text-[9px] font-bold border border-orange-200 dark:border-orange-800 uppercase animate-pulse">🚧 OBRA PAUSADA</span>
                                <?php elseif ($is_atrasado): ?>
                                    <span class="bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400 px-1.5 py-0.5 rounded text-[9px] font-bold border border-red-200 dark:border-red-800 uppercase animate-pulse">⚠️ COM ATRASO</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($p['observacao'])): ?>
                            <p class="text-[10px] mt-1 italic text-amber-600 dark:text-amber-400">
                                Obs: <?= htmlspecialchars($p['observacao']) ?>
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
                                <div class="flex justify-between items-center"><span class="text-gray-500 dark:text-gray-400">Projeto Promob:</span> <span class="<?= corChecklist($p['promob'] ?? '') ?>"><?= $p['promob'] ?? '' ?></span></div>
                                <div class="flex justify-between items-center"><span class="text-gray-500 dark:text-gray-400">Proj. Executivo:</span> <span class="<?= corChecklist($proj_exec) ?>"><?= $proj_exec ?></span></div>
                                <div class="flex justify-between items-center"><span class="text-gray-500 dark:text-gray-400">Corte:</span> <span class="<?= corChecklist($p['corte_furacao'] ?? '') ?>"><?= $p['corte_furacao'] ?? '' ?></span></div>
                                <div class="flex justify-between items-center"><span class="text-gray-500 dark:text-gray-400">Compras:</span> <span class="<?= corChecklist($p['lista_compras'] ?? '') ?>"><?= $p['lista_compras'] ?? '' ?></span></div>
                                <div class="flex justify-between items-center"><span class="text-gray-500 dark:text-gray-400">Ferragens:</span> <span class="<?= corChecklist($p['lista_ferragens'] ?? '') ?>"><?= $p['lista_ferragens'] ?? '' ?></span></div>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ============================================== -->
<!-- MODAIS DO PCP                                  -->
<!-- ============================================== -->
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
                            $codigo_cli = !empty($cli['codigo_cliente']) ? $cli['codigo_cliente'] : "CLI-" . str_pad((string)$cli['id'], 2, "0", STR_PAD_LEFT);
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
                        <option value="entregue">ENTREGUE</option>
                        <option value="instalacao">INSTALAÇÃO</option>
                        <option value="expedicao">EXPEDIÇÃO</option>
                        <option value="producao">PRODUÇÃO</option>
                        <option value="desenvolvimento" selected>DESENV. PCP</option>
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Situação da Obra</label>
                        <select id="novo_situacao" class="w-full px-2 py-1.5 text-sm border border-indigo-300 dark:border-indigo-600 dark:bg-gray-700 dark:text-white rounded font-bold">
                            <option value="NORMAL">NORMAL / EM ANDAMENTO</option>
                            <option value="PAUSADA">PAUSADA (Aguardando Obra/Cliente)</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Equipe Responsável</label>
                        <select id="novo_equipe" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase font-bold">
                            <option value="">Selecione...</option>
                            <?php foreach($equipes_montagem as $eq): ?>
                                <option value="<?= htmlspecialchars($eq) ?>"><?= htmlspecialchars($eq) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Data Início Instalação</label>
                        <input type="date" id="novo_dt_ini_inst" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                    </div>
                    <div class="lg:col-span-2">
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
                        $codigo_cli = !empty($cli['codigo_cliente']) ? $cli['codigo_cliente'] : "CLI-" . str_pad((string)$cli['id'], 2, "0", STR_PAD_LEFT);
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Situação da Obra</label>
                        <select id="edit_situacao" class="w-full px-2 py-1.5 text-sm border border-indigo-300 dark:border-indigo-600 dark:bg-gray-700 dark:text-white rounded font-bold">
                            <option value="NORMAL">NORMAL / EM ANDAMENTO</option>
                            <option value="PAUSADA">PAUSADA (Aguardando Obra/Cliente)</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Equipe Responsável</label>
                        <select id="edit_equipe" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase font-bold">
                            <option value="">Selecione...</option>
                            <?php foreach($equipes_montagem as $eq): ?>
                                <option value="<?= htmlspecialchars($eq) ?>"><?= htmlspecialchars($eq) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Data Início Instalação</label>
                        <input type="date" id="edit_dt_ini_inst" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                    </div>
                    <div class="lg:col-span-2">
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
        
        <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
            <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center uppercase tracking-wide">
                <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Histórico de Movimentações (Lead Time)
            </h4>
            
            <div id="timeline_projeto" class="space-y-3 max-h-48 overflow-y-auto pr-1 text-xs scrollbar-thin">
                <p class="italic text-gray-400 dark:text-gray-500">Nenhum histórico registrado para este projeto.</p>
            </div>
        </div>

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

<script src="assets/js/index.js?v=<?= time() ?>"></script>
<script>
    const corTexto = document.documentElement.classList.contains('dark') ? '#9ca3af' : '#475569';
    const corGrade = document.documentElement.classList.contains('dark') ? '#374151' : '#e2e8f0';

    const ctxRosca = document.getElementById('chartRosca');
    if (ctxRosca) {
        new Chart(ctxRosca.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($chart_rosca_labels) ?>,
                datasets: [{
                    data: <?= json_encode($chart_rosca_data) ?>,
                    backgroundColor: ['#6b7280', '#3b82f6', '#6366f1', '#10b981', '#a855f7'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { position: 'right', labels: { color: corTexto, font: { size: 10, weight: 'bold' } } }
                }
            }
        });
    }

    const ctxBarras = document.getElementById('chartBarras');
    if (ctxBarras) {
        new Chart(ctxBarras.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_bar_labels) ?>,
                datasets: [
                    { label: 'Obras Entregues', data: <?= json_encode($chart_bar_projetos) ?>, backgroundColor: '#10b981', borderRadius: 4 },
                    { label: 'Assist. Abertas', data: <?= json_encode($chart_bar_assistencias) ?>, backgroundColor: '#f59e0b', borderRadius: 4 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { color: corTexto, font: { size: 10, weight: 'bold' }, boxWidth: 12 } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, color: corTexto, font: { size: 10 } }, grid: { color: corGrade } },
                    x: { ticks: { color: corTexto, font: { size: 10 } }, grid: { display: false } }
                }
            }
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?>