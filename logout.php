<?php
// logout.php
// PHP 8: Inicia a sessão de forma segura e a destroi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpa todas as variáveis e encerra
$_SESSION = [];
session_destroy();

header("Location: login.php");
exit;
?>