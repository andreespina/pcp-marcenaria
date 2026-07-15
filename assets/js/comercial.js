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
            group: 'crm_funil', 
            animation: 150, 
            ghostClass: 'sortable-ghost',
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
            alert("Erro estrutural ao salvar a fase.");
        }
    } catch (error) { alert('Falha de conexão com o servidor.'); }
}

// ARQUITETURA DEFINITIVA: Localiza o Lead na memória (à prova de quebra de aspas)
function abrirEdicaoPorId(id) {
    if (typeof crmLeadsDados !== 'undefined') {
        const leadEncontrado = crmLeadsDados.find(item => item.id == id);
        if (leadEncontrado) {
            editarLead(leadEncontrado);
        } else {
            alert("Erro: Registro do lead não localizado na memória.");
        }
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

// ==============================================================
// MODAL CADASTRAR / EDITAR LEAD (COM PROGRAMAÇÃO DEFENSIVA)
// ==============================================================
const modalLead = document.getElementById('modalLead');
const modalLeadConteudo = document.getElementById('modalLeadConteudo');

function abrirModalLead() {
    document.getElementById('formLead').reset();
    
    // Função auxiliar que só preenche o valor se o campo existir no HTML
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
    // Função auxiliar protetora: Impede que o sistema quebre se você apagar algum campo no futuro
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
    
    // O campo "lead_prob" agora existe e será preenchido com segurança
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