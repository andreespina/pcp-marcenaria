// assets/js/assistencias.js

// Instâncias Globais do Quill Editor
let quillNA, quillEA, quillBaixa;

// 1. Utilitários Base
function filtrarSelect(inputId, selectId) {
    let filter = document.getElementById(inputId).value.toUpperCase();
    let select = document.getElementById(selectId);
    let options = select.getElementsByTagName('option');
    for (let i = 0; i < options.length; i++) {
        let txtValue = options[i].textContent || options[i].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) { 
            options[i].style.display = ""; 
        } else { 
            options[i].style.display = "none"; 
        }
    }
}

function filtrarCardsAssistencia() {
    const filtro = document.getElementById('filtro_assistencias').value.toLowerCase();
    const cards = document.querySelectorAll('.card-busca');
    
    cards.forEach(card => {
        const elementosTexto = card.querySelectorAll('.texto-pesquisa');
        let contemFiltro = false;
        
        elementosTexto.forEach(el => {
            if (el.textContent.toLowerCase().includes(filtro)) {
                contemFiltro = true;
            }
        });
        
        if (card.innerText.toLowerCase().includes(filtro)) contemFiltro = true;

        if (contemFiltro) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
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

// LÓGICA DO ACCORDION DO CARD
function toggleAssistenciaCard(element) {
    const conteudo = element.nextElementSibling;
    const icone = element.querySelector('.icon-seta');
    
    if (conteudo.classList.contains('hidden')) {
        conteudo.classList.remove('hidden');
        icone.classList.add('rotate-180');
    } else {
        conteudo.classList.add('hidden');
        icone.classList.remove('rotate-180');
    }
}

// 2. Setup Inicial (Quill e Kanban Drag & Drop)
document.addEventListener('DOMContentLoaded', () => {
    
    // Configuração da Toolbar estilo "Word" para o Quill.js
    const quillOptions = {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'], 
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'color': [] }, { 'background': [] }],
                ['clean'] // Remove formatações
            ]
        }
    };

    // Inicialização dos 3 editores
    if (document.getElementById('quill_na_observacao')) {
        quillNA = new Quill('#quill_na_observacao', quillOptions);
        quillNA.on('text-change', function() {
            document.getElementById('na_observacao').value = quillNA.root.innerHTML;
        });
    }
    
    if (document.getElementById('quill_ea_observacao')) {
        quillEA = new Quill('#quill_ea_observacao', quillOptions);
        quillEA.on('text-change', function() {
            document.getElementById('ea_observacao').value = quillEA.root.innerHTML;
        });
    }

    if (document.getElementById('quill_ast_observacao')) {
        quillBaixa = new Quill('#quill_ast_observacao', quillOptions);
        quillBaixa.on('text-change', function() {
            document.getElementById('ast_observacao').value = quillBaixa.root.innerHTML;
        });
    }

    // Inicialização do Kanban
    const columns = document.querySelectorAll('.kanban-column');
    columns.forEach(col => { 
        new Sortable(col, { 
            group: 'assistencias_group', 
            animation: 180, 
            ghostClass: 'sortable-ghost', 
            delay: 150, 
            delayOnTouchOnly: true, 
            fallbackTolerance: 3,
            onEnd: async function (evt) { 
                const card = evt.item;
                const newStatus = evt.to.getAttribute('data-status');
                
                const conteudo = card.querySelector('.conteudo-assistencia');
                const icone = card.querySelector('.icon-seta');
                
                if (newStatus === 'concluida') {
                    conteudo.classList.add('hidden');
                    icone.classList.remove('rotate-180');
                } else {
                    conteudo.classList.remove('hidden');
                    icone.classList.add('rotate-180');
                }

                await atualizarStatusAsst(card.getAttribute('data-id'), newStatus); 
            } 
        }); 
    });
});

async function atualizarStatusAsst(id, status) { 
    try { 
        await fetch('api/update_assistencia_status.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify({ id: id, status: status }) 
        }); 
    } catch (error) {
        console.error("Falha ao atualizar o status do card.");
    } 
}

function ordenarColuna(colunaId, criterio) {
    const col = document.getElementById('col-' + colunaId); 
    const cards = Array.from(col.children);
    cards.sort((a, b) => { 
        if (criterio === 'data_desc') { 
            return parseInt(b.getAttribute('data-time')) - parseInt(a.getAttribute('data-time')); 
        } else if (criterio === 'data_asc') { 
            return parseInt(a.getAttribute('data-time')) - parseInt(b.getAttribute('data-time')); 
        } else if (criterio === 'nome_asc') { 
            return a.getAttribute('data-nome').localeCompare(b.getAttribute('data-nome')); 
        } 
    });
    cards.forEach(card => col.appendChild(card));
}

// 3. Ações nos Cards
function lerDadosCard(btn) { 
    const card = btn.closest('[data-json]'); 
    return JSON.parse(card.getAttribute('data-json')); 
}
function chamarImpressao(btn) { const dados = lerDadosCard(btn); imprimirOSAssistencia(dados); }
function chamarEdicao(btn) { const dados = lerDadosCard(btn); abrirModalEdicaoAssistencia(dados); }
function chamarBaixa(btn) { const dados = lerDadosCard(btn); abrirModalBaixa(dados); }

async function deletarAssistencia(event, id) {
    event.stopPropagation();
    if (!confirm(`Deseja realmente apagar a assistência #${id}? Esta ação não pode ser desfeita e removerá a cobrança do financeiro caso exista.`)) return;
    
    const cardElement = document.querySelector(`[data-id="${id}"]`);
    
    try {
        const response = await fetch('api/delete_assistencia.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify({ id: id }) 
        });
        const result = await response.json();
        
        if (result.success) { 
            if(cardElement) {
                cardElement.style.opacity = '0'; 
                cardElement.style.transform = 'scale(0.9)'; 
                setTimeout(() => cardElement.remove(), 200); 
            } else {
                window.location.reload();
            }
        } else {
            alert('Erro ao excluir: ' + (result.error || 'Erro desconhecido'));
        }
    } catch (error) { 
        alert('Erro de rede ao tentar excluir.'); 
    }
}

// 4. Impressão de Ordem de Serviço (Ajustada para entender HTML do Quill)
function imprimirOSAssistencia(dados) {
    // Agora dados.obs já contém o HTML do Quill (<ul>, <li>, <p>, <strong>, etc)
    let obsItens = dados.obs ? dados.obs : `<p>Verificar defeito no local.</p>`; 
    
    const codigoCli = dados.codigo_cli ? `[${dados.codigo_cli}]` : '';

    const html = `<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>OS Assistência - AST #${dados.id}</title>
        <style>
            @media print { 
                @page { margin: 0; } 
                body { margin: 0; padding: 1.5cm; } 
            } 
            body { font-family: 'Times New Roman', Times, serif; color: #000; max-width: 800px; margin: 40px auto; line-height: 1.6; }
            .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
            .logo-img { max-width: 160px; height: auto; }
            .date-box { border: 1px solid #000; padding: 8px 12px; min-width: 220px; font-size: 14px; }
            .date-box p { margin: 4px 0; }
            .title-row { display: flex; justify-content: space-between; align-items: baseline; margin-top: 20px; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
            .title-row h1 { font-size: 24px; font-weight: bold; margin: 0; text-transform: uppercase; }
            .title-row h2 { font-size: 18px; font-weight: bold; margin: 0; color: #333; }
            .client-info { font-size: 14px; width: 100%; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px; }
            .client-info table { width: 100%; border-collapse: collapse; }
            .client-info td { padding: 4px 0; vertical-align: top; }
            .address-box { margin-top: 8px; padding: 12px; border: 1px solid #ccc; background-color: #f9f9f9; border-radius: 4px; }
            .tasks { margin-top: 20px; font-size: 15px; min-height: 250px; }
            .tasks p { margin-top: 5px; margin-bottom: 5px; }
            .tasks ul, .tasks ol { padding-left: 20px; margin-top: 10px; line-height: 1.8; }
            .footer-section { margin-top: 40px; padding-top: 20px; }
            .warning { font-size: 13px; font-weight: bold; text-align: left; margin-bottom: 50px; text-transform: uppercase; }
            .signature-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; }
            .sig-box-name { width: 60%; border-top: 1px solid #000; text-align: center; font-size: 12px; padding-top: 5px; }
            .sig-box-date { width: 30%; border-top: 1px solid #000; text-align: center; font-size: 12px; padding-top: 5px; }
        </style>
    </head>
    <body>
        <div class="header">
            <img src="assets/images/sbg_oficial.png" class="logo-img" alt="SBG Móveis & Design" onerror="this.style.display='none';">
            <div class="date-box">
                <p>Solicitação: <strong>${dados.dt_solic || '-'}</strong></p>
                <p>Agendado: <strong>${dados.dt_agend || '-'}</strong></p>
            </div>
        </div>
        
        <div class="title-row">
            <h1>OS Assistência Técnica</h1>
            <h2>AST #${dados.id} &nbsp; ${codigoCli}</h2>
        </div>
        
        <div class="client-info">
            <table>
                <tr>
                    <td colspan="2" style="font-size: 16px;">Cliente: <strong style="text-transform: uppercase;">${dados.cliente || '-'}</strong></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="address-box">
                            <div style="margin-bottom: 4px;"><strong>Endereço:</strong> ${dados.end || '-'}, <strong>Nº/Lote:</strong> ${dados.num || '-'}, <strong>Quadra:</strong> ${dados.qd || '-'}</div>
                            <div style="margin-bottom: 4px;"><strong>Bairro:</strong> ${dados.bairro || '-'} &nbsp;|&nbsp; <strong>Condomínio:</strong> ${dados.cond || '-'}</div>
                            <div><strong>Complemento:</strong> ${dados.comp || '-'} &nbsp;|&nbsp; <strong>Cidade/UF:</strong> ${dados.cid || '-'} &nbsp;|&nbsp; <strong>CEP:</strong> ${dados.cep || '-'}</div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding-top: 15px; width: 50%;">Telefone Fixo: <strong>${dados.fixo || '-'}</strong></td>
                    <td style="padding-top: 15px; width: 50%;">WhatsApp/Celular: <strong>${dados.cel || '-'}</strong></td>
                </tr>
            </table>
        </div>
        
        <div class="tasks">
            <p style="margin:0; text-transform: uppercase; font-weight: bold; border-bottom: 1px solid #ccc; display: inline-block;">Defeito / Relato / Serviço a executar:</p>
            <div style="margin-top: 15px;">
                ${obsItens}
            </div>
        </div>
        
        <div class="footer-section">
            <div class="warning">OBS: Não executar nenhum serviço a mais além do que está descrito nesta ordem de serviço.</div>
            <div class="signature-row">
                <div class="sig-box-name">Assinatura do Cliente</div>
                <div class="sig-box-date">Data</div>
            </div>
            <div class="signature-row">
                <div class="sig-box-name">Assinatura do Montador / Técnico</div>
                <div class="sig-box-date">Data</div>
            </div>
        </div>
    </body>
    </html>`;

    const janelaPrint = window.open('', '_blank', 'width=800,height=800'); 
    janelaPrint.document.write(html); 
    janelaPrint.document.close(); 
    janelaPrint.focus(); 
    setTimeout(() => { janelaPrint.print(); janelaPrint.close(); }, 500);
}

// 5. Modais de Cadastro, Edição e Baixa
const modalNA = document.getElementById('modalNovaAssistencia'); 
const modalNAConteudo = document.getElementById('modalNovaAssistenciaConteudo');

function abrirModalNovaAssistencia() { 
    if(!document.getElementById('search_na_cliente')) return;
    document.getElementById('search_na_cliente').value = ''; 
    filtrarSelect('search_na_cliente', 'na_cliente'); 
    
    // Limpar o Editor Quill
    if(quillNA) quillNA.setText('');
    document.getElementById('na_observacao').value = '';

    modalNA.classList.remove('hidden'); 
    setTimeout(() => { 
        modalNA.classList.remove('opacity-0'); 
        modalNAConteudo.classList.remove('scale-95'); 
    }, 10); 
}

function fecharModalNovaAssistencia() { 
    modalNA.classList.add('opacity-0'); 
    modalNAConteudo.classList.add('scale-95'); 
    setTimeout(() => { 
        modalNA.classList.add('hidden'); 
        document.getElementById('formNovaAssistencia').reset(); 
        toggleFaturamento('na'); 
    }, 300); 
}

async function salvarNovaAssistencia(event) {
    event.preventDefault();
    const form = document.getElementById('formNovaAssistencia');
    const formData = new FormData(form);
    
    // Verificando se o conteúdo rico não está vazio (Quill deixa <p><br></p> por padrão)
    const obsVal = document.getElementById('na_observacao').value;
    if(obsVal === '<p><br></p>' || obsVal.trim() === '') {
        alert("Por favor, preencha o defeito ou problema relatado.");
        return;
    }
    
    try { 
        const response = await fetch('api/nova_assistencia.php', { method: 'POST', body: formData }); 
        const result = await response.json(); 
        
        if (result.success) { 
            window.location.reload(); 
        } else { 
            alert('Erro: ' + (result.error || 'Erro desconhecido')); 
        } 
    } catch (error) { 
        alert('Erro de comunicação. Verifique a sua conexão.'); 
    }
}

const modalEA = document.getElementById('modalEdicaoAssistencia'); 
const modalEAConteudo = document.getElementById('modalEdicaoAssistenciaConteudo');

function abrirModalEdicaoAssistencia(dados) {
    if(!document.getElementById('search_ea_cliente')) return;
    document.getElementById('search_ea_cliente').value = ''; 
    filtrarSelect('search_ea_cliente', 'ea_cliente');
    
    document.getElementById('ea_id').value = dados.id; 
    document.getElementById('ea_cliente').value = dados.cliente || ''; 
    document.getElementById('ea_endereco').value = dados.end || ''; 
    document.getElementById('ea_numero').value = dados.num || ''; 
    document.getElementById('ea_quadra').value = dados.qd || ''; 
    document.getElementById('ea_bairro').value = dados.bairro || ''; 
    document.getElementById('ea_condominio').value = dados.cond || ''; 
    document.getElementById('ea_complemento').value = dados.comp || ''; 
    document.getElementById('ea_cidade').value = dados.cid || ''; 
    document.getElementById('ea_cep').value = dados.cep || ''; 
    document.getElementById('ea_tel_fixo').value = dados.fixo || ''; 
    document.getElementById('ea_tel_cel').value = dados.cel || '';
    
    document.getElementById('ea_tipo_cobranca').value = dados.tipo_cobranca || 'GARANTIA';
    document.getElementById('ea_valor').value = dados.valor_cobrado || '';
    document.getElementById('ea_forma_pagamento').value = dados.forma_pagamento || '';
    toggleFaturamento('ea');

    // Preencher o Editor Quill de Edição
    if(quillEA) {
        quillEA.root.innerHTML = dados.obs || '';
    }
    document.getElementById('ea_observacao').value = dados.obs || ''; 

    const linkComprovante = document.getElementById('ea_link_comprovante');
    if (dados.comprovante_file) {
        linkComprovante.href = dados.comprovante_file;
        linkComprovante.classList.remove('hidden');
    } else {
        linkComprovante.classList.add('hidden');
    }

    const refProjeto = dados.projeto_id ? `(Ref. Projeto Original #${dados.projeto_id})` : ''; 
    document.getElementById('labelEditAstProjeto').innerText = `(ID #${dados.id}) ${refProjeto}`;
    
    modalEA.classList.remove('hidden'); 
    setTimeout(() => { 
        modalEA.classList.remove('opacity-0'); 
        modalEAConteudo.classList.remove('scale-95'); 
    }, 10);
}

function fecharModalEdicaoAssistencia() { 
    modalEA.classList.add('opacity-0'); 
    modalEAConteudo.classList.add('scale-95'); 
    setTimeout(() => { 
        modalEA.classList.add('hidden'); 
        document.getElementById('formEdicaoAssistencia').reset(); 
    }, 300); 
}

async function salvarEdicaoAssistencia(event) {
    event.preventDefault();
    const form = document.getElementById('formEdicaoAssistencia');
    const formData = new FormData(form);

    try { 
        const response = await fetch('api/edit_assistencia.php', { method: 'POST', body: formData }); 
        const result = await response.json(); 
        
        if (result.success) { 
            window.location.reload(); 
        } else { 
            alert('Erro: ' + (result.error || 'Erro desconhecido')); 
        } 
    } catch (error) { 
        alert('Erro de comunicação. Verifique a sua conexão.'); 
    }
}

const modalBaixa = document.getElementById('modalBaixa'); 
const modalBaixaConteudo = document.getElementById('modalBaixaConteudo');

function abrirModalBaixa(dados) {
    document.getElementById('ast_id').value = dados.id; 
    document.getElementById('ast_tecnico').value = dados.tecnico ? dados.tecnico : ''; 
    document.getElementById('ast_data').value = dados.dt_agend_raw ? dados.dt_agend_raw : ''; 
    document.getElementById('ast_resolvido').value = dados.resolvido ? dados.resolvido : 'NAO'; 
    document.getElementById('labelAstProjeto').innerText = `(ID #${dados.id})`;
    
    // Preencher o Editor Quill de Baixa
    if(quillBaixa) {
        quillBaixa.root.innerHTML = dados.obs || '';
    }
    document.getElementById('ast_observacao').value = dados.obs ? dados.obs : ''; 
    
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
        document.getElementById('formBaixa').reset(); 
    }, 300); 
}

async function salvarBaixaServidor(event) {
    event.preventDefault(); 
    const payload = { 
        id: document.getElementById('ast_id').value, 
        tecnico: document.getElementById('ast_tecnico').value, 
        data_atendimento: document.getElementById('ast_data').value, 
        resolvido: document.getElementById('ast_resolvido').value, 
        observacao: document.getElementById('ast_observacao').value 
    };
    try { 
        const response = await fetch('api/concluir_assistencia.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify(payload) 
        }); 
        const result = await response.json(); 
        
        if (result.success) { 
            window.location.reload(); 
        } else { 
            alert('Erro: ' + (result.error || 'Erro desconhecido')); 
        } 
    } catch (error) { 
        alert('Erro de rede.'); 
    }
}

// 6. Fechamento de Modais clicando fora
if(document.getElementById('modalNovaAssistencia')) {
    document.getElementById('modalNovaAssistencia').addEventListener('click', (e) => { 
        if (e.target === document.getElementById('modalNovaAssistencia')) fecharModalNovaAssistencia(); 
    });
}
if(document.getElementById('modalEdicaoAssistencia')) {
    document.getElementById('modalEdicaoAssistencia').addEventListener('click', (e) => { 
        if (e.target === document.getElementById('modalEdicaoAssistencia')) fecharModalEdicaoAssistencia(); 
    });
}
if(document.getElementById('modalBaixa')) {
    document.getElementById('modalBaixa').addEventListener('click', (e) => { 
        if (e.target === document.getElementById('modalBaixa')) fecharModalBaixa(); 
    });
}