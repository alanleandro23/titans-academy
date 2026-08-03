<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
Auth::requireLogin();

$categories = $db->query('SELECT * FROM age_categories ORDER BY sort_order, name')->fetchAll();
$categoryById = [];
foreach ($categories as $categoryRow) {
    $categoryById[(int)$categoryRow['id']] = $categoryRow;
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM teams WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        if (($_POST['action'] ?? 'save') === 'delete') {
            $db->prepare('DELETE FROM teams WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
            flash('success', 'Time excluído.');
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                throw new RuntimeException('Informe o nome do time.');
            }

            $categoryId = nullable_int($_POST['category_id'] ?? null);
            $categoryName = '';
            if ($categoryId !== null) {
                if (!isset($categoryById[$categoryId])) {
                    throw new RuntimeException('Selecione uma categoria válida.');
                }
                $categoryName = (string)$categoryById[$categoryId]['name'];
            }

            $badge = upload_image('badge_path', ($_POST['current_badge'] ?? '') ?: null);
            $data = [
                $name,
                $categoryId,
                $categoryName,
                trim($_POST['coach_name'] ?? ''),
                $badge,
                trim($_POST['notes'] ?? ''),
                isset($_POST['active']) ? 1 : 0,
            ];

            if ($id > 0) {
                $data[] = $id;
                $stmt = $db->prepare('UPDATE teams SET name=?, category_id=?, category=?, coach_name=?, badge_path=?, notes=?, active=? WHERE id=?');
                $stmt->execute($data);
            } else {
                $stmt = $db->prepare('INSERT INTO teams (name, category_id, category, coach_name, badge_path, notes, active) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute($data);
            }
            flash('success', 'Time salvo.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }
    redirect('teams.php');
}

$rows = $db->query(
    "SELECT t.*, COALESCE(c.name, NULLIF(t.category, ''), 'Sem categoria') AS category_name
     FROM teams t
     LEFT JOIN age_categories c ON c.id = t.category_id
     ORDER BY COALESCE(c.sort_order, 9999), category_name, t.name"
)->fetchAll();

require __DIR__ . '/_header.php';
?>
<div class="page-title">
    <div><h1>Times</h1><p>Cadastre equipes e vincule cada uma à sua categoria Sub.</p></div>
    <a class="btn" href="categories.php">Gerenciar categorias Sub</a>
</div>

<div class="admin-grid">
    <form method="post" enctype="multipart/form-data" class="panel form-stack">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= e((string)($edit['id'] ?? '')) ?>">
        <input type="hidden" name="current_badge" value="<?= e($edit['badge_path'] ?? '') ?>">

        <h2><?= $edit ? 'Editar' : 'Novo' ?> time</h2>
        <label>Nome
            <input name="name" value="<?= e($edit['name'] ?? '') ?>" required>
        </label>
        <label>Categoria Sub
            <select name="category_id">
                <option value="">Sem categoria</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category['id'] ?>" <?= (int)($edit['category_id'] ?? 0) === (int)$category['id'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?><?= !$category['active'] ? ' — inativa' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Técnico
            <input name="coach_name" value="<?= e($edit['coach_name'] ?? '') ?>">
        </label>
        <label>Escudo
            <input type="file" name="badge_path" accept="image/*">
        </label>
        <?php if (!empty($edit['badge_path'])): ?><img class="preview-logo" src="../<?= e($edit['badge_path']) ?>" alt="Escudo atual"><?php endif; ?>
        <label>Observações
            <textarea name="notes" rows="4"><?= e($edit['notes'] ?? '') ?></textarea>
        </label>
        <label class="check">
            <input type="checkbox" name="active" <?= ($edit['active'] ?? 1) ? 'checked' : '' ?>> Ativo
        </label>
        <button class="btn primary" type="submit">Salvar time</button>
        <?php if ($edit): ?><a class="btn" href="teams.php">Cancelar</a><?php endif; ?>
    </form>

    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Time</th><th>Categoria</th><th>Técnico</th><th>Status</th><th>Ações</th></tr></thead>
                <tbody>
                <?php if (!$rows): ?><tr><td colspan="5">Nenhum time cadastrado.</td></tr><?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['category_name']) ?></td>
                        <td><?= e($row['coach_name']) ?></td>
                        <td><?= $row['active'] ? 'Ativo' : 'Inativo' ?></td>
                        <td class="actions">
                            <a href="?edit=<?= (int)$row['id'] ?>">Editar</a>
                            <form method="post" onsubmit="return confirm('Excluir este time?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <button type="submit">Excluir</button>
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
