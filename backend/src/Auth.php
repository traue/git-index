<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class Auth
{
    private const MAX_TENTATIVAS = 5;
    private const JANELA_MINUTOS = 15;

    public static function tentarLogin($email, $senha, $ip)
    {
        $pdo = Database::connection();

        if (self::bloqueado($email, $ip)) {
            self::registrarTentativa($pdo, $email, $ip, false);
            return false;
        }

        $stmt = $pdo->prepare('SELECT id, senha_hash, ativo FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        $ok = $usuario
            && (int) $usuario['ativo'] === 1
            && password_verify($senha, $usuario['senha_hash']);

        self::registrarTentativa($pdo, $email, $ip, (bool) $ok);

        if (!$ok) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int) $usuario['id'];
        $_SESSION['usuario_email'] = $email;
        $_SESSION['criado_em'] = time();

        $upd = $pdo->prepare('UPDATE usuarios SET ultimo_login_em = NOW() WHERE id = :id');
        $upd->execute(['id' => $usuario['id']]);

        return true;
    }

    private static function bloqueado($email, $ip)
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM login_tentativas
             WHERE (identificador = :email OR ip = :ip)
               AND sucesso = 0
               AND criado_em > (NOW() - INTERVAL ' . self::JANELA_MINUTOS . ' MINUTE)'
        );
        $stmt->execute(['email' => $email, 'ip' => $ip]);
        return (int) $stmt->fetchColumn() >= self::MAX_TENTATIVAS;
    }

    private static function registrarTentativa(PDO $pdo, $email, $ip, $sucesso)
    {
        $stmt = $pdo->prepare(
            'INSERT INTO login_tentativas (identificador, ip, sucesso) VALUES (:email, :ip, :sucesso)'
        );
        $stmt->execute([
            'email' => $email,
            'ip' => $ip,
            'sucesso' => $sucesso ? 1 : 0,
        ]);
    }

    public static function logado()
    {
        return isset($_SESSION['usuario_id']);
    }

    public static function exigirLogin()
    {
        if (!self::logado()) {
            header('Location: login.php');
            exit;
        }
    }

    public static function usuarioAtual()
    {
        return $_SESSION['usuario_email'] ?? null;
    }

    public static function logout()
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    public static function csrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfVerificar($token)
    {
        return is_string($token)
            && !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}
