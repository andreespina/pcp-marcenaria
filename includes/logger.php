<?php
// includes/logger.php

function registrarLog($pdo, $acao, $tabela, $registro_id, $detalhes, $dados_antigos = null, $dados_novos = null) {
    // Garante que a sessão existe para pegar quem fez a ação
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    
    $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;
    $usuario_nome = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'Sistema';

    try {
        $stmt = $pdo->prepare("INSERT INTO logs_auditoria 
            (usuario_id, usuario_nome, acao, tabela_afetada, registro_id, detalhes, dados_antigos, dados_novos) 
            VALUES (:uid, :unome, :acao, :tabela, :reg_id, :detalhes, :antigos, :novos)");
            
        $stmt->execute([
            'uid' => $usuario_id,
            'unome' => $usuario_nome,
            'acao' => $acao,
            'tabela' => $tabela,
            'reg_id' => $registro_id,
            'detalhes' => $detalhes,
            'antigos' => $dados_antigos ? json_encode($dados_antigos, JSON_UNESCAPED_UNICODE) : null,
            'novos' => $dados_novos ? json_encode($dados_novos, JSON_UNESCAPED_UNICODE) : null
        ]);
    } catch (\PDOException $e) {
        // Falha silenciosa para não travar o sistema caso o log falhe
        error_log("Erro ao salvar log: " . $e->getMessage());
    }
}
?>