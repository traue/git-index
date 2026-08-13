<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (Auth::logado()) {
    header('Location: index.php');
    exit;
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfVerificar($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if ($email !== '' && $senha !== '' && Auth::tentarLogin($email, $senha, $ip)) {
            header('Location: index.php');
            exit;
        }

        $erro = 'E-mail ou senha inválidos.';
    }
}

$csrfToken = Auth::csrfToken();
require __DIR__ . '/partials/header.php';
?>
<section class="login-box">
  <h1>Entrar</h1>
  <?php if ($erro): ?><p class="alert alert--error"><?= h($erro) ?></p><?php endif; ?>
  <form method="post" action="login.php" novalidate>
    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
    <label>E-mail
      <input type="email" name="email" required autofocus autocomplete="username">
    </label>
    <label>Senha
      <input type="password" name="senha" required autocomplete="current-password">
    </label>
    <button type="submit">Entrar</button>
  </form>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
