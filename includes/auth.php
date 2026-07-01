<?php
// includes/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protege páginas HTML tradicionais (redireciona)
function protegerPagina() {
    if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
        header("Location: login.php");
        exit;
    }
}

// Protege endpoints de API que respondem JSON (retorna HTTP 401)
function protegerAPI() {
    if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Acesso negado. Faça login.']);
        exit;
    }
}
?>