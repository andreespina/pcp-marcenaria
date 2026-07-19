// assets/js/usuarios.js

// Esconde ou desativa as permissões se for um ADMIN (já possui acesso total nativo)
function togglePermissoesVisuais() {
    const role = document.getElementById('usr_role').value;
    const container = document.getElementById('container_permissoes');
    const checkboxes = container.querySelectorAll('input[type="checkbox"]');
    
    if (role === 'ADMIN') {
        container.style.opacity = '0.4';
        checkboxes.forEach(cb => {
            cb.checked = true;
            cb.disabled = true;
        });
    } else {
        container.style.opacity = '1';
        checkboxes.forEach(cb => {
            cb.disabled = false;
        });
    }
}

const modalUsr = document.getElementById('modalUsuario');
const modalUsrConteudo = document.getElementById('modalUsuarioConteudo');

function abrirModalUsuario() {
    document.getElementById('formUsuario').reset();
    document.getElementById('usr_id').value = '';
    document.getElementById('usr_password').removeAttribute('required');
    document.getElementById('txt_ajuda_senha').style.display = 'none';
    document.getElementById('modalTitulo').innerText = 'Cadastrar Novo Funcionário';
    
    // Inicia checkboxes limpos
    const checkboxes = document.querySelectorAll('input[name="permissoes[]"]');
    checkboxes.forEach(cb => cb.checked = false);
    
    document.getElementById('usr_role').value = 'USER';
    togglePermissoesVisuais();

    modalUsr.classList.remove('hidden');
    setTimeout(() => { modalUsr.classList.remove('opacity-0'); modalUsrConteudo.classList.remove('scale-95'); }, 10);
}

function editarUsuario(dados) {
    document.getElementById('formUsuario').reset();
    document.getElementById('usr_id').value = dados.id;
    document.getElementById('usr_nome_completo').value = dados.nome_completo || '';
    document.getElementById('usr_setor').value = dados.setor || '';
    document.getElementById('usr_role').value = dados.role || 'USER';
    document.getElementById('usr_username').value = dados.usuario || '';
    
    document.getElementById('usr_password').removeAttribute('required');
    document.getElementById('txt_ajuda_senha').style.display = 'block';
    document.getElementById('modalTitulo').innerText = 'Editar Perfil / Permissões';

    // Trata e ativa as caixas de permissões salvas no JSON do banco
    const checkboxes = document.querySelectorAll('input[name="permissoes[]"]');
    checkboxes.forEach(cb => cb.checked = false); // limpa primeiro

    if (dados.permissoes) {
        let perms = [];
        try {
            perms = JSON.parse(dados.permissoes);
        } catch(e) { perms = []; }
        
        if (Array_isArray(perms) || typeof perms === 'object') {
            checkboxes.forEach(cb => {
                if (perms.includes(cb.value)) {
                    cb.checked = true;
                }
            });
        }
    }

    togglePermissoesVisuais();

    modalUsr.classList.remove('hidden');
    setTimeout(() => { modalUsr.classList.remove('opacity-0'); modalUsrConteudo.classList.remove('scale-95'); }, 10);
}

function fecharModalUsuario() {
    modalUsr.classList.add('opacity-0'); modalUsrConteudo.classList.add('scale-95');
    setTimeout(() => { modalUsr.classList.add('hidden'); }, 300);
}

if(modalUsr) { modalUsr.addEventListener('click', (e) => { if (e.target === modalUsr) fecharModalUsuario(); }); }

// Envio dos dados para a API
async function salvarUsuario(event) {
    event.preventDefault();
    const id = document.getElementById('usr_id').value;
    const endpoint = id ? 'api/edit_usuario.php' : 'api/add_usuario.php';
    
    // Coleta manual das checkboxes marcadas
    const checkboxes = document.querySelectorAll('input[name="permissoes[]"]:checked');
    const permissoesMarcadas = Array.from(checkboxes).map(cb => cb.value);

    const payload = {
        id: id,
        nome_completo: document.getElementById('usr_nome_completo').value,
        setor: document.getElementById('usr_setor').value,
        role: document.getElementById('usr_role').value,
        usuario: document.getElementById('usr_username').value,
        senha: document.getElementById('usr_password').value,
        permissoes: permissoesMarcadas
    };

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        
        const result = await response.json().catch(() => null);
        
        if (response.ok && result && result.success) {
            window.location.reload(); 
        } else {
            const erroMsg = (result && result.error) ? result.error : `Falha no servidor (HTTP ${response.status})`;
            alert('Erro: ' + erroMsg);
        }
    } catch(e) { 
        alert('Falha de conexão com a API.'); 
    }
}

async function excluirUsuario(id) {
    if(!confirm("Atenção: Tem a certeza que deseja remover esta conta de acesso em definitivo?")) return;
    try {
        const response = await fetch('api/delete_usuario.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: id})
        });
        
        const result = await response.json().catch(() => null);
        
        if (response.ok && result && result.success) {
            window.location.reload();
        } else {
            const erroMsg = (result && result.error) ? result.error : `Falha ao apagar (HTTP ${response.status})`;
            alert('Erro: ' + erroMsg);
        }
    } catch(e) { 
        alert('Falha na API.'); 
    }
}

// Auxiliar para compatibilidade de verificação de arrays
function Array_isArray(obj) {
    return Object.prototype.toString.call(obj) === '[object Array]';
}