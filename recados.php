<?php
// recados.php
require_once 'includes/auth.php';
protegerPagina();
require_once 'config/conexao.php';

try {
    // Busca os recados
    $stmt = $pdo->query("SELECT * FROM recados ORDER BY FIELD(prioridade, 'ALTA', 'MEDIA', 'BAIXA'), data_recado DESC, id DESC");
    $recados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Busca as mensagens padrões
    $stmt_msg = $pdo->query("SELECT * FROM mensagens_padroes ORDER BY etapa ASC, titulo ASC");
    $mensagens_padroes = $stmt_msg->fetchAll(PDO::FETCH_ASSOC);

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
$page_title = 'RECADOS E COMUNICAÇÃO';
$page_subtitle = 'Mural Interno e Textos Padrões para Clientes';
$main_class = 'flex-1'; 
$menu_button_text = 'MENU';
$page_actions = '
<div class="flex space-x-2">
    <button onclick="abrirModalNovo()" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> <span class="hidden md:inline">NOVO RECADO</span><span class="md:hidden">RECADO</span>
    </button>
    <button onclick="abrirModalNovaMensagem()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg> <span class="hidden md:inline">TEXTO WPP</span><span class="md:hidden">TEXTO</span>
    </button>
</div>';

require_once 'includes/header.php';
?>

<div class="flex flex-col gap-6">

    <!-- GUIA RÁPIDO -->
    <details class="group bg-yellow-50 dark:bg-yellow-900/10 border border-yellow-200 dark:border-yellow-900/50 rounded-lg shadow-sm transition-colors duration-300">
        <summary class="cursor-pointer p-4 font-bold text-sm text-yellow-700 dark:text-yellow-500 flex items-center justify-between select-none uppercase tracking-wide">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Guia Rápido: Mural e Textos
            </div>
            <svg class="w-5 h-5 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </summary>
        <div class="p-4 pt-0 mt-2 border-t border-yellow-200 dark:border-yellow-900/50">
            <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-2 ml-1 mt-3">
                <li class="flex items-start">
                    <span class="mr-2">📝</span>
                    <span><strong>Mural Interno:</strong> Use o botão <em>"NOVO RECADO"</em> para deixar mensagens para outros setores (ex: Projetos para Produção). Recados de alta prioridade ficam destacados em vermelho no topo.</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">📱</span>
                    <span><strong>Textos Padrões:</strong> Crie mensagens de WhatsApp (botão <em>"TEXTO WPP"</em>) que você envia com frequência aos clientes. Depois, basta clicar em "Copiar Texto" no bloco verde para colar direto no WhatsApp Web!</span>
                </li>
            </ul>
        </div>
    </details>

    <!-- ÁREA 1: TEXTOS PADRÕES -->
    <div>
        <h2 class="text-lg font-bold text-green-600 dark:text-green-400 mb-4 flex items-center border-b border-gray-200 dark:border-gray-700 pb-2">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
            Textos Padrões (WhatsApp / E-mail)
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <?php if (empty($mensagens_padroes)): ?>
                <div class="col-span-full bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 text-center">
                    <p class="text-gray-500 dark:text-gray-400 italic">Nenhuma mensagem padrão cadastrada.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($mensagens_padroes as $msg): ?>
                <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] p-4 flex flex-col relative group transition-all hover:shadow-md hover:border-green-300 dark:hover:border-green-600">
                    <div class="absolute top-2 right-2 flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onclick='abrirModalEdicaoMensagem(<?= $msg['id'] ?>, <?= jsSafe($msg['titulo']) ?>, <?= jsSafe($msg['etapa']) ?>, <?= jsSafe($msg['mensagem']) ?>)' class="bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 p-1.5 rounded hover:bg-blue-100 dark:hover:bg-blue-800 transition-colors" title="Editar">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        </button>
                        <button onclick="deletarMensagem(<?= $msg['id'] ?>)" class="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 p-1.5 rounded hover:bg-red-100 dark:hover:bg-red-800 transition-colors" title="Excluir">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                    
                    <span class="text-[9px] font-black uppercase tracking-wider text-green-600 dark:text-green-400 mb-1 border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 self-start px-2 py-0.5 rounded"><?= htmlspecialchars($msg['etapa']) ?></span>
                    <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-2 truncate pr-14 text-sm uppercase"><?= htmlspecialchars($msg['titulo']) ?></h3>
                    
                    <div class="bg-gray-50 dark:bg-gray-800/80 p-3 rounded text-xs text-gray-600 dark:text-gray-300 italic mb-4 flex-1 whitespace-pre-wrap overflow-y-auto max-h-32 scrollbar-thin border border-gray-100 dark:border-gray-700"><?= htmlspecialchars($msg['mensagem']) ?></div>
                    
                    <button onclick="copiarMensagem(this)" data-texto="<?= htmlspecialchars($msg['mensagem']) ?>" class="w-full bg-green-50 hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/40 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800/50 py-2 rounded text-[11px] font-bold uppercase transition-colors flex items-center justify-center btn-copiar">
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                        <span>Copiar Texto</span>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ÁREA 2: MURAL DE RECADOS -->
    <div>
        <h2 class="text-lg font-bold text-yellow-600 dark:text-yellow-500 mb-4 flex items-center border-b border-gray-200 dark:border-gray-700 pb-2">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            Mural de Recados Internos
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <?php if (empty($recados)): ?>
                <div class="col-span-full bg-white dark:bg-gray-800 p-8 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 text-center">
                    <p class="text-gray-500 dark:text-gray-400 italic">Nenhum recado registrado no mural.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($recados as $r): 
                $cor_borda = 'border-blue-300 dark:border-blue-600';
                $cor_bg = 'bg-blue-50 dark:bg-[#1c2333]/50';
                $cor_texto_pri = 'text-blue-600 dark:text-blue-400';
                
                if ($r['prioridade'] == 'ALTA') {
                    $cor_borda = 'border-red-400 dark:border-red-600';
                    $cor_bg = 'bg-red-50 dark:bg-red-900/10';
                    $cor_texto_pri = 'text-red-600 dark:text-red-400';
                } elseif ($r['prioridade'] == 'MEDIA') {
                    $cor_borda = 'border-yellow-400 dark:border-yellow-600';
                    $cor_bg = 'bg-yellow-50 dark:bg-yellow-900/10';
                    $cor_texto_pri = 'text-yellow-600 dark:text-yellow-400';
                }
            ?>
                <div class="<?= $cor_bg ?> border-t-4 <?= $cor_borda ?> border border-gray-200 dark:border-[#2a3142] rounded-b-lg shadow-sm p-4 flex flex-col relative group transition-all hover:-translate-y-1 hover:shadow-md">
                    <div class="absolute top-2 right-2 flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onclick='abrirModalEdicao(<?= $r['id'] ?>, <?= jsSafe($r['data_recado']) ?>, <?= jsSafe($r['de_quem']) ?>, <?= jsSafe($r['para_quem']) ?>, <?= jsSafe($r['setor']) ?>, <?= jsSafe($r['prioridade']) ?>, <?= jsSafe($r['mensagem']) ?>)' class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 bg-white/50 dark:bg-gray-800/80 p-1.5 rounded transition-colors" title="Editar">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        </button>
                        <button onclick="deletarRecado(<?= $r['id'] ?>)" class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 bg-white/50 dark:bg-gray-800/80 p-1.5 rounded transition-colors" title="Excluir">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>

                    <div class="flex justify-between items-center mb-2.5 pr-14">
                        <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400"><?= formatarData($r['data_recado']) ?></span>
                        <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded border <?= $cor_borda ?> bg-white/50 dark:bg-gray-800/50 <?= $cor_texto_pri ?> tracking-wider"><?= $r['prioridade'] ?></span>
                    </div>

                    <div class="mb-3 space-y-0.5">
                        <p class="text-xs font-semibold text-gray-800 dark:text-gray-200">De: <span class="font-normal"><?= htmlspecialchars($r['de_quem']) ?></span></p>
                        <p class="text-xs font-semibold text-gray-800 dark:text-gray-200">Para: <span class="font-normal"><?= htmlspecialchars($r['para_quem']) ?></span></p>
                        <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 mt-1 uppercase bg-white/40 dark:bg-gray-800/40 inline-block px-1.5 rounded">Setor: <?= htmlspecialchars($r['setor']) ?></p>
                    </div>

                    <div class="bg-white/60 dark:bg-gray-800/60 p-3 rounded border border-white dark:border-gray-700/50 flex-1 overflow-y-auto scrollbar-thin shadow-inner" style="min-height: 90px; max-height: 150px;">
                        <p class="text-xs text-gray-800 dark:text-gray-300 whitespace-pre-wrap italic font-medium leading-relaxed">"<?= htmlspecialchars($r['mensagem']) ?>"</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAIS DE RECADOS E MENSAGENS                  -->
<!-- ============================================== -->

<div id="modalRecado" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalRecadoConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
            <h3 id="modalTitulo" class="text-lg font-bold text-yellow-600 dark:text-yellow-500">Novo Recado</h3>
            <button onclick="fecharModal()" class="text-gray-400 hover:text-gray-800 dark:hover:text-white text-2xl font-bold">&times;</button>
        </div>
        
        <form id="formRecado" onsubmit="salvarRecado(event)">
            <input type="hidden" id="recado_id">
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Data</label>
                    <input type="date" id="recado_data" required class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-yellow-500 text-sm font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Prioridade</label>
                    <select id="recado_prioridade" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-yellow-500 font-bold uppercase text-sm">
                        <option value="BAIXA" class="text-blue-500">BAIXA</option>
                        <option value="MEDIA" class="text-yellow-500" selected>MÉDIA</option>
                        <option value="ALTA" class="text-red-500">ALTA</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">De quem</label>
                    <input type="text" id="recado_de" required placeholder="Ex: João" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-yellow-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Para quem</label>
                    <input type="text" id="recado_para" required placeholder="Ex: Maria" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-yellow-500 uppercase text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Setor / Departamento Destino</label>
                    <input type="text" id="recado_setor" required placeholder="Ex: Produção, Expedição..." class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-yellow-500 uppercase text-sm">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Mensagem / Recado</label>
                <textarea id="recado_mensagem" rows="4" required placeholder="Escreva o recado aqui..." class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-yellow-500 text-sm"></textarea>
            </div>

            <div class="flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                <button type="button" onclick="fecharModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded font-bold transition shadow-sm">Salvar Recado</button>
            </div>
        </form>
    </div>
</div>

<div id="modalMensagem" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-xl p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalMensagemConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
            <h3 id="modalTituloMsg" class="text-lg font-bold text-green-600 dark:text-green-500">Nova Mensagem Padrão</h3>
            <button onclick="fecharModalMensagem()" class="text-gray-400 hover:text-gray-800 dark:hover:text-white text-2xl font-bold">&times;</button>
        </div>
        
        <form id="formMensagem" onsubmit="salvarMensagem(event)">
            <input type="hidden" id="msg_id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Título da Mensagem (Para você identificar)</label>
                    <input type="text" id="msg_titulo" required placeholder="Ex: Confirmação de Medidas" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-green-500 uppercase text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Fase do Atendimento / Etapa</label>
                    <select id="msg_etapa" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-green-500 font-bold uppercase text-sm">
                        <option value="GERAL">Geral / Sem Etapa</option>
                        <option value="PRE-PRODUCAO">Pré-Produção / Projetos</option>
                        <option value="PRODUCAO">Produção / Marcenaria</option>
                        <option value="INSTALACAO">Montagem e Instalação</option>
                        <option value="ASSISTENCIA">Assistência Técnica</option>
                        <option value="COBRANCA">Cobrança / Financeiro</option>
                    </select>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Texto da Mensagem (Cópia exata para o WhatsApp)</label>
                <textarea id="msg_texto" rows="6" required placeholder="Olá! Gostaríamos de informar que seu projeto..." class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-green-500 text-sm"></textarea>
            </div>

            <div class="flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                <button type="button" onclick="fecharModalMensagem()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded font-bold transition shadow-sm">Salvar Padrão</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/recados.js?v=<?= time() ?>"></script>

<?php require_once 'includes/footer.php'; ?>