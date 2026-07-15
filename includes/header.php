<?php
// includes/header.php
$page_title = isset($page_title) ? $page_title : 'PCP Marcenaria';
$page_subtitle = isset($page_subtitle) ? $page_subtitle : '';
$page_title_color = isset($page_title_color) ? $page_title_color : 'text-[#1e3a8a] dark:text-blue-400';
$main_class = isset($main_class) ? $main_class : 'flex-1 max-w-7xl mx-auto w-full';
$menu_button_text = isset($menu_button_text) ? $menu_button_text : 'Ir para...';
$menu_button_class = isset($menu_button_class) ? $menu_button_class : 'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - SBG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class', }</script>
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else { document.documentElement.classList.remove('dark') }
    </script>
    <?= isset($head_extras) ? $head_extras : '' ?>
</head>
<body class="bg-[#f4f7f6] dark:bg-gray-900 min-h-screen p-6 font-sans flex flex-col transition-colors duration-300">
    <main class="<?= $main_class ?>">
        <header class="flex justify-between items-center mb-6 border-b pb-4 border-gray-200 dark:border-gray-700 relative">
            <div>
                <h1 class="text-3xl font-extrabold <?= $page_title_color ?> tracking-tight"><?= htmlspecialchars($page_title) ?></h1>
                <?php if($page_subtitle): ?>
                    <span class="text-sm text-gray-500 dark:text-gray-400 font-medium"><?= htmlspecialchars($page_subtitle) ?></span>
                <?php endif; ?>
            </div>
            
            <div class="flex items-center space-x-4">
                <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none rounded-lg text-sm p-2 transition-colors">
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path></svg>
                </button>
                
                <?php if(!isset($hide_user_greeting) || !$hide_user_greeting): ?>
                <?php 
                    $nomeExibicao = 'Usuário';
                    if (isset($_SESSION['usuario_nome_completo']) && !empty($_SESSION['usuario_nome_completo'])) {
                        $nomeExibicao = $_SESSION['usuario_nome_completo'];
                    } elseif (isset($_SESSION['usuario_nome']) && !empty($_SESSION['usuario_nome'])) {
                        $nomeExibicao = $_SESSION['usuario_nome'];
                    }
                ?>
                <span class="text-sm font-semibold text-gray-600 dark:text-gray-300 hidden md:inline">Olá, <?= htmlspecialchars($nomeExibicao) ?></span>
                <?php endif; ?>
                
                <?= isset($page_actions) ? $page_actions : '' ?>
                
                <?php if(!isset($hide_menu) || !$hide_menu): ?>
                <div class="relative">
                    <button id="menu-toggle" class="flex items-center space-x-1 <?= $menu_button_class ?> px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors focus:outline-none">
                        <span><?= $menu_button_text ?></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div id="dropdown-menu" class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 hidden z-50 overflow-hidden transform opacity-0 scale-95 transition-all duration-200 origin-top-right">
                        
                        <?php 
                        $role = isset($_SESSION['usuario_role']) ? $_SESSION['usuario_role'] : 'USER';
                        $permissoes = isset($_SESSION['usuario_permissoes']) ? $_SESSION['usuario_permissoes'] : []; 
                        ?>

                        <?php if($role === 'ADMIN' || in_array('projetos', $permissoes)): ?>
                        <a href="index.php" class="w-full text-left px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center transition-colors">
                            <svg class="w-4 h-4 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>PAINEL PCP
                        </a>
                        <a href="calendario.php" class="w-full text-left px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center border-t border-gray-100 dark:border-gray-700 transition-colors">
                            <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>CALENDÁRIO
                        </a>
                        <?php endif; ?>

                        <?php if($role === 'ADMIN' || in_array('comercial', $permissoes)): ?>
                        <a href="comercial.php" class="w-full text-left px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center border-t border-gray-100 dark:border-gray-700 transition-colors">
                            <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>COMERCIAL / CRM
                        </a>
                        <?php endif; ?>

                        <?php if($role === 'ADMIN' || in_array('financeiro', $permissoes)): ?>
                        <a href="administrativo.php" class="w-full text-left px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center border-t border-gray-100 dark:border-gray-700 transition-colors">
                            <svg class="w-4 h-4 mr-2 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>ADMINISTRATIVO
                        </a>
                        <?php endif; ?>

                        <?php if($role === 'ADMIN' || in_array('assistencias', $permissoes)): ?>
                        <a href="assistencias.php" class="w-full text-left px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center border-t border-gray-100 dark:border-gray-700 transition-colors">
                            <svg class="w-4 h-4 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>ASSISTÊNCIAS
                        </a>
                        <?php endif; ?>

                        <?php if($role === 'ADMIN' || in_array('almoxarifado', $permissoes)): ?>
                        <a href="almoxarifado.php" class="w-full text-left px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center border-t border-gray-100 dark:border-gray-700 transition-colors">
                            <svg class="w-4 h-4 mr-2 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>ALMOXARIFADO
                        </a>
                        <?php endif; ?>

                        <?php if($role === 'ADMIN' || in_array('clientes', $permissoes)): ?>
                        <a href="clientes.php" class="w-full text-left px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center border-t border-gray-100 dark:border-gray-700 transition-colors">
                            <svg class="w-4 h-4 mr-2 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>CLIENTES
                        </a>
                        <?php endif; ?>

                        <?php if($role === 'ADMIN' || in_array('clientes', $permissoes) || in_array('financeiro', $permissoes) || in_array('almoxarifado', $permissoes)): ?>
                        <a href="fornecedores.php" class="w-full text-left px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center border-t border-gray-100 dark:border-gray-700 transition-colors">
                            <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>FORNECEDORES
                        </a>
                        <?php endif; ?>

                        <?php if($role === 'ADMIN' || in_array('financeiro', $permissoes)): ?>
                        <a href="financeiro.php" class="w-full text-left px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center border-t border-gray-100 dark:border-gray-700 transition-colors">
                            <svg class="w-4 h-4 mr-2 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>FINANCEIRO
                        </a>
                        <?php endif; ?>

                        <?php if($role === 'ADMIN' || in_array('relatorios', $permissoes)): ?>
                        <a href="relatorios.php" class="w-full text-left px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center border-t border-gray-100 dark:border-gray-700 transition-colors">
                            <svg class="w-4 h-4 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>RELATÓRIOS
                        </a>
                        <?php endif; ?>

                        <?php if($role === 'ADMIN' || in_array('recados', $permissoes)): ?>
                        <a href="recados.php" class="w-full text-left px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center border-t border-gray-100 dark:border-gray-700 transition-colors">
                            <svg class="w-4 h-4 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>RECADOS
                        </a>
                        <?php endif; ?>
                        
                        <?= isset($menu_extras) ? $menu_extras : '' ?>
                        
                        <a href="logout.php" class="w-full text-left px-4 py-3 text-sm font-bold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 flex items-center border-t border-gray-100 dark:border-gray-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>SAIR
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </header>