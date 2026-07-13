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
        $equipe = $p['equipe_instalacao'] ? strtoupper($p['equipe_instalacao']) : 'SEM EQUIPA';
        
        // FullCalendar precisa que a data final seja o dia seguinte (exclusive end date) para preencher a barra no calendário corretamente
        $endDate = '';
        if (!empty($p['data_fim_instalacao'])) {
            $end = new DateTime($p['data_fim_instalacao']);
            $end->modify('+1 day'); // Adiciona 1 dia para o visual do calendário
            $endDate = $end->format('Y-m-d');
        } else {
            $endDate = $p['data_inicio_instalacao'];
        }

        // Definir uma cor padrão baseada no status
        $color = '#3b82f6'; // Azul padrão
        if ($p['status'] == 'atrasou') $color = '#ef4444'; // Vermelho se atrasou
        elseif ($p['status'] == 'instalacao') $color = '#10b981'; // Verde se já está instalando
        
        // Se tiver equipa, gera uma cor fixa única para aquela equipa baseada no nome (ex: Equipe A será sempre roxa, Equipe B laranja...)
        if ($p['equipe_instalacao']) {
            $hash = md5($equipe);
            $r = hexdec(substr($hash, 0, 2));
            $g = hexdec(substr($hash, 2, 2));
            $b = hexdec(substr($hash, 4, 2));
            // Escurece um pouco a cor para o texto branco ficar legível
            $r = max(0, $r - 50); $g = max(0, $g - 50); $b = max(0, $b - 50);
            $color = "rgb($r,$g,$b)";
        }

        $eventos[] = [
            'id' => $p['id'],
            'title' => "[" . $equipe . "] " . $p['cliente'],
            'start' => $p['data_inicio_instalacao'],
            'end' => $endDate,
            'color' => $color,
            'extendedProps' => [ // Variáveis extras para usar no modal de clique
                'cliente' => $p['cliente'],
                'equipe' => $equipe,
                'status' => $p['status'],
                'data_inicio' => date('d/m/Y', strtotime($p['data_inicio_instalacao'])),
                'data_fim' => $p['data_fim_instalacao'] ? date('d/m/Y', strtotime($p['data_fim_instalacao'])) : 'Não definida'
            ]
        ];
    }

    echo json_encode($eventos);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>