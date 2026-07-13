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

    // 2. Busca Clientes
    $stmt_cli = $pdo->query("SELECT id, nome_contrato as nome FROM clientes_cadastro ORDER BY nome_contrato ASC");
    $clientes = $stmt_cli->fetchAll(PDO::FETCH_ASSOC);

    // 3. Busca Fornecedores
    $stmt_forn = $pdo->query("SELECT id, nome_fantasia as nome FROM fornecedores ORDER BY nome_fantasia ASC");
    $fornecedores = $stmt_forn->fetchAll(PDO::FETCH_ASSOC);

    // Mapeamento para exibir os nomes corretos na tabela
    $map_clientes = []; foreach($clientes as $c) $map_clientes[$c['id']] = $c['nome'];
    $map_fornecedores = []; foreach($fornecedores as $f) $map_fornecedores[$f['id']] = $f['nome'];

    // 4. Cálculos para o Dashboard
    $receitas_pagas = 0; $receitas_pendentes = 0;
    $despesas_pagas = 0; $despesas_pendentes = 0;
    $total_atrasado = 0;

    foreach ($lancamentos as $l) {
        $valor = (float) $l['valor'];
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
    }

    $saldo_atual = $receitas_pagas - $despesas_pagas;
    $saldo_previsto = ($receitas_pagas + $receitas_pendentes) - ($despesas_pagas + $despesas_pendentes);

} catch (\PDOException $e) {
    die("Erro na consulta: " . $e->getMessage());
}

function formatarData($data) { if (!$data) return '-'; return date('d/m/Y', strtotime($data)); }
function jsSafe($val) { return htmlspecialchars(json_encode($val), ENT_QUOTES, 'UTF-8'); }

$page_title = 'GESTÃO FINANCEIRA';
$page_subtitle = 'Contas a Pagar e Receber';
$menu_button_text = 'MENU';
$page_actions = '
<button onclick="abrirModalLancamento()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
    NOVO LANÇAMENTO
</button>';

require_once 'includes/header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Saldo Realizado (Caixa)</p>
        <p class="text-2xl font-black <?= $saldo_atual >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?> mt-1">
            R$ <?= number_format($saldo_atual, 2, ',', '.') ?>
        </p>
    </div>
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <p class="text-xs font-bold text-blue-500 dark:text-blue-400 uppercase tracking-wide">Saldo Previsto (Mês)</p>
        <p class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">R$ <?= number_format($saldo_previsto, 2, ',', '.') ?></p>
    </div>
    <div class="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-lg shadow-sm border border-emerald-200 dark:border-emerald-800">
        <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">A Receber (Pendente)</p>
        <p class="text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-1">R$ <?= number_format($receitas_pendentes, 2, ',', '.') ?></p>
    </div>
    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg shadow-sm border <?= $total_atrasado > 0 ? 'border-red-400 animate-pulse' : 'border-red-200 dark:border-red-800' ?>">
        <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-wide">A Pagar (Pendente)</p>
        <p class="text-2xl font-black text-red-700 dark:text-red-400 mt-1">
            R$ <?= number_format($despesas_pendentes, 2, ',', '.') ?>
            <?php if($total_atrasado > 0): ?>
                <span class="block text-[10px] text-red-500 mt-1">R$ <?= number_format($total_atrasado, 2, ',', '.') ?> EM ATRASO</span>
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-700/50">
        <h3 class="font-bold text-gray-700 dark:text-gray-200">Extrato e Lançamentos</h3>
        <input type="text" id="filtro_financeiro" onkeyup="filtrarTabela()" placeholder="Buscar lançamento..." class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded text-sm focus:ring-2 focus:ring-blue-500 w-1/3">
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap" id="tabela_financeira">
            <thead class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-600">
                <tr>
                    <th class="px-4 py-3 font-bold">Vencimento</th>
                    <th class="px-4 py-3 font-bold">Descrição</th>
                    <th class="px-4 py-3 font-bold">Entidade</th>
                    <th class="px-4 py-3 font-bold">Valor</th>
                    <th class="px-4 py-3 font-bold text-center">Status</th>
                    <th class="px-4 py-3 font-bold text-center">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($lancamentos)): ?>
                    <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">Nenhum lançamento encontrado.</td></tr>
                <?php endif; ?>
                
                <?php foreach ($lancamentos as $l): 
                    $is_atrasado = ($l['status'] === 'PENDENTE' && $l['data_vencimento'] < $hoje);
                    $cor_valor = $l['tipo'] === 'RECEITA' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
                    $sinal = $l['tipo'] === 'RECEITA' ? '+' : '-';
                    
                    // Definir o Nome da Entidade
                    $nome_entidade = '-';
                    if ($l['entidade_tipo'] === 'CLIENTE' && isset($map_clientes[$l['entidade_id']])) {
                        $nome_entidade = $map_clientes[$l['entidade_id']];
                    } elseif ($l['entidade_tipo'] === 'FORNECEDOR' && isset($map_fornecedores[$l['entidade_id']])) {
                        $nome_entidade = $map_fornecedores[$l['entidade_id']];
                    } elseif (!empty($l['cliente_fornecedor'])) {
                        $nome_entidade = $l['cliente_fornecedor']; // Fallback para registros antigos
                    }
                ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-gray-700 dark:text-gray-200 tr-busca">
                        <td class="px-4 py-3 font-medium <?= $is_atrasado ? 'text-red-500 font-bold' : '' ?>">
                            <?= formatarData($l['data_vencimento']) ?>
                        </td>
                        <td class="px-4 py-3 font-bold uppercase td-busca">
                            <?= htmlspecialchars($l['descricao']) ?>
                            <?php if($l['num_parcelas'] > 1): ?>
                                <span class="text-[10px] text-gray-500 ml-1">(<?= $l['num_parcelas'] ?>x)</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-xs td-busca uppercase"><?= htmlspecialchars($nome_entidade) ?></td>
                        <td class="px-4 py-3 font-black <?= $cor_valor ?>"><?= $sinal ?> R$ <?= number_format($l['valor'], 2, ',', '.') ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($l['status'] === 'PAGO'): ?>
                                <span class="bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400 px-2 py-1 rounded text-[10px] font-bold uppercase border border-green-200">BAIXADO</span>
                            <?php elseif ($is_atrasado): ?>
                                <span class="bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 px-2 py-1 rounded text-[10px] font-bold uppercase border border-red-200">ATRASADO</span>
                            <?php else: ?>
                                <span class="bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400 px-2 py-1 rounded text-[10px] font-bold uppercase border border-yellow-200">PENDENTE</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <?php if ($l['status'] === 'PENDENTE'): ?>
                                <button onclick='abrirModalBaixa(<?= $l['id'] ?>, <?= jsSafe($l['descricao']) ?>, <?= jsSafe($l['tipo']) ?>)' class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 font-bold text-sm" title="Dar Baixa / Pagar">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </button>
                            <?php endif; ?>
                            <button onclick='abrirModalEdicao(<?= jsSafe($l) ?>)' class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-bold" title="Editar">
                                &#9998;
                            </button>
                            <button onclick="deletarLancamento(<?= $l['id'] ?>)" class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 text-lg font-bold ml-1" title="Apagar">
                                &times;
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modalLancamento" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-4xl p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300 max-h-[95vh] overflow-y-auto" id="modalLancamentoConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
            <h3 id="modalTitulo" class="text-lg font-bold text-gray-800 dark:text-gray-100">Novo Lançamento Financeiro</h3>
            <button type="button" onclick="fecharModalLancamento()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">&times;</button>
        </div>
        
        <form id="formLancamento" onsubmit="salvarLancamento(event)">
            <input type="hidden" id="fin_id">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="col-span-1">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
                    <select id="fin_tipo" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 font-bold uppercase">
                        <option value="RECEITA" class="text-green-600">Receita (Entrada)</option>
                        <option value="DESPESA" class="text-red-600">Despesa (Saída)</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Descrição / Referência</label>
                    <input type="text" id="fin_descricao" required placeholder="Ex: Pagamento Fornecedor MDF" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase">
                </div>

                <div class="col-span-1">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Vincular a:</label>
                    <select id="sel_tipo_entidade" onchange="atualizarEntidades()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                        <option value="CLIENTE">Cliente</option>
                        <option value="FORNECEDOR">Fornecedor</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Selecione o Cliente/Fornecedor</label>
                    <select id="sel_entidade_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase">
                        <option value="">Selecione...</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Documento</label>
                    <select id="fin_tipo_documento" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase">
                        <option value="NF">Nota Fiscal (NF)</option>
                        <option value="PEDIDO">Pedido Interno</option>
                        <option value="RECIBO">Recibo</option>
                        <option value="GUIA">Guia / Imposto</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nº Documento</label>
                    <input type="text" id="fin_num_documento" placeholder="Ex: 001234" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Forma de Pagamento</label>
                    <input type="text" id="fin_forma_pagamento" placeholder="Ex: PIX, Boleto, Cartão" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Valor Total (R$)</label>
                    <input type="number" step="0.01" id="fin_valor" required placeholder="0.00" onkeyup="calcularParcelas()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded font-bold text-lg text-blue-600">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Qtd. Parcelas</label>
                    <input type="number" id="fin_parcelas" value="1" min="1" onkeyup="calcularParcelas()" onchange="calcularParcelas()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Valor da Parcela (R$)</label>
                    <input type="text" id="fin_valor_parcela" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-600 dark:text-white rounded font-bold">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Data do Documento</label>
                    <input type="date" id="fin_data_documento" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Data de Vencimento</label>
                    <input type="date" id="fin_vencimento" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded border-l-4 border-l-red-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Plano de Contas</label>
                    <input type="text" id="fin_plano_contas" placeholder="Ex: Fornecedores de Matéria Prima" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase">
                </div>
                
                <div class="md:col-span-3">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Centro de Custo</label>
                    <input type="text" id="fin_centro_custo" placeholder="Ex: Marcenaria / Showroom" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Anexo de Comprovante (Futuramente disponível)</label>
                <input type="file" disabled class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 rounded text-sm text-gray-400 cursor-not-allowed">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Observações Internas</label>
                <textarea id="fin_observacao" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            
            <div class="flex justify-end space-x-3 border-t dark:border-gray-700 pt-4">
                <button type="button" onclick="fecharModalLancamento()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-[#1e3a8a] dark:bg-blue-600 hover:bg-blue-800 text-white rounded font-bold transition shadow-sm">Salvar Registo</button>
            </div>
        </form>
    </div>
</div>

<div id="modalBaixa" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalBaixaConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
            <h3 class="text-lg font-bold text-green-600 dark:text-green-400">Confirmar Liquidação</h3>
            <button type="button" onclick="fecharModalBaixa()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">&times;</button>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">Deseja dar baixa no lançamento <strong id="lbl_baixa_desc" class="uppercase"></strong>?</p>
        
        <form id="formBaixa" onsubmit="salvarBaixa(event)">
            <input type="hidden" id="baixa_id">
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Data do Pagamento/Recebimento</label>
                <input type="date" id="baixa_data" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-green-500 font-bold">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="fecharModalBaixa()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded font-bold transition shadow-sm">Confirmar Baixa</button>
            </div>
        </form>
    </div>
</div>

<script>
    // 1. DADOS IMPORTADOS CORRETAMENTE COM JSON_ENCODE SEM ESCAPE HTML
    const clientes = <?= json_encode($clientes, JSON_UNESCAPED_UNICODE) ?>;
    const fornecedores = <?= json_encode($fornecedores, JSON_UNESCAPED_UNICODE) ?>;

    // 2. Integração Entidades
    function atualizarEntidades() {
        const tipo = document.getElementById('sel_tipo_entidade').value;
        const select = document.getElementById('sel_entidade_id');
        select.innerHTML = '<option value="">Selecione...</option>';
        const lista = (tipo === 'CLIENTE') ? clientes : fornecedores;
        
        if(lista && lista.length > 0) {
            lista.forEach(e => {
                select.innerHTML += `<option value="${e.id}">${e.nome}</option>`;
            });
        }
    }

    // 3. Calculadora de Parcelas
    function calcularParcelas() {
        const total = parseFloat(document.getElementById('fin_valor').value) || 0;
        const parcelas = parseInt(document.getElementById('fin_parcelas').value) || 1;
        if(total > 0 && parcelas > 0) {
            document.getElementById('fin_valor_parcela').value = (total / parcelas).toFixed(2);
        }
    }

    // 4. Modais
    const modalLanc = document.getElementById('modalLancamento');
    const modalLancConteudo = document.getElementById('modalLancamentoConteudo');
    const modalBaixa = document.getElementById('modalBaixa');
    const modalBaixaConteudo = document.getElementById('modalBaixaConteudo');

    function abrirModalLancamento() {
        document.getElementById('formLancamento').reset();
        document.getElementById('fin_id').value = '';
        document.getElementById('modalTitulo').innerText = 'Novo Lançamento Financeiro';
        atualizarEntidades();
        
        modalLanc.classList.remove('hidden');
        setTimeout(() => { modalLanc.classList.remove('opacity-0'); modalLancConteudo.classList.remove('scale-95'); }, 10);
    }

    function fecharModalLancamento() {
        modalLanc.classList.add('opacity-0'); modalLancConteudo.classList.add('scale-95');
        setTimeout(() => { modalLanc.classList.add('hidden'); }, 300);
    }

    function abrirModalEdicao(dados) {
        document.getElementById('fin_id').value = dados.id;
        document.getElementById('fin_tipo').value = dados.tipo;
        document.getElementById('fin_descricao').value = dados.descricao;
        
        // Entidades
        document.getElementById('sel_tipo_entidade').value = dados.entidade_tipo || 'CLIENTE';
        atualizarEntidades();
        document.getElementById('sel_entidade_id').value = dados.entidade_id || '';
        
        // Outros campos
        document.getElementById('fin_num_documento').value = dados.num_documento || '';
        document.getElementById('fin_tipo_documento').value = dados.tipo_documento || 'NF';
        document.getElementById('fin_forma_pagamento').value = dados.forma_pagamento || '';
        document.getElementById('fin_valor').value = dados.valor;
        document.getElementById('fin_parcelas').value = dados.num_parcelas || 1;
        document.getElementById('fin_valor_parcela').value = dados.valor_parcela || dados.valor;
        document.getElementById('fin_data_documento').value = dados.data_documento || '';
        document.getElementById('fin_vencimento').value = dados.data_vencimento;
        document.getElementById('fin_plano_contas').value = dados.plano_contas || '';
        document.getElementById('fin_centro_custo').value = dados.centro_custo || '';
        document.getElementById('fin_observacao').value = dados.observacao || '';
        
        document.getElementById('modalTitulo').innerText = 'Editar Lançamento';
        modalLanc.classList.remove('hidden');
        setTimeout(() => { modalLanc.classList.remove('opacity-0'); modalLancConteudo.classList.remove('scale-95'); }, 10);
    }

    function abrirModalBaixa(id, desc, tipo) {
        document.getElementById('baixa_id').value = id;
        document.getElementById('lbl_baixa_desc').innerText = desc + ' (' + tipo + ')';
        document.getElementById('baixa_data').value = new Date().toISOString().split('T')[0];
        
        modalBaixa.classList.remove('hidden');
        setTimeout(() => { modalBaixa.classList.remove('opacity-0'); modalBaixaConteudo.classList.remove('scale-95'); }, 10);
    }

    function fecharModalBaixa() {
        modalBaixa.classList.add('opacity-0'); modalBaixaConteudo.classList.add('scale-95');
        setTimeout(() => { modalBaixa.classList.add('hidden'); }, 300);
    }

    // 5. Integrações com a API
  async function salvarLancamento(event) {
        event.preventDefault();
        const id = document.getElementById('fin_id').value;
        const endpoint = id ? 'api/edit_lancamento.php' : 'api/add_lancamento.php';
        
        const payload = {
            id: id,
            tipo: document.getElementById('fin_tipo').value,
            descricao: document.getElementById('fin_descricao').value,
            entidade_tipo: document.getElementById('sel_tipo_entidade').value,
            entidade_id: document.getElementById('sel_entidade_id').value,
            num_documento: document.getElementById('fin_num_documento').value,
            tipo_documento: document.getElementById('fin_tipo_documento').value,
            forma_pagamento: document.getElementById('fin_forma_pagamento').value,
            valor: document.getElementById('fin_valor').value,
            num_parcelas: document.getElementById('fin_parcelas').value,
            valor_parcela: document.getElementById('fin_valor_parcela').value,
            data_documento: document.getElementById('fin_data_documento').value,
            data_vencimento: document.getElementById('fin_vencimento').value,
            plano_contas: document.getElementById('fin_plano_contas').value,
            centro_custo: document.getElementById('fin_centro_custo').value,
            observacao: document.getElementById('fin_observacao').value
        };

        try {
            const response = await fetch(endpoint, { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify(payload) 
            });
            
            // Lemos a resposta como texto primeiro para conseguir caçar erros ocultos
            const text = await response.text(); 
            
            try {
                const result = JSON.parse(text);
                if (result.success) { 
                    window.location.reload(); 
                } else { 
                    alert('Erro no banco de dados: ' + (result.error || 'Desconhecido')); 
                }
            } catch(e) {
                console.error("ERRO FATAL NO SERVIDOR:", text);
                alert("Ocorreu um erro no servidor. Aperte a tecla F12 (Console) para ver o motivo da quebra.");
            }
        } catch (error) { 
            console.error(error);
            alert('Erro de comunicação. O arquivo ' + endpoint + ' foi criado corretamente?'); 
        }
    }

    async function salvarBaixa(event) {
        event.preventDefault();
        const payload = {
            id: document.getElementById('baixa_id').value,
            data_pagamento: document.getElementById('baixa_data').value
        };
        try {
            const response = await fetch('api/baixa_lancamento.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const result = await response.json();
            if (result.success) { window.location.reload(); } else { alert('Erro: ' + result.error); }
        } catch (error) { alert('Erro de rede.'); }
    }

    async function deletarLancamento(id) {
        if (!confirm('Tem a certeza que deseja apagar este registo financeiro?')) return;
        try {
            const response = await fetch('api/delete_lancamento.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) });
            const result = await response.json();
            if (result.success) { window.location.reload(); } else { alert('Erro ao apagar: ' + result.error); }
        } catch (error) { alert('Erro de rede.'); }
    }

    // 6. Busca na Tabela
    function filtrarTabela() {
        const filtro = document.getElementById('filtro_financeiro').value.toLowerCase();
        const linhas = document.querySelectorAll('.tr-busca');
        linhas.forEach(linha => {
            const texto = linha.innerText.toLowerCase();
            linha.style.display = texto.includes(filtro) ? '' : 'none';
        });
    }

    // 7. Fechar Modais ao Clicar Fora
    if(modalLanc) modalLanc.addEventListener('click', (e) => { if (e.target === modalLanc) fecharModalLancamento(); });
    if(modalBaixa) modalBaixa.addEventListener('click', (e) => { if (e.target === modalBaixa) fecharModalBaixa(); });

    // Iniciar com os Selects carregados
    window.onload = () => { atualizarEntidades(); }
</script>

<?php require_once 'includes/footer.php'; ?>