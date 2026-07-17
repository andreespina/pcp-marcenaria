<?php
// login.php
require_once 'includes/auth.php';
require_once 'config/conexao.php';

if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    header("Location: index.php");
    exit;
}

$erro = '';
$sucesso = '';

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt->fetchColumn() == 0) {
        $senha_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->query("INSERT INTO usuarios (usuario, senha) VALUES ('admin', '$senha_hash')");
        $sucesso = "Usuário inicial criado! Login: <b>admin</b> | Senha: <b>admin123</b>";
    }
} catch (\PDOException $e) {
    $erro = "Erro: Tabela 'usuarios' não existe no banco de dados.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $senha   = isset($_POST['senha']) ? $_POST['senha'] : '';

   if (!empty($usuario) && !empty($senha)) {
    // Adicionamos 'role' e 'permissoes' no SELECT
    $stmt = $pdo->prepare("SELECT id, usuario, senha, role, permissoes FROM usuarios WHERE usuario = :usuario");
    $stmt->execute(['usuario' => $usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($senha, $user['senha'])) {
            $_SESSION['logado'] = true;
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nome'] = $user['usuario'];
            
            // Salvamos as permissões na sessão usando a sintaxe tradicional compatível
            $_SESSION['usuario_role'] = !empty($user['role']) ? $user['role'] : 'USER';
            $_SESSION['usuario_permissoes'] = !empty($user['permissoes']) ? json_decode($user['permissoes'], true) : [];
                         
            header("Location: index.php");
            exit;
        } else {
            $erro = "Usuário ou senha incorretos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Acesso - MoodLAR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class', }</script>
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else { document.documentElement.classList.remove('dark') }
    </script>
</head>
<body class="relative min-h-screen flex items-center justify-center font-sans">
    
    <!-- Fundo Principal -->
    <div class="absolute inset-0 bg-cover bg-center z-0" style="background-image: url('assets/images/bg.jpeg');"></div>
    <div class="absolute inset-0 bg-white/30 dark:bg-gray-900/80 backdrop-blur-md z-0 transition-colors duration-500"></div>

    <!-- Cartão de Login Premium -->
    <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-lg p-8 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.15)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.5)] w-full max-w-sm border border-white/40 dark:border-gray-700/50 relative z-10 transition-all duration-500">
        
        <button id="theme-toggle" type="button" class="absolute top-5 left-5 text-gray-400 hover:text-[#1e3a8a] dark:hover:text-blue-400 transition-colors duration-300" title="Alternar Modo Escuro">
            <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
            <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path></svg>
        </button>

        <!-- Bloco da Logo 100% Centralizada -->
        <div class="mb-8 mt-4 w-full group text-center">
            <img src="assets/images/logo-moodlar.png" alt="Logo MoodLAR" 
                 class="align-middle h-28 w-auto object-contain block mx-auto z-10 transition-transform duration-500 group-hover:scale-105 
                        drop-shadow-md 
                        dark:[filter:drop-shadow(1px_0_0_#ffffff)_drop-shadow(-1px_0_0_#ffffff)_drop-shadow(0_1px_0_#ffffff)_drop-shadow(0_-1px_0_#ffffff)]">
        </div>

        <?php if ($erro): ?>
            <div class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-400 p-3 mb-4 text-sm font-medium rounded-r">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500 text-green-700 dark:text-green-400 p-3 mb-4 text-sm font-medium rounded-r">
                <?= $sucesso ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5 tracking-wide">Usuário</label>
                <input type="text" name="usuario" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-gray-50/50 dark:bg-gray-700/50 dark:text-white transition-all duration-300 outline-none" required>
            </div>
            
            <div class="mb-8">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5 tracking-wide">Senha</label>
                <input type="password" name="senha" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 bg-gray-50/50 dark:bg-gray-700/50 dark:text-white transition-all duration-300 outline-none" required>
            </div>
            
            <button type="submit" class="w-full bg-gradient-to-r from-[#1e3a8a] to-blue-600 hover:from-blue-800 hover:to-blue-500 text-white font-black py-3 rounded-lg transition-all duration-300 shadow-lg hover:shadow-blue-500/30 tracking-widest uppercase text-sm">
                Acessar Painel
            </button>
        </form>
    </div>
    
<script src="assets/js/main.js"></script>
</body>
</html>