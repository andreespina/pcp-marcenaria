// assets/js/impressoes.js

function mudarModelo(modelo) {
    const modelos = ['capa', 'checklist', 'ferramentas', 'producao'];
    
    modelos.forEach(mod => {
        const el = document.getElementById('mod_' + mod);
        if(el) {
            el.classList.add('hidden');
            el.classList.remove('active-print-tab');
        }
        
        const btn = document.getElementById('btn_' + mod);
        if(btn) {
            btn.className = 'bg-gray-200 text-gray-700 hover:bg-gray-300 px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors';
        }
    });

    const elAtivo = document.getElementById('mod_' + modelo);
    if(elAtivo) {
        elAtivo.classList.remove('hidden');
        elAtivo.classList.add('active-print-tab');
    }

    const btnAtivo = document.getElementById('btn_' + modelo);
    if(btnAtivo) {
        btnAtivo.className = 'bg-blue-600 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors';
    }
}