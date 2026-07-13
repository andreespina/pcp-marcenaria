// Dicionário para deixar os nomes dos estados amigáveis no ecrã
const nomesStatus = {
    'desenvolvimento': 'DESENV. PCP',
    'producao': 'PRODUÇÃO',
    'expedicao': 'EXPEDIÇÃO',
    'instalacao': 'INSTALAÇÃO',
    'atrasou': 'OBRA ATRASOU'
};

function filtrarSelect(inputId, selectId) {
    let filter = document.getElementById(inputId).value.toUpperCase();
    let select = document.getElementById(selectId);
    let options = select.getElementsByTagName('option');
    for (let i = 0; i < options.length; i++) {
        let txtValue = options[i].textContent || options[i].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) { options[i].style.display = ""; } else { options[i].style.display = "none"; }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const columns = document.querySelectorAll('.kanban-column');
    columns.forEach(col => {
        new Sortable(col, {
            group: 'pcp_shared_group', animation: 180, ghostClass: 'sortable-ghost',
            delay: 150, delayOnTouchOnly: true, fallbackTolerance: 3,
            onEnd: async function (evt) { await atualizarStatusNoServidor(evt.item.getAttribute('data-id'), evt.to.getAttribute('data-status')); },
        });
    });
});

async function atualizarStatusNoServidor(id, status) {
    try { await fetch('api/update_status.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id, status: status }) }); } catch (error) { console.error('Erro:', error); }
}

async function deletarCliente(event, id) {
    event.stopPropagation();
    if (!confirm(`Deseja apagar o registo ID #${id}?`)) return;
    const cardElement = document.querySelector(`[data-id="${id}"]`);
    try {
        const response = await fetch('api/delete_client.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) });
        const result = await response.json();
        if (result.success) { cardElement.style.opacity = '0'; cardElement.style.transform = 'scale(0.9)'; setTimeout(() => cardElement.remove(), 200); } 
    } catch (error) { alert('Erro de rede.'); }
}

function imprimirFicha(id, cliente, statusAtual, dataLimite, observacao, promob, corte, compras, ferragens, chkResp, medAgen, medData, equipe, dtIni, dtFim, diasUteis, projExec) {
    let dataFormatada = 'Não informada'; if (dataLimite) { const partes = dataLimite.split('-'); if (partes.length === 3) dataFormatada = `${partes[2]}/${partes[1]}/${partes[0]}`; }
    let medDataFormatada = ''; if (medData) { const p = medData.split('-'); if (p.length === 3) medDataFormatada = ` (${p[2]}/${p[1]}/${p[0]})`; }
    let infoInstalacao = '';
    if (equipe || diasUteis !== null) {
        infoInstalacao = `<div class="checklist" style="margin-top: 15px;"><h3 class="check-title">PLANEAMENTO DE INSTALAÇÃO</h3><div class="grid"><div class="col"><div class="check-item"><span>Equipa:</span> <strong style="text-transform: uppercase;">${equipe || '-'}</strong></div></div><div class="col"><div class="check-item"><span>Previsão:</span> <strong>${dtIni ? dtIni.split('-').reverse().join('/') : '-'} até ${dtFim ? dtFim.split('-').reverse().join('/') : '-'} (${diasUteis || 0} dias úteis)</strong></div></div></div></div>`;
    }

    const html = `<!DOCTYPE html><html><head><title>Ficha - #${id}</title><style>
    @media print { @page { margin: 0; } body { padding: 1.5cm; } }
    body { font-family: Arial, sans-serif; padding: 20px; color: #000; margin: 0; } .container { max-width: 800px; margin: 0 auto; border: 2px solid #000; padding: 20px; border-radius: 8px; } 
    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
    .info-label { font-weight: bold; font-size: 12px; color: #333; text-transform: uppercase; border-bottom: 1px solid #ccc; padding-bottom: 3px; } .info-value { font-size: 16px; margin-top: 4px; padding: 6px 0; } .grid { display: flex; flex-wrap: wrap; gap: 20px; } .col { flex: 1; min-width: 45%; } .checklist { margin-top: 20px; border-top: 2px solid #000; padding-top: 15px; } .check-title { background: #eee; padding: 5px; border: 1px solid #ccc; font-size: 16px; text-align: center;} .check-item { display: flex; justify-content: space-between; border-bottom: 1px dashed #aaa; padding: 8px 0; font-size: 14px; } .box-obs { min-height: 80px; border: 1px solid #aaa; padding: 10px; font-style: italic; background: #fafafa; } .footer { text-align: center; font-size: 11px; color: #666; margin-top: 30px; }</style></head><body><div class="container">
    <div class="header"><img src="assets/images/sbg_oficial.png" alt="SBG" style="max-width: 150px; height: auto;" onerror="this.style.display='none';"><div style="text-align: right;"><h1 style="margin:0; font-size: 24px;">FICHA DE PRODUÇÃO - PCP</h1><p style="margin:5px 0 0 0; font-size: 14px;">Ordem de Serviço #${id}</p></div></div>
    <div style="margin-top:15px;"><div class="info-label">Nome do Cliente</div><div class="info-value" style="font-size: 22px; font-weight: bold; text-transform: uppercase;">${cliente}</div></div><div class="grid"><div class="col"><div class="info-label">Data Limite</div><div class="info-value"><b>${dataFormatada}</b></div></div><div class="col"><div class="info-label">Status Atual</div><div class="info-value"><b>${statusAtual}</b></div></div></div><div style="margin-top:15px;"><div class="info-label">Observações Gerais</div><div class="info-value box-obs">${observacao || 'Nenhuma observação.'}</div></div><div class="checklist"><h3 class="check-title">VERIFICAÇÃO DE PRÉ-PRODUÇÃO E PROJETOS</h3><div class="grid"><div class="col"><div class="check-item"><span>Checklist Obra:</span> <strong>${chkResp}</strong></div><div class="check-item"><span>Medição Obra:</span> <strong>${medAgen} ${medDataFormatada}</strong></div><div class="check-item"><span>Projeto Promob:</span> <strong>${promob}</strong></div><div class="check-item"><span>Projeto Executivo:</span> <strong>${projExec}</strong></div></div><div class="col"><div class="check-item"><span>Corte/Furação:</span> <strong>${corte}</strong></div><div class="check-item"><span>Lista Compras:</span> <strong>${compras}</strong></div><div class="check-item"><span>Lista Ferragens:</span> <strong>${ferragens}</strong></div></div></div></div>${infoInstalacao}<div class="footer">Impresso via PCP AESPINA em ${new Date().toLocaleString('pt-PT')}</div></div></body></html>`;
    const janelaPrint = window.open('', '_blank', 'width=800,height=600'); janelaPrint.document.write(html); janelaPrint.document.close(); janelaPrint.focus(); setTimeout(() => { janelaPrint.print(); janelaPrint.close(); }, 500);
}

// FUNÇÃO NOVA: CARREGAR HISTÓRICO DE LOG
function carregarHistoricoProjeto(projetoId) {
    const timelineContainer = document.getElementById('timeline_projeto');
    if (!timelineContainer) return;

    // Reset de carregamento
    timelineContainer.innerHTML = '<p class="italic text-gray-400 animate-pulse">A carregar histórico...</p>';

    fetch(`api/get_project_logs.php?id=${projetoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.logs.length > 0) {
                timelineContainer.innerHTML = ''; // Limpa o carregando

                data.logs.forEach(log => {
                    const dataFormatada = new Date(log.data_mudanca).toLocaleString('pt-PT');
                    const statusAnt = nomesStatus[log.status_anterior] || 'NÃO DEFINIDO';
                    const statusNovo = nomesStatus[log.status_novo] || 'NÃO DEFINIDO';

                    // Define cores dependendo se foi um status de alerta
                    const badgeCor = log.status_novo === 'atrasou' ? 'text-red-600 bg-red-50 dark:bg-red-900/20' : 'text-blue-600 bg-blue-50 dark:bg-blue-900/20';

                    const itemHtml = `
                        <div class="flex items-start justify-between p-2 rounded bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-700">
                            <div>
                                <span class="font-bold text-gray-700 dark:text-gray-300">${log.usuario}</span> 
                                moveu de <span class="text-gray-500 line-through">${statusAnt}</span> 
                                para <span class="font-bold px-1.5 py-0.5 rounded text-[10px] ${badgeCor}">${statusNovo}</span>
                            </div>
                            <span class="text-gray-400 text-[11px] font-medium whitespace-nowrap ml-2">${dataFormatada}</span>
                        </div>
                    `;
                    timelineContainer.insertAdjacentHTML('beforeend', itemHtml);
                });
            } else {
                timelineContainer.innerHTML = '<p class="italic text-gray-400 dark:text-gray-500">Nenhum histórico de movimentação registado ainda.</p>';
            }
        })
        .catch(error => {
            console.error('Erro ao buscar histórico:', error);
            timelineContainer.innerHTML = '<p class="text-red-500 italic">Erro ao carregar trilha de auditoria.</p>';
        });
}

// MODAL CONTROLLERS
const modalNovo = document.getElementById('modalNovo'); const modalNovoConteudo = document.getElementById('modalNovoConteudo');
function abrirModalNovo() { 
    document.getElementById('search_novo_cliente').value = '';
    filtrarSelect('search_novo_cliente', 'novo_cliente');
    modalNovo.classList.remove('hidden'); setTimeout(() => { modalNovo.classList.remove('opacity-0'); modalNovoConteudo.classList.remove('scale-95'); }, 10); 
}
function fecharModalNovo() { modalNovo.classList.add('opacity-0'); modalNovoConteudo.classList.add('scale-95'); setTimeout(() => { modalNovo.classList.add('hidden'); document.getElementById('formNovo').reset(); }, 300); }

async function salvarNovoServidor(event) {
    event.preventDefault(); 
    const payload = {
        cliente: document.getElementById('novo_cliente').value, status: document.getElementById('novo_status').value, data_limite: document.getElementById('novo_data_limite').value,
        observacao: document.getElementById('novo_observacao').value, promob: document.getElementById('novo_promob').value, 
        projeto_executivo: document.getElementById('novo_executivo').value, corte_furacao: document.getElementById('novo_corte').value,
        lista_compras: document.getElementById('novo_compras').value, lista_ferragens: document.getElementById('novo_ferragens').value,
        checklist_respondido: document.getElementById('novo_checklist').value, checklist_link: document.getElementById('novo_checklist_link').value,
        medicao_agendada: document.getElementById('novo_medicao').value, medicao_data: document.getElementById('novo_medicao_data').value,
        equipe_instalacao: document.getElementById('novo_equipe').value, data_inicio_instalacao: document.getElementById('novo_dt_ini_inst').value, data_fim_instalacao: document.getElementById('novo_dt_fim_inst').value
    };
    try {
        const response = await fetch('api/add_client.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const result = await response.json();
        if (result.success) { fecharModalNovo(); window.location.reload(); } else { alert('Erro: ' + result.error); }
    } catch (error) { alert('Erro de rede.'); }
}

const modalEdicao = document.getElementById('modalEdicao'); const modalConteudo = document.getElementById('modalConteudo');
function abrirModalEdicao(event, id, cliente, dataLimite, observacao, promob, corte, compras, ferragens, chkResp, chkLink, medAgen, medData, equipe, dtIni, dtFim, projExec) {
    event.stopPropagation();
    document.getElementById('search_edit_cliente').value = '';
    filtrarSelect('search_edit_cliente', 'edit_cliente');
    document.getElementById('edit_id').value = id; document.getElementById('edit_cliente').value = cliente; document.getElementById('edit_data_limite').value = dataLimite ? dataLimite : ''; document.getElementById('edit_observacao').value = observacao ? observacao : '';
    document.getElementById('edit_promob').value = promob || 'PARA FAZER'; 
    document.getElementById('edit_executivo').value = projExec || 'PARA FAZER';
    document.getElementById('edit_corte').value = corte || 'PARA ENVIAR'; document.getElementById('edit_compras').value = compras || 'PARA ENVIAR'; document.getElementById('edit_ferragens').value = ferragens || 'PARA ENVIAR';
    document.getElementById('edit_checklist').value = chkResp || 'NAO'; document.getElementById('edit_checklist_link').value = chkLink || ''; document.getElementById('edit_medicao').value = medAgen || 'NAO'; document.getElementById('edit_medicao_data').value = medData || '';
    document.getElementById('edit_equipe').value = equipe || ''; document.getElementById('edit_dt_ini_inst').value = dtIni || ''; document.getElementById('edit_dt_fim_inst').value = dtFim || '';
    document.getElementById('labelIdProjeto').innerText = `(ID #${id})`;
    
    // CARREGA A NOVA ROTINA DA LINHA DO TEMPO
    carregarHistoricoProjeto(id);
    
    modalEdicao.classList.remove('hidden'); setTimeout(() => { modalEdicao.classList.remove('opacity-0'); modalConteudo.classList.remove('scale-95'); }, 10);
}
function fecharModalEdicao() { modalEdicao.classList.add('opacity-0'); modalConteudo.classList.add('scale-95'); setTimeout(() => { modalEdicao.classList.add('hidden'); document.getElementById('formEdicao').reset(); }, 300); }

async function salvarEdicaoServidor(event) {
    event.preventDefault(); 
    const payload = {
        id: document.getElementById('edit_id').value, cliente: document.getElementById('edit_cliente').value, data_limite: document.getElementById('edit_data_limite').value,
        observacao: document.getElementById('edit_observacao').value, promob: document.getElementById('edit_promob').value, 
        projeto_executivo: document.getElementById('edit_executivo').value, corte_furacao: document.getElementById('edit_corte').value,
        lista_compras: document.getElementById('edit_compras').value, lista_ferragens: document.getElementById('edit_ferragens').value,
        checklist_respondido: document.getElementById('edit_checklist').value, checklist_link: document.getElementById('edit_checklist_link').value,
        medicao_agendada: document.getElementById('edit_medicao').value, medicao_data: document.getElementById('edit_medicao_data').value,
        equipe_instalacao: document.getElementById('edit_equipe').value, data_inicio_instalacao: document.getElementById('edit_dt_ini_inst').value, data_fim_instalacao: document.getElementById('edit_dt_fim_inst').value
    };
    try {
        const response = await fetch('api/edit_client.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const result = await response.json();
        if (result.success) { fecharModalEdicao(); window.location.reload(); } else { alert('Erro: ' + result.error); }
    } catch (error) { alert('Erro de rede.'); }
}

const modalUsuario = document.getElementById('modalUsuario'); function abrirModalUsuario() { modalUsuario.classList.remove('hidden'); setTimeout(() => { modalUsuario.classList.remove('opacity-0'); document.getElementById('modalUsuarioConteudo').classList.remove('scale-95'); }, 10); }
function fecharModalUsuario() { modalUsuario.classList.add('opacity-0'); document.getElementById('modalUsuarioConteudo').classList.add('scale-95'); setTimeout(() => { modalUsuario.classList.add('hidden'); document.getElementById('formUsuario').reset(); }, 300); }
async function salvarUsuarioServidor(event) { 
    event.preventDefault(); 
    
    // Captura o nível selecionado
    const roleSelecionado = document.getElementById('novo_role') ? document.getElementById('novo_role').value : 'USER';
    
    // Captura as caixas de seleção que estiverem marcadas
    const checkboxes = document.querySelectorAll('input[name="permissoes[]"]:checked');
    let permissoesSelecionadas = [];
    checkboxes.forEach((cb) => {
        permissoesSelecionadas.push(cb.value);
    });

    const payload = { 
        usuario: document.getElementById('novo_login').value, 
        senha: document.getElementById('novo_senha').value,
        role: roleSelecionado,
        permissoes: permissoesSelecionadas
    }; 
    
    try { 
        const response = await fetch('api/add_user.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify(payload) 
        }); 
        const result = await response.json(); 
        
        if (result.success) { 
            alert('Utilizador registado com sucesso!'); 
            fecharModalUsuario(); 
        } else { 
            alert('Erro: ' + result.error); 
        } 
    } catch (error) { 
        alert('Erro de rede.'); 
    } 
}

const modalSenha = document.getElementById('modalSenha'); function abrirModalSenha() { modalSenha.classList.remove('hidden'); setTimeout(() => { modalSenha.classList.remove('opacity-0'); document.getElementById('modalSenhaConteudo').classList.remove('scale-95'); }, 10); }
function fecharModalSenha() { modalSenha.classList.add('opacity-0'); document.getElementById('modalSenhaConteudo').classList.add('scale-95'); setTimeout(() => { modalSenha.classList.add('hidden'); document.getElementById('formSenha').reset(); }, 300); }
async function salvarSenhaServidor(event) { event.preventDefault(); const payload = { nova_senha: document.getElementById('nova_senha_input').value }; try { const response = await fetch('api/change_password.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) }); const result = await response.json(); if (result.success) { alert('Senha alterada!'); fecharModalSenha(); } else { alert('Erro: ' + result.error); } } catch (error) { alert('Erro de rede.'); } }

const modalNA = document.getElementById('modalNovaAssistencia'); function abrirModalNovaAssistencia(event, projeto_id, cliente) { event.stopPropagation(); document.getElementById('na_projeto_id').value = projeto_id; document.getElementById('na_cliente_nome').value = cliente; document.getElementById('label_na_cliente').innerText = cliente + ` (Projeto #${projeto_id})`; modalNA.classList.remove('hidden'); setTimeout(() => { modalNA.classList.remove('opacity-0'); document.getElementById('modalNovaAssistenciaConteudo').classList.remove('scale-95'); }, 10); }
function fecharModalNovaAssistencia() { modalNA.classList.add('opacity-0'); document.getElementById('modalNovaAssistenciaConteudo').classList.add('scale-95'); setTimeout(() => { modalNA.classList.add('hidden'); document.getElementById('formNovaAssistencia').reset(); }, 300); }
async function salvarNovaAssistencia(event) { event.preventDefault(); const payload = { projeto_id: document.getElementById('na_projeto_id').value, cliente: document.getElementById('na_cliente_nome').value, observacao: document.getElementById('na_observacao').value }; try { const response = await fetch('api/nova_assistencia.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) }); const result = await response.json(); if (result.success) { alert('Assistência registada!'); fecharModalNovaAssistencia(); window.location.reload(); } else { alert('Erro: ' + result.error); } } catch (error) { alert('Erro de rede.'); } }

// LISTENERS FECHAR CLICK FORA
if(modalNovo) modalNovo.addEventListener('click', (e) => { if (e.target === modalNovo) fecharModalNovo(); });
if(modalEdicao) modalEdicao.addEventListener('click', (e) => { if (e.target === modalEdicao) fecharModalEdicao(); });
if(modalUsuario) modalUsuario.addEventListener('click', (e) => { if (e.target === modalUsuario) fecharModalUsuario(); });
if(modalSenha) modalSenha.addEventListener('click', (e) => { if (e.target === modalSenha) fecharModalSenha(); });
if(modalNA) modalNA.addEventListener('click', (e) => { if (e.target === modalNA) fecharModalNovaAssistencia(); });