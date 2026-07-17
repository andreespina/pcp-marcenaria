<?php
// usuarios.php
require_once 'includes/auth.php';
protegerPagina();

// Bloqueio de segurança: Apenas utilizadores com perfil ADMIN podem ver esta tela
if (!isset($_SESSION['usuario_role']) || $_SESSION['usuario_role'] !== 'ADMIN') {
    header("Location: index.php?erro=acesso_negado");
    exit;
}

require_once 'config/conexao.php';

try {
    // Busca todos os usuários do sistema
    $stmt = $pdo->query("SELECT id, nome_completo, usuario, setor, role, permissoes FROM usuarios ORDER BY nome_completo ASC");
    $lista_usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- NOVO: Buscando Cadastros Base para Usuários ---
    $stmt_setor = $pdo->query("SELECT nome FROM cadastros_base WHERE tipo = 'SETOR' ORDER BY nome ASC");
    $setores = $stmt_setor->fetchAll(PDO::FETCH_COLUMN);
    // ---------------------------------------------------

} catch (\PDOException $e) {
    die("Erro ao buscar utilizadores: " . $e->getMessage());
}

$page_title = 'GESTÃO DE ACESSOS';
$page_subtitle = 'Controlo de Usuários, Setores e Permissões';
$menu_button_text = 'MENU';
$page_actions = '
<button onclick="abrirModalUsuario()" class="bg-[#1e3a8a] hover:bg-blue-800 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
    CADASTRAR USUÁRIO
</button>';

$head_extras = '
<style>
    .dark body { background-color: #1a1e2b !important; }
    .app-container { height: calc(100vh - 120px); display: flex; flex-direction: column; }
    .kanban-col::-webkit-scrollbar { width: 6px; }
    .kanban-col::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
    .dark .kanban-col::-webkit-scrollbar-thumb { background-color: #3f4865; }
</style>';

require_once 'includes/header.php';
?>

<div class="app-container gap-6">
    <div class="bg-white dark:bg-[#222736] rounded-lg border border-gray-200 dark:border-[#2a3142] shadow-sm overflow-hidden flex flex-col flex-1 transition-colors duration-300">
        
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
            <h3 class="font-bold text-gray-700 dark:text-gray-200 text-sm uppercase tracking-wider">Controlo de Contas Ativas</h3>
            <span class="text-xs font-bold text-gray-500 dark:text-gray-400"><?= count($lista_usuarios) ?> Usuários no Sistema</span>
        </div>
        
        <div class="overflow-x-auto flex-1 kanban-col">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-10 font-bold">
                    <tr>
                        <th class="px-6 py-3">Nome Completo</th>
                        <th class="px-6 py-3">Login de Acesso</th>
                        <th class="px-6 py-3">Setor / Departamento</th>
                        <th class="px-6 py-3">Nível Tipo</th>
                        <th class="px-6 py-3">Módulos Permitidos</th>
                        <th class="px-6 py-3 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-700 dark:text-gray-200">
                    <?php foreach ($lista_usuarios as $usr): 
                        // Decodifica as permissões salvas no banco
                        $perms_arr = !empty($usr['permissoes']) ? json_decode($usr['permissoes'], true) : [];
                        if (!is_array($perms_arr)) $perms_arr = [];
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-4 font-bold uppercase tracking-wide text-gray-900 dark:text-white">
                                <?= htmlspecialchars($usr['nome_completo'] ? $usr['nome_completo'] : 'NÃO INFORMADO') ?>
                            </td>
                            <td class="px-6 py-4 font-medium text-blue-600 dark:text-blue-400 font-mono"><?= htmlspecialchars($usr['usuario']) ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 uppercase">
                                    <?= htmlspecialchars($usr['setor'] ? $usr['setor'] : 'NÃO DEFINIDO') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if($usr['role'] === 'ADMIN'): ?>
                                    <span class="text-xs bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 px-2 py-0.5 rounded font-black border border-red-200 dark:border-red-800">ADMINISTRADOR</span>
                                <?php else: ?>
                                    <span class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 px-2 py-0.5 rounded font-bold border border-blue-200 dark:border-blue-800">OPERADOR (USER)</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-xs font-mono max-w-xs truncate">
                                <?php 
                                if($usr['role'] === 'ADMIN') {
                                    echo '<span class="text-red-500 font-bold uppercase">ACESSO TOTAL MASTER</span>';
                                } else {
                                    echo !empty($perms_arr) ? implode(', ', array_map('strtoupper', $perms_arr)) : '<span class="text-gray-400 italic">NENHUM</span>';
                                }
                                ?>
                            </td>
                            <td class="px-6 py-4 text-center space-x-3">
                                <button onclick='editarUsuario(<?= json_encode($usr, JSON_UNESCAPED_UNICODE) ?>)' class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-bold transition-colors" title="Editar Informações e Permissões">
                                    &#9998; EDITAR
                                </button>
                                <?php if($usr['id'] != $_SESSION['usuario_id']): ?>
                                    <button onclick="excluirUsuario(<?= $usr['id'] ?>)" class="text-red-500 hover:text-red-700 font-bold transition-colors" title="Remover Usuário">&times; APAGAR</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalUsuario" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300 max-h-[95vh] overflow-y-auto" id="modalUsuarioConteudo">
        <div class="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
            <h3 id="modalTitulo" class="text-lg font-bold text-blue-700 dark:text-blue-400">Configuração de Conta de Acesso</h3>
            <button type="button" onclick="fecharModalUsuario()" class="text-gray-400 hover:text-gray-800 dark:hover:text-white text-2xl font-bold">&times;</button>
        </div>
        
        <form id="formUsuario" onsubmit="salvarUsuario(event)">
            <input type="hidden" id="usr_id" name="id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nome Completo do Funcionário</label>
                    <input type="text" id="usr_nome_completo" name="nome_completo" required placeholder="Ex: André Luiz Espina" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase focus:ring-2 focus:ring-blue-500 font-bold">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Setor / Departamento</label>
                    <select id="usr_setor" name="setor" required class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase focus:ring-2 focus:ring-blue-500 font-bold">
                        <option value="">Selecione...</option>
                        <?php foreach($setores as $setor): ?>
                            <option value="<?= htmlspecialchars($setor) ?>"><?= htmlspecialchars($setor) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Perfil Tipo de Acesso</label>
                    <select id="usr_role" name="role" required onchange="togglePermissoesVisuais()" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded uppercase focus:ring-2 focus:ring-blue-500 font-bold">
                        <option value="USER">Operador comum (USER)</option>
                        <option value="ADMIN">Administrador Master (ADMIN)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nome de Usuário (Login)</label>
                    <input type="text" id="usr_username" name="usuario" required placeholder="Ex: andre.espina" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded font-mono focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Senha de Acesso</label>
                    <input type="password" id="usr_password" name="senha" placeholder="Deixe em branco para manter a atual" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
                    <p id="txt_ajuda_senha" class="text-[10px] text-gray-400 mt-1 hidden">Ao editar, preencha apenas se quiser alterar a senha dele.</p>
                </div>
            </div>

            <div id="container_permissoes" class="bg-gray-50 dark:bg-gray-950 p-4 rounded border border-gray-200 dark:border-gray-700 mb-6 transition-all">
                <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-3 tracking-wide flex items-center">
                    <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Liberar Módulos Específicos (Para Perfil USER)
                </h4>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs font-bold text-gray-700 dark:text-gray-300">
                    <label class="flex items-center space-x-2 cursor-pointer bg-white dark:bg-gray-800 p-2 rounded border dark:border-gray-700 hover:border-blue-400"><input type="checkbox" name="permissoes[]" value="comercial" class="rounded text-blue-600"><span>COMERCIAL / CRM</span></label>
                    <label class="flex items-center space-x-2 cursor-pointer bg-white dark:bg-gray-800 p-2 rounded border dark:border-gray-700 hover:border-blue-400"><input type="checkbox" name="permissoes[]" value="projetos" class="rounded text-blue-600"><span>PAINEL PCP</span></label>
                    <label class="flex items-center space-x-2 cursor-pointer bg-white dark:bg-gray-800 p-2 rounded border dark:border-gray-700 hover:border-blue-400"><input type="checkbox" name="permissoes[]" value="clientes" class="rounded text-blue-600"><span>CLIENTES</span></label>
                    <label class="flex items-center space-x-2 cursor-pointer bg-white dark:bg-gray-800 p-2 rounded border dark:border-gray-700 hover:border-blue-400"><input type="checkbox" name="permissoes[]" value="almoxarifado" class="rounded text-blue-600"><span>ALMOXARIFADO</span></label>
                    <label class="flex items-center space-x-2 cursor-pointer bg-white dark:bg-gray-800 p-2 rounded border dark:border-gray-700 hover:border-blue-400"><input type="checkbox" name="permissoes[]" value="assistencias" class="rounded text-blue-600"><span>ASSISTÊNCIAS</span></label>
                    <label class="flex items-center space-x-2 cursor-pointer bg-white dark:bg-gray-800 p-2 rounded border dark:border-gray-700 hover:border-blue-400"><input type="checkbox" name="permissoes[]" value="recados" class="rounded text-blue-600"><span>RECADOS</span></label>
                    <label class="flex items-center space-x-2 cursor-pointer bg-white dark:bg-gray-800 p-2 rounded border dark:border-gray-700 hover:border-blue-400"><input type="checkbox" name="permissoes[]" value="relatorios" class="rounded text-blue-600"><span>RELATÓRIOS</span></label>
                    <label class="flex items-center space-x-2 cursor-pointer bg-white dark:bg-gray-800 p-2 rounded border dark:border-gray-700 hover:border-blue-400"><input type="checkbox" name="permissoes[]" value="financeiro" class="rounded text-blue-600"><span>FINANCEIRO</span></label>
                </div>
            </div>

            <div class="flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                <button type="button" onclick="fecharModalUsuario()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-bold transition shadow-sm">Confirmar Conta</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/usuarios.js?v=<?= time() ?>"></script>
<?php require_once 'includes/footer.php'; ?>