<?php
// api/save_empresa.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');

if (($_SESSION['usuario_role'] ?? '') !== 'ADMIN') {
    echo json_encode(['success' => false, 'error' => 'Permissão negada.']);
    exit;
}

try {
    // PHP 8: Operador de Coalescência Nula (??) superando os antigos isset()
    $nome_fantasia = (string)($_POST['nome_fantasia'] ?? '');
    $razao_social  = (string)($_POST['razao_social'] ?? '');
    $cnpj          = (string)($_POST['cnpj'] ?? '');
    $telefone      = (string)($_POST['telefone'] ?? '');
    $email         = (string)($_POST['email'] ?? '');
    $endereco      = (string)($_POST['endereco'] ?? '');

    // Lida com o Upload da Logo
    $logo_path = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/logos/';
        if (!is_dir($uploadDir)) { 
            mkdir($uploadDir, 0777, true); 
        }
        
        // Usa timestamp para evitar cache no navegador quando mudar de logo
        $fileName = 'logo_oficial_' . time() . '.' . pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $fileName)) {
            $logo_path = 'uploads/logos/' . $fileName;
        }
    }

    $query = "UPDATE configuracoes_empresa SET 
                nome_fantasia = :nome_fantasia, 
                razao_social = :razao_social, 
                cnpj = :cnpj, 
                telefone = :telefone, 
                email = :email, 
                endereco = :endereco";

    $params = [
        'nome_fantasia' => strtoupper($nome_fantasia),
        'razao_social'  => strtoupper($razao_social),
        'cnpj'          => $cnpj,
        'telefone'      => $telefone,
        'email'         => strtolower($email),
        'endereco'      => $endereco
    ];

    if ($logo_path !== null) {
        $query .= ", logo_path = :logo_path";
        $params['logo_path'] = $logo_path;
    }

    $query .= " WHERE id = 1";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    echo json_encode(['success' => true]);

} catch (\Throwable $e) { 
    // PHP 8: Throwable captura Errors e Exceptions blindando a API
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>