<?php
// administrativo.php
require_once 'includes/auth.php';
protegerPagina(); 
require_once 'config/conexao.php';

try {
    // Busca clientes para o Modal de Novo Contrato Manual
    $stmt_cli = $pdo->query("SELECT id, codigo_cliente, nome_contrato FROM clientes_cadastro ORDER BY nome_contrato ASC");
    $clientes_db = $stmt_cli->fetchAll(PDO::FETCH_ASSOC);

    // Busca todos os contratos
    $stmt = $pdo->query("SELECT * FROM administrativo_contratos ORDER BY id DESC");
    $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_valor = 0; $pendentes = 0; $a_faturar = 0; $pagos = 0;

    foreach ($contratos as $c) {
        $total_valor += $c['valor'];
        if ($c['status_contrato'] === 'PENDENTE') $pendentes++;
        if ($c['status_financeiro'] === 'A FATURAR') $a_faturar++;
        if ($c['status_financeiro'] === 'PAGO') $pagos++;
    }
} catch (\PDOException $e) { 
    die("Erro ao carregar dados: " . $e->getMessage()); 
}

$page_title = 'ADMINISTRATIVO & FINANCEIRO';
$page_subtitle = 'SBG Móveis & Design';
$main_class = 'flex-1'; 
$menu_button_text = 'MENU';
$page_actions = '
<button onclick="abrirModalNovoContrato()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
    NOVO CONTRATO MANUAL
</button>';

$head_extras = '
<style>
    .dark body { background-color: #1a1e2b !important; }
    .table-container { height: calc(100vh - 280px); overflow-y: auto; }
    .table-container::-webkit-scrollbar { width: 6px; }
    .table-container::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
    .dark .table-container::-webkit-scrollbar-thumb { background-color: #4b5563; }
</style>';

require_once 'includes/header.php';
?>

<div class="flex flex-col gap-6">
    <div class="bg-white dark:bg-[#222736] rounded-lg border border-gray-200 dark:border-[#2a3142] shadow-sm p-4">
        <h2 class="text-blue-700 dark:text-blue-400 font-bold mb-4 flex items-center text-lg tracking-wide">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Resumo de Faturamento (Projetos Fechados)
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 dark:bg-transparent border border-gray-300 dark:border-gray-600 rounded p-4 shadow-sm">
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Volume Negociado</p>
                <p class="text-2xl font-black text-gray-800 dark:text-white">R$ <?= number_format($total_valor, 2, ',', '.') ?></p>
            </div>
            <div class="bg-blue-50 dark:bg-transparent border border-blue-300 dark:border-blue-800 rounded p-4 shadow-sm">
                <p class="text-[11px] font-bold text-blue-600 dark:text-blue-400 uppercase mb-1">Contratos Pendentes</p>
                <p class="text-2xl font-black text-blue-700 dark:text-blue-300"><?= $pendentes ?></p>
            </div>
            <div class="bg-yellow-50 dark:bg-transparent border border-yellow-300 dark:border-yellow-800 rounded p-4 shadow-sm">
                <p class="text-[11px] font-bold text-yellow-600 dark:text-yellow-400 uppercase mb-1">A Faturar / Cobrar</p>
                <p class="text-2xl font-black text-yellow-700 dark:text-yellow-300"><?= $a_faturar ?></p>
            </div>
            <div class="bg-emerald-50 dark:bg-transparent border border-emerald-300 dark:border-emerald-800 rounded p-4 shadow-sm">
                <p class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase mb-1">Projetos Pagos</p>
                <p class="text-2xl font-black text-emerald-700 dark:text-emerald-300"><?= $pagos ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-[#222736] rounded-lg border border-gray-200 dark:border-[#2a3142] shadow-sm overflow-hidden flex flex-col">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
            <h3 class="font-bold text-gray-700 dark:text-gray-200 text-sm uppercase tracking-wider">Gestão de Contratos e Recebimentos</h3>
        </div>
        
        <div class="overflow-x-auto table-container">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-10 font-bold">
                    <tr>
                        <th class="px-6 py-3">Cliente / Código</th>
                        <th class="px-6 py-3">Nota Fiscal</th>
                        <th class="px-6 py-3">Valor Total</th>
                        <th class="px-6 py-3">Status Contrato</th>
                        <th class="px-6 py-3">Status Financeiro</th>
                        <th class="px-6 py-3 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-700 dark:text-gray-200">
                    <?php if (empty($contratos)): ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">Nenhuma venda fechada registrada.</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach ($contratos as $c): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-4 font-bold uppercase text-gray-900 dark:text-white">
                                <?= preg_replace('/^(\[.*?\])/', '<span class="text-blue-600 dark:text-blue-400 font-black mr-1">$1</span>', htmlspecialchars($c['cliente_nome'])) ?>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400">
                                <?= !empty($c['numero_nf']) ? htmlspecialchars($c['numero_nf']) : '<span class="italic text-xs">Sem NF</span>' ?>
                            </td>
                            <td class="px-6 py-4 font-black text-emerald-600 dark:text-emerald-400">R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
                            
                            <td class="px-6 py-4">
                                <?php if($c['status_contrato'] === 'ASSINADO'): ?>
                                    <span class="text-[10px] bg-green-100 text-green-800 px-2 py-1 rounded font-bold border border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800">ASSINADO</span>
                                <?php else: ?>
                                    <span class="text-[10px] bg-red-100 text-red-800 px-2 py-1 rounded font-bold border border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800 animate-pulse">PENDENTE</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="px-6 py-4">
                                <?php if($c['status_financeiro'] === 'PAGO'): ?>
                                    <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-1 rounded font-bold border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400">LIQUIDADO</span>
                                <?php elseif($c['status_financeiro'] === 'FATURADO'): ?>
                                    <span class="text-[10px] bg-blue-100 text-blue-800 px-2 py-1 rounded font-bold border border-blue-200 dark:bg-blue-900/30 dark:text-blue-400">FATURADO</span>
                                <?php else: ?>
                                    <span class="text-[10px] bg-yellow-100 text-yellow-800 px-2 py-1 rounded font-bold border border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400">A FATURAR</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="px-6 py-4 text-center space-x-3">
                                <button onclick="abrirModalGerenciar(<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 font-bold transition-colors text-xs border border-blue-600 dark:border-blue-400 px-3 py-1 rounded hover:bg-blue-50 dark:hover:bg-blue-900/30">
                                    GERENCIAR
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalNovoContrato" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalNovoContratoConteudo">
        
        <div class="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
            <h3 class="text-lg font-bold text-blue-700 dark:text-blue-400">Inserir Contrato Manual</h3>
            <button type="button" onclick="fecharModalNovoContrato()" class="text-gray-400 hover:text-gray-800 dark:hover:text-white text-2xl font-bold">&times;</button>
        </div>

        <form id="formNovoContrato" onsubmit="salvarNovoContrato(event)">
            <div class="space-y-4">
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Selecionar Cliente Oficial</label>
                    <input type="text" id="search_manual_cliente" onkeyup="filtrarSelect('search_manual_cliente', 'manual_cliente_id')" placeholder="Pesquisar cliente..." autocomplete="off" class="w-full px-3 py-1.5 mb-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
                    
                    <select id="manual_cliente_id" required size="4" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase scrollbar-thin">
                        <?php foreach ($clientes_db as $cli): 
                            $codigo_cli = !empty($cli['codigo_cliente']) ? $cli['codigo_cliente'] : "CLI-" . str_pad($cli['id'], 2, "0", STR_PAD_LEFT); 
                        ?>
                        <option value="<?= $cli['id'] ?>" class="p-1 border-b border-gray-100 dark:border-gray-700 hover:bg-blue-50 dark:hover:bg-gray-600">
                            [<?= htmlspecialchars($codigo_cli) ?>] - <?= htmlspecialchars($cli['nome_contrato']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Valor Total da Venda (R$)</label>
                    <input type="number" step="0.01" id="manual_valor" required class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-emerald-600 dark:text-emerald-400 font-bold rounded focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Status Contrato</label>
                        <select id="manual_status_contrato" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded font-bold uppercase focus:ring-2 focus:ring-blue-500">
                            <option value="PENDENTE">Pendente</option>
                            <option value="ASSINADO">Assinado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Status Faturamento</label>
                        <select id="manual_status_financeiro" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded font-bold uppercase focus:ring-2 focus:ring-blue-500">
                            <option value="A FATURAR">A Faturar</option>
                            <option value="FATURADO">Faturado</option>
                            <option value="PAGO">Pago</option>
                        </select>
                    </div>
                </div>
                
                <p class="text-[10px] text-gray-500 dark:text-gray-400 italic">Após cadastrar, utilize o botão "GERENCIAR" na tabela para adicionar custos, número de NF e gerar o parcelamento no financeiro.</p>
            </div>
            
            <div class="flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4 mt-6">
                <button type="button" onclick="fecharModalNovoContrato()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-bold transition shadow-sm">Cadastrar Contrato</button>
            </div>
        </form>
    </div>
</div>

<div id="modalGerenciar" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-5xl p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300 max-h-[90vh] overflow-y-auto" id="modalGerenciarConteudo">
        
        <div class="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
            <div>
                <h3 class="text-lg font-bold text-blue-700 dark:text-blue-400">Gestão Administrativa</h3>
                <p id="gerenciar_nome_cliente" class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase mt-1"></p>
            </div>
            <button type="button" onclick="fecharModalGerenciar()" class="text-gray-400 hover:text-gray-800 dark:hover:text-white text-2xl font-bold">&times;</button>
        </div>

        <form id="formGerenciar" onsubmit="salvarGerenciar(event)">
            <input type="hidden" id="gerenciar_id" name="id">
            <input type="hidden" id="gerenciar_valor_total" name="valor_total">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded border border-gray-200 dark:border-gray-700">
                        <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-3 border-b border-gray-200 dark:border-gray-700 pb-1">Contrato e Nota Fiscal</h4>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Status Contrato</label>
                                <select id="gerenciar_status_contrato" name="status_contrato" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded font-bold uppercase focus:ring-2 focus:ring-blue-500">
                                    <option value="PENDENTE">Pendente</option>
                                    <option value="ASSINADO">Assinado</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Status Faturamento</label>
                                <select id="gerenciar_status_financeiro" name="status_financeiro" onchange="verificarStatusFinanceiro()" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded font-bold uppercase focus:ring-2 focus:ring-blue-500">
                                    <option value="A FATURAR">A Faturar</option>
                                    <option value="FATURADO">Faturado</option>
                                    <option value="PAGO">Pago (Liquidado)</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Número da NF</label>
                            <input type="text" id="gerenciar_nf" name="numero_nf" placeholder="Ex: NF 12345" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div id="box_parcelamento" class="bg-blue-50 dark:bg-blue-900/10 p-4 rounded border border-blue-200 dark:border-blue-800 transition-opacity">
                        <h4 class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase mb-3 border-b border-blue-200 dark:border-blue-800 pb-1">Lançar no Financeiro (Parcelamento)</h4>
                        
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Valor da Entrada (R$)</label>
                                <input type="number" step="0.01" id="parc_entrada_valor" placeholder="0,00" class="w-full px-2 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-xs font-bold text-blue-600 dark:text-blue-400">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Data da Entrada</label>
                                <input type="date" id="parc_entrada_data" class="w-full px-2 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-xs text-gray-700 dark:text-gray-300">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Qtd Parcelas</label>
                                <input type="number" id="parc_qtd" placeholder="Ex: 3" class="w-full px-2 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-xs font-bold text-blue-600 dark:text-blue-400">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Valor Parcela (R$)</label>
                                <input type="number" step="0.01" id="parc_valor" placeholder="0,00" class="w-full px-2 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-xs font-bold text-blue-600 dark:text-blue-400">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">1º Vencimento</label>
                                <input type="date" id="parc_data_ini" class="w-full px-2 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-xs text-gray-700 dark:text-gray-300">
                            </div>
                        </div>
                        
                        <button type="button" onclick="gerarPreviaParcelas()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded text-xs transition shadow-sm mb-3">
                            GERAR PRÉVIA DO FATURAMENTO
                        </button>

                        <div id="lista_previa_parcelas" class="space-y-1 max-h-32 overflow-y-auto text-xs hidden">
                            </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded border border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-end border-b border-gray-200 dark:border-gray-700 pb-1 mb-3">
                        <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Custos e Lucratividade</h4>
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">Venda: R$ <span id="label_valor_venda">0,00</span></span>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Custo: MDF / Chapas (R$)</label>
                            <input type="number" step="0.01" id="custo_mdf" name="custo_mdf" onkeyup="calcularLucro()" onchange="calcularLucro()" class="w-full px-3 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-red-600 dark:text-red-400 font-bold rounded">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Custo: Ferragens / Acessórios (R$)</label>
                            <input type="number" step="0.01" id="custo_ferragens" name="custo_ferragens" onkeyup="calcularLucro()" onchange="calcularLucro()" class="w-full px-3 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-red-600 dark:text-red-400 font-bold rounded">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Comissões (R$)</label>
                            <input type="number" step="0.01" id="custo_comissao" name="custo_comissao" onkeyup="calcularLucro()" onchange="calcularLucro()" class="w-full px-3 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-red-600 dark:text-red-400 font-bold rounded">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Outros Custos / Terceiros (R$)</label>
                            <input type="number" step="0.01" id="custo_outros" name="custo_outros" onkeyup="calcularLucro()" onchange="calcularLucro()" class="w-full px-3 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-red-600 dark:text-red-400 font-bold rounded">
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-gray-300 dark:border-gray-600">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Total de Custos:</span>
                            <span class="text-sm font-bold text-red-600 dark:text-red-400">R$ <span id="calc_total_custos">0,00</span></span>
                        </div>
                        <div class="flex justify-between items-center bg-emerald-50 dark:bg-emerald-900/20 p-2 rounded border border-emerald-200 dark:border-emerald-800 mt-2">
                            <span class="text-sm font-black text-emerald-700 dark:text-emerald-400">LUCRO LÍQUIDO:</span>
                            <div class="text-right">
                                <span class="text-lg font-black text-emerald-700 dark:text-emerald-400">R$ <span id="calc_lucro">0,00</span></span>
                                <p class="text-[11px] font-bold text-emerald-600 dark:text-emerald-500">Margem: <span id="calc_margem">0</span>%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4 mt-6">
                <button type="button" onclick="fecharModalGerenciar()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-bold transition shadow-sm">Salvar Dados e Lançamentos</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Utils
    function filtrarSelect(inputId, selectId) {
        let filter = document.getElementById(inputId).value.toUpperCase();
        let select = document.getElementById(selectId);
        let options = select.getElementsByTagName('option');
        for (let i = 0; i < options.length; i++) {
            let txtValue = options[i].textContent || options[i].innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) { options[i].style.display = ""; } else { options[i].style.display = "none"; }
        }
    }

    // Modal Novo Contrato Manual
    const modalNovoContrato = document.getElementById('modalNovoContrato');
    const modalNovoContratoConteudo = document.getElementById('modalNovoContratoConteudo');

    function abrirModalNovoContrato() {
        document.getElementById('formNovoContrato').reset();
        document.getElementById('search_manual_cliente').value = '';
        filtrarSelect('search_manual_cliente', 'manual_cliente_id');
        
        modalNovoContrato.classList.remove('hidden');
        setTimeout(() => { modalNovoContrato.classList.remove('opacity-0'); modalNovoContratoConteudo.classList.remove('scale-95'); }, 10);
    }

    function fecharModalNovoContrato() {
        modalNovoContrato.classList.add('opacity-0'); modalNovoContratoConteudo.classList.add('scale-95');
        setTimeout(() => { modalNovoContrato.classList.add('hidden'); }, 300);
    }

    async function salvarNovoContrato(event) {
        event.preventDefault();
        
        const payload = {
            cliente_id: document.getElementById('manual_cliente_id').value,
            valor: document.getElementById('manual_valor').value,
            status_contrato: document.getElementById('manual_status_contrato').value,
            status_financeiro: document.getElementById('manual_status_financeiro').value
        };

        try {
            const response = await fetch('api/add_administrativo_manual.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
            });
            const result = await response.json();
            
            if(result.success) {
                fecharModalNovoContrato();
                window.location.reload();
            } else {
                alert('Erro: ' + result.error);
            }
        } catch(e) {
            alert('Falha na conexão com a API.');
        }
    }

    // Modal Gerenciar
    const modalGerenciar = document.getElementById('modalGerenciar');
    const modalGerenciarConteudo = document.getElementById('modalGerenciarConteudo');
    let parcelasConfirmadas = [];

    function abrirModalGerenciar(contrato) {
        document.getElementById('formGerenciar').reset();
        parcelasConfirmadas = [];
        document.getElementById('lista_previa_parcelas').innerHTML = '';
        document.getElementById('lista_previa_parcelas').classList.add('hidden');
        
        document.getElementById('gerenciar_id').value = contrato.id;
        document.getElementById('gerenciar_nome_cliente').innerText = contrato.cliente_nome;
        document.getElementById('gerenciar_valor_total').value = contrato.valor;
        document.getElementById('label_valor_venda').innerText = parseFloat(contrato.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2});
        
        document.getElementById('gerenciar_status_contrato').value = contrato.status_contrato || 'PENDENTE';
        document.getElementById('gerenciar_status_financeiro').value = contrato.status_financeiro || 'A FATURAR';
        document.getElementById('gerenciar_nf').value = contrato.numero_nf || '';
        
        document.getElementById('custo_mdf').value = contrato.custo_mdf || '';
        document.getElementById('custo_ferragens').value = contrato.custo_ferragens || '';
        document.getElementById('custo_comissao').value = contrato.custo_comissao || '';
        document.getElementById('custo_outros').value = contrato.custo_outros || '';
        
        verificarStatusFinanceiro();
        calcularLucro();
        
        modalGerenciar.classList.remove('hidden');
        setTimeout(() => { modalGerenciar.classList.remove('opacity-0'); modalGerenciarConteudo.classList.remove('scale-95'); }, 10);
    }

    function fecharModalGerenciar() {
        modalGerenciar.classList.add('opacity-0'); modalGerenciarConteudo.classList.add('scale-95');
        setTimeout(() => { modalGerenciar.classList.add('hidden'); }, 300);
    }

    function verificarStatusFinanceiro() {
        const statusFin = document.getElementById('gerenciar_status_financeiro').value;
        const boxParcelas = document.getElementById('box_parcelamento');
        if(statusFin === 'FATURADO') {
            boxParcelas.style.opacity = '1';
            boxParcelas.style.pointerEvents = 'auto';
        } else {
            boxParcelas.style.opacity = '0.4';
            boxParcelas.style.pointerEvents = 'none';
        }
    }

    function calcularLucro() {
        const valorVenda = parseFloat(document.getElementById('gerenciar_valor_total').value) || 0;
        const cMdf = parseFloat(document.getElementById('custo_mdf').value) || 0;
        const cFer = parseFloat(document.getElementById('custo_ferragens').value) || 0;
        const cCom = parseFloat(document.getElementById('custo_comissao').value) || 0;
        const cOut = parseFloat(document.getElementById('custo_outros').value) || 0;
        
        const totalCustos = cMdf + cFer + cCom + cOut;
        const lucro = valorVenda - totalCustos;
        let margem = valorVenda > 0 ? (lucro / valorVenda) * 100 : 0;

        document.getElementById('calc_total_custos').innerText = totalCustos.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        document.getElementById('calc_lucro').innerText = lucro.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        document.getElementById('calc_margem').innerText = margem.toFixed(1).replace('.', ',');
    }

    function gerarPreviaParcelas() {
        parcelasConfirmadas = [];
        const lista = document.getElementById('lista_previa_parcelas');
        lista.innerHTML = '';
        lista.classList.remove('hidden');

        const vEntrada = parseFloat(document.getElementById('parc_entrada_valor').value) || 0;
        const dEntrada = document.getElementById('parc_entrada_data').value;
        const qtd = parseInt(document.getElementById('parc_qtd').value) || 0;
        const vParcela = parseFloat(document.getElementById('parc_valor').value) || 0;
        const dIni = document.getElementById('parc_data_ini').value;

        if (vEntrada > 0 && dEntrada) {
            parcelasConfirmadas.push({ num: 0, desc: 'Entrada', valor: vEntrada, data: dEntrada });
        }

        if (qtd > 0 && vParcela > 0 && dIni) {
            let dataAtual = new Date(dIni + 'T12:00:00'); 
            for (let i = 1; i <= qtd; i++) {
                const dataFormatada = dataAtual.toISOString().split('T')[0];
                parcelasConfirmadas.push({ num: i, desc: `Parcela ${i}/${qtd}`, valor: vParcela, data: dataFormatada });
                dataAtual.setMonth(dataAtual.getMonth() + 1); 
            }
        }

        if (parcelasConfirmadas.length === 0) {
            lista.innerHTML = '<p class="text-red-500 italic">Preencha os dados da entrada e/ou parcelas.</p>';
            return;
        }

        let totalSoma = 0;
        parcelasConfirmadas.forEach(p => {
            totalSoma += p.valor;
            const dForm = p.data.split('-').reverse().join('/');
            lista.innerHTML += `
                <div class="flex justify-between items-center bg-white dark:bg-gray-800 p-2 border border-blue-100 dark:border-blue-900 rounded">
                    <span class="font-bold text-gray-700 dark:text-gray-300 uppercase">${p.desc}</span>
                    <div class="text-right">
                        <span class="block font-black text-blue-600 dark:text-blue-400">R$ ${p.valor.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                        <span class="text-[10px] text-gray-500 dark:text-gray-400">Venc: ${dForm}</span>
                    </div>
                </div>`;
        });
        
        lista.innerHTML += `<div class="text-right mt-2 font-bold text-gray-700 dark:text-gray-300">Total Lançado: <span class="text-emerald-600">R$ ${totalSoma.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span></div>`;
    }

    async function salvarGerenciar(event) {
        event.preventDefault();
        
        const payload = {
            id: document.getElementById('gerenciar_id').value,
            cliente_nome: document.getElementById('gerenciar_nome_cliente').innerText,
            status_contrato: document.getElementById('gerenciar_status_contrato').value,
            status_financeiro: document.getElementById('gerenciar_status_financeiro').value,
            numero_nf: document.getElementById('gerenciar_nf').value,
            custos: {
                mdf: document.getElementById('custo_mdf').value || 0,
                ferragens: document.getElementById('custo_ferragens').value || 0,
                comissao: document.getElementById('custo_comissao').value || 0,
                outros: document.getElementById('custo_outros').value || 0
            },
            parcelas: document.getElementById('gerenciar_status_financeiro').value === 'FATURADO' ? parcelasConfirmadas : []
        };

        try {
            const response = await fetch('api/save_administrativo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();
            
            if(result.success) {
                fecharModalGerenciar();
                window.location.reload();
            } else {
                alert('Erro ao salvar: ' + result.error);
            }
        } catch(e) {
            alert('Falha na conexão com o servidor.');
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>