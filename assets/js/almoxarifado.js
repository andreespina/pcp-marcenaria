// assets/js/almoxarifado.js

// 1. Filtro da Tabela em Tempo Real
function filtrarTabelaAlmoxarifado() {
    let input = document.getElementById("filtro_tabela_almox").value.toUpperCase();
    let rows = document.querySelectorAll(".row-almox");
    
    rows.forEach(row => {
        let textContent = row.textContent || row.innerText;
        if (textContent.toUpperCase().indexOf(input) > -1) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}

// 2. Controle do Modal (Abrir e Fechar)
const modalItem = document.getElementById('modalItem'); 
const modalItemConteudo = document.getElementById('modalItemConteudo');

function abrirModalItem() {
    document.getElementById('formItem').reset();
    document.getElementById('item_id').value = '';
    document.getElementById('modalTitulo').innerText = 'Cadastrar Novo Material';
    
    modalItem.classList.remove('hidden'); 
    setTimeout(() => { 
        modalItem.classList.remove('opacity-0'); 
        modalItemConteudo.classList.remove('scale-95'); 
    }, 10);
}

function abrirModalEdicao(id, nome, cat, qtd, min, und, obs) {
    document.getElementById('item_id').value = id;
    document.getElementById('item_nome').value = nome;
    document.getElementById('item_categoria').value = cat;
    document.getElementById('item_quantidade').value = qtd;
    document.getElementById('item_minimo').value = min;
    document.getElementById('item_unidade').value = und;
    document.getElementById('item_observacao').value = obs || '';
    
    document.getElementById('modalTitulo').innerText = 'Editar Material';
    
    modalItem.classList.remove('hidden'); 
    setTimeout(() => { 
        modalItem.classList.remove('opacity-0'); 
        modalItemConteudo.classList.remove('scale-95'); 
    }, 10);
}

function fecharModalItem() {
    modalItem.classList.add('opacity-0'); 
    modalItemConteudo.classList.add('scale-95'); 
    setTimeout(() => { 
        modalItem.classList.add('hidden'); 
    }, 300);
}

// Fechar ao clicar fora do conteúdo
if(modalItem) {
    modalItem.addEventListener('click', (e) => {
        if (e.target === modalItem) fecharModalItem();
    });
}

// 3. Ações Rápidas (Ajuste de Estoque na Tabela)
async function ajustarEstoque(id, alteracao, qtd_atual) {
    const novaQtd = parseFloat(qtd_atual) + alteracao;
    if (novaQtd < 0) return; // Impede estoque negativo
    
    try {
        const response = await fetch('api/update_estoque.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify({ id: id, quantidade: novaQtd }) 
        });
        const result = await response.json();
        
        if (result.success) { 
            window.location.reload(); 
        } else { 
            alert('Erro ao atualizar: ' + result.error); 
        }
    } catch (error) { 
        alert('Erro de comunicação com o servidor.'); 
    }
}

// 4. API - Salvar ou Atualizar
async function salvarItemServidor(event) {
    event.preventDefault();
    const id = document.getElementById('item_id').value;
    const endpoint = id ? 'api/edit_item_almox.php' : 'api/add_item_almox.php';
    
    const payload = { 
        id: id, 
        nome_item: document.getElementById('item_nome').value, 
        categoria: document.getElementById('item_categoria').value, 
        quantidade: document.getElementById('item_quantidade').value, 
        quantidade_minima: document.getElementById('item_minimo').value, 
        unidade_medida: document.getElementById('item_unidade').value, 
        observacao: document.getElementById('item_observacao').value 
    };
    
    try {
        const response = await fetch(endpoint, { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify(payload) 
        });
        const result = await response.json();
        
        if (result.success) { 
            window.location.reload(); 
        } else { 
            alert('Erro: ' + (result.error || 'Erro desconhecido.')); 
        }
    } catch (error) { 
        alert('Erro de rede ao salvar material.'); 
    }
}

// 5. API - Deletar
async function deletarItem(id) {
    if (!confirm(`Tem certeza que deseja apagar este material do almoxarifado?`)) return;
    
    try {
        const response = await fetch('api/delete_item_almox.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify({ id: id }) 
        });
        const result = await response.json();
        
        if (result.success) { 
            window.location.reload(); 
        } else { 
            alert('Erro ao apagar: ' + result.error); 
        }
    } catch (error) { 
        alert('Erro de rede ao apagar o material.'); 
    }
}