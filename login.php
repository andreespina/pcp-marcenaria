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
        $stmt = $pdo->prepare("SELECT id, usuario, senha FROM usuarios WHERE usuario = :usuario");
        $stmt->execute(['usuario' => $usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($senha, $user['senha'])) {
            $_SESSION['logado'] = true;
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nome'] = $user['usuario'];
            
            header("Location: index.php");
            exit;
        } else {
            $erro = "Usuário ou senha incorretos.";
        }
    } else {
        $erro = "Preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Acesso - PCP Marcenaria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class', }</script>
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else { document.documentElement.classList.remove('dark') }
    </script>
</head>
<body class="relative min-h-screen flex items-center justify-center font-sans">
    
    <div class="absolute inset-0 bg-cover bg-center z-0" style="background-image: url('assets/images/bg.jpeg');"></div>
    <div class="absolute inset-0 bg-white/40 dark:bg-gray-900/80 backdrop-blur-[2px] z-0 transition-colors duration-300"></div>

    <div class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm p-8 rounded-lg shadow-2xl w-full max-w-sm border border-gray-200 dark:border-gray-700 relative z-10 transition-all duration-300">
        
        <button id="theme-toggle" type="button" class="absolute top-4 left-4 text-gray-400 hover:text-[#1e3a8a] dark:hover:text-blue-400 transition-colors" title="Alternar Modo Escuro">
            <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
            <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path></svg>
        </button>

        <div class="text-center mb-6 mt-4">
            <h1 class="text-2xl font-extrabold text-[#1e3a8a] dark:text-blue-400">PCP Marcenaria</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Acesso restrito à produção</p>
        </div>

        <?php if ($erro): ?>
            <div class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-400 p-3 mb-4 text-sm font-medium">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500 text-green-700 dark:text-green-400 p-3 mb-4 text-sm font-medium">
                <?= $sucesso ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Usuário</label>
                <input type="text" name="usuario" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 dark:text-white transition-colors" required>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Senha</label>
                <input type="password" name="senha" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 dark:text-white transition-colors" required>
            </div>
            
            <button type="submit" class="w-full bg-[#1e3a8a] dark:bg-blue-600 hover:bg-blue-800 dark:hover:bg-blue-500 text-white font-bold py-2.5 rounded transition shadow-sm">
                Entrar no Painel
            </button>
        </form>
    </div>

    <script>
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            themeToggleLightIcon.classList.remove('hidden');
        } else { themeToggleDarkIcon.classList.remove('hidden'); }

        themeToggleBtn.addEventListener('click', function() {
            themeToggleDarkIcon.classList.toggle('hidden');
            themeToggleLightIcon.classList.toggle('hidden');
            if (localStorage.getItem('color-theme')) {
                if (localStorage.getItem('color-theme') === 'light') {
                    document.documentElement.classList.add('dark'); localStorage.setItem('color-theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark'); localStorage.setItem('color-theme', 'light');
                }
            } else {
                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark'); localStorage.setItem('color-theme', 'light');
                } else {
                    document.documentElement.classList.add('dark'); localStorage.setItem('color-theme', 'dark');
                }
            }
        });
    </script>
</body>
</html>