<?php
// assistencias.php
require_once 'includes/auth.php';
protegerPagina();
require_once 'config/conexao.php';

// Define a permissão do usuário
$role = isset($_SESSION['usuario_role']) ? $_SESSION['usuario_role'] : 'USER';

// Colunas do Kanban de Assistências
$titulos_colunas = [
    'pendente'  => ['titulo' => 'SOLICITAÇÕES PENDENTES', 'cor' => 'border-red-500 text-red-700 dark:text-red-400'],
    'agendada'  => ['titulo' => 'VISITAS AGENDADAS', 'cor' => 'border-blue-500 text-blue-700 dark:text-blue-400'],
    'concluida' => ['titulo' => 'RESOLVIDOS / BAIXADOS', 'cor' => 'border-green-500 text-green-700 dark:text-green-400']
];

try {
    $stmt = $pdo->query("SELECT a.*, c.codigo_cliente, c.id as id_cadastro 
                         FROM assistencias_tecnicas a 
                         LEFT JOIN clientes_cadastro c ON a.cliente = c.nome_contrato 
                         ORDER BY a.resolvido_assistencia ASC, a.data_solicitacao DESC");
    $assistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt_cli = $pdo->query("SELECT * FROM clientes_cadastro ORDER BY nome_contrato ASC");
    $lista_clientes_base = $stmt_cli->fetchAll(PDO::FETCH_ASSOC);

    $colunas = [ 'pendente'  => [], 'agendada'  => [], 'concluida' => [] ];
    
    foreach ($assistencias as $a) {
        $status = isset($colunas[$a['status']]) ? $a['status'] : 'pendente';
        $colunas[$status][] = $a;
    }
} catch (\PDOException $e) {
    die("Erro na consulta: " . $e->getMessage());
}

function formatarData($data) {
    if (!$data) return '';
    return date('d/m/Y', strtotime($data));
}

function formatarDataPrint($data) {
    if (!$data) return '';
    return date('d/m/Y', strtotime($data));
}

// Variáveis para o header
$page_title = 'ASSISTÊNCIAS TÉCNICAS';
$page_subtitle = 'CHAMADOS ABERTOS, AGENDADOS E RESOLVIDOS';
$main_class = 'flex-1'; 
$menu_button_text = 'MENU';
$page_actions = '
<button onclick="abrirModalNovaAssistencia()" class="bg-[#1e3a8a] dark:bg-blue-600 hover:bg-blue-800 dark:hover:bg-blue-500 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors">
    + ASSISTÊNCIA
</button>';

// Estilos extras para o Kanban
$head_extras = '
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
';

require_once 'includes/header.php';
?>

<details class="group bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg mb-6 shadow-sm transition-colors duration-300">
    <summary class="cursor-pointer p-4 font-bold text-lg text-amber-800 dark:text-amber-400 flex items-center justify-between select-none">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Guia Rápido: Gestão de Assistências
        </div>
        <svg class="w-5 h-5 transition-transform duration-200 group-open:rotate-180 text-amber-800 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
    </summary>
    <div class="p-4 pt-0 mt-2 border-t border-amber-200 dark:border-amber-800">
        <ul class="text-sm text-amber-800 dark:text-amber-300 space-y-2 ml-1 mt-3">
            <li class="flex items-start">
                <span class="mr-2 font-bold text-lg leading-none">➕</span>
                <span><strong>Abertura:</strong> Use o botão <em>"+ ASSISTÊNCIA"</em> para registrar um novo chamado, definindo se é Garantia ou Faturada.</span>
            </li>
            <li class="flex items-start">
                <span class="mr-2 font-bold text-lg leading-none">🔄</span>
                <span><strong>Fluxo Kanban:</strong> Arraste os cards entre as colunas para organizar o status (Solicitações Pendentes &rarr; Visitas Agendadas &rarr; Resolvidos / Baixados).</span>
            </li>
            <li class="flex items-start">
                <span class="mr-2 font-bold text-lg leading-none">⚡</span>
                <span><strong>Ações Rápidas:</strong> Em cada card, você pode <strong>Imprimir a OS</strong> (🖨️), <strong>Editar</strong> (✏️), ou <strong>Dar Baixa</strong> (✔️) quando resolvido.</span>
            </li>
        </ul>
    </div>
</details>

<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-100 dark:border-red-800 text-center">
        <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase">Aguardando Visita</p>
        <p class="text-2xl font-black text-red-700 dark:text-red-300"><?= count($colunas['pendente']) ?></p>
    </div>
    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-800 text-center">
        <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase">Visita Agendada</p>
        <p class="text-2xl font-black text-blue-700 dark:text-blue-300"><?= count($colunas['agendada']) ?></p>
    </div>
    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-100 dark:border-green-800 text-center">
        <p class="text-xs font-bold text-green-600 dark:text-green-400 uppercase">Baixadas / Resolvidas</p>
        <p class="text-2xl font-black text-green-700 dark:text-green-300"><?= count($colunas['concluida']) ?></p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <?php foreach ($colunas as $status_chave => $lista_assistencias): $conf = $titulos_colunas[$status_chave]; ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex flex-col h-[550px] transition-colors duration-300">
            
            <div class="flex justify-between items-center border-b-2 <?= $conf['cor'] ?> pb-2 mb-3">
                <h2 class="text-sm font-bold uppercase tracking-wider dark:text-gray-100"><?= $conf['titulo'] ?></h2>
                <select onchange="ordenarColuna('<?= $status_chave ?>', this.value)" class="text-[10px] border border-gray-300 dark:border-gray-600 rounded p-1 bg-white dark:bg-gray-700 dark:text-gray-200 focus:outline-none cursor-pointer">
                    <option value="data_desc">Mais Recentes</option>
                    <option value="data_asc">Mais Antigas</option>
                    <option value="nome_asc">Nome (A-Z)</option>
                </select>
            </div>

            <div id="col-<?= $status_chave ?>" data-status="<?= $status_chave ?>" class="kanban-column flex-1 overflow-y-auto space-y-3 pr-1">
                
          <?php foreach ($lista_assistencias as $a): ?>
                    <?php 
                        // Calcula o código de exibição ANTES de gerar o JSON
                        $codigo_exibicao = '';
                        if (!empty($a['codigo_cliente'])) {
                            $codigo_exibicao = $a['codigo_cliente'];
                        } elseif (!empty($a['id_cadastro'])) {
                            $codigo_exibicao = "CLI-" . str_pad($a['id_cadastro'], 2, "0", STR_PAD_LEFT);
                        }

                        // Cria os dados do Card injetando o código do cliente
                        $cardData = htmlspecialchars(json_encode([
                            'id' => $a['id'], 'projeto_id' => $a['projeto_id'], 'cliente' => $a['cliente'], 'obs' => $a['obs_assistencia'],
                            'codigo_cli' => $codigo_exibicao,
                            'end' => $a['endereco'], 'num' => $a['numero_lote'], 'qd' => $a['quadra'],
                            'bairro' => $a['bairro'], 'cond' => $a['condominio'], 'comp' => $a['complemento'],
                            'cid' => $a['cidade'], 'cep' => $a['cep'], 'fixo' => $a['tel_fixo'], 'cel' => $a['tel_cel'],
                            'dt_solic_raw' => $a['data_solicitacao'], 'dt_agend_raw' => $a['data_assistencia'],
                            'dt_solic' => formatarDataPrint($a['data_solicitacao']), 'dt_agend' => formatarDataPrint($a['data_assistencia']),
                            'resolvido' => $a['resolvido_assistencia'], 'tecnico' => $a['tecnico_assistencia'],
                            'tipo_cobranca' => isset($a['tipo_cobranca']) ? $a['tipo_cobranca'] : 'GARANTIA', 
                            'valor_cobrado' => isset($a['valor_cobrado']) ? $a['valor_cobrado'] : null,
                            'forma_pagamento' => isset($a['forma_pagamento']) ? $a['forma_pagamento'] : null, 
                            'comprovante_file' => isset($a['comprovante_file']) ? $a['comprovante_file'] : null
                        ]), ENT_QUOTES, 'UTF-8'); 
                    ?>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 p-3 rounded shadow-sm cursor-grab active:cursor-grabbing transition-all duration-200" 
                         data-id="<?= $a['id'] ?>" data-nome="<?= htmlspecialchars(strtolower($a['cliente'])) ?>" data-time="<?= strtotime($a['data_solicitacao']) ?>" data-json='<?= $cardData ?>'>
                        
                        <div class="flex justify-between items-start mb-1">
                            <div class="flex items-center space-x-1.5">
                                <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-1.5 py-0.5 rounded">AST #<?= $a['id'] ?></span>
                                
                                <button onclick="chamarImpressao(this)" class="text-gray-400 hover:text-gray-800 dark:hover:text-gray-100 transition-colors text-xs px-1" title="Imprimir OS">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                </button>
                                <button onclick="chamarEdicao(this)" class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors text-xs px-1 font-bold" title="Editar Solicitação">
                                    &#9998;
                                </button>
                                <button onclick="chamarBaixa(this)" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 transition-colors text-sm px-1 font-bold" title="Gerenciar / Dar Baixa">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </button>
                                
                                <?php if ($role === 'ADMIN'): ?>
                                    <button onclick="deletarAssistencia(event, <?= $a['id'] ?>)" class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors text-sm px-1 font-bold" title="Apagar Assistência">
                                        &times;
                                    </button>
                                <?php endif; ?>
                            </div>
                            <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400">Criada: <?= formatarData($a['data_solicitacao']) ?></span>
                        </div>
                        
                        <p class="font-bold text-gray-800 dark:text-gray-100 uppercase text-xs mt-1 flex items-center">
                            <?php if($codigo_exibicao): ?>
                                <span class="text-amber-600 dark:text-amber-500 font-black mr-1.5">[<?= htmlspecialchars($codigo_exibicao) ?>]</span>
                            <?php endif; ?>
                            <?= htmlspecialchars($a['cliente']) ?>
                        </p>

                        <div class="mt-2 flex items-center flex-wrap gap-1">
                            <?php if (isset($a['tipo_cobranca']) && $a['tipo_cobranca'] === 'FATURADA'): ?>
                                <span class="bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 px-1.5 py-0.5 rounded text-[9px] font-bold border border-purple-200 dark:border-purple-800 uppercase">
                                    FATURADA (R$ <?= number_format((float)$a['valor_cobrado'], 2, ',', '.') ?>)
                                </span>
                                <?php if (!empty($a['comprovante_file'])): ?>
                                    <a href="<?= htmlspecialchars($a['comprovante_file']) ?>" target="_blank" class="bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 px-1.5 py-0.5 rounded text-[9px] font-bold border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors flex items-center" title="Ver Comprovante" onclick="event.stopPropagation()">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg> COMPROVANTE
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 px-1.5 py-0.5 rounded text-[9px] font-bold border border-emerald-200 dark:border-emerald-800 uppercase">GARANTIA</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($a['projeto_id']): ?>
                            <p class="text-[10px] text-gray-400 mt-1">(Ref. Projeto Original #<?= $a['projeto_id'] ?>)</p>
                        <?php endif; ?>

                        <?php if ($a['cidade'] || $a['condominio']): ?>
                            <p class="text-[10px] text-gray-400 italic"><?= htmlspecialchars($a['condominio']) ?> <?= $a['cidade'] ? '- '.$a['cidade'] : '' ?></p>
                        <?php endif; ?>
                        
                        <?php if ($a['obs_assistencia']): ?>
                            <p class="text-xs mt-2 italic text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 p-2 rounded">
                                <strong class="text-red-500 dark:text-red-400 not-italic">Defeito/Relato:</strong> <br><?= nl2br(htmlspecialchars($a['obs_assistencia'])) ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($a['resolvido_assistencia'] === 'SIM'): ?>
                            <div class="mt-3 pt-2 border-t border-gray-200 dark:border-gray-600 text-[11px]">
                                <p class="text-green-600 dark:text-green-400 font-bold">✔️ Resolvido por: <?= htmlspecialchars($a['tecnico_assistencia']) ?></p>
                                <p class="text-gray-500 dark:text-gray-400">Data de Resolução: <?= formatarData($a['data_assistencia']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div id="modalNovaAssistencia" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300 max-h-[90vh] overflow-y-auto" id="modalNovaAssistenciaConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
            <h3 class="text-lg font-bold text-amber-600 dark:text-amber-500">Abrir Novo Chamado</h3>
            <button onclick="fecharModalNovaAssistencia()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">&times;</button>
        </div>
        
        <form id="formNovaAssistencia" onsubmit="salvarNovaAssistencia(event)" enctype="multipart/form-data">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Escolher Cliente Cadastrado</label>
                    <input type="text" id="search_na_cliente" onkeyup="filtrarSelect('search_na_cliente', 'na_cliente')" placeholder="Pesquisar cliente..." autocomplete="off" class="w-full px-3 py-1.5 mb-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-amber-500">
                    <select id="na_cliente" name="cliente" required size="4" onchange="autoPreencherFormulario(this.value, 'na')" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-amber-500 scrollbar-thin">
                        <?php foreach ($lista_clientes_base as $cb): 
                            $codigo_cb = !empty($cb['codigo_cliente']) ? $cb['codigo_cliente'] : "CLI-" . str_pad($cb['id'], 2, "0", STR_PAD_LEFT);
                        ?>
                        <option value="<?= htmlspecialchars($cb['nome_contrato']) ?>" class="p-1 border-b border-gray-100 dark:border-gray-700 hover:bg-amber-50 dark:hover:bg-gray-600">
                            [<?= htmlspecialchars($codigo_cb) ?>] - <?= htmlspecialchars($cb['nome_contrato']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Endereço</label>
                    <input type="text" id="na_endereco" name="endereco" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Número / Lote</label>
                    <input type="text" id="na_numero" name="numero_lote" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Quadra</label>
                    <input type="text" id="na_quadra" name="quadra" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Bairro</label>
                    <input type="text" id="na_bairro" name="bairro" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Condomínio</label>
                    <input type="text" id="na_condominio" name="condominio" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Complemento</label>
                    <input type="text" id="na_complemento" name="complemento" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Cidade / UF</label>
                    <input type="text" id="na_cidade" name="cidade" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">CEP</label>
                    <input type="text" id="na_cep" name="cep" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Telefone Fixo</label>
                    <input type="text" id="na_tel_fixo" name="tel_fixo" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Celular / WhatsApp</label>
                    <input type="text" id="na_tel_cel" name="tel_cel" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-amber-500">
                </div>

                <div class="md:col-span-3 mt-1 border-t border-gray-200 dark:border-gray-700 pt-3">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Atendimento</label>
                    <select id="na_tipo_cobranca" name="tipo_cobranca" onchange="toggleFaturamento('na')" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded text-sm focus:ring-2 focus:ring-amber-500 font-bold uppercase">
                        <option value="GARANTIA">GARANTIA (Sem custo)</option>
                        <option value="FATURADA">FATURADA (Cobrar do cliente)</option>
                    </select>
                </div>

                <div id="na_dados_faturamento" class="hidden md:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-3 bg-purple-50 dark:bg-purple-900/20 p-3 rounded border border-purple-200 dark:border-purple-800">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Valor Cobrado (R$)</label>
                        <input type="number" step="0.01" id="na_valor" name="valor_cobrado" placeholder="0.00" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded text-sm focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Forma de Pagamento</label>
                        <select id="na_forma_pagamento" name="forma_pagamento" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded text-sm focus:ring-2 focus:ring-purple-500 uppercase">
                            <option value="">Selecione...</option>
                            <option value="PIX">PIX</option>
                            <option value="CARTAO CREDITO">Cartão de Crédito</option>
                            <option value="CARTAO DEBITO">Cartão de Débito</option>
                            <option value="BOLETO">Boleto</option>
                            <option value="DINHEIRO">Dinheiro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Anexar Comprovante</label>
                        <input type="file" id="na_comprovante" name="comprovante" accept="image/*,.pdf" class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-purple-100 file:text-purple-700 hover:file:bg-purple-200 dark:file:bg-purple-900/50 dark:file:text-purple-300 cursor-pointer">
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Defeito ou Problema Relatado</label>
                <textarea id="na_observacao" name="observacao" rows="3" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-amber-500"></textarea>
            </div>

            <div class="flex justify-end space-x-3 border-t dark:border-gray-700 pt-4">
                <button type="button" onclick="fecharModalNovaAssistencia()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded font-bold transition shadow-sm">Registrar Chamado</button>
            </div>
        </form>
    </div>
</div>

<div id="modalEdicaoAssistencia" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300 max-h-[90vh] overflow-y-auto" id="modalEdicaoAssistenciaConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
            <h3 class="text-lg font-bold text-blue-600 dark:text-blue-400">Editar Chamado <span id="labelEditAstProjeto" class="text-gray-400 dark:text-gray-500 text-sm"></span></h3>
            <button onclick="fecharModalEdicaoAssistencia()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">&times;</button>
        </div>
        
        <form id="formEdicaoAssistencia" onsubmit="salvarEdicaoAssistencia(event)" enctype="multipart/form-data">
            <input type="hidden" id="ea_id" name="id">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Cliente</label>
                    <input type="text" id="search_ea_cliente" onkeyup="filtrarSelect('search_ea_cliente', 'ea_cliente')" placeholder="Pesquisar cliente..." autocomplete="off" class="w-full px-3 py-1.5 mb-1 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500">
                    <select id="ea_cliente" name="cliente" required size="4" onchange="autoPreencherFormulario(this.value, 'ea')" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-blue-500 scrollbar-thin">
                        <?php foreach ($lista_clientes_base as $cb): 
                        $codigo_cb = !empty($cb['codigo_cliente']) ? $cb['codigo_cliente'] : "CLI-" . str_pad($cb['id'], 2, "0", STR_PAD_LEFT);
                        ?>
                        <option value="<?= htmlspecialchars($cb['nome_contrato']) ?>" class="p-1 border-b border-gray-100 dark:border-gray-700 hover:bg-blue-50 dark:hover:bg-gray-600">
                            [<?= htmlspecialchars($codigo_cb) ?>] - <?= htmlspecialchars($cb['nome_contrato']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Endereço</label>
                    <input type="text" id="ea_endereco" name="endereco" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Número / Lote</label>
                    <input type="text" id="ea_numero" name="numero_lote" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Quadra</label>
                    <input type="text" id="ea_quadra" name="quadra" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Bairro</label>
                    <input type="text" id="ea_bairro" name="bairro" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Condomínio</label>
                    <input type="text" id="ea_condominio" name="condominio" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Complemento</label>
                    <input type="text" id="ea_complemento" name="complemento" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Cidade / UF</label>
                    <input type="text" id="ea_cidade" name="cidade" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">CEP</label>
                    <input type="text" id="ea_cep" name="cep" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Telefone Fixo</label>
                    <input type="text" id="ea_tel_fixo" name="tel_fixo" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Celular / WhatsApp</label>
                    <input type="text" id="ea_tel_cel" name="tel_cel" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded uppercase text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="md:col-span-3 mt-1 border-t border-gray-200 dark:border-gray-700 pt-3">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Atendimento</label>
                    <select id="ea_tipo_cobranca" name="tipo_cobranca" onchange="toggleFaturamento('ea')" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded text-sm focus:ring-2 focus:ring-blue-500 font-bold uppercase">
                        <option value="GARANTIA">GARANTIA (Sem custo)</option>
                        <option value="FATURADA">FATURADA (Cobrar do cliente)</option>
                    </select>
                </div>

                <div id="ea_dados_faturamento" class="hidden md:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-3 bg-purple-50 dark:bg-purple-900/20 p-3 rounded border border-purple-200 dark:border-purple-800">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Valor Cobrado (R$)</label>
                        <input type="text" id="ea_valor" name="valor_cobrado" placeholder="0.00" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded text-sm focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Forma de Pagamento</label>
                        <select id="ea_forma_pagamento" name="forma_pagamento" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded text-sm focus:ring-2 focus:ring-purple-500 uppercase">
                            <option value="">Selecione...</option>
                            <option value="PIX">PIX</option>
                            <option value="CARTAO CREDITO">Cartão de Crédito</option>
                            <option value="CARTAO DEBITO">Cartão de Débito</option>
                            <option value="BOLETO">Boleto</option>
                            <option value="DINHEIRO">Dinheiro</option>
                        </select>
                    </div>
                    <div>
                        <div class="flex justify-between items-end mb-1">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Anexar Novo Comprovante</label>
                            <a href="#" id="ea_link_comprovante" target="_blank" class="hidden text-[10px] font-bold text-blue-600 hover:underline">Ver Atual</a>
                        </div>
                        <input type="file" id="ea_comprovante" name="comprovante" accept="image/*,.pdf" class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-purple-100 file:text-purple-700 hover:file:bg-purple-200 dark:file:bg-purple-900/50 dark:file:text-purple-300 cursor-pointer">
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Defeito ou Problema Relatado</label>
                <textarea id="ea_observacao" name="observacao" rows="3" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div class="flex justify-end space-x-3 border-t dark:border-gray-700 pt-4">
                <button type="button" onclick="fecharModalEdicaoAssistencia()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-bold transition shadow-sm">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<div id="modalBaixa" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalBaixaConteudo">
        <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-2">
            <h3 class="text-lg font-bold text-green-600 dark:text-green-400">Baixa de Assistência <span id="labelAstProjeto" class="text-gray-400 dark:text-gray-500 text-sm"></span></h3>
            <button onclick="fecharModalBaixa()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">&times;</button>
        </div>
        
        <form id="formBaixa" onsubmit="salvarBaixaServidor(event)">
            <input type="hidden" id="ast_id" name="id">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Técnico Responsável</label>
                <input type="text" id="ast_tecnico" required placeholder="Nome do Técnico" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-green-500 uppercase">
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Data da Visita/Reparo</label>
                    <input type="date" id="ast_data" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1 text-green-600 dark:text-green-400">Problema Resolvido?</label>
                    <select id="ast_resolvido" class="w-full px-3 py-2 border border-green-300 dark:border-green-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-green-500 font-bold">
                        <option value="NAO">NÃO</option>
                        <option value="SIM">SIM (Concluído)</option>
                    </select>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Relato do Problema Original</label>
                <textarea id="ast_observacao" rows="4" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-green-500"></textarea>
            </div>
            <div class="flex justify-end space-x-3 border-t dark:border-gray-700 pt-4">
                <button type="button" onclick="fecharModalBaixa()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded font-bold transition shadow-sm">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
    window.CLIENTES_BASE_DATA = <?= json_encode($lista_clientes_base) ?>;
    
    // Função de Exclusão de Assistência
    async function deletarAssistencia(event, id) {
        event.stopPropagation();
        if (!confirm(`Deseja realmente apagar a assistência #${id}? Esta ação não pode ser desfeita.`)) return;
        
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
</script>
<script src="assets/js/assistencias.js?v=<?= time() ?>"></script>

<?php require_once 'includes/footer.php'; ?>