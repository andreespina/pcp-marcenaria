// assets/js/financeiro.js

// 1. Controle das Entidades (Select Cliente/Fornecedor)
function atualizarEntidades() {
    const tipo = document.getElementById('sel_tipo_entidade').value;
    const select = document.getElementById('sel_entidade_id');
    select.innerHTML = '<option value="">Selecione...</option>';
    
    // Lê as variáveis globais injetadas pelo PHP
    const lista = (tipo === 'CLIENTE') ? window.clientes_data : window.fornecedores_data;
    
    if(lista && lista.length > 0) {
        lista.forEach(e => {
            select.innerHTML += `<option value="${e.id}">${e.nome}</option>`;
        });
    }
}

// 2. Calculadora de Parcelas
function calcularParcelas() {
    const total = parseFloat(document.getElementById('fin_valor').value) || 0;
    const parcelas = parseInt(document.getElementById('fin_parcelas').value) || 1;
    if(total > 0 && parcelas > 0) {
        document.getElementById('fin_valor_parcela').value = (total / parcelas).toFixed(2);
    }
}

// 3. Controle dos Modais (Lançamento)
const modalLanc = document.getElementById('modalLancamento');
const modalLancConteudo = document.getElementById('modalLancamentoConteudo');

function abrirModalLancamento() {
    document.getElementById('formLancamento').reset();
    document.getElementById('fin_id').value = '';
    document.getElementById('modalTitulo').innerText = 'Novo Lançamento Financeiro';
    
    atualizarEntidades();
    
    modalLanc.classList.remove('hidden');
    setTimeout(() => { 
        modalLanc.classList.remove('opacity-0'); 
        modalLancConteudo.classList.remove('scale-95'); 
    }, 10);
}

function fecharModalLancamento() {
    modalLanc.classList.add('opacity-0'); 
    modalLancConteudo.classList.add('scale-95');
    setTimeout(() => { 
        modalLanc.classList.add('hidden'); 
    }, 300);
}

// Edição de Lançamento
function abrirModalEdicao(dados) {
    document.getElementById('fin_id').value = dados.id;
    document.getElementById('fin_tipo').value = dados.tipo;
    document.getElementById('fin_descricao').value = dados.descricao;
    
    document.getElementById('sel_tipo_entidade').value = dados.entidade_tipo || 'CLIENTE';
    atualizarEntidades();
    document.getElementById('sel_entidade_id').value = dados.entidade_id || '';
    
    document.getElementById('fin_num_documento').value = dados.num_documento || '';
    document.getElementById('fin_tipo_documento').value = dados.tipo_documento || 'NF';
    document.getElementById('fin_forma_pagamento').value = dados.forma_pagamento || '';
    document.getElementById('fin_valor').value = dados.valor;
    document.getElementById('fin_parcelas').value = dados.num_parcelas || 1;
    document.getElementById('fin_valor_parcela').value = dados.valor_parcela || dados.valor;
    document.getElementById('fin_data_documento').value = dados.data_documento || '';
    document.getElementById('fin_vencimento').value = dados.data_vencimento;
    document.getElementById('fin_plano_contas').value = dados.plano_contas || '';
    document.getElementById('fin_centro_custo').value = dados.centro_custo || '';
    document.getElementById('fin_observacao').value = dados.observacao || '';
    
    document.getElementById('modalTitulo').innerText = 'Editar Lançamento';
    
    modalLanc.classList.remove('hidden');
    setTimeout(() => { 
        modalLanc.classList.remove('opacity-0'); 
        modalLancConteudo.classList.remove('scale-95'); 
    }, 10);
}

// 4. Salvar Lançamento (API)
async function salvarLancamento(event) {
    event.preventDefault();
    const id = document.getElementById('fin_id').value;
    const endpoint = id ? 'api/edit_lancamento.php' : 'api/add_lancamento.php';
    
    const payload = {
        id: id,
        tipo: document.getElementById('fin_tipo').value,
        descricao: document.getElementById('fin_descricao').value,
        entidade_tipo: document.getElementById('sel_tipo_entidade').value,
        entidade_id: document.getElementById('sel_entidade_id').value,
        num_documento: document.getElementById('fin_num_documento').value,
        tipo_documento: document.getElementById('fin_tipo_documento').value,
        forma_pagamento: document.getElementById('fin_forma_pagamento').value,
        valor: document.getElementById('fin_valor').value,
        num_parcelas: document.getElementById('fin_parcelas').value,
        valor_parcela: document.getElementById('fin_valor_parcela').value,
        data_documento: document.getElementById('fin_data_documento').value,
        data_vencimento: document.getElementById('fin_vencimento').value,
        plano_contas: document.getElementById('fin_plano_contas').value,
        centro_custo: document.getElementById('fin_centro_custo').value,
        observacao: document.getElementById('fin_observacao').value
    };

    try {
        const response = await fetch(endpoint, { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify(payload) 
        });
        
        const result = await response.json().catch(() => null);
        
        if (response.ok && result && result.success) { 
            window.location.reload(); 
        } else { 
            const erroMsg = (result && result.error) ? result.error : `Erro HTTP ${response.status}`;
            alert('Erro no banco de dados: ' + erroMsg); 
        }
    } catch (error) { 
        alert('Erro de comunicação. O arquivo ' + endpoint + ' foi criado corretamente?'); 
    }
}

// 5. Controle do Modal de Baixa
const modalBaixa = document.getElementById('modalBaixa');
const modalBaixaConteudo = document.getElementById('modalBaixaConteudo');

function abrirModalBaixa(id, desc, tipo) {
    document.getElementById('baixa_id').value = id;
    document.getElementById('lbl_baixa_desc').innerText = desc + ' (' + tipo + ')';
    document.getElementById('baixa_data').value = new Date().toISOString().split('T')[0];
    
    modalBaixa.classList.remove('hidden');
    setTimeout(() => { 
        modalBaixa.classList.remove('opacity-0'); 
        modalBaixaConteudo.classList.remove('scale-95'); 
    }, 10);
}

function fecharModalBaixa() {
    modalBaixa.classList.add('opacity-0'); 
    modalBaixaConteudo.classList.add('scale-95');
    setTimeout(() => { 
        modalBaixa.classList.add('hidden'); 
    }, 300);
}

async function salvarBaixa(event) {
    event.preventDefault();
    const payload = {
        id: document.getElementById('baixa_id').value,
        data_pagamento: document.getElementById('baixa_data').value
    };
    
    try {
        const response = await fetch('api/baixa_lancamento.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify(payload) 
        });
        
        const result = await response.json().catch(() => null);
        
        if (response.ok && result && result.success) { 
            window.location.reload(); 
        } else { 
            const erroMsg = (result && result.error) ? result.error : `Erro HTTP ${response.status}`;
            alert('Erro: ' + erroMsg); 
        }
    } catch (error) { 
        alert('Erro de rede ao salvar baixa.'); 
    }
}

// 6. Deletar Lançamento
async function deletarLancamento(id) {
    if (!confirm('Tem a certeza que deseja apagar este registo financeiro?')) return;
    
    try {
        const response = await fetch('api/delete_lancamento.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify({ id: id }) 
        });
        
        const result = await response.json().catch(() => null);
        
        if (response.ok && result && result.success) { 
            window.location.reload(); 
        } else { 
            const erroMsg = (result && result.error) ? result.error : `Erro HTTP ${response.status}`;
            alert('Erro ao apagar: ' + erroMsg); 
        }
    } catch (error) { 
        alert('Erro de rede.'); 
    }
}

// 7. Listagem Agrupada (Acordeão e Filtro)
function toggleGrupo(id, element) {
    const body = document.getElementById('body-' + id);
    const icon = element.querySelector('.icon-seta');
    if (body.classList.contains('hidden')) {
        body.classList.remove('hidden');
        icon.classList.add('rotate-180');
    } else {
        body.classList.add('hidden');
        icon.classList.remove('rotate-180');
    }
}

function filtrarTabela() {
    const filtro = document.getElementById('filtro_financeiro').value.toLowerCase();
    const grupos = document.querySelectorAll('.grupo-financeiro');
    
    grupos.forEach(grupo => {
        const textoGrupo = grupo.innerText.toLowerCase();
        
        if (textoGrupo.includes(filtro)) {
            grupo.style.display = '';
            
            // Inteligência: Se estiver pesquisando mais de 2 letras, expande o acordeão sozinho
            const body = grupo.querySelector('[id^="body-"]');
            const icon = grupo.querySelector('.icon-seta');
            
            if (filtro.length > 2) {
                body.classList.remove('hidden');
                icon.classList.add('rotate-180');
            } else if (filtro.length === 0) {
                // Fecha de novo se apagar a pesquisa
                body.classList.add('hidden');
                icon.classList.remove('rotate-180');
            }
        } else {
            grupo.style.display = 'none';
        }
    });
}

// 8. Eventos Base
if(modalLanc) {
    modalLanc.addEventListener('click', (e) => { 
        if (e.target === modalLanc) fecharModalLancamento(); 
    });
}

if(modalBaixa) {
    modalBaixa.addEventListener('click', (e) => { 
        if (e.target === modalBaixa) fecharModalBaixa(); 
    });
}

// Inicializa Entidades ao carregar o script
window.onload = () => { atualizarEntidades(); };