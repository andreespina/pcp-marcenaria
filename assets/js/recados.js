const modalRecado = document.getElementById('modalRecado'); 
const modalRecadoConteudo = document.getElementById('modalRecadoConteudo');

// ======= LOGICA: RECADOS INTERNOS =======

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

// ======= LOGICA: MENSAGENS PADRÕES =======

const modalMensagem = document.getElementById('modalMensagem'); 
const modalMensagemConteudo = document.getElementById('modalMensagemConteudo');

function abrirModalNovaMensagem() {
    document.getElementById('formMensagem').reset();
    document.getElementById('msg_id').value = '';
    document.getElementById('modalTituloMsg').innerText = 'Nova Mensagem Padrão';
    modalMensagem.classList.remove('hidden'); 
    setTimeout(() => { modalMensagem.classList.remove('opacity-0'); modalMensagemConteudo.classList.remove('scale-95'); }, 10);
}

function abrirModalEdicaoMensagem(id, titulo, etapa, msg) {
    document.getElementById('msg_id').value = id;
    document.getElementById('msg_titulo').value = titulo;
    document.getElementById('msg_etapa').value = etapa;
    document.getElementById('msg_texto').value = msg;
    document.getElementById('modalTituloMsg').innerText = 'Editar Mensagem Padrão';
    modalMensagem.classList.remove('hidden'); 
    setTimeout(() => { modalMensagem.classList.remove('opacity-0'); modalMensagemConteudo.classList.remove('scale-95'); }, 10);
}

function fecharModalMensagem() {
    modalMensagem.classList.add('opacity-0'); modalMensagemConteudo.classList.add('scale-95'); 
    setTimeout(() => { modalMensagem.classList.add('hidden'); }, 300);
}

async function salvarMensagem(event) {
    event.preventDefault();
    const id = document.getElementById('msg_id').value;
    const endpoint = id ? 'api/edit_mensagem.php' : 'api/add_mensagem.php';
    const payload = { 
        id: id, 
        titulo: document.getElementById('msg_titulo').value, 
        etapa: document.getElementById('msg_etapa').value, 
        mensagem: document.getElementById('msg_texto').value 
    };
    try {
        const response = await fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const result = await response.json();
        if (result.success) { window.location.reload(); } else { alert('Erro: ' + (result.error || 'Erro desconhecido')); }
    } catch (error) { alert('Erro de rede.'); }
}

async function deletarMensagem(id) {
    if (!confirm(`Tem certeza que deseja apagar esta mensagem padrão?`)) return;
    try {
        const response = await fetch('api/delete_mensagem.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) });
        const result = await response.json();
        if (result.success) { window.location.reload(); } else { alert('Erro ao apagar: ' + result.error); }
    } catch (error) { alert('Erro de rede ao apagar.'); }
}

function copiarMensagem(btn) {
    const texto = btn.getAttribute('data-texto');
    const btnSpan = btn.querySelector('span');
    
    // Tenta usar a API Clipboard (Navegadores modernos e conexões seguras)
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(texto).then(() => {
            feedbackCopia(btnSpan, btn);
        }).catch(err => {
            console.error('Falha ao copiar', err);
            fallbackCopyTextToClipboard(texto, btnSpan, btn);
        });
    } else {
        // Fallback para conexões não-HTTPS
        fallbackCopyTextToClipboard(texto, btnSpan, btn);
    }
}

function fallbackCopyTextToClipboard(text, btnSpan, btn) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        document.execCommand('copy');
        feedbackCopia(btnSpan, btn);
    } catch (err) {
        console.error('Falha ao copiar com fallback', err);
        alert('Seu navegador não permitiu copiar o texto automaticamente.');
    }
    document.body.removeChild(textArea);
}

function feedbackCopia(btnSpan, btn) {
    const textoOriginal = btnSpan.innerText;
    btnSpan.innerText = 'Copiado!';
    btn.classList.remove('text-green-700', 'dark:text-green-400');
    btn.classList.add('bg-green-600', 'text-white');
    
    setTimeout(() => {
        btnSpan.innerText = textoOriginal;
        btn.classList.remove('bg-green-600', 'text-white');
        btn.classList.add('text-green-700', 'dark:text-green-400');
    }, 2000);
}

if(modalMensagem) modalMensagem.addEventListener('click', (e) => { if (e.target === modalMensagem) fecharModalMensagem(); });