// assets/js/comercial.js

let dragItemTemp = null;
let dragFromTemp = null;
let fullCalendarInstance = null;
let isProcessingDrop = false;

// ==============================================================
// GESTÃO DE FILTROS DE COLUNA
// ==============================================================
function aplicarFiltroColunas() {
    const checkboxes = document.querySelectorAll('.col-filter-cb');
    const estadoFiltros = {};
    checkboxes.forEach(cb => {
        const colId = 'coluna-container-' + cb.value;
        const coluna = document.getElementById(colId);
        if (coluna) {
            if (cb.checked) {
                coluna.style.display = 'flex';
                estadoFiltros[cb.value] = true;
            } else {
                coluna.style.display = 'none';
                estadoFiltros[cb.value] = false;
            }
        }
    });
    localStorage.setItem('sbg_comercial_filtros', JSON.stringify(estadoFiltros));
}

document.addEventListener('click', function(event) {
    const menu = document.getElementById('menuFiltros');
    if (menu && !menu.classList.contains('hidden')) {
        const btn = menu.previousElementSibling;
        if (!menu.contains(event.target) && !btn.contains(event.target)) {
            menu.classList.add('hidden');
        }
    }
});

// ==============================================================
// INICIALIZAÇÃO E DRAG & DROP SEGURO
// ==============================================================
document.addEventListener('DOMContentLoaded', () => {
    toggleNovoCliente();
    
    const filtrosSalvos = localStorage.getItem('sbg_comercial_filtros');
    if (filtrosSalvos) {
        const estadoFiltros = JSON.parse(filtrosSalvos);
        const checkboxes = document.querySelectorAll('.col-filter-cb');
        checkboxes.forEach(cb => {
            if (estadoFiltros[cb.value] !== undefined) cb.checked = estadoFiltros[cb.value];
        });
    }
    aplicarFiltroColunas();

    const columns = document.querySelectorAll('.kanban-col');
    columns.forEach(col => {
        if(col.id === 'calendario_sbg') return; 
        
        new Sortable(col, {
            group: 'crm_funil', 
            animation: 150, 
            ghostClass: 'sortable-ghost',
            onEnd: async function (evt) { 
                if (isProcessingDrop) return;
                isProcessingDrop = true;

                const id = evt.item.getAttribute('data-id');
                const novaFase = evt.to.getAttribute('data-fase');
                const faseAnterior = evt.from.getAttribute('data-fase');
                
                if (!id || faseAnterior === novaFase) {
                    isProcessingDrop = false;
                    return;
                }
                
                if (novaFase === 'FECHADO') {
                    const gerarPCP = confirm('Venda Fechada!\n\n1. O contrato será enviado ao Administrativo.\n2. O Cliente já está consolidado no ERP.\n\nDeseja enviar esta obra agora mesmo para as colunas do Painel PCP?');
                    await atualizarFase(id, novaFase, gerarPCP, null);
                } 
                else if (novaFase === 'PAUSADO' || novaFase === 'PERDIDO') {
                    dragItemTemp = evt.item;
                    dragFromTemp = evt.from;
                    abrirModalMotivo(id, novaFase);
                    isProcessingDrop = false;
                } 
                else {
                    await atualizarFase(id, novaFase, false, null);
                }
            },
        });
    });
});

function toggleNovoCliente() {
    const val = document.getElementById('lead_cliente_id');
    const div = document.getElementById('div_novo_cliente');
    const inputNome = document.getElementById('lead_nome');
    if(val && div && inputNome) {
        if(val.value === 'NOVO') {
            div.style.display = 'grid';
            inputNome.setAttribute('required', 'required');
        } else {
            div.style.display = 'none';
            inputNome.removeAttribute('required');
        }
    }
}

// ==============================================================
// MÓDULOS DE INTEGRAÇÃO E CALENDÁRIO
// ==============================================================
function toggleViewMode() {
    const viewKanban = document.getElementById('view-kanban');
    const viewCalendario = document.getElementById('view-calendario');
    const btn = document.getElementById('btnToggleView');

    if (viewKanban.classList.contains('hidden')) {
        viewKanban.classList.remove('hidden');
        viewCalendario.classList.add('hidden');
        btn.innerHTML = `<svg class="w-4 h-4 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> <span class="hidden md:inline">CALENDÁRIO</span>`;
    } else {
        viewKanban.classList.add('hidden');
        viewCalendario.classList.remove('hidden');
        btn.innerHTML = `<svg class="w-4 h-4 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg> <span class="hidden md:inline">KANBAN</span>`;

        if (!fullCalendarInstance && typeof FullCalendar !== 'undefined') {
            const calendarEl = document.getElementById('calendario_sbg');
            fullCalendarInstance = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt-br',
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
                events: eventosCalendario,
                eventClick: function(info) { 
                    const realId = info.event.id.split('_')[0];
                    abrirEdicaoPorId(realId); 
                }
            });
            fullCalendarInstance.render();
        } else if (fullCalendarInstance) {
            fullCalendarInstance.render();
        }
    }
}

function abrirGoogleCalendar(cliente, data_apres, obs) {
    if(!data_apres) { alert("Atenção: Não há data de apresentação agendada."); return; }
    const dateStr = data_apres.replace(/-/g, '');
    const title = encodeURIComponent("Apresentação SBG: " + cliente);
    const details = encodeURIComponent("Reunião de apresentação de projeto.\n\nObservações: " + (obs || 'Nenhuma observação.'));
    window.open(`https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${dateStr}/${dateStr}&details=${details}`, '_blank');
}

// ==============================================================
// ROTINAS DE BASE E API
// ==============================================================
async function atualizarFase(id, fase, gerarPCP, motivo) {
    try {
        const response = await fetch('api/crm_update_fase.php', { 
            method: 'POST', headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify({ id: id, fase: fase, gerarPCP: gerarPCP, motivo: motivo }) 
        });
        
        const result = await response.json().catch(() => null);
        
        if (response.ok && result && result.success) {
            window.location.reload();
        } else { 
            const erroMsg = (result && result.error) ? result.error : `Erro HTTP ${response.status}`;
            alert('Erro na API: ' + erroMsg); 
            // Mantém o reload no erro para que o card do Kanban volte à posição original caso falhe
            window.location.reload(); 
        }
    } catch (error) { 
        alert('Falha de conexão com o servidor.'); 
        window.location.reload(); 
    }
}

function abrirEdicaoPorId(id) {
    if (typeof crmLeadsDados !== 'undefined') {
        const leadEncontrado = crmLeadsDados.find(item => item.id == id);
        if (leadEncontrado) editarLead(leadEncontrado);
        else alert("Erro: Registro não localizado na memória.");
    } else { alert("Erro fatal: Base da memória não carregada."); }
}

// ==============================================================
// MODAL: MOTIVO (Pausado/Perdido)
// ==============================================================
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
    isProcessingDrop = false; // <-- Trava liberada para não bugar o Drag & Drop
}

function confirmarMotivo(event) {
    event.preventDefault();
    const id = document.getElementById('motivo_lead_id').value;
    const fase = document.getElementById('motivo_nova_fase').value;
    const selecao = document.getElementById('motivo_selecao').value;
    const detalhes = document.getElementById('motivo_detalhes').value;
    fecharModalMotivo();
    atualizarFase(id, fase, false, detalhes ? selecao + " - " + detalhes : selecao);
}

// ==============================================================
// MODAL: NOVO/EDITAR LEAD
// ==============================================================
const modalLead = document.getElementById('modalLead');
const modalLeadConteudo = document.getElementById('modalLeadConteudo');

function abrirModalLead() {
    document.getElementById('formLead').reset();
    const setVal = (id, val) => { const el = document.getElementById(id); if(el) el.value = val; };
    setVal('lead_id', ''); setVal('lead_cliente_id', 'NOVO');
    toggleNovoCliente();
    
    const gridHist = document.getElementById('grid_historicos_gerais');
    if (gridHist) gridHist.classList.add('hidden');

    const titulo = document.getElementById('modalTitulo'); if(titulo) titulo.innerText = 'Cadastrar Novo Lead';
    if(modalLead) { modalLead.classList.remove('hidden'); setTimeout(() => { modalLead.classList.remove('opacity-0'); modalLeadConteudo.classList.remove('scale-95'); }, 10); }
}

function editarLead(lead) {
    const setVal = (id, val) => { const el = document.getElementById(id); if(el) el.value = val; };
    const setCheck = (id, checked) => { const el = document.getElementById(id); if(el) el.checked = checked; };

    setVal('lead_id', lead.id); setVal('lead_cliente_id', lead.cliente_id || 'NOVO');
    toggleNovoCliente();
    setVal('lead_nome', lead.cliente_nome || ''); setVal('lead_telefone', lead.telefone || '');
    setVal('lead_origem', lead.origem || 'INSTAGRAM'); setVal('lead_arquiteto', lead.arquiteto_nome || '');
    setVal('lead_projetista', lead.projetista_responsavel || ''); setVal('lead_ambientes', lead.ambientes || '');
    setVal('lead_prob', lead.probabilidade || 50); setVal('lead_memorial', lead.memorial_descritivo || 'PRA FAZER');
    setVal('lead_valor', lead.valor_estimado || ''); setVal('lead_apresentacao', lead.data_apresentacao || '');
    setVal('lead_inicio_projeto', lead.data_inicio_projeto || ''); setVal('lead_prazo_dias', lead.prazo_projeto_dias || '');
    setVal('lead_entrega_projeto', lead.data_entrega_projeto || ''); setVal('lead_obs', lead.observacao || '');
    setCheck('lead_apres_realizada', (lead.apresentacao_realizada == 1));

    // -- POPULAR HISTÓRICOS (Reuniões e Reprojetos) --
    let temReprojeto = false;
    const divHistRep = document.getElementById('div_historico_reprojetos');
    const listaHistRep = document.getElementById('lista_historico_reprojetos');
    
    if(lead.historico_reprojetos && divHistRep && listaHistRep) {
        try {
            const histRep = JSON.parse(lead.historico_reprojetos);
            if(histRep && histRep.length > 0) {
                temReprojeto = true;
                divHistRep.classList.remove('hidden');
                document.getElementById('count_reprojetos').innerText = histRep.length;
                listaHistRep.innerHTML = histRep.map(h => `
                    <div class="border-b border-orange-200 dark:border-orange-800/50 pb-2 mb-2 last:border-0 last:pb-0 last:mb-0">
                        <span class="font-bold text-orange-600 dark:text-orange-400 bg-orange-100 dark:bg-orange-900/30 px-1 rounded text-[10px] mr-1">REV ${String(h.revisao).padStart(2, '0')}</span> 
                        <span class="text-gray-600 dark:text-gray-300 font-semibold text-[10px]">Agendado: ${h.data.split('-').reverse().join('/')}</span>
                        <p class="text-gray-700 dark:text-gray-300 italic mt-1 text-xs font-medium">" ${h.motivo} "</p>
                    </div>
                `).join('');
            } else { divHistRep.classList.add('hidden'); }
        } catch(e) { divHistRep.classList.add('hidden'); }
    } else if (divHistRep) { divHistRep.classList.add('hidden'); }

    let temReuniao = false;
    const divHistReu = document.getElementById('div_historico_reunioes');
    const listaHistReu = document.getElementById('lista_historico_reunioes');

    if(lead.historico_reunioes && divHistReu && listaHistReu) {
        try {
            const histReu = JSON.parse(lead.historico_reunioes);
            if(histReu && histReu.length > 0) {
                temReuniao = true;
                divHistReu.classList.remove('hidden');
                document.getElementById('count_reunioes').innerText = histReu.length;
                listaHistReu.innerHTML = histReu.map((r, index) => `
                    <div class="flex items-center justify-between border-b border-green-200 dark:border-green-800/50 pb-1 mb-1 last:border-0 last:pb-0 last:mb-0">
                        <span class="text-green-700 dark:text-green-400 font-bold">#${index + 1}</span>
                        <span class="text-gray-700 dark:text-gray-300 font-bold">${r.data.split('-').reverse().join('/')}</span>
                        <span class="text-[10px] font-bold text-green-600 dark:text-green-500 bg-green-100 dark:bg-green-900/40 px-1.5 py-0.5 rounded">REALIZADA</span>
                    </div>
                `).join('');
            } else { divHistReu.classList.add('hidden'); }
        } catch(e) { divHistReu.classList.add('hidden'); }
    } else if (divHistReu) { divHistReu.classList.add('hidden'); }

    const gridHist = document.getElementById('grid_historicos_gerais');
    if (gridHist) {
        if (temReprojeto || temReuniao) gridHist.classList.remove('hidden');
        else gridHist.classList.add('hidden');
    }

    const titulo = document.getElementById('modalTitulo'); if(titulo) titulo.innerText = 'Editar Detalhes do Lead';
    if(modalLead) { modalLead.classList.remove('hidden'); setTimeout(() => { modalLead.classList.remove('opacity-0'); modalLeadConteudo.classList.remove('scale-95'); }, 10); }
}

function fecharModalLead() {
    if(modalLead) { modalLead.classList.add('opacity-0'); modalLeadConteudo.classList.add('scale-95'); setTimeout(() => { modalLead.classList.add('hidden'); }, 300); }
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
        const response = await fetch(endpoint, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data) });
        
        const result = await response.json().catch(() => null);
        
        if (response.ok && result && result.success) {
            window.location.reload(); 
        } else {
            const erroMsg = (result && result.error) ? result.error : `Erro HTTP ${response.status}`;
            alert('Erro: ' + erroMsg);
        }
    } catch(e) { 
        alert('Falha na API.'); 
    }
}

// Botão Lixeira - Agora atua como soft-delete / PERDIDO e já foi redirecionado no PHP para abrirModalMotivo
// Este botão "X" atua como exclusão irreversível:
async function excluirLeadPermanente(id) {
    if(!confirm("⚠️ ATENÇÃO EXTREMA:\nDeseja realmente excluir este lead de forma PERMANENTE?\n\nO projeto será totalmente apagado do banco de dados e NÃO poderá ser recuperado. (Dica: Se ele apenas não fechou negócio, prefira usar o ícone da Lixeira para movê-lo para 'Perdidos').")) return;
    
    try {
        const response = await fetch('api/delete_lead_permanente.php', { 
            method: 'POST', 
            headers: {'Content-Type': 'application/json'}, 
            body: JSON.stringify({id: id}) 
        });
        
        const result = await response.json().catch(() => null);
        
        if (response.ok && result && result.success) {
            window.location.reload();
        } else {
            const erroMsg = (result && result.error) ? result.error : `Erro HTTP ${response.status}`;
            alert('Erro ao excluir: ' + erroMsg);
        }
    } catch(e) { 
        alert('Falha na API ao tentar exclusão permanente.'); 
    }
}

// ==============================================================
// MODAL: REPROJETO
// ==============================================================
const modalReprojeto = document.getElementById('modalReprojeto');
const modalReprojetoConteudo = document.getElementById('modalReprojetoConteudo');

function abrirModalReprojeto(id) {
    document.getElementById('formReprojeto').reset(); 
    document.getElementById('reprojeto_lead_id').value = id;
    modalReprojeto.classList.remove('hidden'); 
    setTimeout(() => { modalReprojeto.classList.remove('opacity-0'); modalReprojetoConteudo.classList.remove('scale-95'); }, 10);
}

function fecharModalReprojeto() {
    modalReprojeto.classList.add('opacity-0'); 
    modalReprojetoConteudo.classList.add('scale-95'); 
    setTimeout(() => { modalReprojeto.classList.add('hidden'); }, 300);
}

async function salvarReprojeto(event) {
    event.preventDefault();
    const id = document.getElementById('reprojeto_lead_id').value; 
    const novaData = document.getElementById('reprojeto_data').value;
    const elMotivo = document.getElementById('reprojeto_motivo');
    const motivo = elMotivo ? elMotivo.value : 'Revisão solicitada pelo cliente';
    
    try {
        const response = await fetch('api/request_reprojeto.php', { 
            method: 'POST', 
            headers: {'Content-Type': 'application/json'}, 
            body: JSON.stringify({id: id, nova_data: novaData, motivo: motivo}) 
        });
        
        const result = await response.json().catch(() => null);
        
        if (response.ok && result && result.success) {
            window.location.reload(); 
        } else {
            const erroMsg = (result && result.error) ? result.error : `Erro HTTP ${response.status}`;
            alert('Erro: ' + erroMsg);
        }
    } catch(e) { 
        alert('Falha de rede ao solicitar reprojeto.'); 
    }
}