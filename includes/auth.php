<?php
// includes/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se está logado e se tem permissão para a página atual (Tipagem PHP 8)
function protegerPagina(?string $modulo_requerido = null): void {
    if (($_SESSION['logado'] ?? false) !== true) {
        header("Location: login.php");
        exit;
    }

    // Se a página exige um módulo específico e o usuário não é ADMIN
    if ($modulo_requerido && ($_SESSION['usuario_role'] ?? '') !== 'ADMIN') {
        $permissoes = $_SESSION['usuario_permissoes'] ?? [];
        if (!in_array($modulo_requerido, $permissoes)) {
            // Se não tiver acesso, redireciona para um aviso ou tela inicial
            $modulo_seguro = htmlspecialchars($modulo_requerido, ENT_QUOTES, 'UTF-8');
            die("<h1>Acesso Negado</h1><p>Você não tem permissão para acessar o módulo: {$modulo_seguro}.</p><a href='index.php'>Voltar ao Início</a>");
        }
    }
}

// Protege endpoints de API (Tipagem PHP 8)
function protegerAPI(?string $modulo_requerido = null): void {
    if (($_SESSION['logado'] ?? false) !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Acesso negado. Faça login.']);
        exit;
    }

    if ($modulo_requerido && ($_SESSION['usuario_role'] ?? '') !== 'ADMIN') {
        $permissoes = $_SESSION['usuario_permissoes'] ?? [];
        if (!in_array($modulo_requerido, $permissoes)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Acesso negado. Você não tem permissão para esta ação.']);
            exit;
        }
    }
}
?>