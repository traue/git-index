<?php
declare(strict_types=1);

/**
 * Leitor mínimo de arquivos .env, sem depender de Composer/pacotes externos
 * (necessário porque o host é hospedagem compartilhada, sem acesso a CLI).
 */
final class Env
{
    private static $loaded = false;

    public static function load($path)
    {
        if (self::$loaded) {
            return;
        }

        if (!is_readable($path)) {
            throw new RuntimeException('Arquivo .env não encontrado ou sem permissão de leitura: ' . $path);
        }

        $linhas = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if ($linha === '' || strpos($linha, '#') === 0) {
                continue;
            }
            if (strpos($linha, '=') === false) {
                continue;
            }

            list($chave, $valor) = explode('=', $linha, 2);
            $chave = trim($chave);
            $valor = trim($valor);
            $valor = preg_replace('/^"(.*)"$/', '$1', $valor);
            $valor = preg_replace("/^'(.*)'$/", '$1', $valor);

            if ($chave !== '' && getenv($chave) === false) {
                putenv($chave . '=' . $valor);
                $_ENV[$chave] = $valor;
            }
        }

        self::$loaded = true;
    }

    public static function get($chave, $padrao = null)
    {
        $valor = getenv($chave);
        return $valor === false ? $padrao : $valor;
    }

    public static function required($chave)
    {
        $valor = self::get($chave);
        if ($valor === null || $valor === '') {
            throw new RuntimeException('Variável de ambiente obrigatória ausente: ' . $chave);
        }
        return $valor;
    }
}
