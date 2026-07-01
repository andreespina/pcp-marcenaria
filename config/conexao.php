<?php
// config/conexao.php
$host = 'mysql.aespina.com';
$db   = 'aespina04';
$user = 'aespina04';
$pass = 'Sg1979AE';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}
?>