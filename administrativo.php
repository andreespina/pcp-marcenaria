<?php
// administrativo.php
require_once 'includes/auth.php';
protegerPagina(); 
require_once 'config/conexao.php';

try {
    // Puxa todos os contratos gerados pelo Comercial (Venda Fechada)
    $stmt = $pdo->query("SELECT * FROM administrativo_contratos ORDER BY data_criacao DESC");
    $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cálculos para o Dashboard Financeiro
    $total_valor = 0; $pendentes = 0; $a_faturar = 0; $pagos = 0;

    foreach ($contratos as $c) {
        $total_valor += $c['valor'];
        if ($c['status_contrato'] === 'PENDENTE') $pendentes++;
        if ($c['status_financeiro'] === 'A FATURAR') $a_faturar++;
        if ($c['status_financeiro'] === 'PAGO') $pagos++;
    }
} catch (\PDOException $e) { 
    die("Erro ao carregar dados financeiros: " . $e->getMessage()); 
}

$page_title = 'ADMINISTRATIVO & FINANCEIRO';
$page_subtitle = 'SBG Móveis & Design';
$main_class = 'flex-1'; 
$menu_button_text = 'MENU';
$page_actions = '
<button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
    NOVO CONTRATO MANUAL
</button>';

$head_extras = '
<style>
    .dark body { background-color: #1a1e2b !important; }
    .table-container { height: calc(100vh - 280px); overflow-y: auto; }
    .table-container::-webkit-scrollbar { width: 6px; }
    .table-container::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
    .dark .table-container::-webkit-scrollbar-thumb { background-color: #4b5563; }
</style>';

require_once 'includes/header.php';
?>

<div class="flex flex-col gap-6">
    
    <div class="bg-white dark:bg-[#222736] rounded-lg border border-gray-200 dark:border-[#2a3142] shadow-sm p-4">
        <h2 class="text-blue-700 dark:text-blue-400 font-bold mb-4 flex items-center text-lg tracking-wide">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Resumo de Faturamento (Projetos Fechados)
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 dark:bg-transparent border border-gray-300 dark:border-gray-600 rounded p-4 shadow-sm">
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Volume Negociado</p>
                <p class="text-2xl font-black text-gray-800 dark:text-white">R$ <?= number_format($total_valor, 2, ',', '.') ?></p>
            </div>
            <div class="bg-blue-50 dark:bg-transparent border border-blue-300 dark:border-blue-800 rounded p-4 shadow-sm">
                <p class="text-[11px] font-bold text-blue-600 dark:text-blue-400 uppercase mb-1">Contratos Pendentes</p>
                <p class="text-2xl font-black text-blue-700 dark:text-blue-300"><?= $pendentes ?></p>
            </div>
            <div class="bg-yellow-50 dark:bg-transparent border border-yellow-300 dark:border-yellow-800 rounded p-4 shadow-sm">
                <p class="text-[11px] font-bold text-yellow-600 dark:text-yellow-400 uppercase mb-1">A Faturar / Cobrar</p>
                <p class="text-2xl font-black text-yellow-700 dark:text-yellow-300"><?= $a_faturar ?></p>
            </div>
            <div class="bg-emerald-50 dark:bg-transparent border border-emerald-300 dark:border-emerald-800 rounded p-4 shadow-sm">
                <p class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase mb-1">Projetos Pagos</p>
                <p class="text-2xl font-black text-emerald-700 dark:text-emerald-300"><?= $pagos ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-[#222736] rounded-lg border border-gray-200 dark:border-[#2a3142] shadow-sm overflow-hidden flex flex-col">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
            <h3 class="font-bold text-gray-700 dark:text-gray-200 text-sm uppercase tracking-wider">Gestão de Contratos e Recebimentos</h3>
        </div>
        
        <div class="overflow-x-auto table-container">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-10 font-bold">
                    <tr>
                        <th class="px-6 py-3">Data Venda</th>
                        <th class="px-6 py-3">Cliente / Código</th>
                        <th class="px-6 py-3">Valor Total</th>
                        <th class="px-6 py-3">Status do Contrato</th>
                        <th class="px-6 py-3">Status Financeiro</th>
                        <th class="px-6 py-3 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-700 dark:text-gray-200">
                    <?php if (empty($contratos)): ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">Nenhuma venda fechada registrada no administrativo.</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach ($contratos as $c): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-500 dark:text-gray-400"><?= date('d/m/Y', strtotime($c['data_criacao'])) ?></td>
                            <td class="px-6 py-4 font-bold uppercase text-gray-900 dark:text-white"><?= htmlspecialchars($c['cliente_nome']) ?></td>
                            <td class="px-6 py-4 font-black text-emerald-600 dark:text-emerald-400">R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
                            
                            <td class="px-6 py-4">
                                <?php if($c['status_contrato'] === 'ASSINADO'): ?>
                                    <span class="text-[10px] bg-green-100 text-green-800 px-2 py-1 rounded font-bold border border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800">ASSINADO</span>
                                <?php else: ?>
                                    <span class="text-[10px] bg-red-100 text-red-800 px-2 py-1 rounded font-bold border border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800 animate-pulse">PENDENTE</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="px-6 py-4">
                                <?php if($c['status_financeiro'] === 'PAGO'): ?>
                                    <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-1 rounded font-bold border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400">LIQUIDADO</span>
                                <?php elseif($c['status_financeiro'] === 'FATURADO'): ?>
                                    <span class="text-[10px] bg-blue-100 text-blue-800 px-2 py-1 rounded font-bold border border-blue-200 dark:bg-blue-900/30 dark:text-blue-400">FATURADO (COBRANDO)</span>
                                <?php else: ?>
                                    <span class="text-[10px] bg-yellow-100 text-yellow-800 px-2 py-1 rounded font-bold border border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400">A FATURAR</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="px-6 py-4 text-center space-x-3">
                                <button class="text-blue-600 hover:text-blue-800 dark:text-blue-400 font-bold transition-colors text-xs" title="Gerenciar Financeiro">
                                    GERENCIAR
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>