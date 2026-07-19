// assets/js/administrativo.js

// 1. UTILITÁRIOS E PESQUISA
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

function filtrarTabelaAdmin() {
    const filtro = document.getElementById('filtro_admin').value.toLowerCase();
    const linhas = document.querySelectorAll('.tr-busca');
    linhas.forEach(linha => {
        const texto = linha.innerText.toLowerCase();
        linha.style.display = texto.includes(filtro) ? '' : 'none';
    });
}

// 2. MODAL NOVO CONTRATO MANUAL
const modalNovoContrato = document.getElementById('modalNovoContrato');
const modalNovoContratoConteudo = document.getElementById('modalNovoContratoConteudo');

function abrirModalNovoContrato() {
    document.getElementById('formNovoContrato').reset();
    document.getElementById('search_manual_cliente').value = '';
    filtrarSelect('search_manual_cliente', 'manual_cliente_id');
    
    modalNovoContrato.classList.remove('hidden');
    setTimeout(() => { 
        modalNovoContrato.classList.remove('opacity-0'); 
        modalNovoContratoConteudo.classList.remove('scale-95'); 
    }, 10);
}

function fecharModalNovoContrato() {
    modalNovoContrato.classList.add('opacity-0'); 
    modalNovoContratoConteudo.classList.add('scale-95');
    setTimeout(() => { modalNovoContrato.classList.add('hidden'); }, 300);
}

async function salvarNovoContrato(event) {
    event.preventDefault();
    
    const payload = {
        cliente_id: document.getElementById('manual_cliente_id').value,
        valor: document.getElementById('manual_valor').value,
        status_contrato: document.getElementById('manual_status_contrato').value,
        status_financeiro: document.getElementById('manual_status_financeiro').value
    };

    try {
        const response = await fetch('api/add_administrativo_manual.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
        });
        const result = await response.json().catch(() => null);
        
        if(response.ok && result && result.success) {
            fecharModalNovoContrato();
            window.location.reload();
        } else {
            const erroMsg = (result && result.error) ? result.error : `Erro HTTP ${response.status}`;
            alert('Erro: ' + erroMsg);
        }
    } catch(e) {
        alert('Falha na conexão com a API.');
    }
}

// 3. MODAL GERENCIAR CONTRATO
const modalGerenciar = document.getElementById('modalGerenciar');
const modalGerenciarConteudo = document.getElementById('modalGerenciarConteudo');
let parcelasConfirmadas = [];

function abrirModalGerenciar(contrato) {
    document.getElementById('formGerenciar').reset();
    parcelasConfirmadas = [];
    document.getElementById('lista_previa_parcelas').innerHTML = '';
    document.getElementById('lista_previa_parcelas').classList.add('hidden');
    
    document.getElementById('gerenciar_id').value = contrato.id;
    document.getElementById('gerenciar_nome_cliente').innerText = contrato.cliente_nome;
    document.getElementById('gerenciar_valor_total').value = contrato.valor;
    document.getElementById('label_valor_venda').innerText = parseFloat(contrato.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2});
    
    document.getElementById('gerenciar_status_contrato').value = contrato.status_contrato || 'PENDENTE';
    document.getElementById('gerenciar_status_financeiro').value = contrato.status_financeiro || 'A FATURAR';
    document.getElementById('gerenciar_nf').value = contrato.numero_nf || '';
    
    document.getElementById('custo_mdf').value = contrato.custo_mdf || '';
    document.getElementById('custo_ferragens').value = contrato.custo_ferragens || '';
    document.getElementById('custo_comissao').value = contrato.custo_comissao || '';
    document.getElementById('custo_outros').value = contrato.custo_outros || '';
    
    verificarStatusFinanceiro();
    calcularLucro();
    
    modalGerenciar.classList.remove('hidden');
    setTimeout(() => { 
        modalGerenciar.classList.remove('opacity-0'); 
        modalGerenciarConteudo.classList.remove('scale-95'); 
    }, 10);
}

function fecharModalGerenciar() {
    modalGerenciar.classList.add('opacity-0'); 
    modalGerenciarConteudo.classList.add('scale-95');
    setTimeout(() => { modalGerenciar.classList.add('hidden'); }, 300);
}

function verificarStatusFinanceiro() {
    const statusFin = document.getElementById('gerenciar_status_financeiro').value;
    const boxParcelas = document.getElementById('box_parcelamento');
    if(statusFin === 'FATURADO') {
        boxParcelas.style.opacity = '1';
        boxParcelas.style.pointerEvents = 'auto';
    } else {
        boxParcelas.style.opacity = '0.4';
        boxParcelas.style.pointerEvents = 'none';
    }
}

function calcularLucro() {
    const valorVenda = parseFloat(document.getElementById('gerenciar_valor_total').value) || 0;
    const cMdf = parseFloat(document.getElementById('custo_mdf').value) || 0;
    const cFer = parseFloat(document.getElementById('custo_ferragens').value) || 0;
    const cCom = parseFloat(document.getElementById('custo_comissao').value) || 0;
    const cOut = parseFloat(document.getElementById('custo_outros').value) || 0;
    
    const totalCustos = cMdf + cFer + cCom + cOut;
    const lucro = valorVenda - totalCustos;
    let margem = valorVenda > 0 ? (lucro / valorVenda) * 100 : 0;

    document.getElementById('calc_total_custos').innerText = totalCustos.toLocaleString('pt-BR', {minimumFractionDigits: 2});
    document.getElementById('calc_lucro').innerText = lucro.toLocaleString('pt-BR', {minimumFractionDigits: 2});
    document.getElementById('calc_margem').innerText = margem.toFixed(1).replace('.', ',');
}

function gerarPreviaParcelas() {
    parcelasConfirmadas = [];
    const lista = document.getElementById('lista_previa_parcelas');
    lista.innerHTML = '';
    lista.classList.remove('hidden');

    const vEntrada = parseFloat(document.getElementById('parc_entrada_valor').value) || 0;
    const dEntrada = document.getElementById('parc_entrada_data').value;
    const qtd = parseInt(document.getElementById('parc_qtd').value) || 0;
    const vParcela = parseFloat(document.getElementById('parc_valor').value) || 0;
    const dIni = document.getElementById('parc_data_ini').value;

    if (vEntrada > 0 && dEntrada) {
        parcelasConfirmadas.push({ num: 0, desc: 'Entrada', valor: vEntrada, data: dEntrada });
    }

    if (qtd > 0 && vParcela > 0 && dIni) {
        let dataAtual = new Date(dIni + 'T12:00:00'); 
        for (let i = 1; i <= qtd; i++) {
            const dataFormatada = dataAtual.toISOString().split('T')[0];
            parcelasConfirmadas.push({ num: i, desc: `Parcela ${i}/${qtd}`, valor: vParcela, data: dataFormatada });
            dataAtual.setMonth(dataAtual.getMonth() + 1); 
        }
    }

    if (parcelasConfirmadas.length === 0) {
        lista.innerHTML = '<p class="text-red-500 italic">Preencha os dados da entrada e/ou parcelas.</p>';
        return;
    }

    let totalSoma = 0;
    parcelasConfirmadas.forEach(p => {
        totalSoma += p.valor;
        const dForm = p.data.split('-').reverse().join('/');
        lista.innerHTML += `
            <div class="flex justify-between items-center bg-white dark:bg-gray-800 p-2 border border-blue-100 dark:border-blue-900 rounded">
                <span class="font-bold text-gray-700 dark:text-gray-300 uppercase">${p.desc}</span>
                <div class="text-right">
                    <span class="block font-black text-blue-600 dark:text-blue-400">R$ ${p.valor.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400">Venc: ${dForm}</span>
                </div>
            </div>`;
    });
    
    lista.innerHTML += `<div class="text-right mt-2 font-bold text-gray-700 dark:text-gray-300">Total Lançado: <span class="text-emerald-600">R$ ${totalSoma.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span></div>`;
}

async function salvarGerenciar(event) {
    event.preventDefault();
    
    const payload = {
        id: document.getElementById('gerenciar_id').value,
        cliente_nome: document.getElementById('gerenciar_nome_cliente').innerText,
        status_contrato: document.getElementById('gerenciar_status_contrato').value,
        status_financeiro: document.getElementById('gerenciar_status_financeiro').value,
        numero_nf: document.getElementById('gerenciar_nf').value,
        custos: {
            mdf: document.getElementById('custo_mdf').value || 0,
            ferragens: document.getElementById('custo_ferragens').value || 0,
            comissao: document.getElementById('custo_comissao').value || 0,
            outros: document.getElementById('custo_outros').value || 0
        },
        parcelas: document.getElementById('gerenciar_status_financeiro').value === 'FATURADO' ? parcelasConfirmadas : []
    };

    try {
        const response = await fetch('api/save_administrativo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await response.json().catch(() => null);
        
        if(response.ok && result && result.success) {
            fecharModalGerenciar();
            window.location.reload();
        } else {
            const erroMsg = (result && result.error) ? result.error : `Erro HTTP ${response.status}`;
            alert('Erro ao salvar: ' + erroMsg);
        }
    } catch(e) {
        alert('Falha na conexão com o servidor.');
    }
}

// 4. INICIALIZAÇÃO E GRÁFICOS (CHART.JS)
document.addEventListener('DOMContentLoaded', () => {
    
    // Fechar modais ao clicar fora
    window.addEventListener('click', (e) => {
        if (e.target === modalNovoContrato) fecharModalNovoContrato();
        if (e.target === modalGerenciar) fecharModalGerenciar();
    });

    const corTexto = document.documentElement.classList.contains('dark') ? '#9ca3af' : '#475569';

    // Gráfico 1: Status Financeiro (Rosca)
    if (document.getElementById('chartAdminStatus') && typeof window.chartAdminStatusData !== 'undefined') {
        new Chart(document.getElementById('chartAdminStatus').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['A Faturar', 'Faturado', 'Pago'],
                datasets: [{
                    data: window.chartAdminStatusData,
                    backgroundColor: ['#f59e0b', '#3b82f6', '#10b981'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '70%',
                plugins: { legend: { position: 'right', labels: { color: corTexto, font: { size: 10, weight: 'bold' } } } }
            }
        });
    }

    // Gráfico 2: Distribuição de Custos vs Lucro Global (Pizza)
    if (document.getElementById('chartAdminCustos') && typeof window.chartAdminCustosData !== 'undefined') {
        new Chart(document.getElementById('chartAdminCustos').getContext('2d'), {
            type: 'pie',
            data: {
                labels: ['MDF / Chapas', 'Ferragens', 'Comissões', 'Outros Custos', 'Lucro Líquido'],
                datasets: [{
                    data: window.chartAdminCustosData,
                    backgroundColor: ['#ef4444', '#f97316', '#eab308', '#64748b', '#10b981'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { color: corTexto, font: { size: 10, weight: 'bold' } } } }
            }
        });
    }
});