<?php
// configuracoes.php
require_once 'includes/auth.php';
protegerPagina();

require_once 'config/conexao.php';

// PHP 8: Validação direta da role na Sessão
if (($_SESSION['usuario_role'] ?? '') !== 'ADMIN') {
    header("Location: index.php?erro=acesso_negado");
    exit;
}

try {
    // 1. Empresa
    $stmtEmp = $pdo->query("SELECT * FROM configuracoes_empresa WHERE id = 1 LIMIT 1");
    $empresa = $stmtEmp->fetch(PDO::FETCH_ASSOC);
    if (!$empresa) {
        $empresa = [
            'nome_fantasia' => '', 'razao_social' => '', 'cnpj' => '', 
            'telefone' => '', 'email' => '', 'endereco' => '', 'logo_path' => ''
        ];
    }

    // 2. Total Usuários (Cast de int no PHP 8 para blindagem de tipo)
    $stmtUserCount = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $total_usuarios = (int) $stmtUserCount->fetchColumn();

    // 3. Cadastros Base
    $stmtCad = $pdo->query("SELECT * FROM cadastros_base ORDER BY tipo ASC, nome ASC");
    $todos_cadastros = $stmtCad->fetchAll(PDO::FETCH_ASSOC);

    // Expandido para comportar as novas amarrações do sistema
    $listas = [
        'CATEGORIA_ALMOX' => [],
        'PLANO_CONTA' => [],
        'FORMA_PAGAMENTO' => [],
        'SETOR' => [],
        'PROJETISTA' => [],
        'EQUIPE_MONTAGEM' => [],
        'UNIDADE_MEDIDA' => [],
        'ORIGEM_LEAD' => []
    ];

    foreach($todos_cadastros as $cad) {
        $tipo = $cad['tipo'] ?? '';
        if(isset($listas[$tipo])) {
            $listas[$tipo][] = $cad;
        }
    }

} catch (\PDOException $e) {
    die("Erro ao carregar configurações: " . $e->getMessage());
}

$page_title = 'CONFIGURAÇÕES DO SISTEMA';
$page_subtitle = 'Gestão da Empresa, Usuários e Parâmetros';
$main_class = 'flex-1 max-w-7xl mx-auto w-full'; 
$menu_button_text = 'MENU';

require_once 'includes/header.php';
?>

<div class="flex flex-col gap-6">
    <div class="border-b border-gray-200 dark:border-[#2a3142]">
        <ul class="flex flex-nowrap overflow-x-auto text-sm font-medium text-center" role="tablist">
            <li class="mr-2" role="presentation">
                <button id="btn_empresa" onclick="mudarAba('empresa')" class="tab-btn active px-4 py-3 border-b-2 text-blue-600 border-blue-600 dark:text-blue-400 dark:border-blue-400 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                    Dados da Empresa
                </button>
            </li>
            <li class="mr-2" role="presentation">
                <button id="btn_usuarios" onclick="mudarAba('usuarios')" class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                    Gestão de Usuários
                </button>
            </li>
            <li role="presentation">
                <button id="btn_cadastros" onclick="mudarAba('cadastros')" class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 uppercase font-black tracking-wider transition-colors whitespace-nowrap">
                    Cadastros Base
                </button>
            </li>
        </ul>
    </div>

    <!-- ABA 1: EMPRESA -->
    <div id="conteudo_empresa" class="tab-content">
        <form id="formEmpresa" onsubmit="salvarEmpresa(event)" enctype="multipart/form-data" class="bg-white dark:bg-[#222736] p-6 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142]">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="md:col-span-1 flex flex-col items-center justify-start border-r border-transparent md:border-gray-200 md:dark:border-gray-700 pr-0 md:pr-6">
                    <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">Logo Oficial (Documentos e OS)</h3>
                    
                    <div class="w-48 h-48 bg-gray-50 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center overflow-hidden mb-4 relative group">
                        <?php if(!empty($empresa['logo_path'])): ?>
                            <img src="<?= htmlspecialchars($empresa['logo_path']) ?>?v=<?= time() ?>" alt="Logo" class="max-w-full max-h-full object-contain p-2" id="preview_logo">
                        <?php else: ?>
                            <img src="" alt="Sem logo" class="hidden max-w-full max-h-full object-contain p-2" id="preview_logo">
                            <span class="text-4xl text-gray-300 dark:text-gray-600" id="icone_sem_logo">🏢</span>
                        <?php endif; ?>
                    </div>
                    
                    <input type="file" id="empresa_logo" name="logo" accept="image/*" onchange="previewImagem(this)" class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-400 cursor-pointer">
                    <p class="text-[10px] text-gray-400 mt-2 text-center">Formatos aceitos: JPG, PNG. Tamanho ideal: 500x500px.</p>
                </div>

                <div class="md:col-span-2 space-y-4">
                    <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">Informações de Contato e Faturamento</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nome Fantasia</label>
                            <input type="text" name="nome_fantasia" value="<?= htmlspecialchars($empresa['nome_fantasia'] ?? '') ?>" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase font-bold text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Razão Social</label>
                            <input type="text" name="razao_social" value="<?= htmlspecialchars($empresa['razao_social'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">CNPJ</label>
                            <input type="text" name="cnpj" value="<?= htmlspecialchars($empresa['cnpj'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Telefone Principal (WhatsApp)</label>
                            <input type="text" name="telefone" value="<?= htmlspecialchars($empresa['telefone'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">E-mail Oficial</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($empresa['email'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Endereço Completo</label>
                            <textarea name="endereco" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 text-sm"><?= htmlspecialchars($empresa['endereco'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 mt-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" class="bg-[#1e3a8a] dark:bg-blue-600 hover:bg-blue-800 dark:hover:bg-blue-700 text-white px-6 py-2 rounded text-sm font-bold shadow-sm transition-colors flex items-center" id="btn_salvar_empresa">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            SALVAR DADOS DA EMPRESA
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ABA 2: USUÁRIOS -->
    <div id="conteudo_usuarios" class="tab-content hidden">
        <div class="bg-white dark:bg-[#222736] p-10 rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] text-center max-w-3xl mx-auto mt-6">
            <div class="w-20 h-20 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            </div>
            <h2 class="text-2xl font-black text-gray-800 dark:text-gray-100 mb-2">Painel de Acessos e Permissões</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                A gestão de usuários, senhas, setores e permissões de módulos possui um ecossistema próprio e seguro. Atualmente, o sistema conta com <strong class="text-blue-600 dark:text-blue-400"><?= $total_usuarios ?> usuários</strong> registrados.
            </p>
            <a href="usuarios.php" class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg text-sm font-bold shadow-md transition-transform hover:-translate-y-0.5">
                ABRIR GESTOR DE USUÁRIOS
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>
    </div>

    <!-- ABA 3: CADASTROS BASE (Expandida) -->
    <div id="conteudo_cadastros" class="tab-content hidden">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            
            <!-- ALMOXARIFADO: Categorias -->
            <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex flex-col h-[350px]">
                <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-orange-50 dark:bg-orange-900/10 flex justify-between items-center">
                    <h3 class="font-bold text-orange-700 dark:text-orange-500 text-[11px] uppercase tracking-wider flex items-center">
                        <span class="w-6 h-6 bg-orange-100 text-orange-600 rounded flex items-center justify-center mr-2">📦</span>
                        Categorias de Estoque
                    </h3>
                    <button onclick="abrirModalCadastro('CATEGORIA_ALMOX', 'Categoria de Estoque')" class="bg-orange-500 hover:bg-orange-600 text-white px-2 py-1 rounded text-[10px] font-bold shadow-sm transition-colors uppercase">
                        + Adicionar
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-2 scrollbar-thin">
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        <?php foreach($listas['CATEGORIA_ALMOX'] as $item): ?>
                            <li class="py-2 px-2 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded group">
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase"><?= htmlspecialchars($item['nome'] ?? '') ?></span>
                                <button onclick="deletarCadastro(<?= $item['id'] ?>)" class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity" title="Apagar">&times;</button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- ALMOXARIFADO: Unidades de Medida -->
            <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex flex-col h-[350px]">
                <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-teal-50 dark:bg-teal-900/10 flex justify-between items-center">
                    <h3 class="font-bold text-teal-700 dark:text-teal-500 text-[11px] uppercase tracking-wider flex items-center">
                        <span class="w-6 h-6 bg-teal-100 text-teal-600 rounded flex items-center justify-center mr-2">📏</span>
                        Unidades de Medida
                    </h3>
                    <button onclick="abrirModalCadastro('UNIDADE_MEDIDA', 'Unidade de Medida')" class="bg-teal-500 hover:bg-teal-600 text-white px-2 py-1 rounded text-[10px] font-bold shadow-sm transition-colors uppercase">
                        + Adicionar
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-2 scrollbar-thin">
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        <?php foreach($listas['UNIDADE_MEDIDA'] as $item): ?>
                            <li class="py-2 px-2 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded group">
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase"><?= htmlspecialchars($item['nome'] ?? '') ?></span>
                                <button onclick="deletarCadastro(<?= $item['id'] ?>)" class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity" title="Apagar">&times;</button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- FINANCEIRO: Planos de Conta -->
            <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex flex-col h-[350px]">
                <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-emerald-50 dark:bg-emerald-900/10 flex justify-between items-center">
                    <h3 class="font-bold text-emerald-700 dark:text-emerald-500 text-[11px] uppercase tracking-wider flex items-center">
                        <span class="w-6 h-6 bg-emerald-100 text-emerald-600 rounded flex items-center justify-center mr-2">📊</span>
                        Planos de Conta (Fin)
                    </h3>
                    <button onclick="abrirModalCadastro('PLANO_CONTA', 'Plano de Conta')" class="bg-emerald-500 hover:bg-emerald-600 text-white px-2 py-1 rounded text-[10px] font-bold shadow-sm transition-colors uppercase">
                        + Adicionar
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-2 scrollbar-thin">
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        <?php foreach($listas['PLANO_CONTA'] as $item): ?>
                            <li class="py-2 px-2 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded group">
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase"><?= htmlspecialchars($item['nome'] ?? '') ?></span>
                                <button onclick="deletarCadastro(<?= $item['id'] ?>)" class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity" title="Apagar">&times;</button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- FINANCEIRO: Formas de Pagamento -->
            <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex flex-col h-[350px]">
                <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-purple-50 dark:bg-purple-900/10 flex justify-between items-center">
                    <h3 class="font-bold text-purple-700 dark:text-purple-500 text-[11px] uppercase tracking-wider flex items-center">
                        <span class="w-6 h-6 bg-purple-100 text-purple-600 rounded flex items-center justify-center mr-2">💳</span>
                        Formas de Pagamento
                    </h3>
                    <button onclick="abrirModalCadastro('FORMA_PAGAMENTO', 'Forma de Pagamento')" class="bg-purple-500 hover:bg-purple-600 text-white px-2 py-1 rounded text-[10px] font-bold shadow-sm transition-colors uppercase">
                        + Adicionar
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-2 scrollbar-thin">
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        <?php foreach($listas['FORMA_PAGAMENTO'] as $item): ?>
                            <li class="py-2 px-2 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded group">
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase"><?= htmlspecialchars($item['nome'] ?? '') ?></span>
                                <button onclick="deletarCadastro(<?= $item['id'] ?>)" class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity" title="Apagar">&times;</button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- USUÁRIOS E RECADOS: Setores -->
            <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex flex-col h-[350px]">
                <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-blue-50 dark:bg-blue-900/10 flex justify-between items-center">
                    <h3 class="font-bold text-blue-700 dark:text-blue-500 text-[11px] uppercase tracking-wider flex items-center">
                        <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded flex items-center justify-center mr-2">🏢</span>
                        Setores / Departamentos
                    </h3>
                    <button onclick="abrirModalCadastro('SETOR', 'Setor')" class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-[10px] font-bold shadow-sm transition-colors uppercase">
                        + Adicionar
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-2 scrollbar-thin">
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        <?php foreach($listas['SETOR'] as $item): ?>
                            <li class="py-2 px-2 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded group">
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase"><?= htmlspecialchars($item['nome'] ?? '') ?></span>
                                <button onclick="deletarCadastro(<?= $item['id'] ?>)" class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity" title="Apagar">&times;</button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- COMERCIAL: Projetistas -->
            <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex flex-col h-[350px]">
                <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-indigo-50 dark:bg-indigo-900/10 flex justify-between items-center">
                    <h3 class="font-bold text-indigo-700 dark:text-indigo-500 text-[11px] uppercase tracking-wider flex items-center">
                        <span class="w-6 h-6 bg-indigo-100 text-indigo-600 rounded flex items-center justify-center mr-2">✏️</span>
                        Projetistas (Comercial)
                    </h3>
                    <button onclick="abrirModalCadastro('PROJETISTA', 'Projetista')" class="bg-indigo-500 hover:bg-indigo-600 text-white px-2 py-1 rounded text-[10px] font-bold shadow-sm transition-colors uppercase">
                        + Adicionar
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-2 scrollbar-thin">
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        <?php foreach($listas['PROJETISTA'] as $item): ?>
                            <li class="py-2 px-2 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded group">
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase"><?= htmlspecialchars($item['nome'] ?? '') ?></span>
                                <button onclick="deletarCadastro(<?= $item['id'] ?>)" class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity" title="Apagar">&times;</button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- COMERCIAL: Origem do Lead -->
            <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex flex-col h-[350px]">
                <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-pink-50 dark:bg-pink-900/10 flex justify-between items-center">
                    <h3 class="font-bold text-pink-700 dark:text-pink-500 text-[11px] uppercase tracking-wider flex items-center">
                        <span class="w-6 h-6 bg-pink-100 text-pink-600 rounded flex items-center justify-center mr-2">🎯</span>
                        Origem do Lead
                    </h3>
                    <button onclick="abrirModalCadastro('ORIGEM_LEAD', 'Origem do Lead')" class="bg-pink-500 hover:bg-pink-600 text-white px-2 py-1 rounded text-[10px] font-bold shadow-sm transition-colors uppercase">
                        + Adicionar
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-2 scrollbar-thin">
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        <?php foreach($listas['ORIGEM_LEAD'] as $item): ?>
                            <li class="py-2 px-2 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded group">
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase"><?= htmlspecialchars($item['nome'] ?? '') ?></span>
                                <button onclick="deletarCadastro(<?= $item['id'] ?>)" class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity" title="Apagar">&times;</button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- PCP / ASSISTÊNCIAS: Equipes de Montagem -->
            <div class="bg-white dark:bg-[#222736] rounded-lg shadow-sm border border-gray-200 dark:border-[#2a3142] flex flex-col h-[350px]">
                <div class="p-3 border-b border-gray-200 dark:border-gray-700 bg-amber-50 dark:bg-amber-900/10 flex justify-between items-center">
                    <h3 class="font-bold text-amber-700 dark:text-amber-500 text-[11px] uppercase tracking-wider flex items-center">
                        <span class="w-6 h-6 bg-amber-100 text-amber-600 rounded flex items-center justify-center mr-2">🧰</span>
                        Equipes de Montagem
                    </h3>
                    <button onclick="abrirModalCadastro('EQUIPE_MONTAGEM', 'Equipe de Montagem')" class="bg-amber-500 hover:bg-amber-600 text-white px-2 py-1 rounded text-[10px] font-bold shadow-sm transition-colors uppercase">
                        + Adicionar
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-2 scrollbar-thin">
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        <?php foreach($listas['EQUIPE_MONTAGEM'] as $item): ?>
                            <li class="py-2 px-2 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded group">
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase"><?= htmlspecialchars($item['nome'] ?? '') ?></span>
                                <button onclick="deletarCadastro(<?= $item['id'] ?>)" class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity" title="Apagar">&times;</button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Padrão de Adição -->
<div id="modalCadastroBase" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm p-6 border border-gray-200 dark:border-gray-700 transform scale-95 transition-all duration-300" id="modalCadastroBaseConteudo">
        <div class="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
            <h3 id="modalCadTitulo" class="text-lg font-bold text-gray-800 dark:text-gray-100">Adicionar Parâmetro</h3>
            <button type="button" onclick="fecharModalCadastro()" class="text-gray-400 hover:text-gray-800 dark:hover:text-white text-2xl font-bold">&times;</button>
        </div>
        
        <form id="formCadastroBase" onsubmit="salvarCadastroBase(event)">
            <input type="hidden" id="cad_tipo" name="tipo">
            <div class="mb-6">
                <label id="lbl_cad_nome" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1 uppercase">Nome da Opção</label>
                <input type="text" id="cad_nome" name="nome" required placeholder="Digite aqui..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-2 focus:ring-blue-500 uppercase font-bold text-sm">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="fecharModalCadastro()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition font-medium text-sm">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-[#1e3a8a] dark:bg-blue-600 hover:bg-blue-700 text-white rounded font-bold transition shadow-sm text-sm">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/configuracoes.js?v=<?= time() ?>"></script>
<?php require_once 'includes/footer.php'; ?>