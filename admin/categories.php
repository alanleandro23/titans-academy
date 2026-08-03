<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
Auth::requireLogin();

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM age_categories WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';

    try {
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare('DELETE FROM age_categories WHERE id = ?')->execute([$id]);
            flash('success', 'Categoria excluída. Atletas e times vinculados ficaram sem categoria.');
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                throw new RuntimeException('Informe o nome da categoria.');
            }

            $data = [
                $name,
                nullable_int($_POST['min_age'] ?? null),
                nullable_int($_POST['max_age'] ?? null),
                trim($_POST['description'] ?? ''),
                isset($_POST['active']) ? 1 : 0,
                (int)($_POST['sort_order'] ?? 0),
            ];

            if ($id > 0) {
                $data[] = $id;
                $stmt = $db->prepare('UPDATE age_categories SET name=?, min_age=?, max_age=?, description=?, active=?, sort_order=? WHERE id=?');
                $stmt->execute($data);
            } else {
                $stmt = $db->prepare('INSERT INTO age_categories (name, min_age, max_age, description, active, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute($data);
            }
            flash('success', 'Categoria salva.');
        }
    } catch (PDOException $e) {
        $message = str_contains($e->getMessage(), 'Duplicate')
            ? 'Já existe uma categoria com esse nome.'
            : 'Não foi possível salvar a categoria.';
        flash('danger', $message);
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }

    redirect('categories.php');
}

$rows = $db->query(
    'SELECT c.*,
        (SELECT COUNT(*) FROM athletes a WHERE a.category_id = c.id) AS athlete_count,
        (SELECT COUNT(*) FROM teams t WHERE t.category_id = c.id) AS team_count
     FROM age_categories c
     ORDER BY c.sort_order, c.name'
)->fetchAll();

require __DIR__ . '/_header.php';
?>
<div class="page-title">
    <div>
        <h1>Categorias Sub</h1>
        <p>Cadastre Sub-5, Sub-7, Sub-9 e as demais categorias da escolinha.</p>
    </div>
</div>

<div class="admin-grid">
    <form method="post" class="panel form-stack">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= e((string)($edit['id'] ?? '')) ?>">

        <h2><?= $edit ? 'Editar' : 'Nova' ?> categoria</h2>

        <label>Nome
            <input name="name" value="<?= e($edit['name'] ?? '') ?>" placeholder="Ex.: Sub-13" required>
        </label>

        <div class="inline-fields">
            <label>Idade mínima
                <input type="number" min="0" max="30" name="min_age" value="<?= e((string)($edit['min_age'] ?? '')) ?>">
            </label>
            <label>Idade máxima
                <input type="number" min="0" max="30" name="max_age" value="<?= e((string)($edit['max_age'] ?? '')) ?>">
            </label>
        </div>

        <label>Ordem de exibição
            <input type="number" name="sort_order" value="<?= e((string)($edit['sort_order'] ?? 0)) ?>">
        </label>

        <label>Descrição
            <textarea name="description" rows="4"><?= e($edit['description'] ?? '') ?></textarea>
        </label>

        <label class="check">
            <input type="checkbox" name="active" <?= ($edit['active'] ?? 1) ? 'checked' : '' ?>> Exibir como categoria ativa
        </label>

        <button class="btn primary" type="submit">Salvar categoria</button>
        <?php if ($edit): ?><a class="btn" href="categories.php">Cancelar</a><?php endif; ?>
    </form>

    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Categoria</th><th>Faixa</th><th>Atletas</th><th>Times</th><th>Status</th><th>Ações</th></tr></thead>
                <tbody>
                <?php if (!$rows): ?><tr><td colspan="6">Nenhuma categoria cadastrada.</td></tr><?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><strong><?= e($row['name']) ?></strong></td>
                        <td><?= e(($row['min_age'] !== null ? $row['min_age'] : '—') . ' a ' . ($row['max_age'] !== null ? $row['max_age'] : '—')) ?></td>
                        <td><?= (int)$row['athlete_count'] ?></td>
                        <td><?= (int)$row['team_count'] ?></td>
                        <td><?= $row['active'] ? 'Ativa' : 'Oculta' ?></td>
                        <td class="actions">
                            <a href="?edit=<?= (int)$row['id'] ?>">Editar</a>
                            <form method="post" onsubmit="return confirm('Excluir esta categoria?')">
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
