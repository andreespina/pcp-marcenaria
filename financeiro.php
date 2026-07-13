<?php
// financeiro.php
require_once 'includes/auth.php';
// protegerPagina('financeiro'); // Descomente após adicionar a permissão no login/header

require_once 'config/conexao.php';

$mes_atual = date('m');
$ano_atual = date('Y');

try {
    // Busca todos os lançamentos do mês atual ou pendentes antigos
    $stmt = $pdo->prepare("SELECT * FROM financeiro 
                           WHERE (MONTH(data_vencimento) = :mes AND YEAR(data_vencimento) = :ano) 
                              OR status = 'PENDENTE'
                           ORDER BY data_vencimento ASC");
    $stmt->execute(['mes' => $mes_atual, 'ano' => $ano_atual]);
    $lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cálculos para os Cards do Dashboard
    $receitas_pagas = 0; $receitas_pendentes = 0;
    $despesas_pagas = 0; $despesas_pendentes = 0;
    $total_atrasado = 0;

    $hoje = date('Y-m-d');

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

function formatarData($data) {
    if (!$data) return '-';
    return date('d/m/Y', strtotime($data));
}

function jsSafe($val) {
    if ($val === null) $val = '';
    return htmlspecialchars(json_encode($val), ENT_QUOTES, 'UTF-8');
}

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
                    <th class="px-4 py-3 font-bold">Cliente/Fornecedor</th>
                    <th class="px-4 py-3 font-bold">Categoria</th>
                    <th class="px-4 py-3 font-bold">Valor</th>
                    <th class="px-4 py-3 font-bold text-center">Status</th>
                    <th class="px-4 py-3 font-bold text-center">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($lancamentos)): ?>
                    <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500 italic">Nenhum lançamento encontrado.</td></tr>
                <?php endif; ?>
                
                <?php foreach ($lancamentos as $l): 
                    $is_atrasado = ($l['status'] === 'PENDENTE' && $l['data_vencimento'] < $hoje);
                    $cor_valor = $l['tipo'] === 'RECEITA' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
                    $sinal = $l['tipo'] === 'RECEITA' ? '+' : '-';
                ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-gray-700 dark:text-gray-200 tr-busca">
                        <td class="px-4 py-3 font-medium <?= $is_atrasado ? 'text-red-500 font-bold' : '' ?>">
                            <?= formatarData($l['data_vencimento']) ?>
                        </td>
                        <td class="px-4 py-3 font-bold uppercase td-busca"><?= htmlspecialchars($l['descricao']) ?></td>
                        <td class="px-4 py-3 text-xs td-busca"><?= htmlspecialchars($l['cliente_fornecedor']) ?: '-' ?></td>
                        <td class="px-4 py-3 text-[10px] uppercase font-semibold text-gray-500 td-busca"><?= htmlspecialchars($l['categoria']) ?></td>
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
                            <button onclick='abrirModalEdicao(<?= $l['id'] ?>, <?= jsSafe($l['tipo']) ?>, <?= jsSafe($l['descricao']) ?>, <?= jsSafe($l['categoria']) ?>, <?= jsSafe($l['cliente_fornecedor']) ?>, <?= jsSafe($l['valor']) ?>, <?= jsSafe($l['data_vencimento']) ?>, <?= jsSafe($l['observacao']) ?>)' class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300" title="Editar">
                                &#9998;
                            </button>
                            <button onclick="deletarLancamento(<?= $l['id'] ?>)" class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 text-lg font-bold" title="Apagar">
                                &times;
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL DE LANÇAMENTO (CRIAR/EDITAR) -->
<div id="modalLancamento" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalLancamentoConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
            <h3 id="modalTitulo" class="text-lg font-bold text-gray-800 dark:text-gray-100">Novo Lançamento Financeiro</h3>
            <button onclick="fecharModalLancamento()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">&times;</button>
        </div>
        
        <form id="formLancamento" onsubmit="salvarLancamento(event)">
            <input type="hidden" id="fin_id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Lançamento</label>
                    <select id="fin_tipo" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 font-bold uppercase">
                        <option value="RECEITA" class="text-green-600">Receita (Entrada)</option>
                        <option value="DESPESA" class="text-red-600">Despesa (Saída)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Valor (R$)</label>
                    <input type="number" step="0.01" id="fin_valor" required placeholder="0.00" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 font-bold">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Descrição / Referência</label>
                    <input type="text" id="fin_descricao" required placeholder="Ex: Pagamento Fornecedor MDF" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Cliente / Fornecedor</label>
                    <input type="text" id="fin_cliente" placeholder="Ex: João da Silva / Leo Madeiras" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Categoria</label>
                    <select id="fin_categoria" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase">
                        <option value="PROJETOS">Projetos / Contratos</option>
                        <option value="MATERIAIS">Materiais / Insumos</option>
                        <option value="CUSTOS FIXOS">Custos Fixos (Luz, Água, Net)</option>
                        <option value="FUNCIONARIOS">Funcionários / Montadores</option>
                        <option value="IMPOSTOS">Impostos / Taxas</option>
                        <option value="OUTROS">Outros</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Data de Vencimento</label>
                    <input type="date" id="fin_vencimento" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
                </div>
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

<!-- MODAL DE BAIXA -->
<div id="modalBaixa" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalBaixaConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
            <h3 class="text-lg font-bold text-green-600 dark:text-green-400">Confirmar Liquidação</h3>
            <button onclick="fecharModalBaixa()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">&times;</button>
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

<script src="assets/js/financeiro.js"></script>
<?php require_once 'includes/footer.php'; ?>