<?php
// almoxarifado.php
require_once 'includes/auth.php';
protegerPagina();

require_once 'config/conexao.php';

try {
    $stmt = $pdo->query("SELECT * FROM almoxarifado ORDER BY categoria ASC, nome_item ASC");
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_itens = count($itens);
    $itens_criticos = 0;
    $estoque_ok = 0;

    foreach ($itens as $i) {
        if ($i['quantidade'] <= $i['quantidade_minima']) {
            $itens_criticos++;
        } else {
            $estoque_ok++;
        }
    }

    // --- NOVO: Buscando Cadastros Base para o Almoxarifado ---
    $stmt_cad_almox = $pdo->query("SELECT tipo, nome FROM cadastros_base WHERE tipo IN ('CATEGORIA_ALMOX', 'UNIDADE_MEDIDA') ORDER BY nome ASC");
    $cadastros_almox = $stmt_cad_almox->fetchAll(PDO::FETCH_ASSOC);
    
    $categorias_almox = [];
    $unidades_medida = [];
    
    foreach($cadastros_almox as $cad) {
        if($cad['tipo'] == 'CATEGORIA_ALMOX') $categorias_almox[] = $cad['nome'];
        if($cad['tipo'] == 'UNIDADE_MEDIDA') $unidades_medida[] = $cad['nome'];
    }
    // ---------------------------------------------------------

} catch (\PDOException $e) {
    die("Erro na consulta: " . $e->getMessage());
}

function jsSafe($val) {
    if ($val === null) $val = '';
    return htmlspecialchars(json_encode($val), ENT_QUOTES, 'UTF-8');
}

// Variáveis para o header
$page_title = 'ALMOXARIFADO';
$page_subtitle = 'Controle de Estoque e Materiais';
$main_class = 'flex-1'; 
$menu_button_text = 'MENU';
$page_actions = '
<button onclick="abrirModalItem()" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
    NOVO ITEM
</button>';

$head_extras = '
<style>
    .dark body { background-color: #1a1e2b !important; }
    .table-container { max-height: calc(100vh - 280px); overflow-y: auto; }
    .table-container::-webkit-scrollbar { width: 6px; }
    .table-container::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
    .dark .table-container::-webkit-scrollbar-thumb { background-color: #4b5563; }
</style>';

require_once 'includes/header.php';
?>

<div class="flex flex-col gap-6">

    <!-- Dashboard: Resumo e Filtro -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex items-center">
            <div class="p-3 rounded-full bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400 mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase">Total de Itens</p>
                <p class="text-2xl font-black text-gray-800 dark:text-white"><?= $total_itens ?></p>
            </div>
        </div>
        <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex items-center">
            <div class="p-3 rounded-full bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-green-600 dark:text-green-400 uppercase">Estoque Saudável</p>
                <p class="text-2xl font-black text-green-700 dark:text-green-300"><?= $estoque_ok ?></p>
            </div>
        </div>
        <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border <?= $itens_criticos > 0 ? 'border-red-400 shadow-md animate-pulse' : 'border-gray-200 dark:border-[#2a3142]' ?> flex items-center">
            <div class="p-3 rounded-full bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-red-600 dark:text-red-400 uppercase">Em Falta / Crítico</p>
                <p class="text-2xl font-black text-red-700 dark:text-red-300"><?= $itens_criticos ?></p>
            </div>
        </div>
        <div class="bg-white dark:bg-[#222736] p-4 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex flex-col justify-center">
            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Pesquisar Material</label>
            <div class="relative">
                <input type="text" id="filtro_tabela_almox" onkeyup="filtrarTabelaAlmoxarifado()" placeholder="Digite o nome ou categoria..." class="w-full px-4 py-1.5 pl-9 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-teal-500 transition-colors text-sm">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
    </div>

    <!-- Tabela de Almoxarifado -->
    <div class="bg-white dark:bg-[#222736] rounded-lg border border-gray-200 dark:border-[#2a3142] shadow-sm overflow-hidden flex flex-col">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
            <h3 class="font-bold text-gray-700 dark:text-gray-200 text-sm uppercase tracking-wider">Lista de Materiais</h3>
        </div>
        
        <div class="overflow-x-auto table-container">
            <table class="w-full text-left text-sm whitespace-nowrap" id="tabelaAlmoxarifado">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-10 font-bold">
                    <tr>
                        <th class="px-6 py-3">Item / Material</th>
                        <th class="px-6 py-3">Categoria</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-center">Estoque Atual</th>
                        <th class="px-6 py-3 text-center" title="Quantidade Mínima de Segurança">Mínimo</th>
                        <th class="px-6 py-3 text-center">Ações Rápidas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-700 dark:text-gray-200">
                    <?php if (empty($itens)): ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400 italic">Nenhum item cadastrado no almoxarifado.</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach ($itens as $i): 
                        $is_critical = ($i['quantidade'] <= $i['quantidade_minima']);
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors row-almox">
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-900 dark:text-white uppercase"><?= htmlspecialchars($i['nome_item']) ?></p>
                                <?php if($i['observacao']): ?>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate max-w-xs" title="<?= htmlspecialchars($i['observacao']) ?>"><?= htmlspecialchars($i['observacao']) ?></p>
                                <?php endif; ?>
                            </td>
                            
                            <td class="px-6 py-4">
                                <span class="bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider">
                                    <?= htmlspecialchars($i['categoria']) ?>
                                </span>
                            </td>
                            
                            <td class="px-6 py-4 text-center">
                                <?php if ($is_critical): ?>
                                    <span class="bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 px-2 py-1 rounded text-[10px] font-bold uppercase border border-red-200 dark:border-red-800/50 animate-pulse">Comprar</span>
                                <?php else: ?>
                                    <span class="bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400 px-2 py-1 rounded text-[10px] font-bold uppercase border border-green-200 dark:border-green-800/30">OK</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center space-x-3">
                                    <button onclick="ajustarEstoque(<?= $i['id'] ?>, -1, <?= $i['quantidade'] ?>)" class="w-6 h-6 rounded bg-red-50 text-red-600 hover:bg-red-200 dark:bg-red-900/30 border border-red-200 dark:border-red-800 dark:text-red-400 flex items-center justify-center font-bold transition-colors" title="Retirar 1 unidade">-</button>
                                    
                                    <span class="font-black text-lg w-16 text-center <?= $is_critical ? 'text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-gray-200' ?>">
                                        <?= (float)$i['quantidade'] ?>
                                        <span class="text-[10px] font-normal text-gray-500 uppercase"><?= htmlspecialchars($i['unidade_medida']) ?></span>
                                    </span>
                                    
                                    <button onclick="ajustarEstoque(<?= $i['id'] ?>, 1, <?= $i['quantidade'] ?>)" class="w-6 h-6 rounded bg-green-50 text-green-600 hover:bg-green-200 dark:bg-green-900/30 border border-green-200 dark:border-green-800 dark:text-green-400 flex items-center justify-center font-bold transition-colors" title="Adicionar 1 unidade">+</button>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 text-center font-semibold text-gray-500 dark:text-gray-400">
                                <?= (float)$i['quantidade_minima'] ?>
                            </td>
                            
                            <td class="px-6 py-4 text-center space-x-2">
                                <button onclick='abrirModalEdicao(<?= $i['id'] ?>, <?= jsSafe($i['nome_item']) ?>, <?= jsSafe($i['categoria']) ?>, <?= jsSafe($i['quantidade']) ?>, <?= jsSafe($i['quantidade_minima']) ?>, <?= jsSafe($i['unidade_medida']) ?>, <?= jsSafe($i['observacao']) ?>)' class="text-blue-500 hover:text-blue-700 dark:hover:text-blue-400 bg-blue-50 dark:bg-blue-900/20 p-1.5 rounded transition-colors" title="Editar Item">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <button onclick="deletarItem(<?= $i['id'] ?>)" class="text-red-500 hover:text-red-700 dark:hover:text-red-400 bg-red-50 dark:bg-red-900/20 p-1.5 rounded transition-colors" title="Apagar Item">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL DE CADASTRO E EDIÇÃO -->
<div id="modalItem" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalItemConteudo">
        
        <div class="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
            <h3 id="modalTitulo" class="text-lg font-bold text-teal-600 dark:text-teal-400">Cadastrar Novo Item</h3>
            <button type="button" onclick="fecharModalItem()" class="text-gray-400 hover:text-gray-800 dark:hover:text-white text-2xl font-bold">&times;</button>
        </div>
        
        <form id="formItem" onsubmit="salvarItemServidor(event)">
            <input type="hidden" id="item_id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Descrição / Nome do Item</label>
                    <input type="text" id="item_nome" required placeholder="Ex: Dobradiça Reta com Amortecedor" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-teal-500 uppercase">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Categoria</label>
                    <select id="item_categoria" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-teal-500 uppercase font-bold">
                        <option value="">Selecione...</option>
                        <?php foreach($categorias_almox as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Unidade de Medida</label>
                    <select id="item_unidade" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-teal-500 uppercase font-bold">
                        <option value="">Selecione...</option>
                        <?php foreach($unidades_medida as $un): ?>
                            <option value="<?= htmlspecialchars($un) ?>"><?= htmlspecialchars($un) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Estoque Atual</label>
                    <input type="number" step="0.01" id="item_quantidade" value="0" required class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-teal-600 dark:text-teal-400 font-black rounded focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1 text-red-600 dark:text-red-400">Estoque Mínimo (Alerta de Compra)</label>
                    <input type="number" step="0.01" id="item_minimo" value="0" required class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-red-300 dark:border-red-800/50 text-red-600 dark:text-red-400 font-black rounded focus:ring-2 focus:ring-red-500">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Fornecedor / Observação (Opcional)</label>
                <textarea id="item_observacao" rows="2" placeholder="Informações de compra, links..." class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-teal-500"></textarea>
            </div>
            <div class="flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4 mt-6">
                <button type="button" onclick="fecharModalItem()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded font-bold transition shadow-sm">Salvar Material</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/almoxarifado.js?v=<?= time() ?>"></script>
<?php require_once 'includes/footer.php'; ?>