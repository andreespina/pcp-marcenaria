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
$menu_button_text = 'MENU';
$page_actions = '
<button onclick="imprimirRelatorio()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg> IMPRIMIR ABA ATUAL
</button>';

// Estilo exclusivo para esconder botões e menus durante a impressão
$head_extras = '
<style>
    @media print {
        body { background: white !important; color: black !important; }
        .no-print { display: none !important; }
        .tab-content.hidden { display: none !important; } /* Garante que só a aba ativa seja impressa */
        .tab-content { display: block !important; }
        .shadow-sm { box-shadow: none !important; border: 1px solid #ddd !important; }
        * { transition: none !important; }
    }
</style>
';

require_once 'includes/header.php';
?>

<div class="mb-6 border-b border-gray-200 dark:border-gray-700 no-print">
    <ul class="flex flex-nowrap overflow-x-auto text-sm font-medium text-center" role="tablist">
        <li class="mr-2" role="presentation">
            <button id="btn_geral" onclick="mudarAba('geral')" class="tab-btn active px-4 py-3 rounded-t-lg border-b-2 text-blue-600 border-blue-600 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-400 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                Resumo Geral
            </button>
        </li>
        <li class="mr-2" role="presentation">
            <button id="btn_projetos" onclick="mudarAba('projetos')" class="tab-btn px-4 py-3 rounded-t-lg border-b-2 border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                Projetos
            </button>
        </li>
        <li class="mr-2" role="presentation">
            <button id="btn_assistencias" onclick="mudarAba('assistencias')" class="tab-btn px-4 py-3 rounded-t-lg border-b-2 border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                Assistências & Faturamento
            </button>
        </li>
        <li class="mr-2" role="presentation">
            <button id="btn_almoxarifado" onclick="mudarAba('almoxarifado')" class="tab-btn px-4 py-3 rounded-t-lg border-b-2 border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                Almoxarifado
            </button>
        </li>
        <li role="presentation">
            <button id="btn_clientes" onclick="mudarAba('clientes')" class="tab-btn px-4 py-3 rounded-t-lg border-b-2 border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                Clientes Base
            </button>
        </li>
    </ul>
</div>

<div id="conteudo_geral" class="tab-content">
    <div class="mb-4">
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Visão Geral da Operação</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Métricas consolidadas de todos os departamentos.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow-sm border border-blue-200 dark:border-blue-800 border-l-4">
            <p class="text-xs font-bold text-blue-500 dark:text-blue-400 uppercase tracking-wide">Projetos em Andamento</p>
            <p class="text-3xl font-black text-gray-800 dark:text-white mt-1"><?= $proj_andamento ?></p>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 p-5 rounded-lg shadow-sm border border-red-200 dark:border-red-800 border-l-4">
            <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-wide">Obras Atrasadas</p>
            <p class="text-3xl font-black text-red-700 dark:text-red-400 mt-1"><?= $proj_atrasados ?></p>
        </div>
        <div class="bg-amber-50 dark:bg-amber-900/20 p-5 rounded-lg shadow-sm border border-amber-200 dark:border-amber-800 border-l-4">
            <p class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wide">Assistências Abertas</p>
            <p class="text-3xl font-black text-amber-700 dark:text-amber-400 mt-1"><?= $ast_pendentes + $ast_agendadas ?></p>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 p-5 rounded-lg shadow-sm border border-purple-200 dark:border-purple-800 border-l-4">
            <p class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wide">Faturado em Assist.</p>
            <p class="text-3xl font-black text-purple-700 dark:text-purple-400 mt-1">R$ <?= number_format($valor_faturado, 2, ',', '.') ?></p>
        </div>
        <div class="bg-orange-50 dark:bg-orange-900/20 p-5 rounded-lg shadow-sm border border-orange-200 dark:border-orange-800 border-l-4">
            <p class="text-xs font-bold text-orange-600 dark:text-orange-400 uppercase tracking-wide">Itens Estoque Crítico</p>
            <p class="text-3xl font-black text-orange-700 dark:text-orange-400 mt-1"><?= $almox_critico ?></p>
        </div>
        <div class="bg-pink-50 dark:bg-pink-900/20 p-5 rounded-lg shadow-sm border border-pink-200 dark:border-pink-800 border-l-4">
            <p class="text-xs font-bold text-pink-600 dark:text-pink-400 uppercase tracking-wide">Total de Clientes</p>
            <p class="text-3xl font-black text-pink-700 dark:text-pink-400 mt-1"><?= $cli_total ?></p>
        </div>
    </div>
</div>

<div id="conteudo_projetos" class="tab-content hidden">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total de Projetos</p>
            <p class="text-2xl font-black text-gray-800 dark:text-white mt-1"><?= $proj_total ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-blue-200 dark:border-blue-800">
            <p class="text-xs font-bold text-blue-500 dark:text-blue-400 uppercase tracking-wide">Em Andamento</p>
            <p class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1"><?= $proj_andamento ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-green-200 dark:border-green-800">
            <p class="text-xs font-bold text-green-500 dark:text-green-400 uppercase tracking-wide">Concluídos</p>
            <p class="text-2xl font-black text-green-600 dark:text-green-400 mt-1"><?= $proj_concluidos ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-red-200 dark:border-red-800">
            <p class="text-xs font-bold text-red-500 dark:text-red-400 uppercase tracking-wide">Atrasados</p>
            <p class="text-2xl font-black text-red-600 dark:text-red-400 mt-1"><?= $proj_atrasados ?></p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-6 py-3 font-bold">ID</th>
                        <th class="px-6 py-3 font-bold">Cliente</th>
                        <th class="px-6 py-3 font-bold">Fase Atual</th>
                        <th class="px-6 py-3 font-bold">Data Limite</th>
                        <th class="px-6 py-3 font-bold">Equipe de Instalação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($projetos)): ?>
                        <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">Nenhum projeto.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($projetos as $p): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-gray-700 dark:text-gray-200">
                            <td class="px-6 py-3 font-bold text-gray-500 dark:text-gray-400">#<?= $p['id'] ?></td>
                            <td class="px-6 py-3 font-bold uppercase text-gray-800 dark:text-gray-100"><?= htmlspecialchars($p['cliente']) ?></td>
                            <td class="px-6 py-3"><span class="px-2 py-1 rounded text-[10px] font-bold uppercase border border-gray-200 dark:border-gray-600"><?= nomeStatus($p['status']) ?></span></td>
                            <td class="px-6 py-3 font-medium"><?= formatarData($p['data_limite']) ?></td>
                            <td class="px-6 py-3 uppercase text-xs text-indigo-600 dark:text-indigo-400 font-semibold"><?= htmlspecialchars($p['equipe_instalacao']) ?: '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="conteudo_assistencias" class="tab-content hidden">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-amber-200 dark:border-amber-800">
            <p class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wide">Total Solicitadas</p>
            <p class="text-2xl font-black text-amber-700 dark:text-amber-400 mt-1"><?= $ast_total ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-emerald-200 dark:border-emerald-800">
            <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Garantia (Custo 0)</p>
            <p class="text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-1"><?= $ast_garantia ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-purple-200 dark:border-purple-800">
            <p class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wide">Qtd Faturadas</p>
            <p class="text-2xl font-black text-purple-700 dark:text-purple-400 mt-1"><?= $ast_faturadas ?></p>
        </div>
        <div class="bg-purple-600 p-4 rounded-lg shadow-sm border border-purple-800 text-white">
            <p class="text-xs font-bold text-purple-200 uppercase tracking-wide">Total Recebido</p>
            <p class="text-2xl font-black mt-1">R$ <?= number_format($valor_faturado, 2, ',', '.') ?></p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-6 py-3 font-bold">OS #</th>
                        <th class="px-6 py-3 font-bold">Cliente</th>
                        <th class="px-6 py-3 font-bold">Data Solicitação</th>
                        <th class="px-6 py-3 font-bold">Status</th>
                        <th class="px-6 py-3 font-bold">Tipo</th>
                        <th class="px-6 py-3 font-bold">Valor (R$)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($assistencias)): ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">Nenhuma assistência.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($assistencias as $a): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-gray-700 dark:text-gray-200">
                            <td class="px-6 py-3 font-bold text-gray-500 dark:text-gray-400">AST #<?= $a['id'] ?></td>
                            <td class="px-6 py-3 font-bold uppercase"><?= htmlspecialchars($a['cliente']) ?></td>
                            <td class="px-6 py-3 text-xs text-gray-500"><?= formatarData($a['data_solicitacao']) ?></td>
                            <td class="px-6 py-3"><span class="px-2 py-1 rounded text-[10px] font-bold uppercase border border-gray-200 dark:border-gray-600"><?= nomeStatus($a['status']) ?></span></td>
                            <td class="px-6 py-3 font-bold text-[11px] <?= ($a['tipo_cobranca'] === 'FATURADA') ? 'text-purple-600 dark:text-purple-400' : 'text-emerald-600 dark:text-emerald-400' ?>">
                                <?= $a['tipo_cobranca'] ?? 'GARANTIA' ?>
                            </td>
                            <td class="px-6 py-3 font-medium">
                                <?= ($a['tipo_cobranca'] === 'FATURADA') ? 'R$ ' . number_format((float)$a['valor_cobrado'], 2, ',', '.') : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="conteudo_almoxarifado" class="tab-content hidden">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total de Itens Cadastrados</p>
            <p class="text-2xl font-black text-gray-800 dark:text-white mt-1"><?= $almox_total ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-green-200 dark:border-green-800">
            <p class="text-xs font-bold text-green-500 dark:text-green-400 uppercase tracking-wide">Estoque Saudável</p>
            <p class="text-2xl font-black text-green-600 dark:text-green-400 mt-1"><?= $almox_ok ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-red-200 dark:border-red-800">
            <p class="text-xs font-bold text-red-500 dark:text-red-400 uppercase tracking-wide">Crítico / Em Falta</p>
            <p class="text-2xl font-black text-red-600 dark:text-red-400 mt-1"><?= $almox_critico ?></p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-6 py-3 font-bold">Item</th>
                        <th class="px-6 py-3 font-bold">Categoria</th>
                        <th class="px-6 py-3 font-bold">Estoque Atual</th>
                        <th class="px-6 py-3 font-bold">Mínimo de Segurança</th>
                        <th class="px-6 py-3 font-bold text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($almoxarifado)): ?>
                        <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">Nenhum item.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($almoxarifado as $al): 
                        $critico = ($al['quantidade'] <= $al['quantidade_minima']);
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-gray-700 dark:text-gray-200">
                            <td class="px-6 py-3 font-bold uppercase text-gray-800 dark:text-gray-100"><?= htmlspecialchars($al['nome_item']) ?></td>
                            <td class="px-6 py-3 text-xs uppercase text-gray-500"><?= htmlspecialchars($al['categoria']) ?></td>
                            <td class="px-6 py-3 font-black <?= $critico ? 'text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-gray-200' ?>"><?= (float)$al['quantidade'] ?> <?= $al['unidade_medida'] ?></td>
                            <td class="px-6 py-3 font-medium text-gray-500"><?= (float)$al['quantidade_minima'] ?></td>
                            <td class="px-6 py-3 text-center">
                                <?php if($critico): ?>
                                    <span class="bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 px-2 py-1 rounded text-[10px] font-bold border border-red-200 uppercase">Comprar</span>
                                <?php else: ?>
                                    <span class="bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400 px-2 py-1 rounded text-[10px] font-bold border border-green-200 uppercase">Saudável</span>
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
    <div class="mb-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-pink-200 dark:border-pink-800 flex items-center justify-between">
        <div>
            <p class="text-xs font-bold text-pink-600 dark:text-pink-400 uppercase tracking-wide">Base de Clientes</p>
            <p class="text-2xl font-black text-pink-700 dark:text-pink-300 mt-1"><?= $cli_total ?> Contratos Cadastrados</p>
        </div>
        <div class="text-5xl opacity-20">🏢</div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-6 py-3 font-bold">Código</th>
                        <th class="px-6 py-3 font-bold">Nome / Contrato</th>
                        <th class="px-6 py-3 font-bold">Telefone Principal</th>
                        <th class="px-6 py-3 font-bold">Cidade</th>
                        <th class="px-6 py-3 font-bold">Arquiteto(a)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($clientes)): ?>
                        <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">Nenhum cliente.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($clientes as $c): 
                        $codigo = !empty($c['codigo_cliente']) ? $c['codigo_cliente'] : "CLI-" . str_pad($c['id'], 2, "0", STR_PAD_LEFT);
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-gray-700 dark:text-gray-200">
                            <td class="px-6 py-3 font-bold text-blue-600 dark:text-blue-400 text-xs"><?= htmlspecialchars($codigo) ?></td>
                            <td class="px-6 py-3 font-bold uppercase text-gray-800 dark:text-gray-100"><?= htmlspecialchars($c['nome_contrato']) ?></td>
                            <td class="px-6 py-3 font-medium"><?= htmlspecialchars($c['telefone'] ?: $c['whatsapp']) ?: '-' ?></td>
                            <td class="px-6 py-3 uppercase text-xs"><?= htmlspecialchars($c['cidade']) ?: '-' ?></td>
                            <td class="px-6 py-3 uppercase text-xs text-indigo-600 dark:text-indigo-400 font-semibold"><?= htmlspecialchars($c['arquiteto_nome']) ?: '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="assets/js/relatorios.js"></script>

<?php require_once 'includes/footer.php'; ?>