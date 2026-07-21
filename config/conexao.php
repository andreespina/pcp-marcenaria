<?php
// config/conexao.php
$host = 'mysql.aespina.com';
$db   = 'aespina06';
$user = 'aespina06';
$pass = 'Sg1979AE';

// PHP 8+: Configurações otimizadas do PDO passadas diretamente na instância
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Padrão no PHP 8.0+, mas mantido explícito por segurança
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Define que todas as buscas tragam arrays associativos por padrão
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Força o retorno de tipos reais (int/float) em vez de strings
];

try {
    // Alterado para utf8mb4 (Padrão mais robusto para acentuação e caracteres modernos)
    $pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, $options);
} catch (\PDOException $e) {
    // Para ambientes de produção muito rigorosos o ideal é não expor o $e->getMessage() na tela,
    // mas deixei mantido para facilitar o seu debug interno caso haja instabilidade no servidor.
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}
?>