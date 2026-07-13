<?php
// fornecedores.php
require_once 'includes/auth.php';
protegerPagina();
require_once 'config/conexao.php';

try {
    $stmt = $pdo->query("SELECT * FROM fornecedores ORDER BY nome_fantasia ASC");
    $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    die("Erro ao carregar fornecedores: " . $e->getMessage());
}

$page_title = 'GESTÃO DE FORNECEDORES';
$page_subtitle = 'Cadastro e Contatos';
$page_actions = '
<button onclick="abrirModalFornecedor()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
    NOVO FORNECEDOR
</button>';

require_once 'includes/header.php';
?>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <table class="w-full text-left text-sm whitespace-nowrap">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
            <tr>
                <th class="px-6 py-3 font-bold">Nome Fantasia</th>
                <th class="px-6 py-3 font-bold">CNPJ/CPF</th>
                <th class="px-6 py-3 font-bold">Contato</th>
                <th class="px-6 py-3 font-bold">Telefone</th>
                <th class="px-6 py-3 font-bold text-center">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <?php foreach ($fornecedores as $f): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-6 py-3 font-bold text-gray-800 dark:text-gray-200 uppercase"><?= htmlspecialchars($f['nome_fantasia']) ?></td>
                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($f['cnpj_cpf']) ?></td>
                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($f['contato_nome']) ?></td>
                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($f['telefone']) ?></td>
                    <td class="px-6 py-3 text-center">
                        <button onclick='editarFornecedor(<?= json_encode($f) ?>)' class="text-blue-500 hover:text-blue-700 font-bold mr-3">Editar</button>
                        <button onclick="deletarFornecedor(<?= $f['id'] ?>)" class="text-red-500 hover:text-red-700 font-bold">Apagar</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="modalFornecedor" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300">
        <h3 id="modalTitulo" class="text-lg font-bold text-blue-600 mb-4">Novo Fornecedor</h3>
        <form id="formFornecedor" onsubmit="salvarFornecedor(event)">
            <input type="hidden" id="f_id" name="id">
            <div class="grid grid-cols-1 gap-4">
                <input type="text" id="f_nome" name="nome_fantasia" required placeholder="Nome Fantasia" class="w-full px-3 py-2 border rounded">
                <input type="text" id="f_razao" name="razao_social" placeholder="Razão Social" class="w-full px-3 py-2 border rounded">
                <input type="text" id="f_cnpj" name="cnpj_cpf" placeholder="CNPJ ou CPF" class="w-full px-3 py-2 border rounded">
                <input type="text" id="f_contato" name="contato_nome" placeholder="Nome do Contato" class="w-full px-3 py-2 border rounded">
                <input type="text" id="f_telefone" name="telefone" placeholder="Telefone" class="w-full px-3 py-2 border rounded">
                <input type="email" id="f_email" name="email" placeholder="E-mail" class="w-full px-3 py-2 border rounded">
            </div>
            <div class="flex justify-end mt-6 gap-2">
                <button type="button" onclick="fecharModalFornecedor()" class="px-4 py-2 bg-gray-200 rounded">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('modalFornecedor');
    function abrirModalFornecedor() { modal.classList.remove('hidden'); setTimeout(() => modal.classList.remove('opacity-0'), 10); }
    function fecharModalFornecedor() { modal.classList.add('opacity-0'); setTimeout(() => modal.classList.add('hidden'), 300); }
    
    function editarFornecedor(f) {
        document.getElementById('f_id').value = f.id;
        document.getElementById('f_nome').value = f.nome_fantasia;
        document.getElementById('f_razao').value = f.razao_social;
        document.getElementById('f_cnpj').value = f.cnpj_cpf;
        document.getElementById('f_contato').value = f.contato_nome;
        document.getElementById('f_telefone').value = f.telefone;
        document.getElementById('f_email').value = f.email;
        document.getElementById('modalTitulo').innerText = 'Editar Fornecedor';
        abrirModalFornecedor();
    }

    async function salvarFornecedor(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        const data = Object.fromEntries(formData.entries());
        const endpoint = data.id ? 'api/edit_fornecedor.php' : 'api/add_fornecedor.php';
        
        const response = await fetch(endpoint, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data) });
        const result = await response.json();
        if(result.success) window.location.reload(); else alert(result.error);
    }

    async function deletarFornecedor(id) {
        if(!confirm('Apagar fornecedor?')) return;
        const res = await fetch('api/delete_fornecedor.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id}) });
        if((await res.json()).success) window.location.reload();
    }
</script>

<?php require_once 'includes/footer.php'; ?>