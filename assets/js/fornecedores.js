// assets/js/fornecedores.js

// 1. Filtro da Tabela em Tempo Real
function filtrarTabelaFornecedores() {
    let input = document.getElementById("filtro_tabela").value.toUpperCase();
    let rows = document.querySelectorAll(".row-fornecedor");
    
    rows.forEach(row => {
        let textContent = row.textContent || row.innerText;
        if (textContent.toUpperCase().indexOf(input) > -1) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}

// 2. Controlo do Modal (Abrir e Fechar)
const modal = document.getElementById('modalFornecedor');
const modalConteudo = document.getElementById('modalConteudo');

function abrirModalFornecedor() { 
    document.getElementById('formFornecedor').reset();
    document.getElementById('f_id').value = '';
    document.getElementById('modalTitulo').innerText = 'Novo Fornecedor';
    modal.classList.remove('hidden'); 
    setTimeout(() => { 
        modal.classList.remove('opacity-0'); 
        modalConteudo.classList.remove('scale-95'); 
    }, 10); 
}

function fecharModalFornecedor() { 
    modal.classList.add('opacity-0'); 
    modalConteudo.classList.add('scale-95'); 
    setTimeout(() => { 
        modal.classList.add('hidden'); 
    }, 300); 
}

// Fechar ao clicar fora do conteúdo
if(modal) {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) fecharModalFornecedor();
    });
}

// 3. Preencher Modal para Edição
function editarFornecedor(f) {
    document.getElementById('f_id').value = f.id;
    document.getElementById('f_nome').value = f.nome_fantasia;
    document.getElementById('f_razao').value = f.razao_social;
    document.getElementById('f_cnpj').value = f.cnpj_cpf;
    document.getElementById('f_contato').value = f.contato_nome;
    document.getElementById('f_telefone').value = f.telefone;
    document.getElementById('f_email').value = f.email;
    document.getElementById('modalTitulo').innerText = 'Editar Fornecedor';
    
    modal.classList.remove('hidden'); 
    setTimeout(() => { 
        modal.classList.remove('opacity-0'); 
        modalConteudo.classList.remove('scale-95'); 
    }, 10); 
}

// 4. API - Salvar ou Atualizar
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
        
        const result = await response.json().catch(() => null);
        
        if (response.ok && result && result.success) {
            window.location.reload();
        } else {
            const erroMsg = (result && result.error) ? result.error : `Erro HTTP ${response.status}`;
            alert('Erro: ' + erroMsg);
        }
    } catch (e) {
        alert('Erro de conexão ao salvar fornecedor.');
    }
}

// 5. API - Deletar
async function deletarFornecedor(id) {
    if(!confirm('Tem a certeza que deseja apagar este fornecedor? Esta ação não pode ser desfeita.')) return;
    
    try {
        const response = await fetch('api/delete_fornecedor.php', { 
            method: 'POST', 
            headers: {'Content-Type': 'application/json'}, 
            body: JSON.stringify({id}) 
        });
        
        const result = await response.json().catch(() => null);
        
        if (response.ok && result && result.success) {
            window.location.reload();
        } else {
            const erroMsg = (result && result.error) ? result.error : `Erro HTTP ${response.status}`;
            alert('Erro: ' + erroMsg);
        }
    } catch (e) {
        alert('Erro de rede ao tentar apagar fornecedor.');
    }
}