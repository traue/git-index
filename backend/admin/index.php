<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
Auth::exigirLogin();

$pdo = Database::connection();
$paginaAtual = 'semestres';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfVerificar($_POST['csrf_token'] ?? null)) {
        flash_definir('Sessão expirada. Tente novamente.', 'error');
        header('Location: index.php');
        exit;
    }

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar_semestre') {
        $codigo = trim((string) ($_POST['codigo'] ?? ''));

        if ($codigo === '' || !preg_match('/^[A-Za-z0-9.\-]{1,10}$/', $codigo)) {
            flash_definir('Código de semestre inválido. Use algo como 26.2.', 'error');
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO semestres (codigo, status) VALUES (:codigo, 'rascunho')");
                $stmt->execute(['codigo' => $codigo]);
                flash_definir('Semestre "' . $codigo . '" criado como rascunho.');
            } catch (PDOException $e) {
                flash_definir('Já existe um semestre com esse código.', 'error');
            }
        }
    } elseif ($acao === 'publicar_semestre') {
        $id = (int) ($_POST['id'] ?? 0);
        $pdo->beginTransaction();
        try {
            $pdo->exec("UPDATE semestres SET status = 'arquivado' WHERE status = 'publicado'");
            $stmt = $pdo->prepare("UPDATE semestres SET status = 'publicado', publicado_em = NOW() WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $pdo->commit();
            flash_definir('Semestre publicado. A API pública já está servindo estes dados.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash_definir('Não foi possível publicar o semestre.', 'error');
        }
    } elseif ($acao === 'arquivar_semestre') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE semestres SET status = 'arquivado' WHERE id = :id");
        $stmt->execute(['id' => $id]);
        flash_definir('Semestre arquivado.');
    } elseif ($acao === 'excluir_semestre') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM semestres WHERE id = :id AND status = 'rascunho'");
        $stmt->execute(['id' => $id]);
        flash_definir($stmt->rowCount() > 0 ? 'Rascunho excluído.' : 'Só é possível excluir semestres em rascunho.');
    } elseif ($acao === 'toggle_active') {
        $novoValor = ($_POST['valor'] ?? '') === '1' ? '1' : '0';
        $stmt = $pdo->prepare("UPDATE configuracoes SET valor = :valor WHERE chave = 'active'");
        $stmt->execute(['valor' => $novoValor]);
        flash_definir($novoValor === '1' ? 'Front reativado.' : 'Front desativado.');
    }

    header('Location: index.php');
    exit;
}

$activeAtual = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'active'")->fetchColumn() === '1';

$semestres = $pdo->query(
    "SELECT s.id, s.codigo, s.status, s.publicado_em,
            (SELECT COUNT(*) FROM disciplinas d WHERE d.semestre_id = s.id) AS total_disciplinas
     FROM semestres s
     ORDER BY (s.status = 'publicado') DESC, s.criado_em DESC"
)->fetchAll();

$csrfToken = Auth::csrfToken();
require __DIR__ . '/partials/header.php';
?>

<section class="card">
  <h1>Interruptor do front</h1>
  <p class="muted">Controla o campo <code>active</code> que o front em git.traue.com.br consulta. Desativado, o front mostra "aguarde instruções do professor".</p>
  <form method="post" action="index.php">
    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
    <input type="hidden" name="acao" value="toggle_active">
    <input type="hidden" name="valor" value="<?= $activeAtual ? '0' : '1' ?>">
    <button type="submit" class="btn <?= $activeAtual ? 'btn--danger' : 'btn--ok' ?>">
      <?= $activeAtual ? 'Desativar front' : 'Ativar front' ?>
    </button>
    <span class="status-pill status-pill--<?= $activeAtual ? 'on' : 'off' ?>">
      <?= $activeAtual ? 'Ativo' : 'Inativo' ?>
    </span>
  </form>
</section>

<section class="card">
  <h1>Novo semestre</h1>
  <form method="post" action="index.php" class="form-inline">
    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
    <input type="hidden" name="acao" value="criar_semestre">
    <label>Código
      <input type="text" name="codigo" placeholder="ex: 26.2" maxlength="10" required>
    </label>
    <button type="submit" class="btn">Criar rascunho</button>
  </form>
</section>

<section class="card">
  <h1>Semestres</h1>
  <table class="table">
    <thead>
      <tr>
        <th>Código</th>
        <th>Status</th>
        <th>Disciplinas</th>
        <th>Publicado em</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($semestres as $s): ?>
      <tr>
        <td><strong><?= h($s['codigo']) ?></strong></td>
        <td><span class="tag tag--<?= h($s['status']) ?>"><?= h($s['status']) ?></span></td>
        <td><?= (int) $s['total_disciplinas'] ?></td>
        <td><?= $s['publicado_em'] ? h($s['publicado_em']) : '—' ?></td>
        <td class="table-actions">
          <a href="disciplinas.php?semestre_id=<?= (int) $s['id'] ?>">Disciplinas</a>

          <?php if ($s['status'] !== 'publicado'): ?>
          <form method="post" action="index.php" class="inline-form">
            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
            <input type="hidden" name="acao" value="publicar_semestre">
            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
            <button type="submit" class="link-btn" onclick="return confirm('Publicar este semestre? Ele passa a ser o que a API pública devolve.')">Publicar</button>
          </form>
          <?php endif; ?>

          <?php if ($s['status'] !== 'arquivado'): ?>
          <form method="post" action="index.php" class="inline-form">
            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
            <input type="hidden" name="acao" value="arquivar_semestre">
            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
            <button type="submit" class="link-btn" onclick="return confirm('Arquivar este semestre?')">Arquivar</button>
          </form>
          <?php endif; ?>

          <?php if ($s['status'] === 'rascunho'): ?>
          <form method="post" action="index.php" class="inline-form">
            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
            <input type="hidden" name="acao" value="excluir_semestre">
            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
            <button type="submit" class="link-btn link-btn--danger" onclick="return confirm('Excluir este rascunho e suas disciplinas?')">Excluir</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$semestres): ?>
      <tr><td colspan="5" class="muted">Nenhum semestre cadastrado ainda.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
