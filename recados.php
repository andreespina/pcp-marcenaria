<?php
// recados.php
require_once 'includes/auth.php';
protegerPagina();
require_once 'config/conexao.php';

try {
    // Busca os recados ordenando primeiro por prioridade e depois por data
    $stmt = $pdo->query("SELECT * FROM recados ORDER BY FIELD(prioridade, 'ALTA', 'MEDIA', 'BAIXA'), data_recado DESC, id DESC");
    $recados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    die("Erro na consulta: " . $e->getMessage());
}

function formatarData($data) {
    if (!$data) return '';
    return date('d/m/Y', strtotime($data));
}

function jsSafe($val) {
    if ($val === null) $val = '';
    return htmlspecialchars(json_encode($val), ENT_QUOTES, 'UTF-8');
}

// Configurações do Header
$page_title = 'RECADOS E AVISOS';
$page_subtitle = 'Mural de Informações e Alertas';
$menu_button_text = 'MENU';
$page_actions = '
<button onclick="abrirModalNovo()" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> NOVO RECADO
</button>';

require_once 'includes/header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php if (empty($recados)): ?>
        <div class="col-span-full bg-white dark:bg-gray-800 p-8 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 text-center">
            <p class="text-gray-500 dark:text-gray-400 italic">Nenhum recado registrado no mural.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($recados as $r): 
        // Define cores baseadas na prioridade
        $cor_borda = 'border-blue-300 dark:border-blue-600';
        $cor_bg = 'bg-blue-50 dark:bg-blue-900/20';
        $cor_texto_pri = 'text-blue-600 dark:text-blue-400';
        
        if ($r['prioridade'] == 'ALTA') {
            $cor_borda = 'border-red-400 dark:border-red-600';
            $cor_bg = 'bg-red-50 dark:bg-red-900/20';
            $cor_texto_pri = 'text-red-600 dark:text-red-400';
        } elseif ($r['prioridade'] == 'MEDIA') {
            $cor_borda = 'border-yellow-400 dark:border-yellow-600';
            $cor_bg = 'bg-yellow-50 dark:bg-yellow-900/20';
            $cor_texto_pri = 'text-yellow-600 dark:text-yellow-400';
        }
    ?>
        <!-- STICKY NOTE CARD -->
        <div class="<?= $cor_bg ?> border-t-4 <?= $cor_borda ?> rounded-b-lg shadow-md p-5 flex flex-col relative transform transition-transform hover:-translate-y-1">
            <div class="absolute top-2 right-2 flex space-x-2">
                <button onclick='abrirModalEdicao(<?= $r['id'] ?>, <?= jsSafe($r['data_recado']) ?>, <?= jsSafe($r['de_quem']) ?>, <?= jsSafe($r['para_quem']) ?>, <?= jsSafe($r['setor']) ?>, <?= jsSafe($r['prioridade']) ?>, <?= jsSafe($r['mensagem']) ?>)' class="text-gray-400 hover:text-blue-600 transition-colors" title="Editar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                </button>
                <button onclick="deletarRecado(<?= $r['id'] ?>)" class="text-gray-400 hover:text-red-600 transition-colors" title="Excluir">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </div>

            <div class="flex justify-between items-center mb-3 pr-10">
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400"><?= formatarData($r['data_recado']) ?></span>
                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded border <?= $cor_borda ?> <?= $cor_texto_pri ?>"><?= $r['prioridade'] ?></span>
            </div>

            <div class="mb-4">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">De: <span class="font-normal"><?= htmlspecialchars($r['de_quem']) ?></span></p>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Para: <span class="font-normal"><?= htmlspecialchars($r['para_quem']) ?></span></p>
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 mt-1 uppercase">Setor: <?= htmlspecialchars($r['setor']) ?></p>
            </div>

            <div class="bg-white/50 dark:bg-gray-800/50 p-3 rounded border border-gray-200/50 dark:border-gray-700/50 flex-1 overflow-y-auto" style="min-height: 100px;">
                <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap italic">"<?= htmlspecialchars($r['mensagem']) ?>"</p>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- MODAL DE CADASTRO/EDIÇÃO -->
<div id="modalRecado" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalRecadoConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
            <h3 id="modalTitulo" class="text-lg font-bold text-yellow-600 dark:text-yellow-500">Novo Recado</h3>
            <button onclick="fecharModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">&times;</button>
        </div>
        
        <form id="formRecado" onsubmit="salvarRecado(event)">
            <input type="hidden" id="recado_id">
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Data</label>
                    <input type="date" id="recado_data" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-yellow-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Prioridade</label>
                    <select id="recado_prioridade" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-yellow-500 font-bold">
                        <option value="BAIXA" class="text-blue-500">BAIXA</option>
                        <option value="MEDIA" class="text-yellow-500" selected>MÉDIA</option>
                        <option value="ALTA" class="text-red-500">ALTA</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">De quem (Remetente)</label>
                    <input type="text" id="recado_de" required placeholder="Ex: João" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-yellow-500 uppercase">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Para quem (Destinatário)</label>
                    <input type="text" id="recado_para" required placeholder="Ex: Maria" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-yellow-500 uppercase">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Setor / Departamento</label>
                    <input type="text" id="recado_setor" required placeholder="Ex: Produção, Expedição, Geral..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-yellow-500 uppercase">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Mensagem / Recado</label>
                <textarea id="recado_mensagem" rows="4" required placeholder="Escreva o recado aqui..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-yellow-500"></textarea>
            </div>

            <div class="flex justify-end space-x-3 border-t dark:border-gray-700 pt-4">
                <button type="button" onclick="fecharModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded font-bold transition shadow-sm">Salvar Recado</button>
            </div>
        </form>
    </div>
</div>

<!-- SCRIPTS EXTERNOS -->
<script src="assets/js/recados.js"></script>

<?php require_once 'includes/footer.php'; ?>