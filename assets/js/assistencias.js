// assets/js/assistencias.js

function filtrarSelect(inputId, selectId) {
    let filter = document.getElementById(inputId).value.toUpperCase();
    let select = document.getElementById(selectId);
    let options = select.getElementsByTagName('option');
    for (let i = 0; i < options.length; i++) {
        let txtValue = options[i].textContent || options[i].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) { options[i].style.display = ""; } else { options[i].style.display = "none"; }
    }
}

function autoPreencherFormulario(nomeCliente, prefixo) {
    if (!nomeCliente) return;
    const match = window.CLIENTES_BASE_DATA.find(c => c.nome_contrato === nomeCliente);
    if (match) {
        if(document.getElementById(prefixo + '_endereco')) document.getElementById(prefixo + '_endereco').value = match.endereco || '';
        if(document.getElementById(prefixo + '_numero')) document.getElementById(prefixo + '_numero').value = match.numero_lote || '';
        if(document.getElementById(prefixo + '_quadra')) document.getElementById(prefixo + '_quadra').value = match.quadra || '';
        if(document.getElementById(prefixo + '_bairro')) document.getElementById(prefixo + '_bairro').value = match.bairro || '';
        if(document.getElementById(prefixo + '_condominio')) document.getElementById(prefixo + '_condominio').value = match.condominio || '';
        if(document.getElementById(prefixo + '_complemento')) document.getElementById(prefixo + '_complemento').value = match.complemento || '';
        if(document.getElementById(prefixo + '_cidade')) document.getElementById(prefixo + '_cidade').value = match.cidade || '';
        if(document.getElementById(prefixo + '_cep')) document.getElementById(prefixo + '_cep').value = match.cep || '';
        if(document.getElementById(prefixo + '_tel_fixo')) document.getElementById(prefixo + '_tel_fixo').value = match.telefone || '';
        if(document.getElementById(prefixo + '_tel_cel')) document.getElementById(prefixo + '_tel_cel').value = match.whatsapp || '';
    }
}

function toggleFaturamento(prefix) {
    const el = document.getElementById(prefix + '_tipo_cobranca');
    if (!el) return;
    const tipo = el.value;
    const div = document.getElementById(prefix + '_dados_faturamento');
    if (tipo === 'FATURADA') {
        div.classList.remove('hidden');
    } else {
        div.classList.add('hidden');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const columns = document.querySelectorAll('.kanban-column');
    columns.forEach(col => { 
        new Sortable(col, { 
            group: 'assistencias_group', animation: 180, ghostClass: 'sortable-ghost', 
            delay: 150, delayOnTouchOnly: true, fallbackTolerance: 3,
            onEnd: async function (evt) { await atualizarStatusAsst(evt.item.getAttribute('data-id'), evt.to.getAttribute('data-status')); } 
        }); 
    });
});

async function atualizarStatusAsst(id, status) { try { await fetch('api/update_assistencia_status.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id, status: status }) }); } catch (error) {} }

function ordenarColuna(colunaId, criterio) {
    const col = document.getElementById('col-' + colunaId); const cards = Array.from(col.children);
    cards.sort((a, b) => { if (criterio === 'data_desc') { return parseInt(b.getAttribute('data-time')) - parseInt(a.getAttribute('data-time')); } else if (criterio === 'data_asc') { return parseInt(a.getAttribute('data-time')) - parseInt(b.getAttribute('data-time')); } else if (criterio === 'nome_asc') { return a.getAttribute('data-nome').localeCompare(b.getAttribute('data-nome')); } });
    cards.forEach(card => col.appendChild(card));
}

function lerDadosCard(btn) { const card = btn.closest('[data-json]'); return JSON.parse(card.getAttribute('data-json')); }
function chamarImpressao(btn) { const dados = lerDadosCard(btn); imprimirOSAssistencia(dados); }
function chamarEdicao(btn) { const dados = lerDadosCard(btn); abrirModalEdicaoAssistencia(dados); }
function chamarBaixa(btn) { const dados = lerDadosCard(btn); abrirModalBaixa(dados); }

function imprimirOSAssistencia(dados) {
    let obsItens = ''; 
    if(dados.obs) { const linhas = dados.obs.split(/\r?\n/); linhas.forEach(linha => { if(linha.trim() !== '') obsItens += `<li>${linha.trim()}</li>`; }); } else { obsItens = `<li>Verificar defeito no local.</li>`; }
    const html = `<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>OS Assistência - AST ${dados.id}</title><style>@media print { @page { margin: 1.5cm; } body { font-family: 'Times New Roman', Times, serif; color: #000; margin: 0; padding: 0; background: #fff; } .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; } .logo-img { max-width: 160px; height: auto; } .date-box { border: 1px solid #000; padding: 8px 12px; min-width: 250px; font-size: 14px; } .date-box p { margin: 4px 0; } .title-row { display: flex; justify-content: space-between; align-items: baseline; margin-top: 30px; margin-bottom: 20px; border-bottom: 1px solid #000; padding-bottom:10px; } .title-row h1 { font-size: 24px; font-weight: normal; margin: 0; } .title-row h2 { font-size: 20px; font-weight: normal; margin: 0; } .client-info { font-size: 14px; line-height: 2.2; width: 100%; border-bottom: 1px solid #000; padding-bottom: 15px; margin-bottom: 20px; } .client-info table { width: 100%; border-collapse: collapse; } .client-info td { vertical-align: bottom; } .tasks { margin-top: 20px; font-size: 15px; font-weight: bold; min-height: 350px; } .tasks ul { list-style-type: none; padding-left: 0; margin-top: 5px; line-height: 1.8; } .tasks li::before { content: "* "; } .footer-section { margin-top: auto; padding-top: 40px; } .warning { font-size: 14px; font-weight: bold; text-align: left; margin-bottom: 60px; } .signature-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 45px; } .sig-box-name { width: 65%; border-top: 1px solid #000; text-align: center; font-size: 12px; padding-top: 5px; } .sig-box-date { width: 25%; border-top: 1px solid #000; text-align: center; font-size: 12px; padding-top: 5px; } } body { font-family: 'Times New Roman', serif; max-width: 800px; margin: 40px auto; line-height: 1.8; color: #000;} .header { display: flex; justify-content: space-between; align-items: flex-start; } .logo-img { max-width: 160px; } .date-box { border: 1px solid #000; padding: 8px 12px; min-width: 250px; } .title-row { display: flex; justify-content: space-between; align-items: baseline; margin-top: 30px; border-bottom: 1px solid #000; padding-bottom:10px;} .client-info { border-bottom: 1px solid #000; padding-bottom: 15px; margin: 20px 0; font-size: 14px; } .client-info table { width: 100%; } .tasks { font-weight: bold; font-size: 15px; min-height: 200px; } .tasks ul { list-style-type: none; padding-left: 0; } .tasks li::before { content: "* "; } .warning { font-weight: bold; margin-bottom: 40px; margin-top: 60px; } .signature-row { display: flex; justify-content: space-between; margin-bottom: 40px; } .sig-box-name { width: 65%; border-top: 1px solid #000; text-align: center; font-size: 13px; padding-top: 5px; } .sig-box-date { width: 25%; border-top: 1px solid #000; text-align: center; font-size: 13px; padding-top: 5px; } </style></head><body><div class="header"><img src="assets/images/sbg_oficial.png" class="logo-img" alt="SBG Móveis & Design" onerror="this.style.display='none';"><div class="date-box"><p>Data da solicitação: <strong>${dados.dt_solic || ''}</strong></p><p>Data agendada: <strong>${dados.dt_agend || ''}</strong></p></div></div><div class="title-row"><h1>Assistência Técnica</h1><h2>AST ${dados.id}</h2><h2>ID </h2></div><div class="client-info"><table><tr><td colspan="2">Cliente: <strong>${dados.cliente || ''}</strong></td><td style="text-align: right;">Número: Lote</td></tr><tr><td colspan="2">Endereço: <strong>${dados.end || ''}</strong></td><td style="text-align: right;"><strong>${dados.num || ''}</strong>, Quadra <strong>${dados.qd || ''}</strong></td></tr><tr><td style="width: 40%;">Bairro: <strong>${dados.bairro || ''}</strong></td><td colspan="2">Condomínio: <strong>${dados.cond || ''}</strong></td></tr><tr><td>Complemento: <strong>${dados.comp || ''}</strong></td><td>Cidade: <strong>${dados.cid || ''}</strong></td><td style="text-align: right;">CEP: <strong>${dados.cep || ''}</strong></td></tr><tr><td>Tel. Fixo: <strong>${dados.fixo || ''}</strong></td><td colspan="2">Tel. Cel: <strong>${dados.cel || ''}</strong></td></tr></table></div><div class="tasks"><ul>${obsItens}</ul></div><div class="footer-section"><div class="warning">OBS: Não fazer nada a mais do que está descrito nesta ordem de serviço.</div><div class="signature-row"><div class="sig-box-name">Assinatura do Cliente</div><div class="sig-box-date">Data</div></div><div class="signature-row"><div class="sig-box-name">Assinatura do Montador</div><div class="sig-box-date">Data</div></div><div class="signature-row"><div class="sig-box-name">Assinatura do Responsável Técnico</div><div class="sig-box-date">Data</div></div></div></body></html>`;
    const janelaPrint = window.open('', '_blank', 'width=800,height=800'); janelaPrint.document.write(html); janelaPrint.document.close(); janelaPrint.focus(); setTimeout(() => { janelaPrint.print(); janelaPrint.close(); }, 500);
}

const modalNA = document.getElementById('modalNovaAssistencia'); const modalNAConteudo = document.getElementById('modalNovaAssistenciaConteudo');
function abrirModalNovaAssistencia() { 
    if(!document.getElementById('search_na_cliente')) return;
    document.getElementById('search_na_cliente').value = ''; 
    filtrarSelect('search_na_cliente', 'na_cliente'); 
    modalNA.classList.remove('hidden'); setTimeout(() => { modalNA.classList.remove('opacity-0'); modalNAConteudo.classList.remove('scale-95'); }, 10); 
}
function fecharModalNovaAssistencia() { 
    modalNA.classList.add('opacity-0'); modalNAConteudo.classList.add('scale-95'); 
    setTimeout(() => { modalNA.classList.add('hidden'); document.getElementById('formNovaAssistencia').reset(); toggleFaturamento('na'); }, 300); 
}

async function salvarNovaAssistencia(event) {
    event.preventDefault();
    const form = document.getElementById('formNovaAssistencia');
    const formData = new FormData(form);
    
    try { 
        const response = await fetch('api/nova_assistencia.php', { method: 'POST', body: formData }); 
        const result = await response.json(); 
        if (result.success) { window.location.reload(); } else { alert('Erro: ' + (result.error || 'Erro desconhecido')); } 
    } catch (error) { alert('Erro de comunicação. Execute o código SQL do banco se for sua primeira vez atualizando.'); }
}

const modalEA = document.getElementById('modalEdicaoAssistencia'); const modalEAConteudo = document.getElementById('modalEdicaoAssistenciaConteudo');
function abrirModalEdicaoAssistencia(dados) {
    if(!document.getElementById('search_ea_cliente')) return;
    document.getElementById('search_ea_cliente').value = ''; filtrarSelect('search_ea_cliente', 'ea_cliente');
    document.getElementById('ea_id').value = dados.id; document.getElementById('ea_cliente').value = dados.cliente || ''; document.getElementById('ea_observacao').value = dados.obs || ''; document.getElementById('ea_endereco').value = dados.end || ''; document.getElementById('ea_numero').value = dados.num || ''; document.getElementById('ea_quadra').value = dados.qd || ''; document.getElementById('ea_bairro').value = dados.bairro || ''; document.getElementById('ea_condominio').value = dados.cond || ''; document.getElementById('ea_complemento').value = dados.comp || ''; document.getElementById('ea_cidade').value = dados.cid || ''; document.getElementById('ea_cep').value = dados.cep || ''; document.getElementById('ea_tel_fixo').value = dados.fixo || ''; document.getElementById('ea_tel_cel').value = dados.cel || '';
    
    document.getElementById('ea_tipo_cobranca').value = dados.tipo_cobranca || 'GARANTIA';
    document.getElementById('ea_valor').value = dados.valor_cobrado || '';
    document.getElementById('ea_forma_pagamento').value = dados.forma_pagamento || '';
    toggleFaturamento('ea');

    const linkComprovante = document.getElementById('ea_link_comprovante');
    if (dados.comprovante_file) {
        linkComprovante.href = dados.comprovante_file;
        linkComprovante.classList.remove('hidden');
    } else {
        linkComprovante.classList.add('hidden');
    }

    const refProjeto = dados.projeto_id ? `(Ref. Projeto Original #${dados.projeto_id})` : ''; document.getElementById('labelEditAstProjeto').innerText = `(ID #${dados.id}) ${refProjeto}`;
    modalEA.classList.remove('hidden'); setTimeout(() => { modalEA.classList.remove('opacity-0'); modalEAConteudo.classList.remove('scale-95'); }, 10);
}

function fecharModalEdicaoAssistencia() { modalEA.classList.add('opacity-0'); modalEAConteudo.classList.add('scale-95'); setTimeout(() => { modalEA.classList.add('hidden'); document.getElementById('formEdicaoAssistencia').reset(); }, 300); }

async function salvarEdicaoAssistencia(event) {
    event.preventDefault();
    const form = document.getElementById('formEdicaoAssistencia');
    const formData = new FormData(form);

    try { 
        const response = await fetch('api/edit_assistencia.php', { method: 'POST', body: formData }); 
        const result = await response.json(); 
        if (result.success) { window.location.reload(); } else { alert('Erro: ' + (result.error || 'Erro desconhecido')); } 
    } catch (error) { alert('Erro de comunicação. Certifique-se que executou o SQL.'); }
}

const modalBaixa = document.getElementById('modalBaixa'); const modalBaixaConteudo = document.getElementById('modalBaixaConteudo');
function abrirModalBaixa(dados) {
    document.getElementById('ast_id').value = dados.id; document.getElementById('ast_tecnico').value = dados.tecnico ? dados.tecnico : ''; document.getElementById('ast_data').value = dados.dt_agend_raw ? dados.dt_agend_raw : ''; document.getElementById('ast_resolvido').value = dados.resolvido ? dados.resolvido : 'NAO'; document.getElementById('ast_observacao').value = dados.obs ? dados.obs : ''; document.getElementById('labelAstProjeto').innerText = `(ID #${dados.id})`;
    modalBaixa.classList.remove('hidden'); setTimeout(() => { modalBaixa.classList.remove('opacity-0'); modalBaixaConteudo.classList.remove('scale-95'); }, 10);
}
function fecharModalBaixa() { modalBaixa.classList.add('opacity-0'); modalBaixaConteudo.classList.add('scale-95'); setTimeout(() => { modalBaixa.classList.add('hidden'); document.getElementById('formBaixa').reset(); }, 300); }
async function salvarBaixaServidor(event) {
    event.preventDefault(); 
    const payload = { id: document.getElementById('ast_id').value, tecnico: document.getElementById('ast_tecnico').value, data_atendimento: document.getElementById('ast_data').value, resolvido: document.getElementById('ast_resolvido').value, observacao: document.getElementById('ast_observacao').value };
    try { const response = await fetch('api/concluir_assistencia.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) }); const result = await response.json(); if (result.success) { window.location.reload(); } else { alert('Erro: ' + (result.error || 'Erro desconhecido')); } } catch (error) { alert('Erro de rede.'); }
}

if(document.getElementById('modalNovaAssistencia')) document.getElementById('modalNovaAssistencia').addEventListener('click', (e) => { if (e.target === document.getElementById('modalNovaAssistencia')) fecharModalNovaAssistencia(); });
if(document.getElementById('modalEdicaoAssistencia')) document.getElementById('modalEdicaoAssistencia').addEventListener('click', (e) => { if (e.target === document.getElementById('modalEdicaoAssistencia')) fecharModalEdicaoAssistencia(); });
if(document.getElementById('modalBaixa')) document.getElementById('modalBaixa').addEventListener('click', (e) => { if (e.target === document.getElementById('modalBaixa')) fecharModalBaixa(); });