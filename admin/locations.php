<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
Auth::requireLogin();

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM locations WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';

    try {
        if ($action === 'delete') {
            $db->prepare('DELETE FROM locations WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
            flash('success', 'Endereço excluído.');
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $unitName = trim($_POST['unit_name'] ?? '');
            $addressLine = trim($_POST['address_line'] ?? '');
            $mapUrl = trim($_POST['map_url'] ?? '');

            if ($unitName === '' || $addressLine === '') {
                throw new RuntimeException('Informe o nome da unidade e o endereço.');
            }
            if ($mapUrl !== '' && !filter_var($mapUrl, FILTER_VALIDATE_URL)) {
                throw new RuntimeException('Informe uma URL válida para o mapa ou deixe o campo vazio.');
            }

            $data = [
                $unitName,
                $addressLine,
                trim($_POST['neighborhood'] ?? ''),
                trim($_POST['city'] ?? ''),
                trim($_POST['state'] ?? ''),
                trim($_POST['postal_code'] ?? ''),
                trim($_POST['phone'] ?? ''),
                trim($_POST['whatsapp'] ?? ''),
                $mapUrl,
                trim($_POST['training_info'] ?? ''),
                isset($_POST['active']) ? 1 : 0,
                (int)($_POST['sort_order'] ?? 0),
            ];

            if ($id > 0) {
                $data[] = $id;
                $stmt = $db->prepare('UPDATE locations SET unit_name=?, address_line=?, neighborhood=?, city=?, state=?, postal_code=?, phone=?, whatsapp=?, map_url=?, training_info=?, active=?, sort_order=? WHERE id=?');
                $stmt->execute($data);
            } else {
                $stmt = $db->prepare('INSERT INTO locations (unit_name, address_line, neighborhood, city, state, postal_code, phone, whatsapp, map_url, training_info, active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute($data);
            }
            flash('success', 'Endereço salvo.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }

    redirect('locations.php');
}

$rows = $db->query('SELECT * FROM locations ORDER BY sort_order, unit_name')->fetchAll();
require __DIR__ . '/_header.php';
?>
<div class="page-title">
    <div>
        <h1>Endereços e unidades</h1>
        <p>Cadastre todos os locais onde a escolinha realiza treinos e atividades.</p>
    </div>
</div>

<div class="admin-grid">
    <form method="post" class="panel form-stack">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= e((string)($edit['id'] ?? '')) ?>">

        <h2><?= $edit ? 'Editar' : 'Nova' ?> unidade</h2>

        <label>Nome da unidade
            <input name="unit_name" value="<?= e($edit['unit_name'] ?? '') ?>" placeholder="Ex.: Arena Titans — Unidade Centro" required>
        </label>
        <label>Endereço completo
            <input name="address_line" value="<?= e($edit['address_line'] ?? '') ?>" required>
        </label>
        <label>Bairro
            <input name="neighborhood" value="<?= e($edit['neighborhood'] ?? '') ?>">
        </label>
        <div class="inline-fields">
            <label>Cidade
                <input name="city" value="<?= e($edit['city'] ?? '') ?>">
            </label>
            <label>Estado
                <input name="state" maxlength="50" value="<?= e($edit['state'] ?? '') ?>">
            </label>
        </div>
        <label>CEP
            <input name="postal_code" value="<?= e($edit['postal_code'] ?? '') ?>">
        </label>
        <div class="inline-fields">
            <label>Telefone
                <input name="phone" value="<?= e($edit['phone'] ?? '') ?>">
            </label>
            <label>WhatsApp
                <input name="whatsapp" value="<?= e($edit['whatsapp'] ?? '') ?>">
            </label>
        </div>
        <label>Link do Google Maps
            <input type="url" name="map_url" value="<?= e($edit['map_url'] ?? '') ?>" placeholder="https://maps.google.com/...">
        </label>
        <label>Dias, horários ou observações
            <textarea name="training_info" rows="4"><?= e($edit['training_info'] ?? '') ?></textarea>
        </label>
        <label>Ordem de exibição
            <input type="number" name="sort_order" value="<?= e((string)($edit['sort_order'] ?? 0)) ?>">
        </label>
        <label class="check">
            <input type="checkbox" name="active" <?= ($edit['active'] ?? 1) ? 'checked' : '' ?>> Exibir no site
        </label>

        <button class="btn primary" type="submit">Salvar endereço</button>
        <?php if ($edit): ?><a class="btn" href="locations.php">Cancelar</a><?php endif; ?>
    </form>

    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Unidade</th><th>Endereço</th><th>Contato</th><th>Status</th><th>Ações</th></tr></thead>
                <tbody>
                <?php if (!$rows): ?><tr><td colspan="5">Nenhum endereço cadastrado.</td></tr><?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><strong><?= e($row['unit_name']) ?></strong></td>
                        <td class="cell-wrap"><?= e(trim($row['address_line'] . ' — ' . $row['neighborhood'] . ' — ' . $row['city'])) ?></td>
                        <td><?= e($row['whatsapp'] ?: $row['phone']) ?></td>
                        <td><?= $row['active'] ? 'Visível' : 'Oculta' ?></td>
                        <td class="actions">
                            <a href="?edit=<?= (int)$row['id'] ?>">Editar</a>
                            <form method="post" onsubmit="return confirm('Excluir este endereço?')">
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
