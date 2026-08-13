<?php
declare(strict_types=1);

// Incluído por toda página do admin (exceto quando ela mesma inclui este
// arquivo antes de qualquer output). Configura sessão segura e helpers.

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Auth.php';

$cookieSecure = (Env::get('APP_ENV', 'production') !== 'local');

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

// Ajuste este path se a estrutura de pastas no host for diferente: deve ser
// o caminho da URL até esta pasta admin (ex: se api.traue.com.br apontar
// direto para esta pasta "disciplinas", troque para '/admin/').
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/disciplinas/admin/',
    'domain' => '',
    'secure' => $cookieSecure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name('disc_admin_sess');
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

function flash_definir($mensagem, $tipo = 'ok')
{
    $_SESSION['flash'] = ['mensagem' => $mensagem, 'tipo' => $tipo];
}

function flash_ler()
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function h($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
