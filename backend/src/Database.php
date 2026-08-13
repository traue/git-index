<?php
declare(strict_types=1);

final class Database
{
    /** @var PDO|null */
    private static $instance = null;

    public static function connection()
    {
        if (self::$instance === null) {
            $host = Env::required('DB_HOST');
            $port = Env::get('DB_PORT', '3306');
            $nome = Env::required('DB_NAME');
            $usuario = Env::required('DB_USER');
            $senha = Env::required('DB_PASS');

            $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $nome . ';charset=utf8mb4';

            self::$instance = new PDO($dsn, $usuario, $senha, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$instance;
    }
}
