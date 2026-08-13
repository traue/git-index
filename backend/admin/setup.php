<?php
declare(strict_types=1);

// Cria o primeiro usuário admin. Só funciona se:
//   1) ainda não existir NENHUM usuário na tabela (se autodesabilita depois
//      do primeiro uso), e
//   2) o token na URL bater com SETUP_TOKEN do .env (evita que alguém
//      encontre a URL e cadastre a própria conta antes de você).
//
// Depois de criar seu usuário, pode apagar este arquivo do servidor —
// não é mais necessário, mas deixá-lo não é um risco por causa do check (1).

require __DIR__ . '/_bootstrap.php';

$pdo = Database::connection();
$totalUsuarios = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();

if ($totalUsuarios > 0) {
    http_response_code(404);
    require __DIR__ . '/partials/header.php';
    echo '<p>Não encontrado.</p>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

$tokenEsperado = Env::get('SETUP_TOKEN', '');
$tokenRecebido = $_GET['token'] ?? ($_POST['token'] ?? '');

if ($tokenEsperado === '' || !hash_equals($tokenEsperado, (string) $tokenRecebido)) {
    http_response_code(404);
    require __DIR__ . '/partials/header.php';
    echo '<p>Não encontrado.</p>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfVerificar($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } else {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');
        $confirmar = (string) ($_POST['confirmar'] ?? '');

        if ($nome === '' || $email === '') {
            $erro = 'Preencha nome e e-mail.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'E-mail inválido.';
        } elseif (strlen($senha) < 10) {
            $erro = 'A senha precisa ter pelo menos 10 caracteres.';
        } elseif ($senha !== $confirmar) {
            $erro = 'As senhas não conferem.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (nome, email, senha_hash, role, ativo) VALUES (:nome, :email, :hash, "admin", 1)'
            );
            $stmt->execute([
                'nome' => $nome,
                'email' => $email,
                'hash' => password_hash($senha, PASSWORD_DEFAULT),
            ]);

            header('Location: login.php');
            exit;
        }
    }
}

$csrfToken = Auth::csrfToken();
require __DIR__ . '/partials/header.php';
?>
<section class="login-box">
  <h1>Criar primeiro usuário</h1>
  <?php if ($erro): ?><p class="alert alert--error"><?= h($erro) ?></p><?php endif; ?>
  <form method="post" action="setup.php?token=<?= h($tokenRecebido) ?>" novalidate>
    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
    <input type="hidden" name="token" value="<?= h($tokenRecebido) ?>">
    <label>Nome
      <input type="text" name="nome" required autofocus>
    </label>
    <label>E-mail
      <input type="email" name="email" required autocomplete="username">
    </label>
    <label>Senha (mín. 10 caracteres)
      <input type="password" name="senha" required minlength="10" autocomplete="new-password">
    </label>
    <label>Confirmar senha
      <input type="password" name="confirmar" required minlength="10" autocomplete="new-password">
    </label>
    <button type="submit">Criar usuário</button>
  </form>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
