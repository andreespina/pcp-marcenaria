<?php
// relatorios.php
require_once 'includes/auth.php';
protegerPagina();

require_once 'config/conexao.php';

try {
    $stmt = $pdo->query("SELECT * FROM projetos_pcp ORDER BY data_limite ASC, id DESC");
    $projetos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Variáveis para os indicadores numéricos
    $total_projetos = count($projetos);
    $atrasados = 0;
    $concluidos = 0;
    $em_andamento = 0;

    foreach ($projetos as $p) {
        if ($p['status'] === 'atrasou') {
            $atrasados++;
        } elseif ($p['status'] === 'assistencia' && isset($p['resolvido_assistencia']) && $p['resolvido_assistencia'] === 'SIM') {
            $concluidos++;
        } else {
            $em_andamento++;
        }
    }
} catch (\PDOException $e) {
    die("Erro na consulta do banco de dados: " . $e->getMessage());
}

function formatarData($data) {
    if (!$data) return '-';
    return date('d/m/Y', strtotime($data));
}

// Função auxiliar para exibir o nome da coluna de forma legível
function nomeStatus($status) {
    $nomes = [
        'instalacao'      => 'Em Instalação',
        'expedicao'       => 'Em Expedição',
        'producao'        => 'Em Produção',
        'desenvolvimento' => 'Desenv. PCP',
        'atrasou'         => 'Obra Atrasou',
        'assistencia'     => 'Ass. Técnica'
    ];
    return isset($nomes[$status]) ? $nomes[$status] : strtoupper($status);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - PCP Marcenaria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class', }</script>
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else { document.documentElement.classList.remove('dark') }
    </script>
</head>
<body class="bg-[#f4f7f6] dark:bg-gray-900 min-h-screen p-6 font-sans flex flex-col transition-colors duration-300">

    <main class="flex-1 max-w-7xl mx-auto w-full">
        <header class="flex justify-between items-center mb-6 border-b pb-4 border-gray-200 dark:border-gray-700">
            <div>
                <h1 class="text-3xl font-extrabold text-purple-700 dark:text-purple-400 tracking-tight">RELATÓRIOS GERAIS</h1>
                <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Visão Estratégica da Produção</span>
            </div>
            
            <div class="flex items-center space-x-4">
                <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none rounded-lg text-sm p-2 transition-colors">
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path></svg>
                </button>
                <a href="index.php" class="flex items-center bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white px-4 py-2 rounded text-sm font-bold shadow-sm transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    VOLTAR AO PAINEL
                </a>
            </div>
        </header>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total de Registros</p>
                <p class="text-3xl font-black text-gray-800 dark:text-white mt-1"><?= $total_projetos ?></p>
            </div>
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-sm font-bold text-blue-500 dark:text-blue-400 uppercase tracking-wide">Em Andamento</p>
                <p class="text-3xl font-black text-blue-600 dark:text-blue-400 mt-1"><?= $em_andamento ?></p>
            </div>
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-sm font-bold text-red-500 dark:text-red-400 uppercase tracking-wide">Obras Atrasadas</p>
                <p class="text-3xl font-black text-red-600 dark:text-red-400 mt-1"><?= $atrasados ?></p>
            </div>
            <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-sm font-bold text-green-500 dark:text-green-400 uppercase tracking-wide">Concluídos / Baixados</p>
                <p class="text-3xl font-black text-green-600 dark:text-green-400 mt-1"><?= $concluidos ?></p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-3 font-bold">ID</th>
                            <th class="px-6 py-3 font-bold">Cliente</th>
                            <th class="px-6 py-3 font-bold">Status Atual</th>
                            <th class="px-6 py-3 font-bold">Data Limite</th>
                            <th class="px-6 py-3 font-bold">Status Extra / Técnico</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($projetos)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400 italic">Nenhum projeto cadastrado no sistema.</td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php foreach ($projetos as $p): ?>
                            <?php 
                                // Regra visual para a linha se o projeto foi concluído (baixado na assistência)
                                $is_concluido = ($p['status'] === 'assistencia' && isset($p['resolvido_assistencia']) && $p['resolvido_assistencia'] === 'SIM');
                                $classe_linha = $is_concluido ? 'bg-green-50/50 dark:bg-green-900/10' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50';
                            ?>
                            <tr class="<?= $classe_linha ?> transition-colors text-gray-700 dark:text-gray-200">
                                <td class="px-6 py-4 font-bold text-gray-500 dark:text-gray-400">#<?= $p['id'] ?></td>
                                <td class="px-6 py-4 font-bold uppercase"><?= htmlspecialchars($p['cliente']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded text-xs font-bold 
                                        <?= $p['status'] === 'atrasou' ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400' : 
                                           ($is_concluido ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400' : 'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400') ?>">
                                        <?= $is_concluido ? 'CONCLUÍDO' : nomeStatus($p['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium"><?= formatarData($p['data_limite']) ?></td>
                                <td class="px-6 py-4 text-xs">
                                    <?php if ($is_concluido && !empty($p['tecnico_assistencia'])): ?>
                                        <span class="text-green-600 dark:text-green-400 font-semibold">Resolvido por: <?= htmlspecialchars($p['tecnico_assistencia']) ?></span>
                                    <?php elseif ($p['status'] === 'atrasou' && !empty($p['observacao'])): ?>
                                        <span class="text-red-500 dark:text-red-400 italic">Motivo: <?= htmlspecialchars($p['observacao']) ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-400 dark:text-gray-500">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="mt-12 pt-6 border-t border-gray-200 dark:border-gray-700 text-center text-xs font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase transition-colors duration-300">
        SISTEMA DE CONTROLE - PCP - AESPINA - 2026
    </footer>

   <script src="assets/js/main.js"></script>
</body>
</html>