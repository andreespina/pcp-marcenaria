<?php
// perfil_cliente.php
require_once 'includes/auth.php';
protegerPagina();
require_once 'config/conexao.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: clientes.php");
    exit;
}

$cliente_id = (int) $_GET['id'];

try {
    // 1. DADOS DO CLIENTE
    $stmt = $pdo->prepare("SELECT * FROM clientes_cadastro WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        die("Cliente não encontrado.");
    }

    $codigo = !empty($cliente['codigo_cliente']) ? $cliente['codigo_cliente'] : "CLI-" . str_pad($cliente['id'], 2, "0", STR_PAD_LEFT);
    $nome_base = $cliente['nome_contrato'];

    // 2. DADOS FINANCEIROS (LTV)
    $stmtFin = $pdo->prepare("SELECT * FROM financeiro WHERE entidade_tipo = 'CLIENTE' AND entidade_id = ? ORDER BY data_vencimento DESC");
    $stmtFin->execute([$cliente_id]);
    $financeiro = $stmtFin->fetchAll(PDO::FETCH_ASSOC);

    $ltv_total = 0; $total_pago = 0; $total_pendente = 0;
    foreach ($financeiro as $f) {
        if ($f['tipo'] === 'RECEITA') {
            $ltv_total += $f['valor'];
            if ($f['status'] === 'PAGO') $total_pago += $f['valor'];
            else $total_pendente += $f['valor'];
        }
    }

    // 3. CONTRATOS (ADMINISTRATIVO) - BUSCA ULTRARRÁPIDA POR ID
    $stmtCont = $pdo->prepare("SELECT * FROM administrativo_contratos WHERE cliente_id = ? ORDER BY id DESC");
    $stmtCont->execute([$cliente_id]);
    $contratos = $stmtCont->fetchAll(PDO::FETCH_ASSOC);

    // 4. PROJETOS (PCP) - NOVA SESSÃO ADICIONADA
    $stmtPcp = $pdo->prepare("SELECT * FROM projetos_pcp WHERE cliente_id = ? ORDER BY id DESC");
    $stmtPcp->execute([$cliente_id]);
    $projetos_pcp = $stmtPcp->fetchAll(PDO::FETCH_ASSOC);

    // 5. ASSISTÊNCIAS TÉCNICAS - BUSCA ULTRARRÁPIDA POR ID
    $stmtAst = $pdo->prepare("SELECT * FROM assistencias_tecnicas WHERE cliente_id = ? ORDER BY data_solicitacao DESC");
    $stmtAst->execute([$cliente_id]);
    $assistencias = $stmtAst->fetchAll(PDO::FETCH_ASSOC);

    // 6. TIMELINE (INTERAÇÕES)
    $stmtInt = $pdo->prepare("SELECT * FROM clientes_interacoes WHERE cliente_id = ? ORDER BY data_registro DESC");
    $stmtInt->execute([$cliente_id]);
    $interacoes = $stmtInt->fetchAll(PDO::FETCH_ASSOC);

} catch (\PDOException $e) {
    die("Erro ao carregar dossiê: " . $e->getMessage());
}

// Funções para exibição
function nomeStatus($status) {
    $nomes = [
        'instalacao'      => 'Instalação',
        'expedicao'       => 'Expedição',
        'producao'        => 'Produção',
        'desenvolvimento' => 'PCP',
        'atrasou'         => 'Atrasou',
        'assistencia'     => 'Concluído'
    ];
    return isset($nomes[$status]) ? $nomes[$status] : strtoupper($status);
}

$page_title = 'DOSSIÊ DO CLIENTE';
$page_subtitle = htmlspecialchars($nome_base);
$main_class = 'flex-1 max-w-7xl mx-auto w-full'; 
$menu_button_text = 'MENU';
$page_actions = '
<a href="clientes.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
    VOLTAR AOS CLIENTES
</a>';

require_once 'includes/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-1 space-y-6">
        
        <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] p-5 relative overflow-hidden">
            <div class="absolute top-0 right-0 bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400 font-black text-xs px-3 py-1 rounded-bl-lg border-b border-l border-pink-200 dark:border-pink-800">
                <?= $codigo ?>
            </div>
            
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center text-white font-bold text-xl mr-4 shadow-md">
                    <?= substr($nome_base, 0, 1) ?>
                </div>
                <div>
                    <h2 class="font-black text-gray-800 dark:text-white text-lg uppercase leading-tight"><?= htmlspecialchars($nome_base) ?></h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold mt-0.5">Cliente Oficial</p>
                </div>
            </div>

            <div class="space-y-3 text-sm">
                <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <span class="w-6 text-center mr-2">📱</span> <strong><?= htmlspecialchars($cliente['telefone'] ?: $cliente['whatsapp']) ?: 'Sem telefone' ?></strong>
                </div>
                <div class="flex items-center text-gray-700 dark:text-gray-300">
                    <span class="w-6 text-center mr-2">📧</span> <span class="truncate"><?= htmlspecialchars($cliente['email']) ?: 'Sem e-mail' ?></span>
                </div>
                <div class="flex items-start text-gray-700 dark:text-gray-300">
                    <span class="w-6 text-center mr-2 mt-0.5">📍</span> 
                    <span class="flex-1 text-xs">
                        <?= htmlspecialchars($cliente['endereco']) ?><?= $cliente['numero_lote'] ? ', Nº ' . htmlspecialchars($cliente['numero_lote']) : '' ?><br>
                        <span class="text-gray-500 dark:text-gray-400 italic"><?= htmlspecialchars($cliente['cidade']) ?></span>
                    </span>
                </div>
            </div>
            
            <?php if($cliente['arquiteto_nome']): ?>
            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                <p class="text-[10px] font-bold text-indigo-500 uppercase mb-1">Arquiteto(a) Parceiro(a)</p>
                <p class="font-bold text-gray-800 dark:text-gray-200 text-sm uppercase"><?= htmlspecialchars($cliente['arquiteto_nome']) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="bg-gradient-to-br from-[#1e3a8a] to-blue-800 rounded-lg shadow-md border border-blue-900 p-5 text-white">
            <h3 class="text-xs font-bold text-blue-200 uppercase tracking-wider mb-4 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                LTV Financeiro (Receitas)
            </h3>
            
            <div class="mb-4">
                <p class="text-[11px] text-blue-300 font-semibold mb-0.5">Total de Negócios Fechados</p>
                <p class="text-3xl font-black">R$ <?= number_format($ltv_total, 2, ',', '.') ?></p>
            </div>
            
            <div class="flex justify-between border-t border-blue-700/50 pt-3">
                <div>
                    <p class="text-[10px] text-emerald-300 font-bold uppercase">Total Recebido</p>
                    <p class="font-bold text-emerald-400">R$ <?= number_format($total_pago, 2, ',', '.') ?></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-red-300 font-bold uppercase">A Receber</p>
                    <p class="font-bold text-red-300">R$ <?= number_format($total_pendente, 2, ',', '.') ?></p>
                </div>
            </div>
        </div>
        
    </div>

    <div class="lg:col-span-1 space-y-4">
        
        <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] overflow-hidden flex flex-col h-[230px]">
            <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
                <h3 class="font-bold text-gray-700 dark:text-gray-200 text-xs uppercase tracking-wider flex items-center">
                    <span class="w-5 h-5 bg-blue-100 text-blue-600 rounded flex items-center justify-center mr-2">📋</span>
                    Contratos Fechados
                </h3>
                <span class="text-xs font-bold text-gray-500 bg-white dark:bg-gray-700 px-2 py-0.5 rounded border dark:border-gray-600"><?= count($contratos) ?></span>
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-2 scrollbar-thin">
                <?php if(empty($contratos)): ?>
                    <p class="text-center text-xs text-gray-500 italic mt-4">Nenhum contrato registado.</p>
                <?php endif; ?>
                <?php foreach($contratos as $cont): ?>
                    <div class="border border-gray-100 dark:border-gray-700 p-2.5 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex justify-between items-start mb-1">
                            <p class="text-xs font-black text-gray-800 dark:text-gray-200">#<?= $cont['id'] ?> - R$ <?= number_format($cont['valor'], 2, ',', '.') ?></p>
                            <span class="text-[9px] font-bold uppercase <?= $cont['status_contrato'] === 'ASSINADO' ? 'text-green-600' : 'text-red-500' ?>"><?= $cont['status_contrato'] ?></span>
                        </div>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 flex justify-between">
                            <span>Fin: <strong><?= $cont['status_financeiro'] ?></strong></span>
                            <span><?= isset($cont['data_criacao']) ? date('d/m/Y', strtotime($cont['data_criacao'])) : 'Registado' ?></span>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] overflow-hidden flex flex-col h-[230px]">
            <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-indigo-50/50 dark:bg-indigo-900/10 flex justify-between items-center">
                <h3 class="font-bold text-indigo-700 dark:text-indigo-500 text-xs uppercase tracking-wider flex items-center">
                    <span class="w-5 h-5 bg-indigo-100 text-indigo-600 rounded flex items-center justify-center mr-2">🏭</span>
                    Projetos PCP
                </h3>
                <span class="text-xs font-bold text-indigo-600 bg-white dark:bg-gray-800 px-2 py-0.5 rounded border border-indigo-200 dark:border-indigo-800"><?= count($projetos_pcp) ?></span>
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-2 scrollbar-thin">
                <?php if(empty($projetos_pcp)): ?>
                    <p class="text-center text-xs text-gray-500 italic mt-4">Nenhum projeto no PCP.</p>
                <?php endif; ?>
                <?php foreach($projetos_pcp as $pcp): ?>
                    <div class="border border-gray-100 dark:border-gray-700 p-2.5 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex justify-between items-start mb-1">
                            <p class="text-[11px] font-black text-gray-800 dark:text-gray-200 uppercase">PROJ #<?= $pcp['id'] ?></p>
                            <span class="text-[9px] font-bold uppercase text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 px-1 rounded"><?= nomeStatus($pcp['status']) ?></span>
                        </div>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 flex justify-between">
                            <span>Equipa: <strong><?= htmlspecialchars($pcp['equipe_instalacao']) ?: '-' ?></strong></span>
                            <span>Prev: <?= date('d/m/Y', strtotime($pcp['data_limite'])) ?></span>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] overflow-hidden flex flex-col h-[230px]">
            <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-amber-50/50 dark:bg-amber-900/10 flex justify-between items-center">
                <h3 class="font-bold text-amber-700 dark:text-amber-500 text-xs uppercase tracking-wider flex items-center">
                    <span class="w-5 h-5 bg-amber-100 text-amber-600 rounded flex items-center justify-center mr-2">🔧</span>
                    Assistências
                </h3>
                <span class="text-xs font-bold text-amber-600 bg-white dark:bg-gray-800 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800"><?= count($assistencias) ?></span>
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-2 scrollbar-thin">
                <?php if(empty($assistencias)): ?>
                    <p class="text-center text-xs text-gray-500 italic mt-4">Nenhuma assistência registada.</p>
                <?php endif; ?>
                <?php foreach($assistencias as $ast): ?>
                    <div class="border border-gray-100 dark:border-gray-700 p-2.5 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex justify-between items-start mb-1">
                            <p class="text-[11px] font-black text-gray-800 dark:text-gray-200 uppercase">AST #<?= $ast['id'] ?> - <?= $ast['status'] ?></p>
                            <span class="text-[9px] font-bold uppercase <?= ($ast['tipo_cobranca'] === 'FATURADA') ? 'text-purple-500' : 'text-emerald-500' ?>"><?= $ast['tipo_cobranca'] ?: 'GARANTIA' ?></span>
                        </div>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 truncate" title="<?= htmlspecialchars($ast['obs_assistencia']) ?>">
                            <?= htmlspecialchars($ast['obs_assistencia']) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
    </div>

    <div class="lg:col-span-1 bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex flex-col h-[738px]">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            <h3 class="font-bold text-gray-700 dark:text-gray-200 text-sm uppercase tracking-wider mb-3">Histórico e Interações</h3>
            
            <form id="formInteracao" onsubmit="salvarInteracao(event)" class="bg-white dark:bg-gray-700 p-3 rounded border border-gray-200 dark:border-gray-600 shadow-sm">
                <input type="hidden" id="int_cliente_id" value="<?= $cliente_id ?>">
                <div class="flex gap-2 mb-2">
                    <select id="int_tipo" class="w-full px-2 py-1.5 text-xs bg-gray-50 dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded font-bold uppercase focus:ring-1 focus:ring-blue-500 text-gray-700 dark:text-gray-200">
                        <option value="WHATSAPP">📱 WhatsApp</option>
                        <option value="LIGACAO">📞 Ligação</option>
                        <option value="REUNIAO">🤝 Reunião</option>
                        <option value="ORCAMENTO">💰 Orçamento</option>
                        <option value="ANOTAÇÃO">📌 Anotação Interna</option>
                    </select>
                </div>
                <textarea id="int_obs" required rows="2" placeholder="O que foi conversado? Registe aqui..." class="w-full px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-500 bg-gray-50 dark:bg-gray-600 dark:text-white rounded focus:ring-1 focus:ring-blue-500 mb-2"></textarea>
                <div class="text-right">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-[11px] font-bold transition-colors">Salvar Registo</button>
                </div>
            </form>
        </div>

        <div class="flex-1 overflow-y-auto p-4 scrollbar-thin">
            <?php if(empty($interacoes)): ?>
                <div class="text-center py-10 opacity-50">
                    <div class="text-4xl mb-2">📜</div>
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Nenhum histórico registado.</p>
                </div>
            <?php else: ?>
                <div class="relative border-l-2 border-blue-100 dark:border-blue-900/50 ml-3 space-y-6 pb-4">
                    <?php foreach($interacoes as $int): 
                        $bg_icon = 'bg-blue-500'; $icon = '📌';
                        if($int['tipo_interacao'] == 'WHATSAPP') { $bg_icon = 'bg-green-500'; $icon = '💬'; }
                        if($int['tipo_interacao'] == 'LIGACAO') { $bg_icon = 'bg-indigo-500'; $icon = '📞'; }
                        if($int['tipo_interacao'] == 'REUNIAO') { $bg_icon = 'bg-purple-500'; $icon = '🤝'; }
                        if($int['tipo_interacao'] == 'ORCAMENTO') { $bg_icon = 'bg-yellow-500'; $icon = '💰'; }
                    ?>
                        <div class="relative pl-6">
                            <div class="absolute -left-[17px] top-1 w-8 h-8 rounded-full <?= $bg_icon ?> flex items-center justify-center text-white text-xs shadow-sm ring-4 ring-white dark:ring-[#222736]">
                                <?= $icon ?>
                            </div>
                            
                            <div class="bg-gray-50 dark:bg-gray-800/80 p-3 rounded-lg border border-gray-100 dark:border-gray-700 shadow-sm">
                                <div class="flex justify-between items-start mb-1.5">
                                    <span class="text-[10px] font-black uppercase text-gray-500 dark:text-gray-400"><?= htmlspecialchars($int['tipo_interacao']) ?></span>
                                    <span class="text-[9px] font-semibold text-gray-400 bg-white dark:bg-gray-700 px-1.5 py-0.5 rounded"><?= date('d/m/y H:i', strtotime($int['data_registro'])) ?></span>
                                </div>
                                <p class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed"><?= nl2br(htmlspecialchars($int['observacao'])) ?></p>
                                <p class="text-[9px] text-gray-400 mt-2 font-semibold">Por: <?= htmlspecialchars($int['usuario_nome']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
    async function salvarInteracao(event) {
        event.preventDefault();
        
        const payload = {
            cliente_id: document.getElementById('int_cliente_id').value,
            tipo: document.getElementById('int_tipo').value,
            observacao: document.getElementById('int_obs').value
        };

        try {
            const response = await fetch('api/add_interacao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();
            
            if (result.success) {
                window.location.reload();
            } else {
                alert("Erro: " + result.error);
            }
        } catch (error) {
            alert("Erro de comunicação com o servidor.");
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>