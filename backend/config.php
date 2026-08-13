<?php
declare(strict_types=1);

// Bootstrap comum: variáveis de ambiente + conexão com o banco.
// Incluído tanto pela API pública (index.php) quanto pelo admin.

error_reporting(E_ALL);
ini_set('display_errors', '0'); // nunca mostrar detalhes de erro para o público
ini_set('log_errors', '1');

require_once __DIR__ . '/src/Env.php';
require_once __DIR__ . '/src/Database.php';

Env::load(__DIR__ . '/.env');

date_default_timezone_set('America/Sao_Paulo');

if (!defined('APP_ENV')) {
    define('APP_ENV', Env::get('APP_ENV', 'production'));
}

if (APP_ENV === 'local') {
    ini_set('display_errors', '1');
}
