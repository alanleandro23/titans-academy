<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
Auth::requireLogin();

if (!db_table_exists($db, 'news')) {
    flash('danger', 'Conclua a atualização V3 do banco antes de cadastrar notícias.');
    redirect('../upgrade_v3.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM news WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $action = $_POST['action'] ?? 'save';
        $id = (int)($_POST['id'] ?? 0);

        if ($action === 'delete') {
            $stmt = $db->prepare('SELECT image_path FROM news WHERE id=?');
            $stmt->execute([$id]);
            $image = $stmt->fetchColumn();
            $db->prepare('DELETE FROM news WHERE id=?')->execute([$id]);
            delete_uploaded_image(is_string($image) ? $image : null);
            flash('success', 'Notícia excluída.');
            redirect('news.php');
        }

        $title = trim($_POST['title'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if (mb_strlen($title) < 5) {
            throw new RuntimeException('Informe um título com pelo menos 5 caracteres.');
        }
        if (mb_strlen($summary) < 10) {
            throw new RuntimeException('Informe um resumo com pelo menos 10 caracteres.');
        }

        $currentImage = trim($_POST['current_image'] ?? '') ?: null;
        $image = upload_image('image_path', $currentImage);
        $publishedAt = normalize_datetime_local($_POST['published_at'] ?? null);
        $active = isset($_POST['active']) ? 1 : 0;
        $featured = isset($_POST['featured']) ? 1 : 0;

        if ($id > 0) {
            $stmt = $db->prepare(
                'UPDATE news
                 SET title=?, summary=?, content=?, image_path=?, published_at=?, active=?, featured=?
                 WHERE id=?'
            );
            $stmt->execute([$title, $summary, $content, $image, $publishedAt, $active, $featured, $id]);
            flash('success', 'Notícia atualizada.');
        } else {
            $slug = slugify($title) . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(2));
            $stmt = $db->prepare(
                'INSERT INTO news (title, slug, summary, content, image_path, published_at, active, featured)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$title, $slug, $summary, $content, $image, $publishedAt, $active, $featured]);
            flash('success', 'Notícia publicada.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }

    redirect('news.php');
}

$rows = $db->query('SELECT * FROM news ORDER BY published_at DESC, id DESC')->fetchAll();
require __DIR__ . '/_header.php';
?>
<div class="page-title">
    <div>
        <h1>Notícias</h1>
        <p>Cadastre as publicações exibidas no carrossel da página inicial.</p>
    </div>
    <a class="btn" href="../index.php#noticias" target="_blank" rel="noopener">Visualizar carrossel</a>
</div>

<div class="admin-grid news-admin-grid">
    <form method="post" enctype="multipart/form-data" class="panel form-stack">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= e((string)($edit['id'] ?? '')) ?>">
        <input type="hidden" name="current_image" value="<?= e($edit['image_path'] ?? '') ?>">

        <h2><?= $edit ? 'Editar notícia' : 'Nova notícia' ?></h2>

        <?php if (!empty($edit['image_path'])): ?>
            <img class="news-admin-preview" src="../<?= e($edit['image_path']) ?>" alt="Imagem atual">
        <?php endif; ?>

        <label>Título
            <input name="title" maxlength="190" value="<?= e($edit['title'] ?? '') ?>" required>
        </label>

        <label>Resumo do carrossel
            <textarea name="summary" rows="4" maxlength="500" required><?= e($edit['summary'] ?? '') ?></textarea>
            <small>Texto curto exibido ao lado da imagem na página inicial.</small>
        </label>

        <label>Conteúdo completo
            <textarea name="content" rows="9"><?= e($edit['content'] ?? '') ?></textarea>
        </label>

        <label>Imagem de capa
            <input type="file" name="image_path" accept="image/jpeg,image/png,image/webp">
            <small>Recomendado: imagem horizontal, preferencialmente 1200 × 700 px.</small>
        </label>

        <label>Data e hora da publicação
            <input type="datetime-local" name="published_at" value="<?= e(!empty($edit['published_at']) ? date('Y-m-d\TH:i', strtotime($edit['published_at'])) : date('Y-m-d\TH:i')) ?>">
        </label>

        <label class="check"><input type="checkbox" name="active" <?= !isset($edit['active']) || $edit['active'] ? 'checked' : '' ?>> Publicada e visível no site</label>
        <label class="check"><input type="checkbox" name="featured" <?= ($edit['featured'] ?? 0) ? 'checked' : '' ?>> Destacar antes das demais no carrossel</label>

        <button class="btn primary" type="submit"><?= $edit ? 'Salvar alterações' : 'Publicar notícia' ?></button>
        <?php if ($edit): ?><a class="btn" href="news.php">Cancelar edição</a><?php endif; ?>
    </form>

    <div class="panel">
        <div class="panel-head"><h2>Publicações cadastradas</h2><span><?= count($rows) ?> registro(s)</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Notícia</th><th>Publicação</th><th>Status</th><th>Destaque</th><th>Ações</th></tr></thead>
                <tbody>
                <?php if (!$rows): ?><tr><td colspan="5">Nenhuma notícia cadastrada.</td></tr><?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>
                            <div class="news-table-title">
                                <?php if ($row['image_path']): ?><img src="../<?= e($row['image_path']) ?>" alt=""><?php else: ?><span>TAF</span><?php endif; ?>
                                <div><strong><?= e($row['title']) ?></strong><small><?= e($row['summary']) ?></small></div>
                            </div>
                        </td>
                        <td><?= format_date($row['published_at'], true) ?></td>
                        <td><span class="status-pill <?= $row['active'] ? 'success' : 'muted' ?>"><?= $row['active'] ? 'Publicada' : 'Oculta' ?></span></td>
                        <td><?= $row['featured'] ? 'Sim' : 'Não' ?></td>
                        <td class="actions">
                            <a href="?edit=<?= (int)$row['id'] ?>">Editar</a>
                            <?php if ($row['active']): ?><a href="../index.php?page=noticia&id=<?= (int)$row['id'] ?>" target="_blank" rel="noopener">Abrir</a><?php endif; ?>
                            <form method="post" onsubmit="return confirm('Excluir esta notícia?')">
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
