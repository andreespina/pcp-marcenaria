<?php
// financeiro.php
require_once 'includes/auth.php';
protegerPagina();

require_once 'config/conexao.php';

$mes_atual = date('m');
$ano_atual = date('Y');
$hoje = date('Y-m-d');

try {
    // 1. Busca todos os lançamentos
    $stmt = $pdo->prepare("SELECT * FROM financeiro ORDER BY data_vencimento ASC");
    $stmt->execute();
    $lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Busca Clientes e Fornecedores para Mapeamento
    $stmt_cli = $pdo->query("SELECT id, nome_contrato as nome FROM clientes_cadastro ORDER BY nome_contrato ASC");
    $clientes = $stmt_cli->fetchAll(PDO::FETCH_ASSOC);

    $stmt_forn = $pdo->query("SELECT id, nome_fantasia as nome FROM fornecedores ORDER BY nome_fantasia ASC");
    $fornecedores = $stmt_forn->fetchAll(PDO::FETCH_ASSOC);

    $map_clientes = []; foreach($clientes as $c) $map_clientes[$c['id']] = $c['nome'];
    $map_fornecedores = []; foreach($fornecedores as $f) $map_fornecedores[$f['id']] = $f['nome'];

    // --- NOVO: Buscando Cadastros Base para o Financeiro ---
    $stmt_cad = $pdo->query("SELECT tipo, nome FROM cadastros_base WHERE tipo IN ('PLANO_CONTA', 'FORMA_PAGAMENTO') ORDER BY nome ASC");
    $cadastros_base = $stmt_cad->fetchAll(PDO::FETCH_ASSOC);
    
    $planos_conta = [];
    $formas_pagamento = [];
    
    foreach($cadastros_base as $cad) {
        if($cad['tipo'] == 'PLANO_CONTA') $planos_conta[] = $cad['nome'];
        if($cad['tipo'] == 'FORMA_PAGAMENTO') $formas_pagamento[] = $cad['nome'];
    }
    // -------------------------------------------------------

    // 3. Cálculos do Dashboard e Agrupamento por Entidade
    $receitas_pagas = 0; $receitas_pendentes = 0;
    $despesas_pagas = 0; $despesas_pendentes = 0;
    $total_atrasado = 0;

    $agrupado = []; // Matriz que vai guardar os lançamentos agrupados

    foreach ($lancamentos as $l) {
        $valor = (float) $l['valor'];
        
        // Dashboard
        if ($l['tipo'] === 'RECEITA') {
            if ($l['status'] === 'PAGO') $receitas_pagas += $valor;
            else $receitas_pendentes += $valor;
        } else {
            if ($l['status'] === 'PAGO') $despesas_pagas += $valor;
            else $despesas_pendentes += $valor;
        }

        if ($l['status'] === 'PENDENTE' && $l['data_vencimento'] < $hoje && $l['tipo'] === 'DESPESA') {
            $total_atrasado += $valor;
        }

        // Lógica de Agrupamento
        $nome_entidade = 'DIVERSOS / NÃO INFORMADO';
        
        if ($l['entidade_tipo'] === 'CLIENTE' && isset($map_clientes[$l['entidade_id']])) {
            $nome_entidade = $map_clientes[$l['entidade_id']];
        } elseif ($l['entidade_tipo'] === 'FORNECEDOR' && isset($map_fornecedores[$l['entidade_id']])) {
            $nome_entidade = $map_fornecedores[$l['entidade_id']];
        } elseif (!empty($l['cliente_fornecedor'])) {
            $nome_entidade = $l['cliente_fornecedor']; 
        }

        // Cria a pasta da entidade se não existir
        if (!isset($agrupado[$nome_entidade])) {
            $agrupado[$nome_entidade] = [
                'nome' => $nome_entidade,
                'receitas' => 0,
                'despesas' => 0,
                'lancamentos' => []
            ];
        }
        
        // Soma os valores dentro do grupo e guarda o lançamento
        if ($l['tipo'] === 'RECEITA') $agrupado[$nome_entidade]['receitas'] += $valor;
        else $agrupado[$nome_entidade]['despesas'] += $valor;
        
        $agrupado[$nome_entidade]['lancamentos'][] = $l;
    }

    // Ordena os grupos em ordem alfabética
    ksort($agrupado);

    $saldo_atual = $receitas_pagas - $despesas_pagas;
    $saldo_previsto = ($receitas_pagas + $receitas_pendentes) - ($despesas_pagas + $despesas_pendentes);

    // --- LÓGICA DE DADOS PARA OS GRÁFICOS ---
    $chart_rosca_labels = ['Receitas Pagas', 'A Receber', 'Despesas Pagas', 'A Pagar'];
    $chart_rosca_data = [$receitas_pagas, $receitas_pendentes, $despesas_pagas, $despesas_pendentes];

    $meses_nomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    $chart_bar_labels = []; $chart_bar_receitas = []; $chart_bar_despesas = [];

    for ($i = 5; $i >= 0; $i--) {
        $data_alvo = strtotime("-$i months");
        $mes_num = date('m', $data_alvo);
        $ano_num = date('Y', $data_alvo);

        $chart_bar_labels[] = $meses_nomes[(int)$mes_num - 1] . '/' . substr($ano_num, 2);
        
        $stmt_rec = $pdo->prepare("SELECT SUM(valor) FROM financeiro WHERE tipo = 'RECEITA' AND MONTH(data_vencimento) = ? AND YEAR(data_vencimento) = ?");
        $stmt_rec->execute([$mes_num, $ano_num]);
        $chart_bar_receitas[] = (float)$stmt_rec->fetchColumn();

        $stmt_des = $pdo->prepare("SELECT SUM(valor) FROM financeiro WHERE tipo = 'DESPESA' AND MONTH(data_vencimento) = ? AND YEAR(data_vencimento) = ?");
        $stmt_des->execute([$mes_num, $ano_num]);
        $chart_bar_despesas[] = (float)$stmt_des->fetchColumn();
    }

} catch (\PDOException $e) { die("Erro na consulta: " . $e->getMessage()); }

function formatarData($data) { if (!$data) return '-'; return date('d/m/Y', strtotime($data)); }
function jsSafe($val) { return htmlspecialchars(json_encode($val), ENT_QUOTES, 'UTF-8'); }

$page_title = 'GESTÃO FINANCEIRA';
$page_subtitle = 'Controle de Caixa, Contas a Pagar e Receber';
$main_class = 'flex-1'; 
$menu_button_text = 'MENU';
$page_actions = '
<button onclick="abrirModalLancamento()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
    NOVO LANÇAMENTO
</button>';

$head_extras = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .dark body { background-color: #1a1e2b !important; }
</style>';

require_once 'includes/header.php';
?>

<div class="flex flex-col gap-6">

    <!-- DASHBOARD MÉTRICAS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex items-center">
            <div class="p-3 rounded-full <?= $saldo_atual >= 0 ? 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400' ?> mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Saldo Realizado (Caixa)</p>
                <p class="text-2xl font-black <?= $saldo_atual >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?> mt-0.5">
                    R$ <?= number_format($saldo_atual, 2, ',', '.') ?>
                </p>
            </div>
        </div>
        <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex items-center">
            <div class="p-3 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Saldo Previsto</p>
                <p class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-0.5">R$ <?= number_format($saldo_previsto, 2, ',', '.') ?></p>
            </div>
        </div>
        <div class="bg-emerald-50 dark:bg-[#1c2333]/50 p-4 rounded-lg shadow-sm border border-emerald-200 dark:border-emerald-800/30 flex items-center">
            <div class="p-3 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-emerald-600 dark:text-emerald-500 uppercase tracking-wide">A Receber (Pendente)</p>
                <p class="text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-0.5">R$ <?= number_format($receitas_pendentes, 2, ',', '.') ?></p>
            </div>
        </div>
        <div class="bg-red-50 dark:bg-[#1c2333]/50 p-4 rounded-lg shadow-sm border <?= $total_atrasado > 0 ? 'border-red-400 animate-pulse' : 'border-red-200 dark:border-red-800/30' ?> flex items-center">
            <div class="p-3 rounded-full bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-red-600 dark:text-red-500 uppercase tracking-wide">A Pagar (Pendente)</p>
                <p class="text-2xl font-black text-red-700 dark:text-red-400 mt-0.5 flex flex-col">
                    <span>R$ <?= number_format($despesas_pendentes, 2, ',', '.') ?></span>
                    <?php if($total_atrasado > 0): ?>
                        <span class="text-[10px] text-red-500 font-bold tracking-wider mt-0.5">R$ <?= number_format($total_atrasado, 2, ',', '.') ?> ATRASADO</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- DASHBOARD GRÁFICOS -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Gráfico Rosca -->
        <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex flex-col items-center justify-center h-[280px]">
            <h3 class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest w-full text-center mb-2">Status Geral Financeiro</h3>
            <div class="relative w-full h-56 flex justify-center">
                <canvas id="chartRoscaFin"></canvas>
            </div>
        </div>
        <!-- Gráfico Barras -->
        <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex flex-col items-center justify-center h-[280px]">
            <h3 class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest w-full text-center mb-2">Fluxo de Caixa (Últimos 6 Meses)</h3>
            <div class="relative w-full h-56 flex justify-center">
                <canvas id="chartBarFin"></canvas>
            </div>
        </div>
    </div>

    <!-- LISTAGEM (ACORDEÃO AGRUPADO) -->
    <div class="bg-white dark:bg-[#222736] rounded-lg border border-gray-200 dark:border-[#2a3142] shadow-sm overflow-hidden flex flex-col">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex flex-col sm:flex-row justify-between items-center gap-3">
            <h3 class="font-bold text-gray-700 dark:text-gray-200 text-sm uppercase tracking-wider">Carteira de Contratos e Lançamentos</h3>
            <div class="relative w-full sm:w-1/3">
                <input type="text" id="filtro_financeiro" onkeyup="filtrarTabela()" placeholder="Buscar cliente ou fornecedor..." class="w-full px-4 py-1.5 pl-9 border border-gray-300 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white rounded text-sm focus:ring-2 focus:ring-blue-500 transition-colors">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
        
        <div class="p-4 space-y-3 bg-gray-50 dark:bg-[#1a1e2b]" style="max-height: calc(100vh - 400px); overflow-y: auto;">
            <?php if (empty($agrupado)): ?>
                <p class="text-center text-gray-500 dark:text-gray-400 italic py-8">Nenhum lançamento encontrado no sistema.</p>
            <?php endif; ?>

            <?php foreach($agrupado as $g): 
                $id_grupo = md5($g['nome']);
            ?>
                <div class="grupo-financeiro bg-white dark:bg-[#222736] rounded-lg border border-gray-200 dark:border-[#2a3142] shadow-sm overflow-hidden transition-colors duration-300">
                    <!-- CABEÇALHO DO ACORDEÃO -->
                    <div class="flex justify-between items-center p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors select-none" onclick="toggleGrupo('<?= $id_grupo ?>', this)">
                        <div class="flex items-center space-x-3 w-1/2">
                            <svg class="w-5 h-5 text-gray-400 transform transition-transform duration-200 icon-seta" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            <h3 class="font-bold text-gray-800 dark:text-gray-100 uppercase text-sm truncate texto-pesquisa" title="<?= htmlspecialchars($g['nome']) ?>"><?= htmlspecialchars($g['nome']) ?></h3>
                            <span class="text-[10px] font-bold bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full whitespace-nowrap"><?= count($g['lancamentos']) ?> Lanç.</span>
                        </div>
                        <div class="flex space-x-4 text-xs font-bold text-right">
                            <?php if($g['receitas'] > 0): ?><span class="text-emerald-600 dark:text-emerald-400 hidden sm:inline">Entradas: R$ <?= number_format($g['receitas'], 2, ',', '.') ?></span><?php endif; ?>
                            <?php if($g['despesas'] > 0): ?><span class="text-red-600 dark:text-red-400 hidden sm:inline">Saídas: R$ <?= number_format($g['despesas'], 2, ',', '.') ?></span><?php endif; ?>
                        </div>
                    </div>

                    <!-- CORPO (TABELA DE PARCELAS) -->
                    <div id="body-<?= $id_grupo ?>" class="hidden border-t border-gray-200 dark:border-gray-700">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 font-semibold text-[10px] uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                                    <tr>
                                        <th class="px-4 py-2">Vencimento</th>
                                        <th class="px-4 py-2">Descrição / Ref.</th>
                                        <th class="px-4 py-2 text-right">Valor</th>
                                        <th class="px-4 py-2 text-center">Status</th>
                                        <th class="px-4 py-2 text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                                    <?php foreach($g['lancamentos'] as $l): 
                                        $is_atrasado = ($l['status'] === 'PENDENTE' && $l['data_vencimento'] < $hoje && $l['tipo'] === 'DESPESA');
                                        $cor_valor = $l['tipo'] === 'RECEITA' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
                                        $sinal = $l['tipo'] === 'RECEITA' ? '+' : '-';
                                    ?>
                                    <tr class="hover:bg-blue-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="px-4 py-2.5 font-medium text-xs <?= $is_atrasado ? 'text-red-500 font-bold' : 'text-gray-700 dark:text-gray-300' ?>">
                                            <?= formatarData($l['data_vencimento']) ?>
                                        </td>
                                        <td class="px-4 py-2.5 text-xs font-bold text-gray-800 dark:text-gray-200 uppercase">
                                            <?= htmlspecialchars($l['descricao']) ?>
                                            <?php if($l['num_parcelas'] > 1): ?>
                                                <span class="text-[9px] text-blue-500 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 px-1 ml-1 rounded">PARCELADO</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-2.5 font-black text-xs text-right <?= $cor_valor ?>">
                                            <?= $sinal ?> R$ <?= number_format($l['valor'], 2, ',', '.') ?>
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <?php if ($l['status'] === 'PAGO'): ?>
                                                <span class="text-[9px] font-bold text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30 px-1.5 py-0.5 rounded border border-green-200 dark:border-green-800/50">BAIXADO</span>
                                            <?php elseif ($is_atrasado): ?>
                                                <span class="text-[9px] font-bold text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30 px-1.5 py-0.5 rounded border border-red-200 dark:border-red-800/50 animate-pulse">ATRASADO</span>
                                            <?php else: ?>
                                                <span class="text-[9px] font-bold text-amber-600 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/30 px-1.5 py-0.5 rounded border border-amber-200 dark:border-amber-800/50">PENDENTE</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-2.5 text-center space-x-1">
                                            <?php if ($l['status'] === 'PENDENTE'): ?>
                                                <button onclick='abrirModalBaixa(<?= $l['id'] ?>, <?= jsSafe($l['descricao']) ?>, <?= jsSafe($l['tipo']) ?>)' class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 font-bold bg-green-50 dark:bg-green-900/20 p-1 rounded transition-colors" title="Dar Baixa / Pagar">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                </button>
                                            <?php endif; ?>
                                            <button onclick='abrirModalEdicao(<?= jsSafe($l) ?>)' class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-bold bg-blue-50 dark:bg-blue-900/20 p-1 rounded transition-colors" title="Editar">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </button>
                                            <button onclick="deletarLancamento(<?= $l['id'] ?>)" class="text-red-400 hover:text-red-600 dark:text-red-400 font-bold bg-red-50 dark:bg-red-900/20 p-1 rounded transition-colors" title="Apagar">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- ========================================== -->
<!-- MODAIS                                     -->
<!-- ========================================== -->
<div id="modalLancamento" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-4xl p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300 max-h-[95vh] overflow-y-auto" id="modalLancamentoConteudo">
        
        <div class="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
            <h3 id="modalTitulo" class="text-lg font-bold text-gray-800 dark:text-gray-100">Novo Lançamento Financeiro</h3>
            <button type="button" onclick="fecharModalLancamento()" class="text-gray-400 hover:text-gray-800 dark:hover:text-white text-2xl font-bold">&times;</button>
        </div>
        
        <form id="formLancamento" onsubmit="salvarLancamento(event)">
            <input type="hidden" id="fin_id">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="col-span-1">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Lançamento</label>
                    <select id="fin_tipo" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 font-bold uppercase">
                        <option value="RECEITA" class="text-green-600 dark:text-green-400">Receita (Entrada)</option>
                        <option value="DESPESA" class="text-red-600 dark:text-red-400">Despesa (Saída)</option>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Descrição / Referência</label>
                    <input type="text" id="fin_descricao" required placeholder="Ex: Pagamento Fornecedor MDF" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase">
                </div>

                <div class="col-span-1">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Vincular a:</label>
                    <select id="sel_tipo_entidade" onchange="atualizarEntidades()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase font-semibold">
                        <option value="CLIENTE">Cliente</option>
                        <option value="FORNECEDOR">Fornecedor</option>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Selecione o Cliente / Fornecedor</label>
                    <select id="sel_entidade_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase">
                        <option value="">Selecione...</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Documento</label>
                    <select id="fin_tipo_documento" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase">
                        <option value="NF">Nota Fiscal (NF)</option>
                        <option value="PEDIDO">Pedido Interno</option>
                        <option value="RECIBO">Recibo</option>
                        <option value="GUIA">Guia / Imposto</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nº Documento</label>
                    <input type="text" id="fin_num_documento" placeholder="Ex: 001234" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Forma de Pagamento</label>
                    <select id="fin_forma_pagamento" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase focus:ring-2 focus:ring-blue-500">
                        <option value="">Selecione...</option>
                        <?php foreach($formas_pagamento as $fp): ?>
                            <option value="<?= htmlspecialchars($fp) ?>"><?= htmlspecialchars($fp) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Valor Total (R$)</label>
                    <input type="number" step="0.01" id="fin_valor" required placeholder="0.00" onkeyup="calcularParcelas()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded font-black text-lg text-blue-600 dark:text-blue-400">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Qtd. Parcelas</label>
                    <input type="number" id="fin_parcelas" value="1" min="1" onkeyup="calcularParcelas()" onchange="calcularParcelas()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Valor da Parcela (R$)</label>
                    <input type="text" id="fin_valor_parcela" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-600 dark:text-white rounded font-bold cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Data do Documento</label>
                    <input type="date" id="fin_data_documento" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded text-sm">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-red-600 dark:text-red-400 mb-1">Data de Vencimento</label>
                    <input type="date" id="fin_vencimento" required class="w-full px-3 py-2 border border-red-300 dark:border-red-800/50 dark:bg-gray-700 dark:text-white rounded border-l-4 border-l-red-500 font-bold text-sm">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Plano de Contas</label>
                    <select id="fin_plano_contas" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase focus:ring-2 focus:ring-blue-500">
                        <option value="">Selecione...</option>
                        <?php foreach($planos_conta as $pc): ?>
                            <option value="<?= htmlspecialchars($pc) ?>"><?= htmlspecialchars($pc) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Centro de Custo</label>
                    <input type="text" id="fin_centro_custo" placeholder="Ex: Marcenaria / Showroom" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Observações Internas</label>
                <textarea id="fin_observacao" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            
            <div class="flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4 mt-6">
                <button type="button" onclick="fecharModalLancamento()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-[#1e3a8a] dark:bg-blue-600 hover:bg-blue-800 text-white rounded font-bold transition shadow-sm">Salvar Lançamento</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Baixar (Pagar/Receber) -->
<div id="modalBaixa" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalBaixaConteudo">
        <div class="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
            <h3 class="text-lg font-bold text-green-600 dark:text-green-400">Confirmar Liquidação</h3>
            <button type="button" onclick="fecharModalBaixa()" class="text-gray-400 hover:text-gray-800 dark:hover:text-white text-2xl font-bold">&times;</button>
        </div>
        
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">Deseja dar baixa no lançamento <strong id="lbl_baixa_desc" class="uppercase text-gray-900 dark:text-white"></strong>?</p>
        
        <form id="formBaixa" onsubmit="salvarBaixa(event)">
            <input type="hidden" id="baixa_id">
            <div class="mb-6">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Data do Pagamento/Recebimento</label>
                <input type="date" id="baixa_data" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-green-500 font-bold">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="fecharModalBaixa()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded font-bold transition shadow-sm">Confirmar Baixa</button>
            </div>
        </form>
    </div>
</div>

<script>
    window.clientes_data = <?= json_encode($clientes, JSON_UNESCAPED_UNICODE) ?>;
    window.fornecedores_data = <?= json_encode($fornecedores, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="assets/js/financeiro.js?v=<?= time() ?>"></script>

<!-- Script para a Geração dos Gráficos Interativos Chart.js -->
<script>
    const corTexto = document.documentElement.classList.contains('dark') ? '#9ca3af' : '#475569';
    const corGrade = document.documentElement.classList.contains('dark') ? '#374151' : '#e2e8f0';

    // Gráfico de Rosca - Status Financeiro
    const ctxRosca = document.getElementById('chartRoscaFin');
    if (ctxRosca) {
        new Chart(ctxRosca.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($chart_rosca_labels) ?>,
                datasets: [{
                    data: <?= json_encode($chart_rosca_data) ?>,
                    backgroundColor: ['#10b981', '#34d399', '#f59e0b', '#ef4444'], // Verde Forte, Verde Claro, Laranja, Vermelho
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'right', labels: { color: corTexto, font: { size: 10, weight: 'bold' }, boxWidth: 12 } }
                }
            }
        });
    }

    // Gráfico de Barras - Fluxo de 6 Meses
    const ctxBar = document.getElementById('chartBarFin');
    if (ctxBar) {
        new Chart(ctxBar.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_bar_labels) ?>,
                datasets: [
                    { label: 'Receitas', data: <?= json_encode($chart_bar_receitas) ?>, backgroundColor: '#10b981', borderRadius: 3 },
                    { label: 'Despesas', data: <?= json_encode($chart_bar_despesas) ?>, backgroundColor: '#ef4444', borderRadius: 3 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { color: corTexto, font: { size: 10, weight: 'bold' }, boxWidth: 12 } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1000, color: corTexto, font: { size: 10 } }, grid: { color: corGrade } },
                    x: { ticks: { color: corTexto, font: { size: 10 } }, grid: { display: false } }
                }
            }
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?>