<?php
// api/add_user.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');
$json = file_get_contents('php://input');
$data = json_decode($json);

$usuario = isset($data->usuario) ? trim($data->usuario) : '';
$senha   = isset($data->senha) ? $data->senha : '';

if (!empty($usuario) && !empty($senha)) {
    try {
        // Verifica se usuário já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario");
        $stmt->execute(['usuario' => $usuario]);
        
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Este usuário já está cadastrado.']);
        } else {
            // Criptografa a senha e cadastra
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, senha) VALUES (:usuario, :senha)");
            $stmt->execute(['usuario' => $usuario, 'senha' => $senha_hash]);
            echo json_encode(['success' => true]);
        }
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    $pdo = null;
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Usuário e senha são obrigatórios.']);
}
?>