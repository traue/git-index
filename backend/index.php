<?php
declare(strict_types=1);

// API pública de disciplinas — consumida pelo front em git.traue.com.br.
// Mantém o MESMO formato de resposta de antes (active, turnos.{diurno,
// noturno,ead}[].{nome,dia,repo}), agora montado a partir do banco em vez
// de um discs.json estático. Só GET; somente leitura.

require_once __DIR__ . '/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

try {
    $pdo = Database::connection();

    $activeValor = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'active'")->fetchColumn();
    $active = $activeValor === '1';

    $semestre = $pdo->query("SELECT id, codigo FROM semestres WHERE status = 'publicado' LIMIT 1")->fetch();

    $turnos = ['diurno' => [], 'noturno' => [], 'ead' => []];

    if ($semestre) {
        $stmt = $pdo->prepare(
            'SELECT nome, curso, tipo, turno, dia, repo
             FROM disciplinas
             WHERE semestre_id = :semestre_id
             ORDER BY ordem ASC, id ASC'
        );
        $stmt->execute(['semestre_id' => $semestre['id']]);

        foreach ($stmt as $linha) {
            $chave = $linha['tipo'] === 'ead' ? 'ead' : $linha['turno'];
            if (!isset($turnos[$chave])) {
                continue; // dado inconsistente (não deveria acontecer via admin)
            }

            $nome = $linha['curso'] !== null && $linha['curso'] !== ''
                ? $linha['nome'] . ' (' . $linha['curso'] . ')'
                : $linha['nome'];

            if ($linha['tipo'] === 'presencial') {
                $turnos[$chave][] = [
                    'nome' => $nome,
                    'dia' => $linha['dia'],
                    'repo' => $linha['repo'],
                ];
            } else {
                $turnos[$chave][] = [
                    'nome' => $nome,
                    'repo' => $linha['repo'],
                ];
            }
        }
    }

    $resposta = [
        'active' => $active,
        'semestre' => $semestre ? $semestre['codigo'] : null,
        'turnos' => $turnos,
    ];

    echo json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('[disciplinas-api] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'internal_error']);
}
