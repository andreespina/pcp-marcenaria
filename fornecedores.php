<?php
// fornecedores.php
require_once 'includes/auth.php';
protegerPagina();
require_once 'config/conexao.php';

try {
    $stmt = $pdo->query("SELECT * FROM fornecedores ORDER BY nome_fantasia ASC");
    $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_fornecedores = count($fornecedores);
} catch (\PDOException $e) {
    die("Erro ao carregar fornecedores: " . $e->getMessage());
}

$page_title = 'FORNECEDORES';
$page_subtitle = 'Gestão de Contatos e Parceiros';
$main_class = 'flex-1'; 
$menu_button_text = 'MENU';

$page_actions = '
<button onclick="abrirModalFornecedor()" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
    NOVO FORNECEDOR
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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-[#222736] rounded-lg border border-gray-200 dark:border-[#2a3142] shadow-sm p-4 flex items-center">
            <div class="p-3 rounded-full bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400 mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase">Total de Fornecedores</p>
                <p class="text-2xl font-black text-gray-800 dark:text-white"><?= $total_fornecedores ?></p>
            </div>
        </div>

        <div class="md:col-span-2 bg-white dark:bg-[#222736] rounded-lg border border-gray-200 dark:border-[#2a3142] shadow-sm p-4 flex flex-col justify-center">
            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Pesquisar Fornecedor</label>
            <div class="relative">
                <input type="text" id="filtro_tabela" onkeyup="filtrarTabelaFornecedores()" placeholder="Digite o nome, contato ou CNPJ..." class="w-full px-4 py-2 pl-10 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-orange-500 transition-colors">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-[#222736] rounded-lg border border-gray-200 dark:border-[#2a3142] shadow-sm overflow-hidden flex flex-col">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
            <h3 class="font-bold text-gray-700 dark:text-gray-200 text-sm uppercase tracking-wider">Lista de Parceiros Cadastrados</h3>
        </div>
        
        <div class="overflow-x-auto table-container">
            <table class="w-full text-left text-sm whitespace-nowrap" id="tabelaFornecedores">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-10 font-bold">
                    <tr>
                        <th class="px-6 py-3">Nome Fantasia</th>
                        <th class="px-6 py-3">CNPJ / CPF</th>
                        <th class="px-6 py-3">Contato & Email</th>
                        <th class="px-6 py-3">Telefone</th>
                        <th class="px-6 py-3 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-700 dark:text-gray-200">
                    <?php if (empty($fornecedores)): ?>
                        <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">Nenhum fornecedor cadastrado.</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach ($fornecedores as $f): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors row-fornecedor">
                            <td class="px-6 py-4">
                                <p class="font-bold uppercase text-gray-900 dark:text-white"><?= htmlspecialchars($f['nome_fantasia'] ?? '') ?></p>
                                <?php if(!empty($f['razao_social'])): ?>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase"><?= htmlspecialchars($f['razao_social']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-600 dark:text-gray-300">
                                <!-- Uso do Operador Elvis (?:) no PHP 8 -->
                                <?= htmlspecialchars($f['cnpj_cpf'] ?? '') ?: '<span class="italic text-gray-400">Não informado</span>' ?>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-800 dark:text-gray-200 uppercase"><?= htmlspecialchars($f['contato_nome'] ?? '') ?: '-' ?></p>
                                <?php if(!empty($f['email'])): ?>
                                    <p class="text-[11px] text-blue-500 dark:text-blue-400"><?= htmlspecialchars($f['email']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 font-bold text-gray-800 dark:text-gray-300">
                                <?= htmlspecialchars($f['telefone'] ?? '') ?: '-' ?>
                            </td>
                            <td class="px-6 py-4 text-center space-x-2">
                                <button onclick='editarFornecedor(<?= htmlspecialchars(json_encode($f), ENT_QUOTES, 'UTF-8') ?>)' class="text-blue-500 hover:text-blue-700 dark:hover:text-blue-400 bg-blue-50 dark:bg-blue-900/20 p-1.5 rounded transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <button onclick="deletarFornecedor(<?= $f['id'] ?? 0 ?>)" class="text-red-500 hover:text-red-700 dark:hover:text-red-400 bg-red-50 dark:bg-red-900/20 p-1.5 rounded transition-colors" title="Apagar">
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

<div id="modalFornecedor" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalConteudo">
        
        <div class="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
            <h3 id="modalTitulo" class="text-lg font-bold text-orange-600 dark:text-orange-500">Novo Fornecedor</h3>
            <button type="button" onclick="fecharModalFornecedor()" class="text-gray-400 hover:text-gray-800 dark:hover:text-white text-2xl font-bold">&times;</button>
        </div>

        <form id="formFornecedor" onsubmit="salvarFornecedor(event)">
            <input type="hidden" id="f_id" name="id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nome Fantasia (Obrigatório)</label>
                    <input type="text" id="f_nome" name="nome_fantasia" required placeholder="Ex: Madeireira Central" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase focus:ring-2 focus:ring-orange-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Razão Social</label>
                    <input type="text" id="f_razao" name="razao_social" placeholder="Ex: Central Madeiras LTDA" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">CNPJ / CPF</label>
                    <input type="text" id="f_cnpj" name="cnpj_cpf" placeholder="00.000.000/0000-00" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Telefone / WhatsApp</label>
                    <input type="text" id="f_telefone" name="telefone" placeholder="(00) 00000-0000" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nome do Contato</label>
                    <input type="text" id="f_contato" name="contato_nome" placeholder="Ex: João Silva" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">E-mail</label>
                    <input type="email" id="f_email" name="email" placeholder="contato@empresa.com" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded lowercase focus:ring-2 focus:ring-orange-500">
                </div>
            </div>

            <div class="flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4 mt-6">
                <button type="button" onclick="fecharModalFornecedor()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded font-bold transition shadow-sm">Salvar Fornecedor</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/fornecedores.js?v=<?= time() ?>"></script>

<?php require_once 'includes/footer.php'; ?>