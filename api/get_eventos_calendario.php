<?php
// api/get_eventos_calendario.php
require_once '../includes/auth.php';
protegerAPI();
require_once '../config/conexao.php';

header('Content-Type: application/json');

try {
    // Busca projetos que tenham data de instalação definida
    $stmt = $pdo->query("SELECT id, cliente, status, equipe_instalacao, data_inicio_instalacao, data_fim_instalacao 
                         FROM projetos_pcp 
                         WHERE data_inicio_instalacao IS NOT NULL AND data_inicio_instalacao != ''");
    $projetos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $eventos = [];
    foreach ($projetos as $p) {
        $equipe = !empty($p['equipe_instalacao']) ? strtoupper($p['equipe_instalacao']) : 'SEM EQUIPA';
        
        $endDate = (string)($p['data_inicio_instalacao'] ?? '');
        if (!empty($p['data_fim_instalacao'])) {
            try {
                $end = new DateTime($p['data_fim_instalacao']);
                $end->modify('+1 day'); // Adiciona 1 dia para o visual do calendário
                $endDate = $end->format('Y-m-d');
            } catch (\Exception $e) {
                // Se a data vier corrompida do banco, faz um fallback
                $endDate = $p['data_fim_instalacao'];
            }
        }

        $status = (string)($p['status'] ?? '');
        $color = '#3b82f6'; // Azul padrão
        if ($status === 'atrasou') {
            $color = '#ef4444'; // Vermelho se atrasou
        } elseif ($status === 'instalacao') {
            $color = '#10b981'; // Verde se já está instalando
        }
        
        if (!empty($p['equipe_instalacao'])) {
            $hash = md5($equipe);
            $r = max(0, hexdec(substr($hash, 0, 2)) - 50);
            $g = max(0, hexdec(substr($hash, 2, 2)) - 50);
            $b = max(0, hexdec(substr($hash, 4, 2)) - 50);
            $color = "rgb({$r},{$g},{$b})";
        }

        $eventos[] = [
            'id' => $p['id'],
            'title' => "[{$equipe}] " . ($p['cliente'] ?? 'Sem Cliente'),
            'start' => $p['data_inicio_instalacao'],
            'end' => $endDate,
            'color' => $color,
            'extendedProps' => [
                'cliente' => $p['cliente'] ?? '',
                'equipe' => $equipe,
                'status' => $status,
                'data_inicio' => !empty($p['data_inicio_instalacao']) ? date('d/m/Y', strtotime($p['data_inicio_instalacao'])) : '',
                'data_fim' => !empty($p['data_fim_instalacao']) ? date('d/m/Y', strtotime($p['data_fim_instalacao'])) : 'Não definida'
            ]
        ];
    }

    echo json_encode($eventos);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>