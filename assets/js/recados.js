const modalRecado = document.getElementById('modalRecado'); 
const modalRecadoConteudo = document.getElementById('modalRecadoConteudo');

function abrirModalNovo() {
    document.getElementById('formRecado').reset();
    document.getElementById('recado_id').value = '';
    document.getElementById('recado_data').value = new Date().toISOString().split('T')[0];
    document.getElementById('modalTitulo').innerText = 'Novo Recado';
    modalRecado.classList.remove('hidden'); 
    setTimeout(() => { modalRecado.classList.remove('opacity-0'); modalRecadoConteudo.classList.remove('scale-95'); }, 10);
}

function abrirModalEdicao(id, data, de, para, setor, pri, msg) {
    document.getElementById('recado_id').value = id;
    document.getElementById('recado_data').value = data;
    document.getElementById('recado_de').value = de;
    document.getElementById('recado_para').value = para;
    document.getElementById('recado_setor').value = setor;
    document.getElementById('recado_prioridade').value = pri;
    document.getElementById('recado_mensagem').value = msg;
    document.getElementById('modalTitulo').innerText = 'Editar Recado';
    modalRecado.classList.remove('hidden'); 
    setTimeout(() => { modalRecado.classList.remove('opacity-0'); modalRecadoConteudo.classList.remove('scale-95'); }, 10);
}

function fecharModal() {
    modalRecado.classList.add('opacity-0'); modalRecadoConteudo.classList.add('scale-95'); 
    setTimeout(() => { modalRecado.classList.add('hidden'); }, 300);
}

async function salvarRecado(event) {
    event.preventDefault();
    const id = document.getElementById('recado_id').value;
    const endpoint = id ? 'api/edit_recado.php' : 'api/add_recado.php';
    const payload = { id: id, data_recado: document.getElementById('recado_data').value, de_quem: document.getElementById('recado_de').value, para_quem: document.getElementById('recado_para').value, setor: document.getElementById('recado_setor').value, prioridade: document.getElementById('recado_prioridade').value, mensagem: document.getElementById('recado_mensagem').value };
    try {
        const response = await fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const result = await response.json();
        if (result.success) { window.location.reload(); } else { alert('Erro: ' + (result.error || 'Erro desconhecido')); }
    } catch (error) { alert('Erro de rede.'); }
}

async function deletarRecado(id) {
    if (!confirm(`Tem certeza que deseja apagar este recado?`)) return;
    try {
        const response = await fetch('api/delete_recado.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) });
        const result = await response.json();
        if (result.success) { window.location.reload(); } else { alert('Erro ao apagar: ' + result.error); }
    } catch (error) { alert('Erro de rede ao apagar.'); }
}

if(modalRecado) modalRecado.addEventListener('click', (e) => { if (e.target === modalRecado) fecharModal(); });