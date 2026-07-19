// assets/js/configuracoes.js

// 1. Controle das Abas
function mudarAba(abaId) {
    const conteudos = document.querySelectorAll('.tab-content');
    conteudos.forEach(c => c.classList.add('hidden'));

    const botoes = document.querySelectorAll('.tab-btn');
    botoes.forEach(b => {
        b.classList.remove('text-blue-600', 'border-blue-600', 'dark:text-blue-400', 'dark:border-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20');
        b.classList.add('text-gray-500', 'border-transparent', 'hover:text-gray-600', 'hover:border-gray-300', 'dark:text-gray-400', 'dark:hover:text-gray-300');
    });

    document.getElementById('conteudo_' + abaId).classList.remove('hidden');

    const btnAtivo = document.getElementById('btn_' + abaId);
    btnAtivo.classList.remove('text-gray-500', 'border-transparent', 'hover:text-gray-600', 'hover:border-gray-300', 'dark:text-gray-400', 'dark:hover:text-gray-300');
    btnAtivo.classList.add('text-blue-600', 'border-blue-600', 'dark:text-blue-400', 'dark:border-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20');
}

// 2. Pré-visualização da Logo da Empresa
function previewImagem(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('preview_logo').src = e.target.result;
            document.getElementById('preview_logo').classList.remove('hidden');
            if(document.getElementById('icone_sem_logo')) {
                document.getElementById('icone_sem_logo').classList.add('hidden');
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// 3. Salvar Dados da Empresa na Base de Dados
async function salvarEmpresa(event) {
    event.preventDefault();
    const btn = document.getElementById('btn_salvar_empresa');
    const txtOriginal = btn.innerHTML;
    btn.innerHTML = 'SALVANDO...';
    btn.disabled = true;

    const form = document.getElementById('formEmpresa');
    const formData = new FormData(form);

    try {
        const response = await fetch('api/save_empresa.php', { method: 'POST', body: formData });
        const result = await response.json().catch(() => null);
        
        if (response.ok && result && result.success) {
            alert("Dados da empresa atualizados com sucesso!");
            window.location.reload();
        } else {
            const erroMsg = (result && result.error) ? result.error : `Erro HTTP ${response.status}`;
            alert("Erro: " + erroMsg);
            btn.innerHTML = txtOriginal;
            btn.disabled = false;
        }
    } catch (error) {
        alert("Erro de comunicação com o servidor.");
        btn.innerHTML = txtOriginal;
        btn.disabled = false;
    }
}

// 4. Modais e APIs de Cadastros Base
const modalCad = document.getElementById('modalCadastroBase');
const modalCadConteudo = document.getElementById('modalCadastroBaseConteudo');

function abrirModalCadastro(tipo, tituloVisual) {
    document.getElementById('formCadastroBase').reset();
    document.getElementById('cad_tipo').value = tipo;
    document.getElementById('modalCadTitulo').innerText = 'Adicionar ' + tituloVisual;
    document.getElementById('lbl_cad_nome').innerText = 'Nome para ' + tituloVisual;
    
    modalCad.classList.remove('hidden');
    setTimeout(() => { modalCad.classList.remove('opacity-0'); modalCadConteudo.classList.remove('scale-95'); }, 10);
}

function fecharModalCadastro() {
    modalCad.classList.add('opacity-0'); modalCadConteudo.classList.add('scale-95');
    setTimeout(() => { modalCad.classList.add('hidden'); }, 300);
}

if(modalCad) {
    modalCad.addEventListener('click', (e) => { 
        if (e.target === modalCad) fecharModalCadastro(); 
    });
}

async function salvarCadastroBase(event) {
    event.preventDefault();
    
    const payload = {
        tipo: document.getElementById('cad_tipo').value,
        nome: document.getElementById('cad_nome').value
    };

    try {
        const response = await fetch('api/save_cadastro.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const result = await response.json().catch(() => null);
        
        if (response.ok && result && result.success) {
            window.location.reload();
        } else {
            const erroMsg = (result && result.error) ? result.error : `Erro HTTP ${response.status}`;
            alert("Erro: " + erroMsg);
        }
    } catch (error) {
        alert("Erro de comunicação com o servidor.");
    }
}

async function deletarCadastro(id) {
    if (!confirm("Tem certeza que deseja apagar este item das listas?")) return;
    
    try {
        const response = await fetch('api/delete_cadastro.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json().catch(() => null);
        
        if (response.ok && result && result.success) {
            window.location.reload();
        } else {
            const erroMsg = (result && result.error) ? result.error : `Erro HTTP ${response.status}`;
            alert("Erro: " + erroMsg);
        }
    } catch (error) {
        alert("Erro de comunicação com o servidor.");
    }
}