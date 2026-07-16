<?php
// calendario.php
require_once 'includes/auth.php';
protegerPagina();
require_once 'config/conexao.php';

$page_title = 'CALENDÁRIO DE INSTALAÇÕES';
$page_subtitle = 'Gestão Visual e Google Agenda';
// O segredo do tamanho reduzido está aqui: max-w-5xl (não ocupa a tela toda) e justify-center
$main_class = 'flex-1 w-full max-w-5xl mx-auto';
$menu_button_text = 'MENU';
$menu_button_class = 'bg-[#1e3a8a] dark:bg-blue-600 hover:bg-blue-800 dark:hover:bg-blue-500 text-white';

// Adicionando o script do FullCalendar e o Plugin do Google Agenda
$head_extras = '
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/google-calendar@6.1.10/index.global.min.js"></script>
<style>
    /* Estilo Minimalista e Clean */
    .fc { font-family: inherit; }
    .fc-toolbar-title { font-size: 1.1rem !important; font-weight: 800 !important; text-transform: uppercase; color: #475569; }
    .dark .fc-toolbar-title { color: #cbd5e1; }
    
    /* Botões Limpos */
    .fc-button-primary { background-color: #f1f5f9 !important; border: none !important; color: #475569 !important; font-weight: bold !important; box-shadow: none !important; text-transform: capitalize; transition: all 0.2s;}
    .fc-button-primary:hover { background-color: #e2e8f0 !important; color: #1e293b !important; }
    .dark .fc-button-primary { background-color: #334155 !important; color: #e2e8f0 !important; }
    .dark .fc-button-primary:hover { background-color: #475569 !important; color: #fff !important; }
    .fc-button-active { background-color: #e0e7ff !important; color: #1e3a8a !important; }
    .dark .fc-button-active { background-color: #1e3a8a !important; color: #bfdbfe !important; }
    
    /* Bordas Suaves */
    .fc-theme-standard td, .fc-theme-standard th { border-color: #f1f5f9; }
    .dark .fc-theme-standard td, .dark .fc-theme-standard th { border-color: #334155; }
    .fc-col-header-cell { padding: 8px 0; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid #e2e8f0 !important; }
    .dark .fc-col-header-cell { color: #94a3b8; border-bottom-color: #475569 !important; }
    .fc-day-today { background-color: #f8fafc !important; }
    .dark .fc-day-today { background-color: #1e293b !important; }
    
    /* Eventos com Cores Claras/Pastéis */
    .fc-event { 
        cursor: pointer; 
        border: none !important; 
        border-radius: 6px; 
        padding: 4px 6px; 
        font-size: 0.7rem; 
        font-weight: 700; 
        margin-bottom: 2px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        transition: transform 0.1s;
    }
    .fc-event:hover { transform: scale(1.02); }
</style>';

require_once 'includes/header.php';
?>

<div class="bg-white dark:bg-[#222736] rounded-2xl shadow-sm border border-gray-100 dark:border-[#2a3142] p-6 lg:p-8 transition-colors duration-300">
    
    <div class="mb-6 flex flex-wrap gap-5 text-[11px] font-bold text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700 pb-4 uppercase tracking-wide justify-center">
        <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-[#e0e7ff] mr-2 ring-2 ring-[#e0e7ff]/50"></span> Projetos (PCP)</div>
        <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-[#dcfce7] mr-2 ring-2 ring-[#dcfce7]/50"></span> Google Agenda</div>
    </div>
    
    <div id="calendar" class="w-full"></div>
</div>

<div id="modalEvento" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm p-6 border border-gray-100 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalEventoConteudo">
        <div class="flex justify-between items-center mb-4 border-b border-gray-100 dark:border-gray-700 pb-3">
            <h3 class="text-sm font-black uppercase tracking-wider text-[#1e3a8a] dark:text-blue-400">Detalhes da Instalação</h3>
            <button onclick="fecharModalEvento()" class="text-gray-400 hover:text-gray-800 dark:hover:text-white text-xl font-bold">&times;</button>
        </div>
        <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
            <p class="flex justify-between border-b border-gray-50 dark:border-gray-700/50 pb-2">
                <span class="text-gray-400">Cliente:</span> 
                <span id="ev_cliente" class="uppercase font-bold text-gray-800 dark:text-gray-100"></span>
            </p>
            <p class="flex justify-between border-b border-gray-50 dark:border-gray-700/50 pb-2">
                <span class="text-gray-400">Equipa Resp:</span> 
                <span id="ev_equipe" class="uppercase font-bold text-indigo-600 dark:text-indigo-400"></span>
            </p>
            <p class="flex flex-col border-b border-gray-50 dark:border-gray-700/50 pb-2">
                <span class="text-gray-400 text-xs mb-1">Período Agendado:</span> 
                <span id="ev_periodo" class="font-bold"></span>
            </p>
            <p class="flex justify-between">
                <span class="text-gray-400">Fase (Kanban):</span> 
                <span id="ev_status" class="uppercase font-bold bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-[10px]"></span>
            </p>
        </div>
        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-between gap-3">
            <button onclick="fecharModalEvento()" class="flex-1 px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition font-bold text-xs uppercase tracking-wide">Fechar</button>
            <a href="index.php" class="flex-1 text-center px-4 py-2 bg-[#1e3a8a] hover:bg-blue-700 text-white rounded-lg font-bold transition text-xs uppercase tracking-wide shadow-sm">Ver no Kanban</a>
        </div>
    </div>
</div>

<script src="assets/js/calendario.js?v=<?= time() ?>"></script>
<?php require_once 'includes/footer.php'; ?>