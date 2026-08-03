<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $action = $_POST['action'] ?? 'save_settings';
        if ($action === 'save_social') {
            $id = (int)($_POST['social_id'] ?? 0);
            $platform = trim($_POST['platform'] ?? 'outra');
            $title = trim($_POST['social_title'] ?? '');
            $url = trim($_POST['social_url'] ?? '');
            $sort = (int)($_POST['sort_order'] ?? 0);
            if ($title === '' || !filter_var($url, FILTER_VALIDATE_URL)) throw new RuntimeException('Informe um título e um link válido para a rede social.');
            if ($id) { $db->prepare('UPDATE social_links SET platform=?,title=?,url=?,sort_order=? WHERE id=?')->execute([$platform,$title,$url,$sort,$id]); }
            else { $db->prepare('INSERT INTO social_links(platform,title,url,sort_order) VALUES(?,?,?,?)')->execute([$platform,$title,$url,$sort]); }
            flash('success','Rede social salva.'); redirect('settings.php#redes-sociais');
        }
        if ($action === 'delete_social') {
            $db->prepare('DELETE FROM social_links WHERE id=?')->execute([(int)($_POST['social_id'] ?? 0)]);
            flash('success','Rede social removida.'); redirect('settings.php#redes-sociais');
        }
        $current = settings($db);
        $logo = upload_image('logo_path', $current['logo_path'] ?? null);
        $aboutBanner = upload_image('about_banner_path', $current['about_banner_path'] ?? null);

        if (isset($_POST['use_taf_logo'])) {
            $logo = 'assets/images/logo-titans-v3.png';
        }

        $stmt = $db->prepare(
            'UPDATE settings SET school_name=?, slogan=?, hero_title=?, hero_text=?, about_text=?, about_banner_path=?, logo_path=?,
             primary_color=?, secondary_color=?, phone=?, whatsapp=?, email=?, instagram=?, facebook=?, footer_text=?
             WHERE id=1'
        );
        $stmt->execute([
            trim($_POST['school_name'] ?? ''),
            trim($_POST['slogan'] ?? ''),
            trim($_POST['hero_title'] ?? ''),
            trim($_POST['hero_text'] ?? ''),
            trim($_POST['about_text'] ?? ''),
            $aboutBanner,
            $logo,
            trim($_POST['primary_color'] ?? '#25275e'),
            trim($_POST['secondary_color'] ?? '#27a9d6'),
            trim($_POST['phone'] ?? ''),
            trim($_POST['whatsapp'] ?? ''),
            trim($_POST['email'] ?? ''),
            trim($_POST['instagram'] ?? ''),
            trim($_POST['facebook'] ?? ''),
            trim($_POST['footer_text'] ?? ''),
        ]);

        flash('success', 'Personalização atualizada.');
        redirect('settings.php');
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
        redirect('settings.php');
    }
}

$s = settings($db);
$locationCount = (int)$db->query('SELECT COUNT(*) FROM locations')->fetchColumn();
require __DIR__ . '/_header.php';
?>
<div class="page-title">
    <div>
        <h1>Personalização</h1>
        <p>Altere a identidade visual, os textos e os contatos gerais da Titans Academy Futebol.</p>
    </div>
</div>

<div class="panel location-callout">
    <div>
        <strong>Endereços agora são cadastrados por unidade</strong>
        <p>Você pode cadastrar quantos locais de treino forem necessários. Atualmente há <?= $locationCount ?> endereço(s).</p>
    </div>
    <a class="btn primary" href="locations.php">Gerenciar endereços</a>
</div>

<form method="post" enctype="multipart/form-data" class="panel form-grid">
    <?= csrf_field() ?><input type="hidden" name="action" value="save_settings">

    <label>Nome da escolinha
        <input name="school_name" value="<?= e($s['school_name']) ?>" required>
    </label>
    <label>Slogan
        <input name="slogan" value="<?= e($s['slogan']) ?>">
    </label>
    <label class="full">Título principal
        <input name="hero_title" value="<?= e($s['hero_title']) ?>">
    </label>
    <label class="full">Texto principal
        <textarea name="hero_text" rows="3"><?= e($s['hero_text']) ?></textarea>
    </label>
    <label class="full">Sobre a escolinha
        <textarea name="about_text" rows="8"><?= e($s['about_text']) ?></textarea>
    </label>

    <label class="full">Banner da página Sobre
        <input type="file" name="about_banner_path" accept="image/jpeg,image/png,image/webp">
        <?php if (!empty($s['about_banner_path'])): ?><img class="settings-banner-preview" src="../<?= e($s['about_banner_path']) ?>" alt="Banner atual"><?php endif; ?>
    </label>

    <label>Enviar outra logo
        <input type="file" name="logo_path" accept="image/jpeg,image/png,image/webp">
    </label>
    <div class="logo-settings-preview">
        <?php if ($s['logo_path']): ?><img class="preview-logo" src="../<?= e($s['logo_path']) ?>" alt="Logo atual"><?php endif; ?>
        <label class="check"><input type="checkbox" name="use_taf_logo"> Usar a logo Titans incluída na V3</label>
    </div>

    <label>Cor principal
        <input type="color" name="primary_color" value="<?= e($s['primary_color']) ?>">
    </label>
    <label>Cor secundária
        <input type="color" name="secondary_color" value="<?= e($s['secondary_color']) ?>">
    </label>
    <label>Telefone geral
        <input name="phone" value="<?= e($s['phone']) ?>">
    </label>
    <label>WhatsApp geral
        <input name="whatsapp" value="<?= e($s['whatsapp']) ?>">
    </label>
    <label>E-mail
        <input type="email" name="email" value="<?= e($s['email']) ?>">
    </label>
    <label class="full">Texto do rodapé
        <input name="footer_text" value="<?= e($s['footer_text']) ?>">
    </label>

    <button class="btn primary" type="submit">Salvar alterações</button>
</form>

<?php
$socialRows = db_table_exists($db, 'social_links') ? $db->query('SELECT * FROM social_links ORDER BY sort_order,id')->fetchAll() : [];
$socialEdit = null;
if (!empty($_GET['social_edit'])) { foreach ($socialRows as $r) if ((int)$r['id'] === (int)$_GET['social_edit']) $socialEdit = $r; }
?>
<section class="panel social-settings" id="redes-sociais">
    <div class="page-title compact"><div><h2>Redes sociais</h2><p>Cadastre Instagram, TikTok, YouTube, X ou qualquer outra plataforma, com título personalizado e link.</p></div></div>
    <div class="social-settings-grid">
        <form method="post" class="social-editor">
            <?= csrf_field() ?><input type="hidden" name="action" value="save_social"><input type="hidden" name="social_id" value="<?= (int)($socialEdit['id'] ?? 0) ?>">
            <label>Plataforma<select name="platform"><?php foreach(['instagram'=>'Instagram','tiktok'=>'TikTok','youtube'=>'YouTube','x'=>'X / Twitter','facebook'=>'Facebook','linkedin'=>'LinkedIn','outra'=>'Outra'] as $v=>$l): ?><option value="<?= $v ?>" <?= ($socialEdit['platform'] ?? '')===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></label>
            <label>Título exibido<input name="social_title" value="<?= e($socialEdit['title'] ?? '') ?>" placeholder="Ex.: Instagram oficial" required></label>
            <label class="full">Link<input type="url" name="social_url" value="<?= e($socialEdit['url'] ?? '') ?>" placeholder="https://..." required></label>
            <label>Ordem<input type="number" name="sort_order" min="0" value="<?= (int)($socialEdit['sort_order'] ?? 0) ?>"></label>
            <div class="social-actions"><button class="btn primary" type="submit"><?= $socialEdit ? 'Salvar alteração' : 'Adicionar rede' ?></button><?php if($socialEdit): ?><a class="btn" href="settings.php#redes-sociais">Cancelar</a><?php endif; ?></div>
        </form>
        <div class="social-list">
            <?php if (!$socialRows): ?><p class="empty">Nenhuma rede social cadastrada.</p><?php endif; ?>
            <?php foreach($socialRows as $social): ?><article class="social-item"><div class="social-icon"><?= e(strtoupper(substr($social['platform'],0,2))) ?></div><div class="social-info"><span class="social-platform"><?= e(strtoupper($social['platform'])) ?></span><strong><?= e($social['title']) ?></strong><a href="<?=e($social['url'])?>" target="_blank" rel="noopener"><?= e($social['url']) ?></a></div><div class="social-item-actions"><a class="btn small" href="settings.php?social_edit=<?= (int)$social['id'] ?>#redes-sociais">Editar</a><form method="post" onsubmit="return confirm('Remover esta rede social?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete_social"><input type="hidden" name="social_id" value="<?= (int)$social['id'] ?>"><button class="btn danger small">Excluir</button></form></div></article><?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
