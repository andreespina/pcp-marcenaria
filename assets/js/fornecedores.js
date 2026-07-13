// assets/js/fornecedores.js

// Abrir e fechar modal
const modal = document.getElementById('modalFornecedor');
const modalConteudo = modal.querySelector('div');

function abrirModalFornecedor() { 
    document.getElementById('formFornecedor').reset();
    document.getElementById('f_id').value = '';
    document.getElementById('modalTitulo').innerText = 'Novo Fornecedor';
    modal.classList.remove('hidden'); 
    setTimeout(() => { modal.classList.remove('opacity-0'); modalConteudo.classList.remove('scale-95'); }, 10); 
}

function fecharModalFornecedor() { 
    modal.classList.add('opacity-0'); modalConteudo.classList.add('scale-95'); 
    setTimeout(() => { modal.classList.add('hidden'); }, 300); 
}

// Salvar (Adicionar ou Editar)
async function salvarFornecedor(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    const endpoint = data.id ? 'api/edit_fornecedor.php' : 'api/add_fornecedor.php';
    
    try {
        const response = await fetch(endpoint, { 
            method: 'POST', 
            headers: {'Content-Type': 'application/json'}, 
            body: JSON.stringify(data) 
        });
        const result = await response.json();
        if(result.success) {
            window.location.reload();
        } else {
            alert('Erro: ' + result.error);
        }
    } catch (e) {
        alert('Erro de conexão.');
    }
}

// Editar (Preencher modal)
function editarFornecedor(f) {
    document.getElementById('f_id').value = f.id;
    document.getElementById('f_nome').value = f.nome_fantasia;
    document.getElementById('f_razao').value = f.razao_social;
    document.getElementById('f_cnpj').value = f.cnpj_cpf;
    document.getElementById('f_contato').value = f.contato_nome;
    document.getElementById('f_telefone').value = f.telefone;
    document.getElementById('f_email').value = f.email;
    document.getElementById('modalTitulo').innerText = 'Editar Fornecedor';
    abrirModalFornecedor();
}

// Deletar
async function deletarFornecedor(id) {
    if(!confirm('Tem a certeza que deseja apagar este fornecedor?')) return;
    try {
        const res = await fetch('api/delete_fornecedor.php', { 
            method: 'POST', 
            headers: {'Content-Type': 'application/json'}, 
            body: JSON.stringify({id}) 
        });
        const result = await res.json();
        if(result.success) window.location.reload();
        else alert(result.error);
    } catch (e) {
        alert('Erro ao apagar.');
    }
}

 const modal = document.getElementById('modalFornecedor');
    function abrirModalFornecedor() { modal.classList.remove('hidden'); setTimeout(() => modal.classList.remove('opacity-0'), 10); }
    function fecharModalFornecedor() { modal.classList.add('opacity-0'); setTimeout(() => modal.classList.add('hidden'), 300); }
    
    function editarFornecedor(f) {
        document.getElementById('f_id').value = f.id;
        document.getElementById('f_nome').value = f.nome_fantasia;
        document.getElementById('f_razao').value = f.razao_social;
        document.getElementById('f_cnpj').value = f.cnpj_cpf;
        document.getElementById('f_contato').value = f.contato_nome;
        document.getElementById('f_telefone').value = f.telefone;
        document.getElementById('f_email').value = f.email;
        document.getElementById('modalTitulo').innerText = 'Editar Fornecedor';
        abrirModalFornecedor();
    }

    async function salvarFornecedor(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        const data = Object.fromEntries(formData.entries());
        const endpoint = data.id ? 'api/edit_fornecedor.php' : 'api/add_fornecedor.php';
        
        const response = await fetch(endpoint, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data) });
        const result = await response.json();
        if(result.success) window.location.reload(); else alert(result.error);
    }

    async function deletarFornecedor(id) {
        if(!confirm('Apagar fornecedor?')) return;
        const res = await fetch('api/delete_fornecedor.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id}) });
        if((await res.json()).success) window.location.reload();
    }