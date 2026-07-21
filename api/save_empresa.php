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
    // Coleta dos dados do formulário
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
        
        // Cria o diretório se não existir (O '@' oculta warnings de permissão)
        if (!is_dir($uploadDir)) { 
            @mkdir($uploadDir, 0777, true); 
        }
        
        // Usa timestamp para evitar cache no navegador
        $extensao = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $fileName = 'logo_oficial_' . time() . '.' . $extensao;
        
        // Tenta mover o arquivo e joga um erro claro se falhar por permissão de pasta
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $fileName)) {
            $logo_path = 'uploads/logos/' . $fileName;
        } else {
            throw new Exception("Falha ao salvar a imagem. Verifique se a pasta 'uploads/logos/' tem permissão de escrita (CHMOD 777) no servidor.");
        }
    }

    // Verifica se a empresa já existe no banco (ID = 1)
    $check = $pdo->query("SELECT id FROM configuracoes_empresa WHERE id = 1")->fetch();

    if ($check) {
        // Se já existe, atualiza (UPDATE)
        $query = "UPDATE configuracoes_empresa SET 
                    nome_fantasia = :nome_fantasia, 
                    razao_social = :razao_social, 
                    cnpj = :cnpj, 
                    telefone = :telefone, 
                    email = :email, 
                    endereco = :endereco";
        
        if ($logo_path !== null) {
            $query .= ", logo_path = :logo_path";
        }
        $query .= " WHERE id = 1";
        
    } else {
        // Se não existe, insere a primeira vez (INSERT)
        $query = "INSERT INTO configuracoes_empresa (id, nome_fantasia, razao_social, cnpj, telefone, email, endereco";
        if ($logo_path !== null) {
            $query .= ", logo_path";
        }
        $query .= ") VALUES (1, :nome_fantasia, :razao_social, :cnpj, :telefone, :email, :endereco";
        if ($logo_path !== null) {
            $query .= ", :logo_path";
        }
        $query .= ")";
    }

    // Organiza os parâmetros para evitar SQL Injection
    $params = [
        'nome_fantasia' => strtoupper($nome_fantasia),
        'razao_social'  => strtoupper($razao_social),
        'cnpj'          => $cnpj,
        'telefone'      => $telefone,
        'email'         => strtolower($email),
        'endereco'      => $endereco
    ];

    if ($logo_path !== null) {
        $params['logo_path'] = $logo_path;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    echo json_encode(['success' => true]);

} catch (\Throwable $e) { 
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>