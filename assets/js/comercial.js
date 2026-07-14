// assets/js/comercial.js

let dragItemTemp = null;
let dragFromTemp = null;

function toggleNovoCliente() {
    const val = document.getElementById('lead_cliente_id').value;
    const div = document.getElementById('div_novo_cliente');
    const inputNome = document.getElementById('lead_nome');
    if(val === 'NOVO') {
        div.style.display = 'grid';
        inputNome.setAttribute('required', 'required');
    } else {
        div.style.display = 'none';
        inputNome.removeAttribute('required');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    toggleNovoCliente();
    const columns = document.querySelectorAll('.kanban-col');
    columns.forEach(col => {
        new Sortable(col, {
            group: 'crm_funil', animation: 150, ghostClass: 'sortable-ghost',
            onEnd: async function (evt) { 
                const id = evt.item.getAttribute('data-id');
                const novaFase = evt.to.getAttribute('data-fase');
                const faseAnterior = evt.from.getAttribute('data-fase');
                
                if (faseAnterior === novaFase) return;
                
                if (novaFase === 'FECHADO') {
                    const gerarPCP = confirm('Venda Fechada!\n\n1. O contrato será enviado ao Administrativo.\n2. O Cliente já está consolidado no ERP.\n\nDeseja enviar esta obra agora mesmo para as colunas do Painel PCP?');
                    await atualizarFase(id, novaFase, gerarPCP, null);
                } 
                else if (novaFase === 'PAUSADO' || novaFase === 'PERDIDO') {
                    dragItemTemp = evt.item;
                    dragFromTemp = evt.from;
                    abrirModalMotivo(id, novaFase);
                } 
                else {
                    await atualizarFase(id, novaFase, false, null);
                }
            },
        });
    });
});

async function atualizarFase(id, fase, gerarPCP, motivo) {
    try {
        const res = await fetch('api/crm_update_fase.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify({ id: id, fase: fase, gerarPCP: gerarPCP, motivo: motivo }) 
        });
        const text = await res.text();
        try {
            const result = JSON.parse(text);
            if (result.success) {
                window.location.reload();
            } else {
                alert('Erro na API: ' + (result.error || 'Desconhecido'));
                window.location.reload();
            }
        } catch(e) {
            alert("Ocorreu um erro no servidor ao atualizar a fase. Verifique o console.");
        }
    } catch (error) { alert('Falha de conexão.'); }
}

const modalMotivo = document.getElementById('modalMotivo');
const modalMotivoConteudo = document.getElementById('modalMotivoConteudo');

function abrirModalMotivo(id, novaFase) {
    document.getElementById('formMotivo').reset();
    document.getElementById('motivo_lead_id').value = id;
    document.getElementById('motivo_nova_fase').value = novaFase;
    document.getElementById('modalMotivoTitulo').innerText = (novaFase === 'PAUSADO') ? 'Por que este projeto está sendo PAUSADO?' : 'Por que este projeto foi PERDIDO?';
    modalMotivo.classList.remove('hidden');
    setTimeout(() => { modalMotivo.classList.remove('opacity-0'); modalMotivoConteudo.classList.remove('scale-95'); }, 10);
}

function fecharModalMotivo() {
    modalMotivo.classList.add('opacity-0'); modalMotivoConteudo.classList.add('scale-95');
    setTimeout(() => { modalMotivo.classList.add('hidden'); }, 300);
}

function cancelarMotivo() {
    if(dragFromTemp && dragItemTemp) {
        dragFromTemp.appendChild(dragItemTemp);
    }
    fecharModalMotivo();
}

function confirmarMotivo(event) {
    event.preventDefault();
    const id = document.getElementById('motivo_lead_id').value;
    const fase = document.getElementById('motivo_nova_fase').value;
    const selecao = document.getElementById('motivo_selecao').value;
    const detalhes = document.getElementById('motivo_detalhes').value;
    const motivoFinal = detalhes ? selecao + " - " + detalhes : selecao;
    
    fecharModalMotivo();
    atualizarFase(id, fase, false, motivoFinal);
}

const modalLead = document.getElementById('modalLead');
const modalLeadConteudo = document.getElementById('modalLeadConteudo');

function abrirModalLead() {
    document.getElementById('formLead').reset();
    document.getElementById('lead_id').value = '';
    document.getElementById('lead_cliente_id').value = 'NOVO';
    toggleNovoCliente();
    document.getElementById('modalTitulo').innerText = 'Cadastrar Novo Lead';
    modalLead.classList.remove('hidden');
    setTimeout(() => { modalLead.classList.remove('opacity-0'); modalLeadConteudo.classList.remove('scale-95'); }, 10);
}

function editarLead(lead) {
    document.getElementById('lead_id').value = lead.id;
    document.getElementById('lead_cliente_id').value = lead.cliente_id || 'NOVO';
    toggleNovoCliente();
    document.getElementById('lead_nome').value = lead.cliente_nome || '';
    document.getElementById('lead_telefone').value = lead.telefone || '';
    document.getElementById('lead_origem').value = lead.origem || 'INSTAGRAM';
    document.getElementById('lead_arquiteto').value = lead.arquiteto_nome || '';
    document.getElementById('lead_projetista').value = lead.projetista_responsavel || '';
    document.getElementById('lead_ambientes').value = lead.ambientes || '';
    document.getElementById('lead_prob').value = lead.probabilidade || 50;
    document.getElementById('lead_memorial').value = lead.memorial_descritivo || 'PRA FAZER';
    document.getElementById('lead_valor').value = lead.valor_estimado || '';
    document.getElementById('lead_apresentacao').value = lead.data_apresentacao || '';
    document.getElementById('lead_inicio_projeto').value = lead.data_inicio_projeto || '';
    document.getElementById('lead_prazo_dias').value = lead.prazo_projeto_dias || '';
    document.getElementById('lead_obs').value = lead.observacao || '';
    document.getElementById('modalTitulo').innerText = 'Editar Detalhes do Lead';
    modalLead.classList.remove('hidden');
    setTimeout(() => { modalLead.classList.remove('opacity-0'); modalLeadConteudo.classList.remove('scale-95'); }, 10);
}

function fecharModalLead() {
    modalLead.classList.add('opacity-0'); modalLeadConteudo.classList.add('scale-95');
    setTimeout(() => { modalLead.classList.add('hidden'); }, 300);
}

if(modalLead) { modalLead.addEventListener('click', (e) => { if (e.target === modalLead) fecharModalLead(); }); }

async function salvarLead(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    const endpoint = data.id ? 'api/edit_lead.php' : 'api/add_lead.php';
    try {
        const res = await fetch(endpoint, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data) });
        const text = await res.text();
        try {
            const result = JSON.parse(text);
            if(result.success) window.location.reload(); 
            else alert('Erro: ' + result.error);
        } catch(e) { alert("Ocorreu um erro no servidor."); }
    } catch(e) { alert('Falha na API.'); }
}

async function excluirLead(id) {
    if(!confirm("Deseja realmente cancelar/ocultar este lead?\nEle desaparecerá da tela, mas continuará salvo na base de dados para o histórico.")) return;
    try {
        const res = await fetch('api/delete_lead_soft.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id: id}) });
        const result = await res.json();
        if(result.success) window.location.reload();
        else alert('Erro ao ocultar lead: ' + result.error);
    } catch(e) { alert('Falha na conexão com a API de exclusão.'); }
}

// ==========================================
// NOVAS FUNÇÕES DO MODAL DE REPROJETO
// ==========================================
const modalReprojeto = document.getElementById('modalReprojeto');
const modalReprojetoConteudo = document.getElementById('modalReprojetoConteudo');

function abrirModalReprojeto(id) {
    document.getElementById('formReprojeto').reset();
    document.getElementById('reprojeto_lead_id').value = id;
    
    modalReprojeto.classList.remove('hidden');
    setTimeout(() => { modalReprojeto.classList.remove('opacity-0'); modalReprojetoConteudo.classList.remove('scale-95'); }, 10);
}

function fecharModalReprojeto() {
    modalReprojeto.classList.add('opacity-0'); modalReprojetoConteudo.classList.add('scale-95');
    setTimeout(() => { modalReprojeto.classList.add('hidden'); }, 300);
}

async function salvarReprojeto(event) {
    event.preventDefault();
    const id = document.getElementById('reprojeto_lead_id').value;
    const novaData = document.getElementById('reprojeto_data').value;
    
    try {
        const res = await fetch('api/request_reprojeto.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: id, nova_data: novaData})
        });
        const result = await res.json();
        if(result.success) {
            window.location.reload();
        } else {
            alert('Erro ao solicitar reprojeto: ' + result.error);
        }
    } catch(e) {
        alert('Falha na comunicação com a API.');
    }
}