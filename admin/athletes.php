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
    $stmt = $db->prepare('SELECT * FROM athletes WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';

    try {
        if ($action === 'delete') {
            $db->prepare('DELETE FROM athletes WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
            flash('success', 'Atleta excluído.');
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                throw new RuntimeException('Informe o nome do atleta.');
            }

            $categoryId = nullable_int($_POST['category_id'] ?? null);
            $categoryName = '';
            if ($categoryId !== null) {
                if (!isset($categoryById[$categoryId])) {
                    throw new RuntimeException('Selecione uma categoria válida.');
                }
                $categoryName = (string)$categoryById[$categoryId]['name'];
            }

            $photo = upload_image('photo_path', ($_POST['current_photo'] ?? '') ?: null);
            $data = [
                $name,
                ($_POST['birth_date'] ?? '') ?: null,
                $categoryId,
                $categoryName,
                trim($_POST['position_name'] ?? ''),
                nullable_int($_POST['shirt_number'] ?? null),
                trim($_POST['bio'] ?? ''),
                $photo,
                trim($_POST['guardian_name'] ?? ''),
                trim($_POST['guardian_phone'] ?? ''),
                isset($_POST['active']) ? 1 : 0,
            ];

            if ($id > 0) {
                $data[] = $id;
                $stmt = $db->prepare('UPDATE athletes SET name=?, birth_date=?, category_id=?, category=?, position_name=?, shirt_number=?, bio=?, photo_path=?, guardian_name=?, guardian_phone=?, active=? WHERE id=?');
                $stmt->execute($data);
            } else {
                $stmt = $db->prepare('INSERT INTO athletes (name, birth_date, category_id, category, position_name, shirt_number, bio, photo_path, guardian_name, guardian_phone, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute($data);
            }
            flash('success', 'Atleta salvo.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }

    redirect('athletes.php');
}

$q = trim($_GET['q'] ?? '');
$selectedCategory = (int)($_GET['category'] ?? 0);
$status = $_GET['status'] ?? 'all';
if (!in_array($status, ['all', 'active', 'inactive'], true)) {
    $status = 'all';
}

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(a.name LIKE ? OR a.position_name LIKE ? OR a.guardian_name LIKE ? OR a.guardian_phone LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($selectedCategory > 0) {
    $where[] = 'a.category_id = ?';
    $params[] = $selectedCategory;
}
if ($status === 'active') {
    $where[] = 'a.active = 1';
} elseif ($status === 'inactive') {
    $where[] = 'a.active = 0';
}

$sql = 'SELECT a.*, COALESCE(c.name, NULLIF(a.category, \'\'), \'Sem categoria\') AS category_name
        FROM athletes a
        LEFT JOIN age_categories c ON c.id = a.category_id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY COALESCE(c.sort_order, 9999), category_name, a.name';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$countRows = $db->query(
    'SELECT c.id, c.name, c.sort_order, COUNT(a.id) AS total
     FROM age_categories c
     LEFT JOIN athletes a ON a.category_id = c.id
     WHERE c.active = 1
     GROUP BY c.id, c.name, c.sort_order
     ORDER BY c.sort_order, c.name'
)->fetchAll();
$totalAthletes = (int)$db->query('SELECT COUNT(*) FROM athletes')->fetchColumn();

function athlete_filter_url(array $changes = []): string
{
    $query = array_merge([
        'q' => $_GET['q'] ?? '',
        'category' => $_GET['category'] ?? 0,
        'status' => $_GET['status'] ?? 'all',
    ], $changes);
    $query = array_filter($query, static fn($value) => $value !== '' && $value !== 0 && $value !== 'all');
    return 'athletes.php' . ($query ? '?' . http_build_query($query) : '');
}

require __DIR__ . '/_header.php';
?>
<div class="page-title">
    <div>
        <h1>Atletas</h1>
        <p>Pesquise atletas e navegue rapidamente entre as categorias Sub.</p>
    </div>
    <a class="btn" href="categories.php">Gerenciar categorias Sub</a>
</div>

<div class="category-tabs panel compact-panel" role="navigation" aria-label="Categorias de atletas">
    <a class="<?= $selectedCategory === 0 ? 'active' : '' ?>" href="<?= e(athlete_filter_url(['category' => 0])) ?>">
        Todos <span><?= $totalAthletes ?></span>
    </a>
    <?php foreach ($countRows as $category): ?>
        <a class="<?= $selectedCategory === (int)$category['id'] ? 'active' : '' ?>" href="<?= e(athlete_filter_url(['category' => (int)$category['id']])) ?>">
            <?= e($category['name']) ?> <span><?= (int)$category['total'] ?></span>
        </a>
    <?php endforeach; ?>
</div>

<form method="get" class="panel search-panel">
    <label class="search-field">Buscar atleta
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Nome, posição ou responsável">
    </label>
    <label>Status
        <select name="status">
            <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Todos</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Visíveis no site</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Ocultos</option>
        </select>
    </label>
    <?php if ($selectedCategory > 0): ?><input type="hidden" name="category" value="<?= $selectedCategory ?>"><?php endif; ?>
    <button class="btn primary" type="submit">Pesquisar</button>
    <a class="btn" href="athletes.php">Limpar filtros</a>
</form>

<div class="admin-grid">
    <form method="post" enctype="multipart/form-data" class="panel form-stack">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= e((string)($edit['id'] ?? '')) ?>">
        <input type="hidden" name="current_photo" value="<?= e($edit['photo_path'] ?? '') ?>">

        <h2><?= $edit ? 'Editar' : 'Novo' ?> atleta</h2>
        <label>Nome
            <input name="name" value="<?= e($edit['name'] ?? '') ?>" required>
        </label>
        <label>Nascimento
            <input type="date" name="birth_date" value="<?= e($edit['birth_date'] ?? '') ?>">
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
        <label>Posição
            <input name="position_name" value="<?= e($edit['position_name'] ?? '') ?>" placeholder="Ex.: Goleiro">
        </label>
        <label>Número da camisa
            <input type="number" min="0" name="shirt_number" value="<?= e((string)($edit['shirt_number'] ?? '')) ?>">
        </label>
        <label>Descrição pública
            <textarea name="bio" rows="3"><?= e($edit['bio'] ?? '') ?></textarea>
        </label>
        <label>Responsável
            <input name="guardian_name" value="<?= e($edit['guardian_name'] ?? '') ?>">
        </label>
        <label>Telefone do responsável
            <input name="guardian_phone" value="<?= e($edit['guardian_phone'] ?? '') ?>">
        </label>
        <label>Foto
            <input type="file" name="photo_path" accept="image/*">
        </label>
        <?php if (!empty($edit['photo_path'])): ?><img class="preview-person" src="../<?= e($edit['photo_path']) ?>" alt="Foto atual"><?php endif; ?>
        <label class="check">
            <input type="checkbox" name="active" <?= ($edit['active'] ?? 1) ? 'checked' : '' ?>> Exibir no site
        </label>
        <button class="btn primary" type="submit">Salvar atleta</button>
        <?php if ($edit): ?><a class="btn" href="athletes.php">Cancelar</a><?php endif; ?>
    </form>

    <div class="panel">
        <div class="panel-head">
            <h2>Resultados</h2>
            <span class="result-count"><?= count($rows) ?> atleta(s)</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Atleta</th><th>Categoria</th><th>Posição</th><th>Responsável</th><th>Status</th><th>Ações</th></tr></thead>
                <tbody>
                <?php if (!$rows): ?><tr><td colspan="6">Nenhum atleta encontrado com esses filtros.</td></tr><?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>
                            <div class="person-cell">
                                <?php if ($row['photo_path']): ?><img src="../<?= e($row['photo_path']) ?>" alt=""><?php else: ?><span><?= e((string)($row['shirt_number'] ?: 'AT')) ?></span><?php endif; ?>
                                <strong><?= e($row['name']) ?></strong>
                            </div>
                        </td>
                        <td><?= e($row['category_name']) ?></td>
                        <td><?= e($row['position_name']) ?></td>
                        <td><?= e($row['guardian_name']) ?><br><small><?= e($row['guardian_phone']) ?></small></td>
                        <td><?= $row['active'] ? 'Visível' : 'Oculto' ?></td>
                        <td class="actions">
                            <a href="?edit=<?= (int)$row['id'] ?>">Editar</a>
                            <form method="post" onsubmit="return confirm('Excluir este atleta?')">
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
