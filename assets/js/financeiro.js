// assets/js/financeiro.js

function filtrarTabela() {
    const filtro = document.getElementById('filtro_financeiro').value.toLowerCase();
    const linhas = document.querySelectorAll('.tr-busca');
    linhas.forEach(linha => {
        const texto = linha.innerText.toLowerCase();
        linha.style.display = texto.includes(filtro) ? '' : 'none';
    });
}

const modalLanc = document.getElementById('modalLancamento');
const modalLancConteudo = document.getElementById('modalLancamentoConteudo');

function abrirModalLancamento() {
    document.getElementById('formLancamento').reset();
    document.getElementById('fin_id').value = '';
    document.getElementById('modalTitulo').innerText = 'Novo Lançamento Financeiro';
    modalLanc.classList.remove('hidden');
    setTimeout(() => { modalLanc.classList.remove('opacity-0'); modalLancConteudo.classList.remove('scale-95'); }, 10);
}

function abrirModalEdicao(id, tipo, desc, cat, cli, valor, data, obs) {
    document.getElementById('fin_id').value = id;
    document.getElementById('fin_tipo').value = tipo;
    document.getElementById('fin_descricao').value = desc;
    document.getElementById('fin_categoria').value = cat;
    document.getElementById('fin_cliente').value = cli;
    document.getElementById('fin_valor').value = valor;
    document.getElementById('fin_vencimento').value = data;
    document.getElementById('fin_observacao').value = obs;
    
    document.getElementById('modalTitulo').innerText = 'Editar Lançamento';
    modalLanc.classList.remove('hidden');
    setTimeout(() => { modalLanc.classList.remove('opacity-0'); modalLancConteudo.classList.remove('scale-95'); }, 10);
}

function fecharModalLancamento() {
    modalLanc.classList.add('opacity-0'); modalLancConteudo.classList.add('scale-95');
    setTimeout(() => { modalLanc.classList.add('hidden'); }, 300);
}

async function salvarLancamento(event) {
    event.preventDefault();
    const id = document.getElementById('fin_id').value;
    const endpoint = id ? 'api/edit_lancamento.php' : 'api/add_lancamento.php';
    
    const payload = {
        id: id,
        tipo: document.getElementById('fin_tipo').value,
        valor: document.getElementById('fin_valor').value,
        descricao: document.getElementById('fin_descricao').value,
        cliente_fornecedor: document.getElementById('fin_cliente').value,
        categoria: document.getElementById('fin_categoria').value,
        data_vencimento: document.getElementById('fin_vencimento').value,
        observacao: document.getElementById('fin_observacao').value
    };

    try {
        const response = await fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const result = await response.json();
        if (result.success) { window.location.reload(); } else { alert('Erro: ' + (result.error || 'Desconhecido')); }
    } catch (error) { alert('Erro de comunicação.'); }
}

const modalBaixa = document.getElementById('modalBaixa');
const modalBaixaConteudo = document.getElementById('modalBaixaConteudo');

function abrirModalBaixa(id, desc, tipo) {
    document.getElementById('baixa_id').value = id;
    document.getElementById('lbl_baixa_desc').innerText = desc + ' (' + tipo + ')';
    document.getElementById('baixa_data').value = new Date().toISOString().split('T')[0];
    
    modalBaixa.classList.remove('hidden');
    setTimeout(() => { modalBaixa.classList.remove('opacity-0'); modalBaixaConteudo.classList.remove('scale-95'); }, 10);
}

function fecharModalBaixa() {
    modalBaixa.classList.add('opacity-0'); modalBaixaConteudo.classList.add('scale-95');
    setTimeout(() => { modalBaixa.classList.add('hidden'); }, 300);
}

async function salvarBaixa(event) {
    event.preventDefault();
    const payload = {
        id: document.getElementById('baixa_id').value,
        data_pagamento: document.getElementById('baixa_data').value
    };

    try {
        const response = await fetch('api/baixa_lancamento.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const result = await response.json();
        if (result.success) { window.location.reload(); } else { alert('Erro: ' + result.error); }
    } catch (error) { alert('Erro de rede.'); }
}

async function deletarLancamento(id) {
    if (!confirm('Tem a certeza que deseja apagar este registo financeiro?')) return;
    try {
        const response = await fetch('api/delete_lancamento.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) });
        const result = await response.json();
        if (result.success) { window.location.reload(); } else { alert('Erro ao apagar: ' + result.error); }
    } catch (error) { alert('Erro de rede.'); }
}

if(modalLanc) modalLanc.addEventListener('click', (e) => { if (e.target === modalLanc) fecharModalLancamento(); });
if(modalBaixa) modalBaixa.addEventListener('click', (e) => { if (e.target === modalBaixa) fecharModalBaixa(); });