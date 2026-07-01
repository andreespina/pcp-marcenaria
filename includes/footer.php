<?php
// includes/footer.php
?>
    </main>

    <footer class="mt-12 pt-6 border-t border-gray-200 dark:border-gray-700 text-center text-xs font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase transition-colors duration-300 pb-6">
        SISTEMA DE CONTROLE - PCP - AESPINA - 2026
    </footer>

    <script>
        // MENU DROPDOWN LOGIC
        const menuToggle = document.getElementById('menu-toggle');
        const dropdownMenu = document.getElementById('dropdown-menu');
        if (menuToggle && dropdownMenu) {
            function fecharMenu() { dropdownMenu.classList.add('opacity-0', 'scale-95'); setTimeout(() => { dropdownMenu.classList.add('hidden'); }, 200); }
            menuToggle.addEventListener('click', (e) => { e.stopPropagation(); if (dropdownMenu.classList.contains('hidden')) { dropdownMenu.classList.remove('hidden'); setTimeout(() => { dropdownMenu.classList.remove('opacity-0', 'scale-95'); }, 10); } else { fecharMenu(); } });
            document.addEventListener('click', (e) => { if (!dropdownMenu.contains(e.target) && e.target !== menuToggle) { fecharMenu(); } });
        }

        // DARK MODE TOGGLE
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
        if (themeToggleBtn) {
            if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) { 
                if(themeToggleLightIcon) themeToggleLightIcon.classList.remove('hidden'); 
            } else { 
                if(themeToggleDarkIcon) themeToggleDarkIcon.classList.remove('hidden'); 
            }
            themeToggleBtn.addEventListener('click', function() {
                if(themeToggleDarkIcon) themeToggleDarkIcon.classList.toggle('hidden'); 
                if(themeToggleLightIcon) themeToggleLightIcon.classList.toggle('hidden');
                
                if (localStorage.getItem('color-theme')) {
                    if (localStorage.getItem('color-theme') === 'light') { document.documentElement.classList.add('dark'); localStorage.setItem('color-theme', 'dark'); } 
                    else { document.documentElement.classList.remove('dark'); localStorage.setItem('color-theme', 'light'); }
                } else {
                    if (document.documentElement.classList.contains('dark')) { document.documentElement.classList.remove('dark'); localStorage.setItem('color-theme', 'light'); } 
                    else { document.documentElement.classList.add('dark'); localStorage.setItem('color-theme', 'dark'); }
                }
            });
        }
    </script>
</body>
</html>