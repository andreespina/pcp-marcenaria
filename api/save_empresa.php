<?php
// api/save_empresa.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');

if ($_SESSION['usuario_role'] !== 'ADMIN') {
    echo json_encode(['success' => false, 'error' => 'Permissão negada.']);
    exit;
}

try {
    // CORREÇÃO PHP DE COMPATIBILIDADE (Utilizando isset() tradicional)
    $nome_fantasia = isset($_POST['nome_fantasia']) ? $_POST['nome_fantasia'] : '';
    $razao_social  = isset($_POST['razao_social']) ? $_POST['razao_social'] : '';
    $cnpj          = isset($_POST['cnpj']) ? $_POST['cnpj'] : '';
    $telefone      = isset($_POST['telefone']) ? $_POST['telefone'] : '';
    $email         = isset($_POST['email']) ? $_POST['email'] : '';
    $endereco      = isset($_POST['endereco']) ? $_POST['endereco'] : '';

    // Lida com o Upload da Logo
    $logo_path = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/logos/';
        if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }
        
        // Usa timestamp para evitar cache no navegador quando mudar de logo
        $fileName = 'logo_oficial_' . time() . '.' . pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        
        if (@move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $fileName)) {
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

} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>