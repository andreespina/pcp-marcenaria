<?php
// calendario.php
require_once 'includes/auth.php';
protegerPagina();
require_once 'config/conexao.php';

$page_title = 'CALENDÁRIO DE INSTALAÇÕES';
$page_subtitle = 'Gestão Visual de Equipes';
$main_class = 'flex-1';
$menu_button_text = 'MENU';
$menu_button_class = 'bg-[#1e3a8a] dark:bg-blue-600 hover:bg-blue-800 dark:hover:bg-blue-500 text-white';

// Adicionando o script do FullCalendar no Head
$head_extras = '
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<style>
    .fc-event { cursor: pointer; border: none; border-radius: 4px; padding: 2px 4px; font-size: 0.75rem; font-weight: bold; }
    .fc-toolbar-title { font-size: 1.25rem !important; font-weight: 900 !important; text-transform: uppercase; color: #1e3a8a; }
    .dark .fc-toolbar-title { color: #60a5fa; }
    .fc-theme-standard td, .fc-theme-standard th { border-color: #e5e7eb; }
    .dark .fc-theme-standard td, .dark .fc-theme-standard th { border-color: #374151; }
    .dark .fc-col-header-cell { background-color: #1f2937; color: #e5e7eb; }
    .fc-day-today { background-color: #eff6ff !important; }
    .dark .fc-day-today { background-color: #1e3a8a40 !important; }
</style>';

require_once 'includes/header.php';
?>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5 transition-colors duration-300">
    
    <div class="mb-5 flex flex-wrap gap-4 text-xs font-bold text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700 pb-4">
        <div class="flex items-center"><span class="w-4 h-4 rounded shadow-sm bg-[#10b981] mr-2"></span> Em Instalação (Sem Equipa Definida)</div>
        <div class="flex items-center"><span class="w-4 h-4 rounded shadow-sm bg-[#ef4444] mr-2"></span> Atrasado (Sem Equipa Definida)</div>
        <div class="flex items-center"><span class="w-4 h-4 rounded shadow-sm bg-purple-600 mr-2"></span> As restantes cores são geradas automaticamente por Equipa!</div>
    </div>
    
    <div id="calendar"></div>
</div>

<div id="modalEvento" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalEventoConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
            <h3 class="text-lg font-bold text-[#1e3a8a] dark:text-blue-400">Detalhes da Obra</h3>
            <button onclick="fecharModalEvento()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">&times;</button>
        </div>
        <div class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
            <p><strong>Cliente:</strong> <span id="ev_cliente" class="uppercase font-bold"></span></p>
            <p><strong>Equipa Resp:</strong> <span id="ev_equipe" class="uppercase font-bold text-indigo-600 dark:text-indigo-400"></span></p>
            <p><strong>Período:</strong> <span id="ev_periodo"></span></p>
            <p><strong>Fase Atual (Kanban):</strong> <span id="ev_status" class="uppercase"></span></p>
        </div>
        <div class="mt-6 flex justify-between">
            <a href="index.php" class="px-4 py-2 bg-[#1e3a8a] hover:bg-blue-700 text-white rounded font-medium transition text-sm">Ir para o Kanban</a>
            <button onclick="fecharModalEvento()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium text-sm">Fechar</button>
        </div>
    </div>
</div>

<script src="assets/js/calendario.js"></script>
<?php require_once 'includes/footer.php'; ?>