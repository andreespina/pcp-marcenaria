<?php
require_once 'config/conexao.php';
echo "<h2 style='font-family: Arial;'>Iniciando a vinculação Relacional de IDs...</h2>";

try {
    // 1. ADMINISTRATIVO
    $stmt1 = $pdo->query("SELECT id, cliente_nome FROM administrativo_contratos WHERE cliente_id IS NULL");
    $admin = $stmt1->fetchAll();
    foreach($admin as $ad) {
        // Limpa a tag [CLI-08] para procurar o nome real
        $nome = trim(preg_replace('/^\[.*?\]\s*/', '', $ad['cliente_nome']));
        $q = $pdo->prepare("SELECT id FROM clientes_cadastro WHERE TRIM(nome_contrato) = ? LIMIT 1");
        $q->execute([$nome]);
        if($cli = $q->fetch()) {
            $pdo->prepare("UPDATE administrativo_contratos SET cliente_id = ? WHERE id = ?")->execute([$cli['id'], $ad['id']]);
            echo "✅ Contrato Adm #{$ad['id']} vinculado ao Cliente ID {$cli['id']}.<br>";
        }
    }

    // 2. ASSISTÊNCIAS
    $stmt2 = $pdo->query("SELECT id, cliente FROM assistencias_tecnicas WHERE cliente_id IS NULL");
    $ast = $stmt2->fetchAll();
    foreach($ast as $a) {
        $q = $pdo->prepare("SELECT id FROM clientes_cadastro WHERE TRIM(nome_contrato) = ? LIMIT 1");
        $q->execute([trim($a['cliente'])]);
        if($cli = $q->fetch()) {
            $pdo->prepare("UPDATE assistencias_tecnicas SET cliente_id = ? WHERE id = ?")->execute([$cli['id'], $a['id']]);
            echo "✅ Assistência #{$a['id']} vinculada ao Cliente ID {$cli['id']}.<br>";
        }
    }

    // 3. PROJETOS (PCP)
    $stmt3 = $pdo->query("SELECT id, cliente FROM projetos_pcp WHERE cliente_id IS NULL");
    $projs = $stmt3->fetchAll();
    foreach($projs as $p) {
        $q = $pdo->prepare("SELECT id FROM clientes_cadastro WHERE TRIM(nome_contrato) = ? LIMIT 1");
        $q->execute([trim($p['cliente'])]);
        if($cli = $q->fetch()) {
            $pdo->prepare("UPDATE projetos_pcp SET cliente_id = ? WHERE id = ?")->execute([$cli['id'], $p['id']]);
            echo "✅ Projeto PCP #{$p['id']} vinculado ao Cliente ID {$cli['id']}.<br>";
        }
    }

    echo "<h2 style='color:green; font-family: Arial;'>Processo finalizado com sucesso! O seu banco agora é Relacional. Pode apagar este arquivo do servidor.</h2>";

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>