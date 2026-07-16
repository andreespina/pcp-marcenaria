<?php
// clientes.php
require_once 'includes/auth.php';
protegerPagina();
require_once 'config/conexao.php';

try {
    $stmt = $pdo->query("SELECT * FROM clientes_cadastro ORDER BY nome_contrato ASC");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_clientes = count($clientes);
} catch (\PDOException $e) {
    die("Erro na consulta: " . $e->getMessage());
}

function jsSafe($val) {
    if ($val === null) $val = '';
    return htmlspecialchars(json_encode($val), ENT_QUOTES, 'UTF-8');
}

// ---- VARIÁVEIS PARA O HEADER.PHP ----
$page_title = 'CLIENTES';
$page_subtitle = 'Cadastro e Gestão de Contatos';
$main_class = 'flex-1'; 
$menu_button_text = 'MENU';

$page_actions = '
<button onclick="abrirModalCliente()" class="bg-[#1e3a8a] dark:bg-blue-600 hover:bg-blue-800 dark:hover:bg-blue-500 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
    NOVO CLIENTE
</button>';

require_once 'includes/header.php';
// -------------------------------------
?>

<div class="flex flex-col gap-6">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-[#222736] rounded-lg border border-gray-200 dark:border-[#2a3142] shadow-sm p-4 flex items-center">
            <div class="p-3 rounded-full bg-blue-50 dark:bg-blue-900/20 text-[#1e3a8a] dark:text-blue-400 mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase">Total de Clientes</p>
                <p class="text-2xl font-black text-gray-800 dark:text-white"><?= $total_clientes ?></p>
            </div>
        </div>

        <div class="md:col-span-2 bg-white dark:bg-[#222736] rounded-lg border border-gray-200 dark:border-[#2a3142] shadow-sm p-4 flex flex-col justify-center">
            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Pesquisar Cliente</label>
            <div class="relative">
                <input type="text" id="filtro_clientes" onkeyup="filtrarTabela()" placeholder="Digite o nome, código ou CPF..." class="w-full px-4 py-2 pl-10 bg-gray-50 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-white rounded focus:ring-2 focus:ring-[#1e3a8a] dark:focus:ring-blue-500 transition-colors">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
    </div>

    <details class="group bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800 rounded-lg shadow-sm transition-colors duration-300">
        <summary class="cursor-pointer p-4 font-bold text-sm text-[#1e3a8a] dark:text-blue-400 flex items-center justify-between select-none uppercase tracking-wide">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Guia Rápido: Gestão de Clientes
            </div>
            <svg class="w-5 h-5 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </summary>
        <div class="p-4 pt-0 mt-2 border-t border-blue-200 dark:border-blue-800">
            <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-2 ml-1 mt-3">
                <li class="flex items-start">
                    <span class="mr-2">📋</span>
                    <span><strong>Cadastro:</strong> Clique no botão <em>"NOVO CLIENTE"</em> para registrar um contrato. Insira os dados pessoais, endereço da obra e contatos do arquiteto(a) responsável.</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">👁️</span>
                    <span><strong>Dossiê 360º:</strong> Clique no ícone de "Olho" para ver a linha do tempo do cliente, histórico financeiro, contratos fechados e assistências.</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">⚙️</span>
                    <span><strong>Gestão:</strong> Utilize a barra de busca acima para encontrar clientes rapidamente. Clique na linha de qualquer cliente para expandir e ver os dados completos.</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">🖨️</span>
                    <span><strong>Fichas:</strong> Clique no ícone de impressora na linha do cliente para gerar formulários de <em>Medição</em>. O sistema criará um documento pronto para impressão.</span>
                </li>
            </ul>
        </div>
    </details>

    <div class="bg-white dark:bg-[#222736] rounded-lg border border-gray-200 dark:border-[#2a3142] shadow-sm overflow-hidden flex flex-col p-4">
        
        <div class="space-y-2" id="listaClientes">
            <?php if (empty($clientes)): ?>
                <div class="p-6 text-center text-gray-500 dark:text-gray-400 italic">Nenhum cliente cadastrado no momento.</div>
            <?php endif; ?>
            
            <?php foreach ($clientes as $c): 
                $codigo_exibicao = !empty($c['codigo_cliente']) ? htmlspecialchars($c['codigo_cliente']) : "CLI-" . str_pad($c['id'], 2, "0", STR_PAD_LEFT);

                $end_parts = [];
                if($c['endereco']) $end_parts[] = $c['endereco'];
                if($c['numero_lote']) $end_parts[] = $c['numero_lote'];
                if($c['condominio']) $end_parts[] = $c['condominio'];
                if($c['cidade']) $end_parts[] = $c['cidade'];
                $endereco_resumido = implode(", ", $end_parts);

                $cardData = htmlspecialchars(json_encode([
                    'id' => $c['id'], 'codigo_cliente' => $c['codigo_cliente'], 'nome' => $c['nome_contrato'], 'cpf' => $c['cpf_cnpj'],
                    'end' => $c['endereco'], 'num' => $c['numero_lote'], 'qd' => $c['quadra'],
                    'bairro' => $c['bairro'], 'cond' => $c['condominio'], 'comp' => $c['complemento'],
                    'cid' => $c['cidade'], 'cep' => $c['cep'], 'tel' => $c['telefone'], 'wpp' => $c['whatsapp'],
                    'email' => $c['email'], 'obs' => $c['observacao'],
                    'arq_nome' => $c['arquiteto_nome'], 'arq_wpp' => $c['arquiteto_whatsapp'], 'arq_email' => $c['arquiteto_email']
                ]), ENT_QUOTES, 'UTF-8');
            ?>
                <div class="linha-cliente border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50/50 dark:bg-gray-800/40 overflow-hidden shadow-sm transition-all hover:border-blue-300 dark:hover:border-blue-600" data-json='<?= $cardData ?>'>
                    
                    <div class="p-3.5 flex items-center justify-between cursor-pointer select-none" onclick="toggleDetails(this)">
                        <div class="flex items-center space-x-3.5 overflow-hidden">
                            <svg class="w-4 h-4 text-gray-400 transform transition-transform duration-200 icon-seta flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                            
                            <span class="text-[11px] font-black text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 px-2 py-0.5 rounded border border-blue-200 dark:border-blue-800 tracking-wider flex-shrink-0 codigo-busca">
                                [<?= $codigo_exibicao ?>]
                            </span>
                            
                            <span class="font-bold text-gray-800 dark:text-gray-100 uppercase truncate text-sm nome-busca"><?= htmlspecialchars($c['nome_contrato']) ?></span>
                        </div>
                        
                        <div class="flex items-center space-x-2 pl-2" onclick="event.stopPropagation();">
                            
                            <a href="perfil_cliente.php?id=<?= $c['id'] ?>" class="text-gray-500 hover:text-purple-600 dark:text-gray-400 dark:hover:text-purple-400 transition-colors p-1.5 bg-white dark:bg-gray-700 rounded shadow-sm border border-gray-200 dark:border-gray-600 flex items-center justify-center" title="Ver Dossiê 360º">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </a>

                            <button onclick="chamarImpressaoFicha(this)" class="text-gray-500 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400 transition-colors p-1.5 bg-white dark:bg-gray-700 rounded shadow-sm border border-gray-200 dark:border-gray-600" title="Imprimir Ficha de Medição">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            </button>
                            <button onclick="chamarEdicao(this)" class="text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 transition-colors p-1.5 bg-white dark:bg-gray-700 rounded shadow-sm border border-gray-200 dark:border-gray-600" title="Editar Cliente">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                            <button onclick="deletarCliente(<?= $c['id'] ?>)" class="text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 transition-colors p-1.5 bg-white dark:bg-gray-700 rounded shadow-sm border border-gray-200 dark:border-gray-600" title="Apagar Cliente">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="details-container hidden border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900/30 p-4 transition-all">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs">
                            
                            <div class="space-y-2">
                                <h4 class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Contatos Gerais</h4>
                                <p class="text-gray-700 dark:text-gray-300"><strong class="text-gray-500 dark:text-gray-400">E-mail:</strong> <?= htmlspecialchars($c['email']) ?: '-' ?></p>
                                <?php if($c['telefone']): ?>
                                    <p class="text-gray-700 dark:text-gray-300"><strong class="text-gray-500 dark:text-gray-400">Telefone:</strong> <?= htmlspecialchars($c['telefone']) ?></p>
                                <?php endif; ?>
                                <?php if($c['whatsapp']): ?>
                                    <p class="text-green-600 dark:text-green-400 font-bold">
                                        <a href="https://wa.me/55<?= preg_replace('/[^0-9]/', '', $c['whatsapp']) ?>" target="_blank" class="hover:underline flex items-center">
                                            <span class="mr-1">💬 WhatsApp:</span> <?= htmlspecialchars($c['whatsapp']) ?>
                                        </a>
                                    </p>
                                <?php endif; ?>
                                <p class="text-gray-700 dark:text-gray-300"><strong class="text-gray-500 dark:text-gray-400">CPF/CNPJ:</strong> <?= htmlspecialchars($c['cpf_cnpj']) ?: '-' ?></p>
                            </div>

                            <div class="bg-indigo-50/60 dark:bg-indigo-900/20 p-3 rounded border border-indigo-100 dark:border-indigo-800">
                                <h4 class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 mb-2">Arquiteto(a) Vinculado</h4>
                                <?php if($c['arquiteto_nome']): ?>
                                    <p class="text-gray-800 dark:text-gray-200 font-bold uppercase mb-1"><?= htmlspecialchars($c['arquiteto_nome']) ?></p>
                                    <?php if($c['arquiteto_whatsapp']): ?>
                                        <p class="text-green-600 dark:text-green-400 font-bold mb-1">
                                            <a href="https://wa.me/55<?= preg_replace('/[^0-9]/', '', $c['arquiteto_whatsapp']) ?>" target="_blank" class="hover:underline">
                                                💬 <?= htmlspecialchars($c['arquiteto_whatsapp']) ?>
                                            </a>
                                        </p>
                                    <?php endif; ?>
                                    <?php if($c['arquiteto_email']): ?>
                                        <p class="text-gray-600 dark:text-gray-400"><?= htmlspecialchars($c['arquiteto_email']) ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="italic text-gray-500 dark:text-gray-400">Nenhum profissional cadastrado.</p>
                                <?php endif; ?>
                            </div>

                            <div class="space-y-1.5">
                                <h4 class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Local da Obra</h4>
                                <?php if($endereco_resumido): ?>
                                    <p class="text-gray-800 dark:text-gray-200 font-bold uppercase"><?= htmlspecialchars($c['endereco']) ?><?= $c['numero_lote'] ? ', Nº ' . htmlspecialchars($c['numero_lote']) : '' ?></p>
                                    <?php if($c['quadra'] || $c['bairro']): ?>
                                        <p class="text-gray-600 dark:text-gray-400"><?= $c['quadra'] ? 'Quadra/Lote: ' . htmlspecialchars($c['quadra']) : '' ?><?= $c['bairro'] ? ' - Bairro: ' . htmlspecialchars($c['bairro']) : '' ?></p>
                                    <?php endif; ?>
                                    <?php if($c['condominio']): ?>
                                        <p class="text-amber-600 dark:text-amber-400 font-bold mt-1">🏢 Condomínio: <?= htmlspecialchars($c['condominio']) ?></p>
                                    <?php endif; ?>
                                    <?php if($c['complemento'] || $c['cidade']): ?>
                                        <p class="text-gray-500 dark:text-gray-400 italic mt-1"><?= $c['complemento'] ? htmlspecialchars($c['complemento']) . ' ' : '' ?><?= $c['cidade'] ? ' (' . htmlspecialchars($c['cidade']) . ')' : '' ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="italic text-gray-500 dark:text-gray-400">Endereço não cadastrado.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if($c['observacao']): ?>
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <h4 class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Observações do Cliente</h4>
                                <p class="bg-yellow-50 dark:bg-yellow-900/10 p-3 rounded border border-yellow-200 dark:border-yellow-800/50 text-yellow-800 dark:text-yellow-400 italic font-medium whitespace-pre-wrap">"<?= htmlspecialchars($c['observacao']) ?>"</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div id="modalCliente" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-4xl p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300 max-h-[95vh] overflow-y-auto" id="modalClienteConteudo">
        
        <div class="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
            <h3 id="modalTitulo" class="text-lg font-bold text-[#1e3a8a] dark:text-blue-400">Cadastrar Novo Cliente</h3>
            <button type="button" onclick="fecharModalCliente()" class="text-gray-400 hover:text-gray-800 dark:hover:text-white text-2xl font-bold">&times;</button>
        </div>
        
        <form id="formCliente" onsubmit="salvarClienteServidor(event)">
            <input type="hidden" id="cli_id">
            
            <h4 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3 border-b border-gray-200 dark:border-gray-700 pb-1">1. Dados Principais</h4>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1 text-blue-600 dark:text-blue-400">Código Interno</label>
                    <input type="text" id="cli_codigo" placeholder="Ex: 2026-089" class="w-full px-3 py-2 border border-blue-300 dark:border-blue-600 bg-blue-50 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 font-bold text-sm uppercase">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nome do Contrato / Cliente</label>
                    <input type="text" id="cli_nome" required placeholder="Ex: JOÃO DA SILVA" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">CPF ou CNPJ</label>
                    <input type="text" id="cli_cpf" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">E-mail do Cliente</label>
                    <input type="email" id="cli_email" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
            </div>

            <h4 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3 border-b border-gray-200 dark:border-gray-700 pb-1">2. Local de Instalação e Contato</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6 bg-gray-50 dark:bg-gray-700/30 p-4 rounded border border-gray-200 dark:border-gray-700">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Endereço</label>
                    <input type="text" id="cli_end" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Número / Lote</label>
                    <input type="text" id="cli_num" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Quadra</label>
                    <input type="text" id="cli_quadra" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Bairro</label>
                    <input type="text" id="cli_bairro" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Condomínio</label>
                    <input type="text" id="cli_cond" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Complemento</label>
                    <input type="text" id="cli_comp" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Cidade / UF</label>
                    <input type="text" id="cli_cid" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">CEP</label>
                    <input type="text" id="cli_cep" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Telefone Fixo</label>
                    <input type="text" id="cli_tel" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Celular / WhatsApp</label>
                    <input type="text" id="cli_wpp" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
            </div>

            <h4 class="text-sm font-bold text-indigo-500 dark:text-indigo-400 uppercase tracking-widest mb-3 border-b border-indigo-200 dark:border-indigo-800 pb-1">3. Dados do Arquiteto(a)</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6 bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded border border-indigo-100 dark:border-indigo-800">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nome do Arquiteto(a)</label>
                    <input type="text" id="cli_arq_nome" placeholder="Nome do profissional" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-indigo-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">WhatsApp</label>
                    <input type="text" id="cli_arq_wpp" placeholder="(00) 00000-0000" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">E-mail</label>
                    <input type="email" id="cli_arq_email" placeholder="arq@dominio.com" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Observações Internas</label>
                <textarea id="cli_obs" rows="2" placeholder="Restrições de horário, etc..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div class="flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4 mt-6">
                <button type="button" onclick="fecharModalCliente()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-[#1e3a8a] dark:bg-blue-600 hover:bg-blue-800 text-white rounded font-bold transition shadow-sm">Salvar Ficha</button>
            </div>
        </form>
    </div>
</div>

<div id="modalImpressao" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300 max-h-[90vh] overflow-y-auto" id="modalImpressaoConteudo">
        
        <div class="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">Imprimir Ficha Cliente</h3>
            <button type="button" onclick="fecharModalImpressao()" class="text-gray-400 hover:text-gray-800 dark:hover:text-white text-2xl font-bold">&times;</button>
        </div>
        
        <form onsubmit="gerarImpressaoFicha(event)">
            <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 mb-4 uppercase" id="label_impressao_nome">CLIENTE SELECIONADO</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Formulário</label>
                    <select id="tipo_ficha_impressao" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-green-500 uppercase text-sm font-bold">
                        <option value="FICHA DE MEDIÇÃO">Ficha de Medição</option>
                        <option value="CONFIRMAÇÃO DE MEDIDAS">Confirmação de Medidas</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Data da Visita</label>
                    <input type="date" id="data_ficha_impressao" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-green-500 text-sm font-bold">
                </div>
            </div>

            <div class="mb-6">
                <div class="flex justify-between items-end mb-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Ambientes a serem medidos (Opcional)</label>
                    <button type="button" onclick="marcarTodosAmbientes()" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline">Marcar Todos</button>
                </div>
                <div class="grid grid-cols-2 gap-2 bg-gray-50 dark:bg-gray-700/50 p-3 rounded border border-gray-200 dark:border-gray-600">
                    <label class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="ambientes[]" value="Cozinha" class="rounded text-green-600 focus:ring-green-500 chk-ambiente"><span>Cozinha</span></label>
                    <label class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="ambientes[]" value="Sala Estar/Jantar" class="rounded text-green-600 focus:ring-green-500 chk-ambiente"><span>Sala Estar/Jantar</span></label>
                    <label class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="ambientes[]" value="Dormitório Casal" class="rounded text-green-600 focus:ring-green-500 chk-ambiente"><span>Dorm. Casal</span></label>
                    <label class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="ambientes[]" value="Dormitório Solteiro" class="rounded text-green-600 focus:ring-green-500 chk-ambiente"><span>Dorm. Solteiro</span></label>
                    <label class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="ambientes[]" value="Banheiro/Lavabo" class="rounded text-green-600 focus:ring-green-500 chk-ambiente"><span>Banheiro/Lavabo</span></label>
                    <label class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="ambientes[]" value="Área de Serviço" class="rounded text-green-600 focus:ring-green-500 chk-ambiente"><span>Área de Serviço</span></label>
                    <label class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="ambientes[]" value="Escritório/Home Office" class="rounded text-green-600 focus:ring-green-500 chk-ambiente"><span>Escritório</span></label>
                    <label class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="ambientes[]" value="Área Gourmet" class="rounded text-green-600 focus:ring-green-500 chk-ambiente"><span>Área Gourmet</span></label>
                    
                    <div class="col-span-2 flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300 mt-1">
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" id="chk_outros" value="Outros" class="rounded text-green-600 focus:ring-green-500 chk-ambiente" onchange="toggleOutrosInput()">
                            <span>Outros:</span>
                        </label>
                        <input type="text" id="input_outros_texto" disabled placeholder="Especifique os ambientes..." class="flex-1 px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded focus:ring-1 focus:ring-green-500 disabled:opacity-50 disabled:bg-gray-200 dark:disabled:bg-gray-700 transition-all">
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4 mt-6">
                <button type="button" onclick="fecharModalImpressao()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded font-bold transition shadow-sm flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Imprimir
                </button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/clientes.js?v=<?= time() ?>"></script>

<?php require_once 'includes/footer.php'; ?>