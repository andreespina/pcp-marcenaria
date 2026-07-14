// assets/js/comercial.js

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

// Drag & Drop
document.addEventListener('DOMContentLoaded', () => {
    toggleNovoCliente();
    const columns = document.querySelectorAll('.kanban-col');
    columns.forEach(col => {
        new Sortable(col, {
            group: 'crm_funil', animation: 150, ghostClass: 'sortable-ghost',
            onEnd: async function (evt) { 
                const id = evt.item.getAttribute('data-id');
                const novaFase = evt.to.getAttribute('data-fase');
                
                if (evt.from === evt.to) return;
                
                if (novaFase === 'FECHADO') {
                    const gerarPCP = confirm('Venda Fechada!\n\n1. O contrato será enviado ao Administrativo.\n2. O Cliente já está consolidado no ERP.\n\nDeseja enviar esta obra agora mesmo para as colunas do Painel PCP?');
                    await atualizarFase(id, novaFase, gerarPCP);
                } else {
                    await atualizarFase(id, novaFase, false);
                }
            },
        });
    });
});

async function atualizarFase(id, fase, gerarPCP) {
    try {
        const res = await fetch('api/crm_update_fase.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id, fase, gerarPCP }) });
        const result = await res.json();
        if (result.success) window.location.reload();
        else alert('Erro na API: ' + (result.error || 'Desconhecido'));
    } catch (error) { alert('Falha de conexão.'); }
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
    
    // Novos Campos
    document.getElementById('lead_projetista').value = lead.projetista_responsavel || '';
    document.getElementById('lead_ambientes').value = lead.ambientes || '';
    document.getElementById('lead_prob').value = lead.probabilidade || 50;
    document.getElementById('lead_memorial').value = lead.memorial_descritivo || 'PRA FAZER';
    
    document.getElementById('lead_valor').value = lead.valor_estimado || '';
    document.getElementById('lead_apresentacao').value = lead.data_apresentacao || '';
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
        } catch(e) { 
            console.error(text);
            alert("Ocorreu um erro no servidor. Verifique o console do navegador (F12)."); 
        }
    } catch(e) { alert('Falha na API.'); }
}