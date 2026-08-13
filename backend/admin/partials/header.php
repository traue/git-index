<?php
/** @var string $paginaAtual */
$paginaAtual = $paginaAtual ?? '';
$flash = flash_ler();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="robots" content="noindex, nofollow" />
<title>Disciplinas · Admin</title>
<link rel="stylesheet" href="assets/admin.css" />
</head>
<body>
<?php if (Auth::logado()): ?>
<div class="shell">
  <header class="topbar">
    <div class="brand">Disciplinas <em>Admin</em></div>
    <nav>
      <a href="index.php" class="<?= $paginaAtual === 'semestres' ? 'on' : '' ?>">Semestres</a>
    </nav>
    <div class="user">
      <span><?= h(Auth::usuarioAtual()) ?></span>
      <a href="logout.php">Sair</a>
    </div>
  </header>
  <main class="content">
<?php else: ?>
<div class="shell shell--auth">
  <main class="content">
<?php endif; ?>

<?php if ($flash): ?>
  <p class="alert alert--<?= h($flash['tipo']) ?>"><?= h($flash['mensagem']) ?></p>
<?php endif; ?>
