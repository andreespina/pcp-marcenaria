// assets/js/clientes.js

let clienteImpressaoAtual = null; 

// 1. Controle do Accordion (Expandir detalhes do cliente)
function toggleDetails(headerElement) {
    const rowBlock = headerElement.closest('.linha-cliente');
    const detailsContainer = rowBlock.querySelector('.details-container');
    const arrowIcon = rowBlock.querySelector('.icon-seta');
    
    if (detailsContainer.classList.contains('hidden')) {
        detailsContainer.classList.remove('hidden');
        arrowIcon.classList.add('rotate-90');
    } else {
        detailsContainer.classList.add('hidden');
        arrowIcon.classList.remove('rotate-90');
    }
}

// 2. Filtro de Pesquisa da Tabela
function filtrarTabela() {
    const filtro = document.getElementById('filtro_clientes').value.toLowerCase();
    const linhas = document.querySelectorAll('.linha-cliente');
    
    linhas.forEach(linha => {
        const nome = linha.querySelector('.nome-busca').textContent.toLowerCase();
        const codigo = linha.querySelector('.codigo-busca').textContent.toLowerCase();
        
        if (nome.includes(filtro) || codigo.includes(filtro)) { 
            linha.style.display = ''; 
        } else { 
            linha.style.display = 'none'; 
        }
    });
}

// Utilitário para ler os dados do Card
function lerDadosCard(btn) { 
    const card = btn.closest('[data-json]'); 
    return JSON.parse(card.getAttribute('data-json')); 
}

// 3. Controle do Modal de Cadastro / Edição
const modalCliente = document.getElementById('modalCliente'); 
const modalClienteConteudo = document.getElementById('modalClienteConteudo');

function abrirModalCliente() {
    document.getElementById('formCliente').reset();
    document.getElementById('cli_id').value = '';
    document.getElementById('modalTitulo').innerText = 'Cadastrar Novo Cliente';
    
    modalCliente.classList.remove('hidden'); 
    setTimeout(() => { 
        modalCliente.classList.remove('opacity-0'); 
        modalClienteConteudo.classList.remove('scale-95'); 
    }, 10);
}

function chamarEdicao(btn) { 
    const dados = lerDadosCard(btn); 
    abrirModalEdicao(dados); 
}

function abrirModalEdicao(dados) {
    document.getElementById('cli_id').value = dados.id; 
    document.getElementById('cli_codigo').value = dados.codigo_cliente || ''; 
    document.getElementById('cli_nome').value = dados.nome || ''; 
    document.getElementById('cli_cpf').value = dados.cpf || ''; 
    document.getElementById('cli_end').value = dados.end || ''; 
    document.getElementById('cli_num').value = dados.num || ''; 
    document.getElementById('cli_quadra').value = dados.qd || ''; 
    document.getElementById('cli_bairro').value = dados.bairro || ''; 
    document.getElementById('cli_cond').value = dados.cond || ''; 
    document.getElementById('cli_comp').value = dados.comp || ''; 
    document.getElementById('cli_cid').value = dados.cid || ''; 
    document.getElementById('cli_cep').value = dados.cep || ''; 
    document.getElementById('cli_tel').value = dados.tel || ''; 
    document.getElementById('cli_wpp').value = dados.wpp || ''; 
    document.getElementById('cli_email').value = dados.email || ''; 
    document.getElementById('cli_obs').value = dados.obs || ''; 
    document.getElementById('cli_arq_nome').value = dados.arq_nome || ''; 
    document.getElementById('cli_arq_wpp').value = dados.arq_wpp || ''; 
    document.getElementById('cli_arq_email').value = dados.arq_email || '';
    
    document.getElementById('modalTitulo').innerText = 'Editar Cadastro';
    
    modalCliente.classList.remove('hidden'); 
    setTimeout(() => { 
        modalCliente.classList.remove('opacity-0'); 
        modalClienteConteudo.classList.remove('scale-95'); 
    }, 10);
}

function fecharModalCliente() {
    modalCliente.classList.add('opacity-0'); 
    modalClienteConteudo.classList.add('scale-95'); 
    setTimeout(() => { 
        modalCliente.classList.add('hidden'); 
    }, 300);
}

// 4. API - Salvar e Deletar
async function salvarClienteServidor(event) {
    event.preventDefault();
    const id = document.getElementById('cli_id').value;
    const endpoint = id ? 'api/edit_cadastro_cliente.php' : 'api/add_cadastro_cliente.php';
    
    const payload = {
        id: id, 
        codigo_cliente: document.getElementById('cli_codigo').value, 
        nome_contrato: document.getElementById('cli_nome').value, 
        cpf_cnpj: document.getElementById('cli_cpf').value, 
        telefone: document.getElementById('cli_tel').value, 
        whatsapp: document.getElementById('cli_wpp').value, 
        email: document.getElementById('cli_email').value, 
        endereco: document.getElementById('cli_end').value, 
        numero_lote: document.getElementById('cli_num').value, 
        quadra: document.getElementById('cli_quadra').value, 
        bairro: document.getElementById('cli_bairro').value, 
        condominio: document.getElementById('cli_cond').value, 
        complemento: document.getElementById('cli_comp').value, 
        cidade: document.getElementById('cli_cid').value, 
        cep: document.getElementById('cli_cep').value, 
        observacao: document.getElementById('cli_obs').value, 
        arquiteto_nome: document.getElementById('cli_arq_nome').value, 
        arquiteto_whatsapp: document.getElementById('cli_arq_wpp').value, 
        arquiteto_email: document.getElementById('cli_arq_email').value
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
            alert('Erro: ' + erroMsg); 
        } 
    } catch (error) { 
        alert('Erro de rede.'); 
    }
}

async function deletarCliente(id) {
    if (!confirm(`Tem a certeza que deseja apagar a ficha deste cliente? Isso não apaga os projetos do PCP atrelados a ele.`)) return;
    try { 
        const response = await fetch('api/delete_cadastro_cliente.php', { 
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
        alert('Falha na comunicação com o servidor.');
    }
}

// 5. Módulo de Impressão de Ficha
const modalImpressao = document.getElementById('modalImpressao'); 
const modalImpressaoConteudo = document.getElementById('modalImpressaoConteudo');

function marcarTodosAmbientes() {
    const checkboxes = document.querySelectorAll('.chk-ambiente');
    const todosMarcados = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => { cb.checked = !todosMarcados; });
    toggleOutrosInput();
}

function toggleOutrosInput() {
    const chkOutros = document.getElementById('chk_outros');
    const inputOutros = document.getElementById('input_outros_texto');
    inputOutros.disabled = !chkOutros.checked;
    if (!chkOutros.checked) { 
        inputOutros.value = ''; 
    } else { 
        inputOutros.focus(); 
    }
}

function chamarImpressaoFicha(btn) {
    clienteImpressaoAtual = lerDadosCard(btn); 
    const nomeCliente = clienteImpressaoAtual.nome || 'Cliente Desconhecido';
    const codCliente = clienteImpressaoAtual.codigo_cliente || `CLI-${String(clienteImpressaoAtual.id).padStart(2, '0')}`;
    
    document.getElementById('label_impressao_nome').innerText = `[${codCliente}] ${nomeCliente}`;
    document.getElementById('data_ficha_impressao').value = '';
    
    document.querySelectorAll('.chk-ambiente').forEach(cb => cb.checked = false);
    toggleOutrosInput();
    
    modalImpressao.classList.remove('hidden'); 
    setTimeout(() => { 
        modalImpressao.classList.remove('opacity-0'); 
        modalImpressaoConteudo.classList.remove('scale-95'); 
    }, 10);
}

function fecharModalImpressao() { 
    modalImpressao.classList.add('opacity-0'); 
    modalImpressaoConteudo.classList.add('scale-95'); 
    setTimeout(() => { 
        modalImpressao.classList.add('hidden'); 
        clienteImpressaoAtual = null; 
    }, 300); 
}

function gerarImpressaoFicha(event) {
    event.preventDefault();
    if (!clienteImpressaoAtual) return;
    
    const tipoFicha = document.getElementById('tipo_ficha_impressao').value;
    const c = clienteImpressaoAtual;
    const codigo = c.codigo_cliente || `CLI-${String(c.id).padStart(2, '0')}`;
    
    const inputData = document.getElementById('data_ficha_impressao').value;
    let dataFormatada = '____/____/20____';
    if (inputData) { 
        const partes = inputData.split('-'); 
        dataFormatada = `${partes[2]}/${partes[1]}/${partes[0]}`; 
    }
    
    const checkboxes = document.querySelectorAll('.chk-ambiente:checked');
    let ambientes = [];
    checkboxes.forEach(cb => { 
        if (cb.id === 'chk_outros') { 
            const extra = document.getElementById('input_outros_texto').value.trim(); 
            ambientes.push(extra ? `Outros (${extra})` : 'Outros'); 
        } else { 
            ambientes.push(cb.value); 
        } 
    });
    
    const textoAmbientes = ambientes.length > 0 ? ambientes.join(', ') : 'Nenhum ambiente específico marcado.';
    
    let linhaEnd = c.end ? c.end : ''; 
    if (c.num) linhaEnd += `, Nº ${c.num}`; 
    if (c.qd) linhaEnd += ` (Qd ${c.qd})`; 
    if (c.comp) linhaEnd += ` - ${c.comp}`;
    
    let linhaBairro = c.bairro ? c.bairro : ''; 
    if (c.cond) linhaBairro += ` - Condomínio ${c.cond}`;
    
    let linhaCid = c.cid ? c.cid : ''; 
    if (c.cep) linhaCid += ` - CEP: ${c.cep}`;

    const html = `<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>SBG - ${tipoFicha}</title><style>@media print { @page { margin: 0; } body { padding: 1.5cm; } } body { font-family: Arial, sans-serif; color: #000; margin: 0; padding: 20px; } .container { max-width: 1000px; margin: 0 auto; } .header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; } .header-left { display: flex; align-items: center; gap: 20px; } .logo-img { max-width: 160px; height: auto; } .header h1 { margin: 0; font-size: 26px; text-transform: uppercase; } .header h3 { margin: 0; font-size: 14px; color: #555; } .info-box { border: 1px solid #000; padding: 15px; border-radius: 4px; margin-bottom: 15px; background: #fff;} .info-grid { display: flex; flex-wrap: wrap; gap: 10px; } .info-col { flex: 1; min-width: 48%; } .label { font-size: 11px; text-transform: uppercase; color: #666; font-weight: bold; margin-bottom: 2px; display: block;} .value { font-size: 14px; font-weight: bold; } .title-client { font-size: 18px; font-weight: 900; text-transform: uppercase; } .drawing-area { width: 100%; height: 500px; border: 2px solid #000; margin-top: 20px; position: relative; background-size: 20px 20px; background-image: linear-gradient(to right, #e5e7eb 1px, transparent 1px), linear-gradient(to bottom, #e5e7eb 1px, transparent 1px); } .drawing-title { position: absolute; top: -10px; left: 15px; background: #fff; padding: 0 10px; font-weight: bold; font-size: 14px; } .footer { display: flex; justify-content: space-between; margin-top: 40px; } .sig-line { width: 300px; border-top: 1px solid #000; text-align: center; padding-top: 5px; font-size: 12px; } </style></head><body><div class="container"><div class="header"><div class="header-left"><img src="assets/images/sbg_oficial.png" class="logo-img" alt="SBG Móveis" onerror="this.style.display='none';"><div><h3>PCP Marcenaria</h3><h1>${tipoFicha}</h1></div></div><div style="text-align: right;"><span class="label">Data da Visita</span><span style="font-size: 18px; font-weight: bold;">${dataFormatada}</span></div></div><div class="info-box"><div class="info-grid"><div class="info-col" style="flex: 0 0 100%;"><span class="label">Cliente / Contrato</span><span class="title-client">[${codigo}] ${c.nome}</span></div><div class="info-col"><span class="label">Telefone Fixo / Celular (Whatsapp)</span><span class="value">${c.tel || '-'} ${c.wpp ? ' / ' + c.wpp : ''}</span></div><div class="info-col"><span class="label">E-mail</span><span class="value">${c.email || '-'}</span></div><div class="info-col" style="flex: 0 0 100%;"><span class="label">Endereço do Local</span><span class="value">${linhaEnd || '-'}</span><br><span class="value" style="font-weight: normal;">${linhaBairro}</span><br><span class="value" style="font-weight: normal;">${linhaCid}</span></div><div class="info-col" style="flex: 0 0 100%; border-top: 1px dashed #ccc; padding-top: 10px; margin-top: 5px;"><span class="label">Ambientes</span><span class="value" style="color: #d97706;">${textoAmbientes}</span></div><div class="info-col" style="flex: 0 0 100%; margin-top: 5px;"><span class="label">Observações de Cadastro</span><span class="value" style="font-weight: normal; font-style: italic;">${c.obs || 'Nenhuma restrição ou observação cadastrada.'}</span></div></div></div><div class="drawing-area"><span class="drawing-title">Área de Anotações e Croqui</span></div><div class="footer"><div class="sig-line">Assinatura do Cliente</div><div class="sig-line">Assinatura do Responsável</div></div></div></body></html>`;
    
    const janelaPrint = window.open('', '_blank', 'width=900,height=800'); 
    janelaPrint.document.write(html); 
    janelaPrint.document.close(); 
    janelaPrint.focus();
    
    setTimeout(() => { 
        janelaPrint.print(); 
        janelaPrint.close(); 
        fecharModalImpressao(); 
    }, 500);
}

// 6. Fechar Modais ao clicar fora
if(modalCliente) modalCliente.addEventListener('click', (e) => { if (e.target === modalCliente) fecharModalCliente(); });
if(modalImpressao) modalImpressao.addEventListener('click', (e) => { if (e.target === modalImpressao) fecharModalImpressao(); });