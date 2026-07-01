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
$menu_button_text = 'MENU';
$page_actions = '
<button onclick="abrirModalItem()" class="bg-[#1e3a8a] dark:bg-blue-600 hover:bg-blue-800 dark:hover:bg-blue-500 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors">
    + ITEM
</button>';
require_once 'includes/header.php';
// -------------------------------------

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Almoxarifado - PCP Marcenaria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class', }</script>
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else { document.documentElement.classList.remove('dark') }
    </script>
</head>
<body class="bg-[#f4f7f6] dark:bg-gray-900 min-h-screen p-6 font-sans flex flex-col transition-colors duration-300">

<!-- GUIA RÁPIDO: ALMOXARIFADO -->
<details class="group bg-slate-50 dark:bg-slate-900/20 border border-slate-200 dark:border-slate-800 rounded-lg mb-6 shadow-sm transition-colors duration-300">
    <summary class="cursor-pointer p-4 font-bold text-lg text-slate-800 dark:text-slate-300 flex items-center justify-between select-none">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Guia Rápido: Controle de Estoque
        </div>
        <svg class="w-5 h-5 transition-transform duration-200 group-open:rotate-180 text-slate-800 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
    </summary>
    <div class="p-4 pt-0 mt-2 border-t border-slate-200 dark:border-slate-800">
        <ul class="text-sm text-slate-700 dark:text-slate-400 space-y-2 ml-1 mt-3">
            <li class="flex items-start">
                <span class="mr-2">📦</span>
                <span><strong>Cadastro e Edição:</strong> Use o botão <em>"+ ITEM"</em> para registrar novos materiais. Você pode organizar por categorias e definir um "Estoque Mínimo" para alertas. Para editar, clique no ícone do lápis (✏️).</span>
            </li>
            <li class="flex items-start">
                <span class="mr-2">⚡</span>
                <span><strong>Ações Rápidas:</strong> Na própria tabela, utilize os botões de <strong>+</strong> e <strong>-</strong> para dar entrada ou saída rápida em um material do estoque.</span>
            </li>
            <li class="flex items-start">
                <span class="mr-2">🚨</span>
                <span><strong>Alertas de Estoque:</strong> Itens com quantidade igual ou menor que o estoque mínimo definido ficarão marcados em vermelho com o status <em>COMPRAR</em>, facilitando a gestão de reposição.</span>
            </li>
        </ul>
    </div>
</details>

    <main class="flex-1 max-w-7xl mx-auto w-full">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Itens Cadastrados</p>
                <p class="text-3xl font-black text-gray-800 dark:text-white mt-1"><?= $total_itens ?></p>
            </div>
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-xs font-bold text-green-500 dark:text-green-400 uppercase tracking-wide">Estoque Saudável</p>
                <p class="text-3xl font-black text-green-600 dark:text-green-400 mt-1"><?= $estoque_ok ?></p>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 p-5 rounded-lg border <?= $itens_criticos > 0 ? 'border-red-400 shadow-md animate-pulse' : 'border-red-100 dark:border-red-800' ?>">
                <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-wide">Estoque Crítico / Em Falta</p>
                <p class="text-3xl font-black text-red-700 dark:text-red-300 mt-1"><?= $itens_criticos ?></p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 font-bold">Item</th>
                            <th class="px-4 py-3 font-bold">Categoria</th>
                            <th class="px-4 py-3 font-bold text-center">Status</th>
                            <th class="px-4 py-3 font-bold text-center">Estoque Atual</th>
                            <th class="px-4 py-3 font-bold text-center" title="Quantidade Mínima de Segurança">Mínimo</th>
                            <th class="px-4 py-3 font-bold text-center">Ações Rápidas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($itens)): ?>
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400 italic">Nenhum item cadastrado no almoxarifado.</td></tr>
                        <?php endif; ?>
                        
                        <?php foreach ($itens as $i): 
                            $is_critical = ($i['quantidade'] <= $i['quantidade_minima']);
                        ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-gray-700 dark:text-gray-200">
                                
                                <td class="px-4 py-3">
                                    <p class="font-bold text-gray-800 dark:text-white uppercase"><?= htmlspecialchars($i['nome_item']) ?></p>
                                    <?php if($i['observacao']): ?>
                                        <p class="text-[10px] text-gray-500 dark:text-gray-400"><?= htmlspecialchars($i['observacao']) ?></p>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-4 py-3"><span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-1 rounded text-xs font-semibold uppercase"><?= htmlspecialchars($i['categoria']) ?></span></td>
                                
                                <td class="px-4 py-3 text-center">
                                    <?php if ($is_critical): ?>
                                        <span class="bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 px-2 py-1 rounded text-xs font-bold uppercase border border-red-200 dark:border-red-800">Comprar</span>
                                    <?php else: ?>
                                        <span class="bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400 px-2 py-1 rounded text-xs font-bold uppercase">OK</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button onclick="ajustarEstoque(<?= $i['id'] ?>, -1, <?= $i['quantidade'] ?>)" class="w-6 h-6 rounded-full bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-900/50 dark:text-red-400 flex items-center justify-center font-bold focus:outline-none transition-colors">-</button>
                                        <span class="font-black text-lg w-12 text-center <?= $is_critical ? 'text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-gray-200' ?>"><?= (float)$i['quantidade'] ?> <span class="text-[10px] font-normal text-gray-500"><?= $i['unidade_medida'] ?></span></span>
                                        <button onclick="ajustarEstoque(<?= $i['id'] ?>, 1, <?= $i['quantidade'] ?>)" class="w-6 h-6 rounded-full bg-green-100 text-green-600 hover:bg-green-200 dark:bg-green-900/50 dark:text-green-400 flex items-center justify-center font-bold focus:outline-none transition-colors">+</button>
                                    </div>
                                </td>
                                
                                <td class="px-4 py-3 text-center text-sm font-semibold text-gray-500 dark:text-gray-400"><?= (float)$i['quantidade_minima'] ?></td>
                                
                                <td class="px-4 py-3 text-center">
                                    <button onclick='abrirModalEdicao(<?= $i['id'] ?>, <?= jsSafe($i['nome_item']) ?>, <?= jsSafe($i['categoria']) ?>, <?= jsSafe($i['quantidade']) ?>, <?= jsSafe($i['quantidade_minima']) ?>, <?= jsSafe($i['unidade_medida']) ?>, <?= jsSafe($i['observacao']) ?>)' class="text-blue-500 hover:text-blue-700 dark:hover:text-blue-300 transition-colors mx-1" title="Editar Item">
                                        &#9998;
                                    </button>
                                    <button onclick="deletarItem(<?= $i['id'] ?>)" class="text-red-400 hover:text-red-600 dark:hover:text-red-400 transition-colors text-lg mx-1" title="Apagar Item">
                                        &times;
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="modalItem" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalItemConteudo">
            <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
                <h3 id="modalTitulo" class="text-lg font-bold text-[#1e3a8a] dark:text-blue-400">Cadastrar Novo Item</h3>
                <button onclick="fecharModalItem()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">&times;</button>
            </div>
            
            <form id="formItem" onsubmit="salvarItemServidor(event)">
                <input type="hidden" id="item_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Descrição / Nome do Item</label>
                        <input type="text" id="item_nome" required placeholder="Ex: Dobradiça Reta com Amortecedor" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Categoria</label>
                        <select id="item_categoria" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase">
                            <option value="FERRAGENS">Ferragens</option>
                            <option value="CHAPAS MDF">Chapas MDF</option>
                            <option value="FITAS DE BORDA">Fitas de Borda</option>
                            <option value="PARAFUSOS">Parafusos / Fixação</option>
                            <option value="CONSUMIVEIS">Consumíveis (Cola, Lixa)</option>
                            <option value="GERAL">Geral</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Unidade de Medida</label>
                        <select id="item_unidade" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase">
                            <option value="UN">Unidade (UN)</option>
                            <option value="CX">Caixa (CX)</option>
                            <option value="M">Metro (M)</option>
                            <option value="CH">Chapa (CH)</option>
                            <option value="RL">Rolo (RL)</option>
                            <option value="LT">Litro (LT)</option>
                            <option value="KG">Quilo (KG)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Estoque Atual</label>
                        <input type="number" step="0.01" id="item_quantidade" value="0" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Estoque Mínimo (Alerta)</label>
                        <input type="number" step="0.01" id="item_minimo" value="0" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Fornecedor / Observação (Opcional)</label>
                    <textarea id="item_observacao" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="flex justify-end space-x-3 border-t dark:border-gray-700 pt-4">
                    <button type="button" onclick="fecharModalItem()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-[#1e3a8a] dark:bg-blue-600 hover:bg-blue-800 text-white rounded font-bold transition shadow-sm">Salvar Material</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // MENU DROPDOWN LOGIC
        const menuToggle = document.getElementById('menu-toggle');
        const dropdownMenu = document.getElementById('dropdown-menu');
        function fecharMenu() { dropdownMenu.classList.add('opacity-0', 'scale-95'); setTimeout(() => { dropdownMenu.classList.add('hidden'); }, 200); }
        menuToggle.addEventListener('click', (e) => { e.stopPropagation(); if (dropdownMenu.classList.contains('hidden')) { dropdownMenu.classList.remove('hidden'); setTimeout(() => { dropdownMenu.classList.remove('opacity-0', 'scale-95'); }, 10); } else { fecharMenu(); } });
        document.addEventListener('click', (e) => { if (!dropdownMenu.contains(e.target) && e.target !== menuToggle) { fecharMenu(); } });

        // DARK MODE TOGGLE
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) { themeToggleLightIcon.classList.remove('hidden'); } else { themeToggleDarkIcon.classList.remove('hidden'); }
        themeToggleBtn.addEventListener('click', function() {
            themeToggleDarkIcon.classList.toggle('hidden'); themeToggleLightIcon.classList.toggle('hidden');
            if (localStorage.getItem('color-theme')) {
                if (localStorage.getItem('color-theme') === 'light') { document.documentElement.classList.add('dark'); localStorage.setItem('color-theme', 'dark'); } 
                else { document.documentElement.classList.remove('dark'); localStorage.setItem('color-theme', 'light'); }
            } else {
                if (document.documentElement.classList.contains('dark')) { document.documentElement.classList.remove('dark'); localStorage.setItem('color-theme', 'light'); } 
                else { document.documentElement.classList.add('dark'); localStorage.setItem('color-theme', 'dark'); }
            }
        });

        // FUNÇÕES DE ESTOQUE RÁPIDO (+ e -)
        async function ajustarEstoque(id, alteracao, qtd_atual) {
            const novaQtd = parseFloat(qtd_atual) + alteracao;
            if (novaQtd < 0) return; // Não deixa ficar negativo via botão rápido

            try {
                const response = await fetch('api/update_estoque.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id, quantidade: novaQtd }) });
                const result = await response.json();
                if (result.success) { window.location.reload(); } else { alert('Erro ao atualizar: ' + result.error); }
            } catch (error) { alert('Erro de rede.'); }
        }

        // DELETAR ITEM
        async function deletarItem(id) {
            if (!confirm(`Tem certeza que deseja apagar este material do almoxarifado?`)) return;
            try {
                const response = await fetch('api/delete_item_almox.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) });
                const result = await response.json();
                if (result.success) { window.location.reload(); } else { alert('Erro ao apagar: ' + result.error); }
            } catch (error) { alert('Erro de rede ao apagar.'); }
        }

        // MODAL DE CADASTRO / EDIÇÃO
        const modalItem = document.getElementById('modalItem'); 
        const modalItemConteudo = document.getElementById('modalItemConteudo');

        function abrirModalItem() {
            document.getElementById('formItem').reset();
            document.getElementById('item_id').value = '';
            document.getElementById('modalTitulo').innerText = 'Cadastrar Novo Item';
            modalItem.classList.remove('hidden'); 
            setTimeout(() => { modalItem.classList.remove('opacity-0'); modalItemConteudo.classList.remove('scale-95'); }, 10);
        }

        function abrirModalEdicao(id, nome, cat, qtd, min, und, obs) {
            document.getElementById('item_id').value = id;
            document.getElementById('item_nome').value = nome;
            document.getElementById('item_categoria').value = cat;
            document.getElementById('item_quantidade').value = qtd;
            document.getElementById('item_minimo').value = min;
            document.getElementById('item_unidade').value = und;
            document.getElementById('item_observacao').value = obs || '';
            
            document.getElementById('modalTitulo').innerText = 'Editar Material';
            modalItem.classList.remove('hidden'); 
            setTimeout(() => { modalItem.classList.remove('opacity-0'); modalItemConteudo.classList.remove('scale-95'); }, 10);
        }

        function fecharModalItem() {
            modalItem.classList.add('opacity-0'); modalItemConteudo.classList.add('scale-95'); 
            setTimeout(() => { modalItem.classList.add('hidden'); }, 300);
        }

        async function salvarItemServidor(event) {
            event.preventDefault();
            const id = document.getElementById('item_id').value;
            const endpoint = id ? 'api/edit_item_almox.php' : 'api/add_item_almox.php';
            
            const payload = {
                id: id,
                nome_item: document.getElementById('item_nome').value,
                categoria: document.getElementById('item_categoria').value,
                quantidade: document.getElementById('item_quantidade').value,
                quantidade_minima: document.getElementById('item_minimo').value,
                unidade_medida: document.getElementById('item_unidade').value,
                observacao: document.getElementById('item_observacao').value
            };

            try {
                const response = await fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const result = await response.json();
                if (result.success) { window.location.reload(); } else { alert('Erro: ' + (result.error || 'Erro desconhecido')); }
            } catch (error) { alert('Erro de rede.'); }
        }

        modalItem.addEventListener('click', (e) => { if (e.target === modalItem) fecharModalItem(); });
    </script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>