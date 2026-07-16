// assets/js/relatorios.js

function mudarAba(abaId) {
    // 1. Ocultar todos os conteúdos das abas
    const conteudos = document.querySelectorAll('.tab-content');
    conteudos.forEach(c => c.classList.add('hidden'));

    // 2. Remover o estilo 'ativo' de todos os botões
    const botoes = document.querySelectorAll('.tab-btn');
    botoes.forEach(b => {
        b.classList.remove('text-blue-600', 'border-blue-600', 'dark:text-blue-400', 'dark:border-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20');
        b.classList.add('text-gray-500', 'border-transparent', 'hover:text-gray-600', 'hover:border-gray-300', 'dark:text-gray-400', 'dark:hover:text-gray-300');
    });

    // 3. Mostrar a aba selecionada
    document.getElementById('conteudo_' + abaId).classList.remove('hidden');

    // 4. Adicionar o estilo 'ativo' ao botão clicado
    const btnAtivo = document.getElementById('btn_' + abaId);
    btnAtivo.classList.remove('text-gray-500', 'border-transparent', 'hover:text-gray-600', 'hover:border-gray-300', 'dark:text-gray-400', 'dark:hover:text-gray-300');
    btnAtivo.classList.add('text-blue-600', 'border-blue-600', 'dark:text-blue-400', 'dark:border-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20');
}

// Filtro Universal para as Tabelas
function filtrarTabela(inputId, rowClass) {
    const filtro = document.getElementById(inputId).value.toLowerCase();
    const linhas = document.querySelectorAll('.' + rowClass);
    
    linhas.forEach(linha => {
        // Busca apenas nos elementos marcados com 'td-busca' para evitar sujeira HTML
        const colunasBusca = linha.querySelectorAll('.td-busca');
        let textoLinha = '';
        
        colunasBusca.forEach(col => {
            textoLinha += col.textContent.toLowerCase() + ' ';
        });

        if (textoLinha.includes(filtro)) {
            linha.style.display = '';
        } else {
            linha.style.display = 'none';
        }
    });
}

function imprimirRelatorio() {
    window.print();
}