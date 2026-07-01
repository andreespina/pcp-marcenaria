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
$menu_button_text = 'MENU';
$page_actions = '
<button onclick="abrirModalCliente()" class="bg-[#1e3a8a] dark:bg-blue-600 hover:bg-blue-800 dark:hover:bg-blue-500 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors">
    + CLIENTE
</button>';
require_once 'includes/header.php';
// -------------------------------------
?>

<!-- GUIA RÁPIDO: CLIENTES -->
<details class="group bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg mb-6 shadow-sm transition-colors duration-300">
    <summary class="cursor-pointer p-4 font-bold text-lg text-blue-800 dark:text-blue-300 flex items-center justify-between select-none">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Guia Rápido: Gestão de Clientes
        </div>
        <svg class="w-5 h-5 transition-transform duration-200 group-open:rotate-180 text-blue-800 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
    </summary>
    <div class="p-4 pt-0 mt-2 border-t border-blue-200 dark:border-blue-800">
        <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-2 ml-1 mt-3">
            <li class="flex items-start">
                <span class="mr-2">📋</span>
                <span><strong>Cadastro:</strong> Clique no botão <em>"+ CLIENTE"</em> para registrar um novo contrato. Insira os dados pessoais, endereço completo da obra e os contatos do arquiteto(a) responsável.</span>
            </li>
            <li class="flex items-start">
                <span class="mr-2">⚙️</span>
                <span><strong>Gestão:</strong> Utilize a barra de busca abaixo para encontrar clientes rapidamente pelo nome ou código. Você pode clicar em qualquer linha para expandir e ver o endereço completo, contatos ou dados do arquiteto.</span>
            </li>
            <li class="flex items-start">
                <span class="mr-2">🖨️</span>
                <span><strong>Impressão de Ficha:</strong> Clique no ícone de impressora no cabeçalho do cliente para gerar formulários de <em>Medição</em> ou <em>Confirmação de Medidas</em>. Selecione os ambientes, e o sistema criará um documento pronto para levar à obra.</span>
            </li>
        </ul>
    </div>
</details>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden p-4">
    <div class="mb-4">
        <input type="text" id="filtro_clientes" onkeyup="filtrarTabela()" placeholder="Buscar cliente por nome ou código..." class="w-full md:w-1/3 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded focus:ring-2 focus:ring-blue-500 text-sm">
    </div>

    <div class="space-y-2" id="listaClientes">
        <?php if (empty($clientes)): ?>
            <div class="p-6 text-center text-gray-500 dark:text-gray-400 italic">Nenhum cliente cadastrado.</div>
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
            <div class="linha-cliente border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800/40 overflow-hidden shadow-sm transition-all" data-json='<?= $cardData ?>'>
                
                <div class="p-3.5 flex items-center justify-between cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/30 select-none" onclick="toggleDetails(this)">
                    <div class="flex items-center space-x-3.5 overflow-hidden">
                        <svg class="w-4 h-4 text-gray-400 transform transition-transform duration-200 icon-seta flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        
                        <span class="text-[11px] font-black text-blue-600 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/50 px-2 py-0.5 rounded border border-blue-200 dark:border-blue-800 tracking-wider flex-shrink-0 codigo-busca">
                            <?= $codigo_exibicao ?>
                        </span>
                        
                        <span class="font-bold text-gray-800 dark:text-gray-100 uppercase truncate text-sm nome-busca"><?= htmlspecialchars($c['nome_contrato']) ?></span>
                    </div>
                    
                    <div class="flex items-center space-x-2 pl-2" onclick="event.stopPropagation();">
                        <button onclick="chamarImpressaoFicha(this)" class="text-gray-400 hover:text-green-600 dark:hover:text-green-400 transition-colors p-1" title="Imprimir Ficha de Medição">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        </button>
                        <button onclick="chamarEdicao(this)" class="text-blue-500 hover:text-blue-700 dark:hover:text-blue-300 transition-colors p-1 text-sm font-bold" title="Editar Cliente">
                            &#9998;
                        </button>
                        <button onclick="deletarCliente(<?= $c['id'] ?>)" class="text-red-400 hover:text-red-600 dark:hover:text-red-400 transition-colors text-lg p-1 font-bold" title="Apagar Cliente">
                            &times;
                        </button>
                    </div>
                </div>
                
                <div class="details-container hidden border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/10 p-4 transition-all">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs">
                        
                        <div class="space-y-1.5">
                            <h4 class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">Contatos Gerais</h4>
                            <p class="text-gray-700 dark:text-gray-300"><strong class="text-gray-400 dark:text-gray-500">E-mail:</strong> <?= htmlspecialchars($c['email']) ?: '-' ?></p>
                            <?php if($c['telefone']): ?>
                                <p class="text-gray-700 dark:text-gray-300"><strong class="text-gray-400 dark:text-gray-500">Telefone:</strong> <?= htmlspecialchars($c['telefone']) ?></p>
                            <?php endif; ?>
                            <?php if($c['whatsapp']): ?>
                                <p class="text-green-600 dark:text-green-400 font-semibold">
                                    <a href="https://wa.me/55<?= preg_replace('/[^0-9]/', '', $c['whatsapp']) ?>" target="_blank" class="hover:underline flex items-center">
                                        <span class="mr-1">💬 WhatsApp:</span> <?= htmlspecialchars($c['whatsapp']) ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                            <p class="text-gray-700 dark:text-gray-300"><strong class="text-gray-400 dark:text-gray-500">CPF/CNPJ:</strong> <?= htmlspecialchars($c['cpf_cnpj']) ?: '-' ?></p>
                        </div>

                        <div class="bg-indigo-50/60 dark:bg-indigo-950/20 p-3 rounded border border-indigo-100 dark:border-indigo-900/40">
                            <h4 class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 dark:text-indigo-400 mb-1.5">Arquiteto(a) Vinculado(a)</h4>
                            <?php if($c['arquiteto_nome']): ?>
                                <p class="text-gray-800 dark:text-gray-200 font-bold uppercase mb-0.5"><?= htmlspecialchars($c['arquiteto_nome']) ?></p>
                                <?php if($c['arquiteto_whatsapp']): ?>
                                    <p class="text-green-600 dark:text-green-400 font-semibold mb-0.5">
                                        <a href="https://wa.me/55<?= preg_replace('/[^0-9]/', '', $c['arquiteto_whatsapp']) ?>" target="_blank" class="hover:underline">
                                            💬 <?= htmlspecialchars($c['arquiteto_whatsapp']) ?>
                                        </a>
                                    </p>
                                <?php endif; ?>
                                <?php if($c['arquiteto_email']): ?>
                                    <p class="text-gray-500 dark:text-gray-400"><?= htmlspecialchars($c['arquiteto_email']) ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="italic text-gray-400 dark:text-gray-500">Nenhum profissional cadastrado.</p>
                            <?php endif; ?>
                        </div>

                        <div class="space-y-1">
                            <h4 class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">Local da Obra</h4>
                            <?php if($endereco_resumido): ?>
                                <p class="text-gray-800 dark:text-gray-200 font-semibold uppercase"><?= htmlspecialchars($c['endereco']) ?><?= $c['numero_lote'] ? ', Nº ' . htmlspecialchars($c['numero_lote']) : '' ?></p>
                                <?php if($c['quadra'] || $c['bairro']): ?>
                                    <p class="text-gray-500 dark:text-gray-400"><?= $c['quadra'] ? 'Quadra/Lote: ' . htmlspecialchars($c['quadra']) : '' ?><?= $c['bairro'] ? ' - Bairro: ' . htmlspecialchars($c['bairro']) : '' ?></p>
                                <?php endif; ?>
                                <?php if($c['condominio']): ?>
                                    <p class="text-amber-600 dark:text-amber-400 font-bold">🏢 Condomínio: <?= htmlspecialchars($c['condominio']) ?></p>
                                <?php endif; ?>
                                <?php if($c['complemento'] || $c['cidade']): ?>
                                    <p class="text-gray-500 dark:text-gray-400 italic"><?= $c['complemento'] ? htmlspecialchars($c['complemento']) . ' ' : '' ?><?= $c['cidade'] ? ' (' . htmlspecialchars($c['cidade']) . ')' : '' ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="italic text-gray-400 dark:text-gray-500">Endereço não cadastrado.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if($c['observacao']): ?>
                        <div class="mt-3.5 pt-3 border-t border-gray-200 dark:border-gray-700">
                            <h4 class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">Observações do Cliente</h4>
                            <p class="bg-white dark:bg-gray-800 p-2.5 rounded border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 italic font-mono whitespace-pre-wrap">"<?= htmlspecialchars($c['observacao']) ?>"</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="modalCliente" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-4xl p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300 max-h-[95vh] overflow-y-auto" id="modalClienteConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
            <h3 id="modalTitulo" class="text-lg font-bold text-[#1e3a8a] dark:text-blue-400">Cadastrar Novo Cliente</h3>
            <button onclick="fecharModalCliente()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">&times;</button>
        </div>
        
        <form id="formCliente" onsubmit="salvarClienteServidor(event)">
            <input type="hidden" id="cli_id">
            
            <h4 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3 border-b border-gray-200 dark:border-gray-700 pb-1">1. Dados Principais</h4>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1 text-blue-600 dark:text-blue-400">Código Interno</label>
                    <input type="text" id="cli_codigo" placeholder="Ex: 2026-089" class="w-full px-2 py-1.5 border border-blue-300 dark:border-blue-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 font-bold text-sm uppercase">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nome do Contrato / Cliente</label>
                    <input type="text" id="cli_nome" required placeholder="Ex: JOÃO DA SILVA" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">CPF ou CNPJ</label>
                    <input type="text" id="cli_cpf" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">E-mail do Cliente</label>
                    <input type="email" id="cli_email" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
            </div>

            <h4 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3 border-b border-gray-200 dark:border-gray-700 pb-1">2. Local de Instalação e Contato</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6 bg-gray-50 dark:bg-gray-700/30 p-4 rounded border border-gray-200 dark:border-gray-700">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Endereço</label>
                    <input type="text" id="cli_end" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Número / Lote</label>
                    <input type="text" id="cli_num" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Quadra</label>
                    <input type="text" id="cli_quadra" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Bairro</label>
                    <input type="text" id="cli_bairro" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Condomínio</label>
                    <input type="text" id="cli_cond" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Complemento</label>
                    <input type="text" id="cli_comp" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Cidade / UF</label>
                    <input type="text" id="cli_cid" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">CEP</label>
                    <input type="text" id="cli_cep" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Telefone Fixo</label>
                    <input type="text" id="cli_tel" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Celular / WhatsApp</label>
                    <input type="text" id="cli_wpp" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
            </div>

            <h4 class="text-sm font-bold text-indigo-500 dark:text-indigo-400 uppercase tracking-widest mb-3 border-b border-indigo-200 dark:border-indigo-800 pb-1">3. Dados do Arquiteto(a)</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6 bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded border border-indigo-100 dark:border-indigo-800">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nome do Arquiteto(a)</label>
                    <input type="text" id="cli_arq_nome" placeholder="Nome do profissional" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-indigo-500 uppercase text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">WhatsApp</label>
                    <input type="text" id="cli_arq_wpp" placeholder="(00) 00000-0000" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">E-mail</label>
                    <input type="email" id="cli_arq_email" placeholder="arq@dominio.com" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Observações Internas</label>
                <textarea id="cli_obs" rows="2" placeholder="Restrições de horário, etc..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div class="flex justify-end space-x-3 border-t dark:border-gray-700 pt-4">
                <button type="button" onclick="fecharModalCliente()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-[#1e3a8a] dark:bg-blue-600 hover:bg-blue-800 text-white rounded font-bold transition shadow-sm">Salvar Ficha</button>
            </div>
        </form>
    </div>
</div>

<div id="modalImpressao" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300 max-h-[90vh] overflow-y-auto" id="modalImpressaoConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">Imprimir Ficha Cliente</h3>
            <button onclick="fecharModalImpressao()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">&times;</button>
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
                        <input type="text" id="input_outros_texto" disabled placeholder="Especifique os ambientes..." class="flex-1 px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded focus:ring-1 focus:ring-green-500 disabled:opacity-50 disabled:bg-gray-200 dark:disabled:bg-gray-700 transition-all">
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 border-t dark:border-gray-700 pt-4">
                <button type="button" onclick="fecharModalImpressao()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded font-bold transition shadow-sm flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Imprimir
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let clienteImpressaoAtual = null; 

    // LÓGICA DO INTERRUPTOR DE EXPANSÃO (ACCORDION)
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

    // FILTRAGEM TOTALMENTE PRESERVADA E FUNCIONAL COM O ACCORDION
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

    function lerDadosCard(btn) { const card = btn.closest('[data-json]'); return JSON.parse(card.getAttribute('data-json')); }
    
    // Funções de Edição e Deleção
    function chamarEdicao(btn) { const dados = lerDadosCard(btn); abrirModalEdicao(dados); }
    async function deletarCliente(id) {
        if (!confirm(`Tem certeza que deseja apagar a ficha deste cliente? Isso não apaga os projetos atrelados a ele.`)) return;
        try { await fetch('api/delete_cadastro_cliente.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) }); window.location.reload(); } catch (error) {}
    }

    // Modal Cliente
    const modalCliente = document.getElementById('modalCliente'); 
    const modalClienteConteudo = document.getElementById('modalClienteConteudo');

    function abrirModalCliente() {
        document.getElementById('formCliente').reset();
        document.getElementById('cli_id').value = '';
        document.getElementById('modalTitulo').innerText = 'Cadastrar Novo Cliente';
        modalCliente.classList.remove('hidden'); 
        setTimeout(() => { modalCliente.classList.remove('opacity-0'); modalClienteConteudo.classList.remove('scale-95'); }, 10);
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

        // Preenche campos do arquiteto
        document.getElementById('cli_arq_nome').value = dados.arq_nome || '';
        document.getElementById('cli_arq_wpp').value = dados.arq_wpp || '';
        document.getElementById('cli_arq_email').value = dados.arq_email || '';
        
        document.getElementById('modalTitulo').innerText = 'Editar Cadastro';
        modalCliente.classList.remove('hidden'); 
        setTimeout(() => { modalCliente.classList.remove('opacity-0'); modalClienteConteudo.classList.remove('scale-95'); }, 10);
    }

    function fecharModalCliente() {
        modalCliente.classList.add('opacity-0'); modalClienteConteudo.classList.add('scale-95'); 
        setTimeout(() => { modalCliente.classList.add('hidden'); }, 300);
    }

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
            // Envia campos do arquiteto
            arquiteto_nome: document.getElementById('cli_arq_nome').value,
            arquiteto_whatsapp: document.getElementById('cli_arq_wpp').value,
            arquiteto_email: document.getElementById('cli_arq_email').value
        };

        try {
            const response = await fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const result = await response.json();
            if (result.success) { window.location.reload(); } else { alert('Erro: ' + (result.error || 'Erro desconhecido')); }
        } catch (error) { alert('Erro de rede.'); }
    }

    // ==========================================
    // LÓGICA DE IMPRESSÃO
    // ==========================================
    const modalImpressao = document.getElementById('modalImpressao'); 
    const modalImpressaoConteudo = document.getElementById('modalImpressaoConteudo');

    function marcarTodosAmbientes() {
        const checkboxes = document.querySelectorAll('.chk-ambiente');
        const todosMarcados = Array.from(checkboxes).every(cb => cb.checked);
        
        checkboxes.forEach(cb => {
            cb.checked = !todosMarcados;
        });
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
        
        // Resetar modal
        document.getElementById('data_ficha_impressao').value = '';
        document.querySelectorAll('.chk-ambiente').forEach(cb => cb.checked = false);
        toggleOutrosInput();

        modalImpressao.classList.remove('hidden'); 
        setTimeout(() => { modalImpressao.classList.remove('opacity-0'); modalImpressaoConteudo.classList.remove('scale-95'); }, 10);
    }

    function fecharModalImpressao() {
        modalImpressao.classList.add('opacity-0'); modalImpressaoConteudo.classList.add('scale-95'); 
        setTimeout(() => { modalImpressao.classList.add('hidden'); clienteImpressaoAtual = null; }, 300);
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

        // Resgata os ambientes e checa o campo "Outros"
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

        const html = `
            <!DOCTYPE html>
            <html lang="pt-BR">
            <head>
                <meta charset="UTF-8">
                <title>SBG Móveis & Design - ${tipoFicha}</title>
                <style>
                    @media print {
                        @page { margin: 0; } 
                        body { padding: 1.5cm; } 
                    }
                    body { font-family: Arial, sans-serif; color: #000; margin: 0; padding: 20px; } 
                    .container { max-width: 1000px; margin: 0 auto; } 
                    .header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; } 
                    .header-left { display: flex; align-items: center; gap: 20px; }
                    .logo-img { max-width: 160px; height: auto; }
                    .header h1 { margin: 0; font-size: 26px; text-transform: uppercase; }
                    .header h3 { margin: 0; font-size: 14px; color: #555; }
                    .info-box { border: 1px solid #000; padding: 15px; border-radius: 4px; margin-bottom: 15px; background: #fff;}
                    .info-grid { display: flex; flex-wrap: wrap; gap: 10px; }
                    .info-col { flex: 1; min-width: 48%; }
                    .label { font-size: 11px; text-transform: uppercase; color: #666; font-weight: bold; margin-bottom: 2px; display: block;}
                    .value { font-size: 14px; font-weight: bold; }
                    .title-client { font-size: 18px; font-weight: 900; text-transform: uppercase; }
                    
                    .drawing-area { 
                        width: 100%; 
                        height: 500px; 
                        border: 2px solid #000; 
                        margin-top: 20px; 
                        position: relative;
                        background-size: 20px 20px;
                        background-image: 
                            linear-gradient(to right, #e5e7eb 1px, transparent 1px),
                            linear-gradient(to bottom, #e5e7eb 1px, transparent 1px);
                    }
                    .drawing-title { position: absolute; top: -10px; left: 15px; background: #fff; padding: 0 10px; font-weight: bold; font-size: 14px; }
                    .footer { display: flex; justify-content: space-between; margin-top: 40px; }
                    .sig-line { width: 300px; border-top: 1px solid #000; text-align: center; padding-top: 5px; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <div class="header-left">
                            <img src="assets/images/sbg_oficial.png" class="logo-img" alt="SBG Móveis" onerror="this.style.display='none';">
                            <div>
                                <h3>PCP Marcenaria</h3>
                                <h1>${tipoFicha}</h1>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span class="label">Data da Visita</span>
                            <span style="font-size: 18px; font-weight: bold;">${dataFormatada}</span>
                        </div>
                    </div>

                    <div class="info-box">
                        <div class="info-grid">
                            <div class="info-col" style="flex: 0 0 100%;">
                                <span class="label">Cliente / Contrato</span>
                                <span class="title-client">[${codigo}] ${c.nome}</span>
                            </div>
                            
                            <div class="info-col">
                                <span class="label">Telefone Fixo / Celular (Whatsapp)</span>
                                <span class="value">${c.tel || '-'} ${c.wpp ? ' / ' + c.wpp : ''}</span>
                            </div>
                            <div class="info-col">
                                <span class="label">E-mail</span>
                                <span class="value">${c.email || '-'}</span>
                            </div>

                            <div class="info-col" style="flex: 0 0 100%;">
                                <span class="label">Endereço do Local</span>
                                <span class="value">${linhaEnd || '-'}</span><br>
                                <span class="value" style="font-weight: normal;">${linhaBairro}</span><br>
                                <span class="value" style="font-weight: normal;">${linhaCid}</span>
                            </div>

                            <div class="info-col" style="flex: 0 0 100%; border-top: 1px dashed #ccc; padding-top: 10px; margin-top: 5px;">
                                <span class="label">Ambientes</span>
                                <span class="value" style="color: #d97706;">${textoAmbientes}</span>
                            </div>

                            <div class="info-col" style="flex: 0 0 100%; margin-top: 5px;">
                                <span class="label">Observações de Cadastro</span>
                                <span class="value" style="font-weight: normal; font-style: italic;">${c.obs || 'Nenhuma restrição ou observação cadastrada.'}</span>
                            </div>
                        </div>
                    </div>

                    <div class="drawing-area">
                        <span class="drawing-title">Área de Anotações e Croqui</span>
                    </div>

                    <div class="footer">
                        <div class="sig-line">Assinatura do Cliente</div>
                        <div class="sig-line">Assinatura do Responsável</div>
                    </div>
                </div>
            </body>
            </html>
        `;

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

    modalCliente.addEventListener('click', (e) => { if (e.target === modalCliente) fecharModalCliente(); });
    modalImpressao.addEventListener('click', (e) => { if (e.target === modalImpressao) fecharModalImpressao(); });
</script>

<?php require_once 'includes/footer.php'; ?>