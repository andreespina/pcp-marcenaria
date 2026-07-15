// assets/js/comercial.js

let dragItemTemp = null;
let dragFromTemp = null;
let fullCalendarInstance = null; // Variável global do calendário

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
        if(col.id === 'calendario_sbg') return; // Pula a coluna do calendário se tentar rastrear
        
        new Sortable(col, {
            group: 'crm_funil', 
            animation: 150, 
            ghostClass: 'sortable-ghost',
            onEnd: async function (evt) { 
                const id = evt.item.getAttribute('data-id');
                const novaFase = evt.to.getAttribute('data-fase');
                const faseAnterior = evt.from.getAttribute('data-fase');
                
                if (!id || faseAnterior === novaFase) return;
                
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

// ==============================================================
// ALTERNÂNCIA (TOGGLE) KANBAN VS CALENDÁRIO
// ==============================================================
function toggleViewMode() {
    const viewKanban = document.getElementById('view-kanban');
    const viewCalendario = document.getElementById('view-calendario');
    const btn = document.getElementById('btnToggleView');

    if (viewKanban.classList.contains('hidden')) {
        // Voltar para KANBAN
        viewKanban.classList.remove('hidden');
        viewCalendario.classList.add('hidden');
        btn.innerHTML = `<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> VER CALENDÁRIO`;
    } else {
        // Ir para CALENDÁRIO
        viewKanban.classList.add('hidden');
        viewCalendario.classList.remove('hidden');
        btn.innerHTML = `<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg> VER KANBAN`;

        // Inicia o calendário só na primeira vez que abre
        if (!fullCalendarInstance && typeof FullCalendar !== 'undefined') {
            const calendarEl = document.getElementById('calendario_sbg');
            fullCalendarInstance = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt-br',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                events: eventosCalendario, // Busca da memória do PHP
                eventClick: function(info) {
                    abrirEdicaoPorId(info.event.id);
                }
            });
            fullCalendarInstance.render();
        } else if (fullCalendarInstance) {
            fullCalendarInstance.render(); // Redimensiona se tela mudar
        }
    }
}

// ==============================================================
// INTEGRAÇÃO GOOGLE CALENDAR
// ==============================================================
function abrirGoogleCalendar(cliente, data_apres, obs) {
    if(!data_apres) {
        alert("Atenção: Não há data de apresentação agendada para este projeto.");
        return;
    }
    
    // Formata a data de "YYYY-MM-DD" para o formato Google "YYYYMMDD"
    const dateStr = data_apres.replace(/-/g, '');
    
    const title = encodeURIComponent("Apresentação SBG: " + cliente);
    const details = encodeURIComponent("Reunião de apresentação de projeto.\n\nObservações: " + (obs || 'Nenhuma observação.'));
    
    // Constrói a URL de template do Google
    const url = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${dateStr}/${dateStr}&details=${details}`;
    
    window.open(url, '_blank');
}

// ==============================================================
// ROTINAS DE BASE (SLA, API, MODAIS)
// ==============================================================
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
            if (result.success) window.location.reload();
            else { alert('Erro na API: ' + (result.error || 'Desconhecido')); window.location.reload(); }
        } catch(e) { alert("Erro estrutural ao salvar a fase."); }
    } catch (error) { alert('Falha de conexão com o servidor.'); }
}

function abrirEdicaoPorId(id) {
    if (typeof crmLeadsDados !== 'undefined') {
        const leadEncontrado = crmLeadsDados.find(item => item.id == id);
        if (leadEncontrado) editarLead(leadEncontrado);
        else alert("Erro: Registro do lead não localizado na memória.");
    } else {
        alert("Erro fatal: Base de dados da memória não foi carregada.");
    }
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
    if(dragFromTemp && dragItemTemp) dragFromTemp.appendChild(dragItemTemp);
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
    const setVal = (id, val) => { const el = document.getElementById(id); if(el) el.value = val; };
    setVal('lead_id', '');
    setVal('lead_cliente_id', 'NOVO');
    toggleNovoCliente();
    const titulo = document.getElementById('modalTitulo');
    if(titulo) titulo.innerText = 'Cadastrar Novo Lead';
    if(modalLead) {
        modalLead.classList.remove('hidden');
        setTimeout(() => { modalLead.classList.remove('opacity-0'); modalLeadConteudo.classList.remove('scale-95'); }, 10);
    }
}

function editarLead(lead) {
    const setVal = (id, val) => { const el = document.getElementById(id); if(el) el.value = val; };
    const setCheck = (id, checked) => { const el = document.getElementById(id); if(el) el.checked = checked; };

    setVal('lead_id', lead.id);
    setVal('lead_cliente_id', lead.cliente_id || 'NOVO');
    toggleNovoCliente();
    setVal('lead_nome', lead.cliente_nome || '');
    setVal('lead_telefone', lead.telefone || '');
    setVal('lead_origem', lead.origem || 'INSTAGRAM');
    setVal('lead_arquiteto', lead.arquiteto_nome || '');
    setVal('lead_projetista', lead.projetista_responsavel || '');
    setVal('lead_ambientes', lead.ambientes || '');
    setVal('lead_prob', lead.probabilidade || 50);
    setVal('lead_memorial', lead.memorial_descritivo || 'PRA FAZER');
    setVal('lead_valor', lead.valor_estimado || '');
    setVal('lead_apresentacao', lead.data_apresentacao || '');
    setVal('lead_inicio_projeto', lead.data_inicio_projeto || '');
    setVal('lead_prazo_dias', lead.prazo_projeto_dias || '');
    setVal('lead_entrega_projeto', lead.data_entrega_projeto || '');
    setVal('lead_obs', lead.observacao || '');
    
    setCheck('lead_apres_realizada', (lead.apresentacao_realizada == 1));
    
    const titulo = document.getElementById('modalTitulo');
    if(titulo) titulo.innerText = 'Editar Detalhes do Lead';
    
    if(modalLead) {
        modalLead.classList.remove('hidden');
        setTimeout(() => { modalLead.classList.remove('opacity-0'); modalLeadConteudo.classList.remove('scale-95'); }, 10);
    }
}

function fecharModalLead() {
    if(modalLead) {
        modalLead.classList.add('opacity-0'); modalLeadConteudo.classList.add('scale-95');
        setTimeout(() => { modalLead.classList.add('hidden'); }, 300);
    }
}

if(modalLead) { modalLead.addEventListener('click', (e) => { if (e.target === modalLead) fecharModalLead(); }); }

async function salvarLead(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    const checkRealizada = document.getElementById('lead_apres_realizada');
    data.apresentacao_realizada = (checkRealizada && checkRealizada.checked) ? 1 : 0;
    
    const endpoint = data.id ? 'api/edit_lead.php' : 'api/add_lead.php';
    try {
        const res = await fetch(endpoint, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data) });
        const text = await res.text();
        try {
            const result = JSON.parse(text);
            if(result.success) window.location.reload(); 
            else alert('Erro: ' + result.error);
        } catch(e) { alert("Erro ao processar retorno da gravação."); }
    } catch(e) { alert('Falha na API.'); }
}

async function excluirLead(id) {
    if(!confirm("Deseja realmente cancelar/ocultar este lead?")) return;
    try {
        const res = await fetch('api/delete_lead_soft.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id: id}) });
        const result = await res.json();
        if(result.success) window.location.reload();
        else alert('Erro ao ocultar: ' + result.error);
    } catch(e) { alert('Falha na API de exclusão.'); }
}

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
        const res = await fetch('api/request_reprojeto.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id: id, nova_data: novaData}) });
        const result = await res.json();
        if(result.success) window.location.reload();
        else alert('Erro: ' + result.error);
    } catch(e) { alert('Falha de rede.'); }
}