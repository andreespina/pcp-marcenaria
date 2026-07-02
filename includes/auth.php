<?php
// includes/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se está logado e se tem permissão para a página atual
function protegerPagina($modulo_requerido = null) {
    if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
        header("Location: login.php");
        exit;
    }

    // Se a página exige um módulo específico e o usuário não é ADMIN
    if ($modulo_requerido && $_SESSION['usuario_role'] !== 'ADMIN') {
        $permissoes = isset($_SESSION['usuario_permissoes']) ? $_SESSION['usuario_permissoes'] : [];
        if (!in_array($modulo_requerido, $permissoes)) {
            // Se não tiver acesso, redireciona para um aviso ou tela inicial
            die("<h1>Acesso Negado</h1><p>Você não tem permissão para acessar o módulo: $modulo_requerido.</p><a href='index.php'>Voltar ao Início</a>");
        }
    }
}

// Protege endpoints de API
function protegerAPI($modulo_requerido = null) {
    if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Acesso negado. Faça login.']);
        exit;
    }

    if ($modulo_requerido && $_SESSION['usuario_role'] !== 'ADMIN') {
        $permissoes = isset($_SESSION['usuario_permissoes']) ? $_SESSION['usuario_permissoes'] : [];
        if (!in_array($modulo_requerido, $permissoes)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Acesso negado. Você não tem permissão para esta ação.']);
            exit;
        }
    }
}
?>