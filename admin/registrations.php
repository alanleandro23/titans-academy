<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        if (($_POST['action'] ?? 'save') === 'delete') {
            $db->prepare('DELETE FROM competition_teams WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
            flash('success', 'Inscrição removida.');
        } else {
            $db->prepare(
                'INSERT INTO competition_teams (competition_id, team_id, group_name, registration_notes)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE group_name=VALUES(group_name), registration_notes=VALUES(registration_notes)'
            )->execute([
                (int)($_POST['competition_id'] ?? 0),
                (int)($_POST['team_id'] ?? 0),
                trim($_POST['group_name'] ?? ''),
                trim($_POST['registration_notes'] ?? ''),
            ]);
            flash('success', 'Time inscrito na competição.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }
    redirect('registrations.php');
}

$competitions = $db->query("SELECT id, name FROM competitions WHERE competition_type IN ('campeonato','torneio') ORDER BY start_date DESC, name")->fetchAll();
$teams = $db->query(
    "SELECT t.id, t.name, COALESCE(c.name, NULLIF(t.category, ''), 'Sem categoria') AS category_name
     FROM teams t
     LEFT JOIN age_categories c ON c.id = t.category_id
     WHERE t.active = 1
     ORDER BY category_name, t.name"
)->fetchAll();
$rows = $db->query(
    "SELECT ct.*, comp.name AS competition_name, t.name AS team_name,
            COALESCE(c.name, NULLIF(t.category, ''), 'Sem categoria') AS category_name
     FROM competition_teams ct
     JOIN competitions comp ON comp.id = ct.competition_id
     JOIN teams t ON t.id = ct.team_id
     LEFT JOIN age_categories c ON c.id = t.category_id
     ORDER BY comp.start_date DESC, comp.name, category_name, t.name"
)->fetchAll();

require __DIR__ . '/_header.php';
?>
<div class="page-title"><div><h1>Times inscritos</h1><p>Vincule os times a cada campeonato ou torneio.</p></div></div>
<div class="admin-grid">
    <form method="post" class="panel form-stack">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <h2>Nova inscrição</h2>
        <label>Competição
            <select name="competition_id" required>
                <option value="">Selecione</option>
                <?php foreach ($competitions as $competition): ?><option value="<?= (int)$competition['id'] ?>"><?= e($competition['name']) ?></option><?php endforeach; ?>
            </select>
        </label>
        <label>Time
            <select name="team_id" required>
                <option value="">Selecione</option>
                <?php foreach ($teams as $team): ?><option value="<?= (int)$team['id'] ?>"><?= e($team['name'] . ' — ' . $team['category_name']) ?></option><?php endforeach; ?>
            </select>
        </label>
        <label>Grupo ou chave<input name="group_name" placeholder="Ex.: Grupo A"></label>
        <label>Informações<textarea name="registration_notes" rows="4" placeholder="Jogadores inscritos, observações, documentação..."></textarea></label>
        <button class="btn primary" type="submit">Cadastrar inscrição</button>
    </form>

    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Competição</th><th>Time</th><th>Categoria</th><th>Grupo</th><th>Informações</th><th>Ação</th></tr></thead>
                <tbody>
                <?php if (!$rows): ?><tr><td colspan="6">Nenhum time inscrito.</td></tr><?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['competition_name']) ?></td>
                        <td><?= e($row['team_name']) ?></td>
                        <td><?= e($row['category_name']) ?></td>
                        <td><?= e($row['group_name']) ?></td>
                        <td class="cell-wrap"><?= e($row['registration_notes']) ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('Remover a inscrição?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <button class="link-danger" type="submit">Remover</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
