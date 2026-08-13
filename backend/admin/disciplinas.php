<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
Auth::exigirLogin();

$pdo = Database::connection();
$paginaAtual = 'semestres';

$semestreId = (int) ($_GET['semestre_id'] ?? ($_POST['semestre_id'] ?? 0));

$stmt = $pdo->prepare('SELECT id, codigo, status FROM semestres WHERE id = :id');
$stmt->execute(['id' => $semestreId]);
$semestre = $stmt->fetch();

if (!$semestre) {
    http_response_code(404);
    require __DIR__ . '/partials/header.php';
    echo '<p>Semestre não encontrado. <a href="index.php">Voltar</a></p>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

function disciplinas_validar(array $dados)
{
    $nome = trim((string) ($dados['nome'] ?? ''));
    $curso = trim((string) ($dados['curso'] ?? ''));
    $tipo = (string) ($dados['tipo'] ?? '');
    $turno = (string) ($dados['turno'] ?? '');
    $dia = trim((string) ($dados['dia'] ?? ''));
    $repo = trim((string) ($dados['repo'] ?? ''));
    $ordem = (int) ($dados['ordem'] ?? 0);

    $erros = [];

    if ($nome === '' || mb_strlen($nome) > 150) {
        $erros[] = 'Nome é obrigatório (até 150 caracteres).';
    }
    if ($curso !== '' && mb_strlen($curso) > 80) {
        $erros[] = 'Curso deve ter até 80 caracteres.';
    }
    if (!in_array($tipo, ['presencial', 'ead'], true)) {
        $erros[] = 'Tipo inválido.';
    }
    if ($repo === '' || !preg_match('/^[A-Za-z0-9._\-]{1,120}$/', $repo)) {
        $erros[] = 'Repositório é obrigatório e só pode ter letras, números, ponto, underline e hífen.';
    }
    if ($dia !== '' && mb_strlen($dia) > 60) {
        $erros[] = 'Dia/encontro deve ter até 60 caracteres.';
    }

    if ($tipo === 'presencial') {
        if (!in_array($turno, ['diurno', 'noturno'], true)) {
            $erros[] = 'Selecione o turno (diurno ou noturno) para disciplinas presenciais.';
        }
    } else {
        $turno = null;
        $dia = null;
    }

    return [
        'erros' => $erros,
        'valores' => [
            'nome' => $nome,
            'curso' => $curso !== '' ? $curso : null,
            'tipo' => $tipo,
            'turno' => $turno,
            'dia' => $dia !== '' ? $dia : null,
            'repo' => $repo,
            'ordem' => max(0, $ordem),
        ],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::csrfVerificar($_POST['csrf_token'] ?? null)) {
        flash_definir('Sessão expirada. Tente novamente.', 'error');
        header('Location: disciplinas.php?semestre_id=' . $semestreId);
        exit;
    }

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {
        $id = (int) ($_POST['id'] ?? 0);
        $validacao = disciplinas_validar($_POST);

        if ($validacao['erros']) {
            flash_definir(implode(' ', $validacao['erros']), 'error');
        } else {
            $v = $validacao['valores'];
            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE disciplinas SET nome=:nome, curso=:curso, tipo=:tipo, turno=:turno,
                     dia=:dia, repo=:repo, ordem=:ordem WHERE id=:id AND semestre_id=:semestre_id'
                );
                $v['id'] = $id;
                $v['semestre_id'] = $semestreId;
                $stmt->execute($v);
                flash_definir('Disciplina atualizada.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO disciplinas (semestre_id, nome, curso, tipo, turno, dia, repo, ordem)
                     VALUES (:semestre_id, :nome, :curso, :tipo, :turno, :dia, :repo, :ordem)'
                );
                $v['semestre_id'] = $semestreId;
                $stmt->execute($v);
                flash_definir('Disciplina cadastrada.');
            }
        }
    } elseif ($acao === 'excluir') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM disciplinas WHERE id = :id AND semestre_id = :semestre_id');
        $stmt->execute(['id' => $id, 'semestre_id' => $semestreId]);
        flash_definir('Disciplina removida.');
    }

    header('Location: disciplinas.php?semestre_id=' . $semestreId);
    exit;
}

$editando = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare('SELECT * FROM disciplinas WHERE id = :id AND semestre_id = :semestre_id');
    $stmt->execute(['id' => (int) $_GET['editar'], 'semestre_id' => $semestreId]);
    $editando = $stmt->fetch() ?: null;
}

$stmt = $pdo->prepare('SELECT * FROM disciplinas WHERE semestre_id = :semestre_id ORDER BY tipo, turno, ordem, id');
$stmt->execute(['semestre_id' => $semestreId]);
$disciplinas = $stmt->fetchAll();

$csrfToken = Auth::csrfToken();
require __DIR__ . '/partials/header.php';
?>

<p><a href="index.php">&larr; Semestres</a></p>

<section class="card">
  <h1>Disciplinas — Semestre <?= h($semestre['codigo']) ?> <span class="tag tag--<?= h($semestre['status']) ?>"><?= h($semestre['status']) ?></span></h1>

  <form method="post" action="disciplinas.php" class="form-grid" id="form-disciplina">
    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
    <input type="hidden" name="acao" value="salvar">
    <input type="hidden" name="semestre_id" value="<?= $semestreId ?>">
    <input type="hidden" name="id" value="<?= $editando ? (int) $editando['id'] : '' ?>">

    <label>Nome
      <input type="text" name="nome" maxlength="150" required value="<?= h($editando['nome'] ?? '') ?>">
    </label>
    <label>Curso (opcional, ex: ADS 02A/B)
      <input type="text" name="curso" maxlength="80" value="<?= h($editando['curso'] ?? '') ?>">
    </label>
    <label>Tipo
      <select name="tipo" id="campo-tipo" required>
        <option value="">Selecione</option>
        <option value="presencial" <?= ($editando['tipo'] ?? '') === 'presencial' ? 'selected' : '' ?>>Presencial</option>
        <option value="ead" <?= ($editando['tipo'] ?? '') === 'ead' ? 'selected' : '' ?>>EaD</option>
      </select>
    </label>
    <label id="campo-turno-wrap">Turno
      <select name="turno" id="campo-turno">
        <option value="">Selecione</option>
        <option value="diurno" <?= ($editando['turno'] ?? '') === 'diurno' ? 'selected' : '' ?>>Diurno</option>
        <option value="noturno" <?= ($editando['turno'] ?? '') === 'noturno' ? 'selected' : '' ?>>Noturno</option>
      </select>
    </label>
    <label id="campo-dia-wrap">Dia / encontro (ex: 6ª (manhã))
      <input type="text" name="dia" id="campo-dia" maxlength="60" value="<?= h($editando['dia'] ?? '') ?>">
    </label>
    <label>Repositório (GitHub)
      <input type="text" name="repo" maxlength="120" required pattern="[A-Za-z0-9._\-]+" value="<?= h($editando['repo'] ?? '') ?>">
    </label>
    <label>Ordem de exibição
      <input type="number" name="ordem" min="0" value="<?= h($editando['ordem'] ?? '0') ?>">
    </label>

    <div class="form-actions">
      <button type="submit" class="btn"><?= $editando ? 'Salvar alterações' : 'Adicionar disciplina' ?></button>
      <?php if ($editando): ?>
        <a href="disciplinas.php?semestre_id=<?= $semestreId ?>" class="btn btn--ghost">Cancelar edição</a>
      <?php endif; ?>
    </div>
  </form>
</section>

<section class="card">
  <table class="table">
    <thead>
      <tr>
        <th>Nome</th>
        <th>Curso</th>
        <th>Tipo</th>
        <th>Turno</th>
        <th>Dia</th>
        <th>Repo</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($disciplinas as $d): ?>
      <tr>
        <td><?= h($d['nome']) ?></td>
        <td><?= h($d['curso'] ?? '—') ?></td>
        <td><?= h($d['tipo']) ?></td>
        <td><?= h($d['turno'] ?? '—') ?></td>
        <td><?= h($d['dia'] ?? '—') ?></td>
        <td><code><?= h($d['repo']) ?></code></td>
        <td class="table-actions">
          <a href="disciplinas.php?semestre_id=<?= $semestreId ?>&editar=<?= (int) $d['id'] ?>">Editar</a>
          <form method="post" action="disciplinas.php" class="inline-form">
            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" name="semestre_id" value="<?= $semestreId ?>">
            <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
            <button type="submit" class="link-btn link-btn--danger" onclick="return confirm('Remover esta disciplina?')">Remover</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$disciplinas): ?>
      <tr><td colspan="7" class="muted">Nenhuma disciplina cadastrada neste semestre ainda.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<script src="assets/admin.js"></script>
<?php require __DIR__ . '/partials/footer.php'; ?>
