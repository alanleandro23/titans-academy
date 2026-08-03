<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$siteSettings = settings($db);
$page = $_GET['page'] ?? 'inicio';
$allowed = ['inicio', 'noticia', 'sobre', 'professores', 'atletas', 'conquistas', 'agenda', 'partida', 'multimidia', 'mural', 'contato'];
if (!in_array($page, $allowed, true)) {
    http_response_code(404);
    $page = 'inicio';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'comment') {
    verify_csrf();
    $name = trim($_POST['visitor_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($message) < 5 || mb_strlen($message) > 1000) {
        flash('danger', 'Preencha nome, e-mail válido e um comentário de 5 a 1.000 caracteres.');
    } else {
        $stmt = $db->prepare('INSERT INTO comments (visitor_name, email, message) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, $message]);
        $db->prepare("UPDATE comments SET status='approved' WHERE id=?")->execute([(int)$db->lastInsertId()]);
        flash('success', 'Comentário publicado com sucesso.');
    }
    redirect('index.php?page=mural');
}

$flash = pull_flash();
$pageTitle = match ($page) {
    'noticia' => 'Notícia',
    'sobre' => 'Sobre nós',
    'professores' => 'Professores',
    'atletas' => 'Atletas',
    'conquistas' => 'Conquistas',
    'agenda' => 'Competições e partidas',
    'partida' => 'Detalhes da partida',
    'multimidia' => 'Multimídia',
    'mural' => 'Mural',
    'contato' => 'Contato',
    default => $siteSettings['school_name'] ?? 'Escolinha de Futebol',
};

require __DIR__ . '/templates/header.php';

if ($flash): ?>
    <div class="container"><div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div></div>
<?php endif; ?>

<?php if ($page === 'inicio'):
    $upcoming = $db->query("SELECT * FROM matches WHERE match_date >= NOW() ORDER BY match_date ASC, id ASC LIMIT 4")->fetchAll();
    $recentMatches = $db->query("SELECT * FROM matches WHERE match_date < NOW() ORDER BY match_date DESC, id DESC LIMIT 4")->fetchAll();
    $achievements = db_table_exists($db, 'achievements')
        ? $db->query("SELECT a.*,c.name category_name FROM achievements a LEFT JOIN age_categories c ON c.id=a.category_id WHERE a.active=1 ORDER BY COALESCE(a.achievement_year,0) DESC,a.id DESC LIMIT 3")->fetchAll()
        : [];
    $newsItems = [];
    if (db_table_exists($db, 'news')) {
        $newsItems = $db->query(
            "SELECT * FROM news
             WHERE active=1 AND published_at <= NOW()
             ORDER BY featured DESC, published_at DESC, id DESC
             LIMIT 6"
        )->fetchAll();
    }

    $homeStats = [
        ['value' => (int)$db->query("SELECT COUNT(*) FROM athletes WHERE active=1")->fetchColumn(), 'label' => 'Atletas'],
        ['value' => (int)$db->query("SELECT COUNT(*) FROM coaches WHERE active=1")->fetchColumn(), 'label' => 'Professores'],
        ['value' => (int)$db->query("SELECT COUNT(*) FROM age_categories WHERE active=1")->fetchColumn(), 'label' => 'Categorias'],
        ['value' => (int)$db->query("SELECT COUNT(*) FROM locations WHERE active=1")->fetchColumn(), 'label' => 'Unidades'],
    ];
    $nextMatch = $upcoming[0] ?? null;
?>
<section class="home-hero">
    <div class="hero-glow hero-glow-one" aria-hidden="true"></div>
    <div class="hero-glow hero-glow-two" aria-hidden="true"></div>
    <div class="container home-hero-grid">
        <div class="home-hero-copy">
            <span class="hero-kicker"><span></span> Titans Academy Futebol</span>
            <h1><?= e($siteSettings['hero_title']) ?></h1>
            <p><?= e($siteSettings['hero_text']) ?></p>
            <div class="hero-actions">
                <a class="btn primary hero-primary" href="index.php?page=contato">Quero conhecer a TAF</a>
                <a class="btn hero-secondary" href="index.php?page=atletas">Conheça os atletas</a>
            </div>
            <div class="home-stats" aria-label="Números da escolinha">
                <?php foreach ($homeStats as $stat): ?>
                    <div><strong><?= (int)$stat['value'] ?></strong><span><?= e($stat['label']) ?></span></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="home-hero-visual">
            <div class="logo-stage">
                <div class="logo-ring logo-ring-one"></div>
                <div class="logo-ring logo-ring-two"></div>
                <?php if (!empty($siteSettings['logo_path'])): ?>
                    <img src="<?= e($siteSettings['logo_path']) ?>" alt="<?= e($siteSettings['school_name']) ?>">
                <?php else: ?>
                    <span class="hero-ball">⚽</span>
                <?php endif; ?>
            </div>

            <?php if ($nextMatch): ?>
                <article class="next-match-float">
                    <span>Próximo compromisso</span>
                    <strong><?= e($nextMatch['home_team']) ?> <small>x</small> <?= e($nextMatch['away_team']) ?></strong>
                    <p><?= format_date($nextMatch['match_date'], true) ?> · <?= e($nextMatch['location'] ?: 'Local a definir') ?></p>
                </article>
            <?php else: ?>
                <article class="next-match-float">
                    <span>Formação completa</span>
                    <strong>Treino · Disciplina · Evolução</strong>
                    <p>Desenvolvimento esportivo e pessoal em todas as categorias.</p>
                </article>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="home-quick-nav">
    <div class="container quick-nav-grid">
        <a href="index.php?page=sobre"><span class="quick-icon">TAF</span><div><strong>Nossa história</strong><small>Conheça a academia</small></div><b>→</b></a>
        <a href="index.php?page=atletas"><span class="quick-icon">SUB</span><div><strong>Categorias</strong><small>Atletas por faixa</small></div><b>→</b></a>
        <a href="index.php?page=agenda"><span class="quick-icon">JOG</span><div><strong>Jogos e torneios</strong><small>Acompanhe a agenda</small></div><b>→</b></a>
        <a href="index.php?page=contato"><span class="quick-icon">LOC</span><div><strong>Nossas unidades</strong><small>Endereços e contatos</small></div><b>→</b></a>
    </div>
</section>

<section class="section news-home-section" id="noticias">
    <div class="container">
        <div class="section-head modern-section-head">
            <div><span class="eyebrow">Fique por dentro</span><h2>Últimas notícias</h2><p>Novidades, resultados e informações da Titans Academy Futebol.</p></div>
            <?php if ($newsItems): ?><span class="carousel-counter"><b data-news-current>01</b> / <?= str_pad((string)count($newsItems), 2, '0', STR_PAD_LEFT) ?></span><?php endif; ?>
        </div>

        <?php if ($newsItems): ?>
            <div class="news-carousel" data-news-carousel aria-roledescription="carrossel" aria-label="Últimas notícias">
                <div class="news-carousel-track">
                    <?php foreach ($newsItems as $index => $news): ?>
                        <article class="news-slide <?= $index === 0 ? 'is-active' : '' ?>" data-news-slide aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>">
                            <div class="news-media">
                                <?php if ($news['image_path']): ?>
                                    <img src="<?= e($news['image_path']) ?>" alt="<?= e($news['title']) ?>">
                                <?php else: ?>
                                    <div class="news-image-fallback">
                                        <?php if (!empty($siteSettings['logo_path'])): ?><img src="<?= e($siteSettings['logo_path']) ?>" alt=""><?php else: ?><span>TAF</span><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <span class="news-number"><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            </div>
                            <div class="news-copy">
                                <span class="news-date"><?= format_date($news['published_at']) ?></span>
                                <h3><?= e($news['title']) ?></h3>
                                <p><?= e($news['summary']) ?></p>
                                <a class="news-link" href="index.php?page=noticia&id=<?= (int)$news['id'] ?>">Ler notícia completa <span>→</span></a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if (count($newsItems) > 1): ?>
                    <div class="news-carousel-controls">
                        <div class="news-dots" role="tablist" aria-label="Selecionar notícia">
                            <?php foreach ($newsItems as $index => $news): ?>
                                <button class="<?= $index === 0 ? 'is-active' : '' ?>" type="button" data-news-dot="<?= $index ?>" aria-label="Ir para notícia <?= $index + 1 ?>" aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="carousel-arrows">
                            <button type="button" data-news-prev aria-label="Notícia anterior">←</button>
                            <button type="button" data-news-next aria-label="Próxima notícia">→</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="news-empty-state">
                <div><span>TAF</span></div>
                <section><h3>As notícias aparecerão aqui</h3><p>Cadastre as primeiras publicações em <strong>Painel → Notícias</strong> para ativar o carrossel da página inicial.</p></section>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section home-agenda-section">
    <div class="container">
        <div class="section-head modern-section-head">
            <div><span class="eyebrow">Em campo</span><h2>Jogos da Titans</h2><p>Veja os últimos resultados e os próximos compromissos cadastrados.</p></div>
            <a class="section-link" href="index.php?page=agenda">Agenda completa <span>→</span></a>
        </div>
        <div class="home-games-columns">
            <section class="home-games-group">
                <div class="games-group-title"><span>Resultados</span><h3>Últimos jogos</h3></div>
                <div class="home-match-grid single-column">
                    <?php if (!$recentMatches): ?><div class="home-empty-card"><strong>Nenhum resultado publicado</strong><p>Os jogos disputados aparecerão aqui.</p></div><?php endif; ?>
                    <?php foreach ($recentMatches as $match): ?>
                        <a class="home-match-card finished match-card-link" href="index.php?page=partida&id=<?= (int)$match['id'] ?>">
                            <div class="match-date-box"><strong><?= $match['match_date'] ? date('d', strtotime($match['match_date'])) : '--' ?></strong><span><?= e(month_short_pt($match['match_date'])) ?></span></div>
                            <div class="match-info">
                                <span><?= e($match['location'] ?: 'Local não informado') ?></span>
                                <div class="home-club-score">
                                    <div><?php if (!empty($match['home_logo_path'])): ?><img src="<?= e($match['home_logo_path']) ?>" alt=""><?php endif; ?><strong><?= e($match['home_team']) ?></strong><b><?= $match['score_home'] !== null ? e((string)$match['score_home']) : '-' ?></b></div>
                                    <small>x</small>
                                    <div><b><?= $match['score_away'] !== null ? e((string)$match['score_away']) : '-' ?></b><strong><?= e($match['away_team']) ?></strong><?php if (!empty($match['away_logo_path'])): ?><img src="<?= e($match['away_logo_path']) ?>" alt=""><?php endif; ?></div>
                                </div>
                                <p><?= format_date($match['match_date'], true) ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <section class="home-games-group">
                <div class="games-group-title"><span>Agenda</span><h3>Próximos jogos</h3></div>
                <div class="home-match-grid single-column">
                    <?php if (!$upcoming): ?><div class="home-empty-card"><strong>Agenda em atualização</strong><p>As próximas partidas serão publicadas em breve.</p></div><?php endif; ?>
                    <?php foreach ($upcoming as $index => $match): ?>
                        <a class="home-match-card match-card-link <?= $index === 0 ? 'featured' : '' ?>" href="index.php?page=partida&id=<?= (int)$match['id'] ?>">
                            <div class="match-date-box"><strong><?= $match['match_date'] ? date('d', strtotime($match['match_date'])) : '--' ?></strong><span><?= e(month_short_pt($match['match_date'])) ?></span></div>
                            <div class="match-info">
                                <span><?= e($match['location'] ?: 'Local a definir') ?></span>
                                <div class="home-club-score no-score">
                                    <div><?php if (!empty($match['home_logo_path'])): ?><img src="<?= e($match['home_logo_path']) ?>" alt=""><?php endif; ?><strong><?= e($match['home_team']) ?></strong></div>
                                    <small>x</small>
                                    <div><strong><?= e($match['away_team']) ?></strong><?php if (!empty($match['away_logo_path'])): ?><img src="<?= e($match['away_logo_path']) ?>" alt=""><?php endif; ?></div>
                                </div>
                                <p><?= $match['match_date'] ? date('H:i', strtotime($match['match_date'])) : 'Horário a definir' ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>
</section>

<section class="section home-achievements-section">
    <div class="container achievements-layout">
        <div class="achievements-intro">
            <span class="eyebrow light">Orgulho Titans</span>
            <h2>Conquistas que contam nossa história</h2>
            <p>Cada título representa dedicação, trabalho coletivo e evolução dentro e fora de campo.</p>
            <a class="btn hero-secondary" href="index.php?page=conquistas">Ver todas as conquistas</a>
        </div>
        <div class="achievement-list">
            <?php if (!$achievements): ?>
                <article><span class="trophy-mark">🏆</span><div><small>Em construção</small><h3>Nossas próximas conquistas começam nos treinos de hoje.</h3></div></article>
            <?php endif; ?>
            <?php foreach ($achievements as $achievement): ?>
                <article>
                    <span class="trophy-mark">🏆</span>
                    <div><small><?= e((string)($achievement['achievement_year'] ?: date('Y'))) ?><?= !empty($achievement['category_name']) ? ' • '.e($achievement['category_name']) : '' ?></small><h3><?= e($achievement['title']) ?></h3><p><?= e($achievement['placement'] ?: 'Conquista da equipe') ?></p></div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php elseif ($page === 'noticia'):
    $newsItem = null;
    if (db_table_exists($db, 'news')) {
        $stmt = $db->prepare('SELECT * FROM news WHERE id=? AND active=1 AND published_at <= NOW() LIMIT 1');
        $stmt->execute([(int)($_GET['id'] ?? 0)]);
        $newsItem = $stmt->fetch();
    }
?>
<?php if (!$newsItem): ?>
<section class="page-hero"><div class="container"><span class="eyebrow">Notícias</span><h1>Notícia não encontrada</h1></div></section>
<section class="section"><div class="container"><div class="news-empty-state"><section><h3>Esta publicação não está disponível.</h3><p>Ela pode ter sido removida ou ainda não foi publicada.</p><a class="btn primary" href="index.php#noticias">Voltar às notícias</a></section></div></div></section>
<?php else: ?>
<section class="news-detail-hero">
    <div class="container news-detail-heading"><a href="index.php#noticias">← Voltar para notícias</a><span><?= format_date($newsItem['published_at']) ?></span><h1><?= e($newsItem['title']) ?></h1><p><?= e($newsItem['summary']) ?></p></div>
</section>
<article class="section news-detail">
    <div class="container news-detail-container">
        <?php if ($newsItem['image_path']): ?><img class="news-detail-image" src="<?= e($newsItem['image_path']) ?>" alt="<?= e($newsItem['title']) ?>"><?php endif; ?>
        <div class="news-detail-body"><?= nl2br(e($newsItem['content'] ?: $newsItem['summary'])) ?></div>
    </div>
</article>
<?php endif; ?>

<?php elseif ($page === 'sobre'): ?>
<section class="page-hero about-page-hero"<?php if (!empty($siteSettings['about_banner_path'])): ?> style="background-image:linear-gradient(rgba(9,12,44,.68),rgba(9,12,44,.68)),url('<?= e($siteSettings['about_banner_path']) ?>')"<?php endif; ?>><div class="container"><span class="eyebrow">Institucional</span><h1>Sobre nós</h1></div></section>
<section class="section compact-top"><div class="container prose"><p><?= nl2br(e($siteSettings['about_text'])) ?></p></div></section>

<?php elseif ($page === 'professores'):
    $rows = $db->query('SELECT * FROM coaches WHERE active=1 ORDER BY sort_order, name')->fetchAll();
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Equipe técnica</span><h1>Professores</h1></div></section>
<section class="section">
    <div class="container cards people">
        <?php if (!$rows): ?><p class="empty">Nenhum professor cadastrado.</p><?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <article class="card person">
                <?php if ($row['photo_path']): ?><img src="<?= e($row['photo_path']) ?>" alt="<?= e($row['name']) ?>"><?php else: ?><div class="photo-placeholder">FT</div><?php endif; ?>
                <h3><?= e($row['name']) ?></h3>
                <span class="tag"><?= e($row['role_title']) ?></span>
                <p><?= e($row['bio']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php elseif ($page === 'atletas'):
    $athleteSearch = trim($_GET['q'] ?? '');
    $selectedCategory = (int)($_GET['category'] ?? 0);

    $categoryCounts = $db->query(
        'SELECT c.id, c.name, c.sort_order, COUNT(a.id) AS total
         FROM age_categories c
         LEFT JOIN athletes a ON a.category_id = c.id AND a.active = 1
         WHERE c.active = 1
         GROUP BY c.id, c.name, c.sort_order
         ORDER BY c.sort_order, c.name'
    )->fetchAll();

    $conditions = ['a.active = 1'];
    $parameters = [];
    if ($selectedCategory > 0) {
        $conditions[] = 'a.category_id = ?';
        $parameters[] = $selectedCategory;
    }
    if ($athleteSearch !== '') {
        $conditions[] = '(a.name LIKE ? OR a.position_name LIKE ?)';
        $like = '%' . $athleteSearch . '%';
        $parameters[] = $like;
        $parameters[] = $like;
    }

    $stmt = $db->prepare(
        "SELECT a.*, COALESCE(c.name, NULLIF(a.category, ''), 'Sem categoria') AS category_name
         FROM athletes a
         LEFT JOIN age_categories c ON c.id = a.category_id
         WHERE " . implode(' AND ', $conditions) . "
         ORDER BY COALESCE(c.sort_order, 9999), category_name, a.name"
    );
    $stmt->execute($parameters);
    $rows = $stmt->fetchAll();
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Elenco</span><h1>Atletas</h1></div></section>
<section class="section">
    <div class="container">
        <form class="athlete-search" method="get">
            <input type="hidden" name="page" value="atletas">
            <?php if ($selectedCategory > 0): ?><input type="hidden" name="category" value="<?= $selectedCategory ?>"><?php endif; ?>
            <label>
                <span>Buscar atleta</span>
                <input type="search" name="q" value="<?= e($athleteSearch) ?>" placeholder="Digite o nome ou a posição">
            </label>
            <button class="btn primary" type="submit">Pesquisar</button>
            <?php if ($athleteSearch !== ''): ?><a class="btn" href="index.php?page=atletas<?= $selectedCategory ? '&category=' . $selectedCategory : '' ?>">Limpar busca</a><?php endif; ?>
        </form>

        <div class="public-category-tabs" role="navigation" aria-label="Categorias de atletas">
            <a class="<?= $selectedCategory === 0 ? 'active' : '' ?>" href="index.php?page=atletas<?= $athleteSearch !== '' ? '&q=' . urlencode($athleteSearch) : '' ?>">Todos</a>
            <?php foreach ($categoryCounts as $category): ?>
                <a class="<?= $selectedCategory === (int)$category['id'] ? 'active' : '' ?>" href="index.php?page=atletas&category=<?= (int)$category['id'] ?><?= $athleteSearch !== '' ? '&q=' . urlencode($athleteSearch) : '' ?>">
                    <?= e($category['name']) ?> <span><?= (int)$category['total'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="cards people">
            <?php if (!$rows): ?><p class="empty">Nenhum atleta encontrado.</p><?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <article class="card person">
                    <?php if ($row['photo_path']): ?><img src="<?= e($row['photo_path']) ?>" alt="<?= e($row['name']) ?>"><?php else: ?><div class="photo-placeholder"><?= e((string)($row['shirt_number'] ?: 'AT')) ?></div><?php endif; ?>
                    <h3><?= e($row['name']) ?></h3>
                    <p><strong><?= e($row['category_name']) ?></strong><?php if ($row['position_name']): ?> • <?= e($row['position_name']) ?><?php endif; ?></p>
                    <p><?= e($row['bio']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php elseif ($page === 'conquistas'):
    $rows = $db->query('SELECT a.*,c.name category_name FROM achievements a LEFT JOIN age_categories c ON c.id=a.category_id WHERE a.active=1 ORDER BY a.achievement_year DESC,a.id DESC')->fetchAll();
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Galeria de títulos</span><h1>Conquistas</h1></div></section>
<section class="section">
    <div class="container timeline">
        <?php if (!$rows): ?><p class="empty">Nenhuma conquista cadastrada.</p><?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <article class="timeline-item">
                <?php if ($row['image_path']): ?><img src="<?= e($row['image_path']) ?>" alt="<?= e($row['title']) ?>"><?php endif; ?>
                <div>
                    <span class="tag"><?= e((string)$row['achievement_year']) ?><?= $row['category_name'] ? ' • '.e($row['category_name']) : '' ?></span>
                    <h2><?= e($row['title']) ?></h2>
                    <strong><?= e($row['placement']) ?></strong>
                    <p><?= e($row['description']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php elseif ($page === 'agenda'):
    $competitions = $db->query("SELECT * FROM competitions WHERE is_achievement=0 ORDER BY FIELD(status,'andamento','proximo','concluido'), start_date IS NULL, start_date DESC")->fetchAll();
    $registeredRows = $db->query(
        "SELECT ct.competition_id, ct.group_name, ct.registration_notes, t.name,
                COALESCE(c.name, NULLIF(t.category, ''), 'Sem categoria') AS category_name
         FROM competition_teams ct
         JOIN teams t ON t.id = ct.team_id
         LEFT JOIN age_categories c ON c.id = t.category_id
         WHERE t.active = 1
         ORDER BY t.name"
    )->fetchAll();
    $registered = [];
    foreach ($registeredRows as $teamRow) {
        $registered[(int)$teamRow['competition_id']][] = $teamRow;
    }
    $matches = $db->query('SELECT m.*, c.name competition_name FROM matches m LEFT JOIN competitions c ON c.id=m.competition_id ORDER BY m.match_date IS NULL, m.match_date DESC')->fetchAll();
?>
<section class="page-hero competitions-page-hero"><div class="container"><span class="eyebrow">Competições</span><h1>Torneios, campeonatos e amistosos</h1></div></section>
<section class="section">
    <div class="container">
        <h2>Competições</h2>
        <div class="cards">
            <?php if (!$competitions): ?><p class="empty">Nenhuma competição cadastrada.</p><?php endif; ?>
            <?php foreach ($competitions as $competition): ?>
                <article class="card">
                    <span class="tag"><?= e(ucfirst($competition['competition_type'])) ?> • <?= e($competition['status']) ?></span>
                    <h3><?= e($competition['name']) ?></h3>
                    <p><?= format_date($competition['start_date']) ?><?php if ($competition['end_date']): ?> a <?= format_date($competition['end_date']) ?><?php endif; ?></p>
                    <p><?= e($competition['location']) ?></p>
                    <p><?= e($competition['description']) ?></p>
                    <?php if (!empty($registered[(int)$competition['id']])): ?>
                        <div class="registered-teams">
                            <strong>Times participantes</strong>
                            <?php foreach ($registered[(int)$competition['id']] as $team): ?>
                                <div>
                                    <span><?= e($team['name']) ?></span>
                                    <small><?= e(trim($team['category_name'] . ($team['group_name'] ? ' • ' . $team['group_name'] : ''))) ?></small>
                                    <?php if ($team['registration_notes']): ?><p><?= e($team['registration_notes']) ?></p><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <h2 class="mt">Partidas</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Data</th><th>Confronto</th><th>Competição</th><th>Local</th></tr></thead>
                <tbody>
                <?php if (!$matches): ?><tr><td colspan="4">Nenhuma partida cadastrada.</td></tr><?php endif; ?>
                <?php foreach ($matches as $match): ?>
                    <tr>
                        <td><?= format_date($match['match_date'], true) ?></td>
                        <td><a class="public-score-line match-detail-link" href="index.php?page=partida&id=<?= (int)$match['id'] ?>"><span><?php if (!empty($match['home_logo_path'])): ?><img src="<?= e($match['home_logo_path']) ?>" alt=""><?php endif; ?><?= e($match['home_team']) ?> <b><?= $match['score_home'] !== null ? e((string)$match['score_home']) : '' ?></b></span><em>x</em><span><b><?= $match['score_away'] !== null ? e((string)$match['score_away']) : '' ?></b> <?= e($match['away_team']) ?><?php if (!empty($match['away_logo_path'])): ?><img src="<?= e($match['away_logo_path']) ?>" alt=""><?php endif; ?></span></a></td>
                        <td><a class="match-detail-text" href="index.php?page=partida&id=<?= (int)$match['id'] ?>"><?= e($match['competition_name']) ?></a></td>
                        <td><?= e($match['location']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>


<?php elseif ($page === 'partida'):
    $matchId=(int)($_GET['id']??0);$match=null;$matchRoster=['home'=>[],'away'=>[]];$matchEvents=['home'=>[],'away'=>[]];
    if($matchId>0){$st=$db->prepare('SELECT m.*,c.name competition_name FROM matches m LEFT JOIN competitions c ON c.id=m.competition_id WHERE m.id=? LIMIT 1');$st->execute([$matchId]);$match=$st->fetch()?:null;}
    if($match && db_table_exists($db,'match_roster')){$st=$db->prepare('SELECT * FROM match_roster WHERE match_id=? ORDER BY team_side,person_type DESC,id');$st->execute([$matchId]);foreach($st->fetchAll() as $r)$matchRoster[$r['team_side']][]=$r;}
    if($match && db_table_exists($db,'match_events')){$st=$db->prepare('SELECT me.*,COALESCE(a.name,me.participant_name) athlete_name,COALESCE(aa.name,me.assist_name) assist_display FROM match_events me LEFT JOIN athletes a ON a.id=me.athlete_id LEFT JOIN athletes aa ON aa.id=me.assist_athlete_id WHERE me.match_id=? ORDER BY COALESCE(me.minute_mark,999),me.id');$st->execute([$matchId]);foreach($st->fetchAll() as $ev)$matchEvents[$ev['team_side']][]=$ev;}
    $eventIcon=['gol'=>'●','assistencia'=>'👟','cartao_amarelo'=>'▮','cartao_vermelho'=>'▮','substituicao_entrada'=>'↥','substituicao_saida'=>'↧'];
?>
<?php if(!$match): ?>
<section class="page-hero"><div class="container"><span class="eyebrow">Partidas</span><h1>Partida não encontrada</h1></div></section>
<section class="section"><div class="container"><a class="btn primary" href="index.php?page=agenda">Voltar às partidas</a></div></section>
<?php else: ?>
<section class="page-hero match-detail-hero"><div class="container"><span class="eyebrow"><?=e($match['competition_name']?:ucfirst(str_replace('_',' ',$match['match_type'])))?></span><h1>Detalhes da partida</h1><p><?=format_date($match['match_date'],true)?> · <?=e($match['location']?:'Local não informado')?></p></div></section>
<section class="section match-detail-section"><div class="container">
<a class="match-back" href="index.php?page=agenda">← Voltar para competições e partidas</a>
<div class="match-scoreboard">
<div class="match-team home"><?php if($match['home_logo_path']):?><img src="<?=e($match['home_logo_path'])?>" alt=""><?php endif;?><h2><?=e($match['home_team'])?></h2><span>Técnico: <?=e($match['home_coach_name']?:'Não informado')?></span></div>
<div class="match-score-main"><b><?= $match['score_home']!==null?e((string)$match['score_home']):'-' ?></b><i>x</i><b><?= $match['score_away']!==null?e((string)$match['score_away']):'-' ?></b><small><?=e(ucfirst($match['status']))?></small></div>
<div class="match-team away"><?php if($match['away_logo_path']):?><img src="<?=e($match['away_logo_path'])?>" alt=""><?php endif;?><h2><?=e($match['away_team'])?></h2><span>Técnico: <?=e($match['away_coach_name']?:'Não informado')?></span></div>
</div>
<?php if(trim((string)$match['notes'])!==''):?><article class="match-summary"><span class="eyebrow">Resumo da partida</span><p><?=nl2br(e($match['notes']))?></p></article><?php endif;?>
<div class="match-detail-grid">
<?php foreach(['home','away'] as $side): $teamName=$side==='home'?$match['home_team']:$match['away_team']; ?>
<section class="match-lineup"><h3><?=e($teamName)?></h3><div class="lineup-columns"><div><h4>Relacionados</h4><div class="lineup-vertical"><?php $has=false;foreach($matchRoster[$side] as $r):if($r['person_type']!=='atleta' || (int)$r['starter']!==1)continue;$has=true;?><span><?=e($r['person_name'])?></span><?php endforeach;?><?php if(!$has):?><small>Nenhum relacionado.</small><?php endif;?></div></div><div><h4>Reservas</h4><div class="lineup-vertical reserves"><?php $hasReserve=false;foreach($matchRoster[$side] as $r):if($r['person_type']!=='atleta' || (int)$r['starter']!==0)continue;$hasReserve=true;?><span><?=e($r['person_name'])?></span><?php endforeach;?><?php if(!$hasReserve):?><small>Nenhum reserva.</small><?php endif;?></div></div></div>
<h4>Lances da partida</h4><div class="event-list"><?php if(!$matchEvents[$side]):?><small>Nenhum lance registrado.</small><?php endif;?><?php foreach($matchEvents[$side] as $ev):$type=$ev['event_type'];?><div class="match-event type-<?=e($type)?>"><span class="event-minute"><?=e(($ev['period']??'1')==='2'?'2T ': (($ev['period']??'1')==='extra'?'PR ':'1T '))?><?= $ev['minute_mark']!==null?e((string)$ev['minute_mark'])."'":'—' ?></span><span class="event-symbol"><?= $eventIcon[$type]??'•' ?></span><div><strong><?=e($ev['athlete_name']?:'Atleta não informado')?></strong><?php if($type==='gol' && $ev['assist_display']):?><small>Assistência: <?=e($ev['assist_display'])?></small><?php elseif($type==='assistencia'):?><small>Assistência</small><?php elseif(str_starts_with($type,'substituicao_')):?><small><?= $type==='substituicao_entrada'?'Entrou em campo':'Saiu de campo' ?><?= $ev['assist_display']?' · '.e($ev['assist_display']):'' ?></small><?php elseif($ev['notes']):?><small><?=e($ev['notes'])?></small><?php endif;?></div></div><?php endforeach;?></div></section>
<?php endforeach;?></div>
<div class="match-legend"><span><b class="goal-dot"></b> Gol</span><span>👟 Assistência</span><span><b class="yellow-card"></b> Cartão amarelo</span><span><b class="red-card"></b> Cartão vermelho</span><span>↥ Entrada</span><span>↧ Saída</span></div>
</div></section>
<?php endif; ?>

<?php elseif ($page === 'multimidia'):
    $mediaType = $_GET['tipo'] ?? 'todos';
    $allowedMedia = ['todos','wallpaper','foto','video'];
    if (!in_array($mediaType,$allowedMedia,true)) $mediaType='todos';
    $sql = "SELECT m.*,c.name category_name FROM media m LEFT JOIN age_categories c ON c.id=m.category_id WHERE m.active=1";
    $params=[]; if($mediaType!=='todos'){ $sql.=' AND m.media_type=?'; $params[]=$mediaType; }
    $sql.=' ORDER BY m.event_date DESC,m.id DESC'; $st=$db->prepare($sql);$st->execute($params);$mediaRows=$st->fetchAll();
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Titans em imagens</span><h1>Multimídia</h1></div></section>
<section class="section compact-top"><div class="container"><div class="public-category-tabs"><?php foreach(['todos'=>'Tudo','wallpaper'=>'Wallpapers','foto'=>'Fotos','video'=>'Vídeos'] as $k=>$l):?><a class="<?=$mediaType===$k?'active':''?>" href="index.php?page=multimidia&tipo=<?=$k?>"><?=$l?></a><?php endforeach;?></div><div class="media-grid"><?php if(!$mediaRows):?><p class="empty">Nenhuma mídia publicada.</p><?php endif;?><?php foreach($mediaRows as $m):?><article class="media-card"><?php if($m['media_type']==='video' && $m['video_url']):?><a class="video-cover" href="<?=e($m['video_url'])?>" target="_blank" rel="noopener"><?php if($m['file_path']):?><img src="<?=e($m['file_path'])?>" alt="<?=e($m['title'])?>"><?php else:?><span>▶</span><?php endif;?></a><?php elseif($m['file_path']):?><a href="<?=e($m['file_path'])?>" target="_blank"><img src="<?=e($m['file_path'])?>" alt="<?=e($m['title'])?>"></a><?php endif;?><div><span class="tag"><?=e(ucfirst($m['media_type']))?></span><h3><?=e($m['title'])?></h3><p><?=e($m['description'])?></p><small><?=e($m['category_name']??'Todas as categorias')?> • <?=format_date($m['event_date'])?></small></div></article><?php endforeach;?></div></div></section>

<?php elseif ($page === 'mural'):
    $comments = $db->query("SELECT * FROM comments WHERE status='approved' ORDER BY created_at DESC LIMIT 50")->fetchAll();
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Comunidade</span><h1>Mural de comentários</h1></div></section>
<section class="section">
    <div class="container two-columns">
        <div>
            <h2>Comentários publicados</h2>
            <div class="comments">
                <?php if (!$comments): ?><p class="empty">Seja a primeira pessoa a deixar uma mensagem.</p><?php endif; ?>
                <?php foreach ($comments as $comment): ?>
                    <blockquote><p>“<?= e($comment['message']) ?>”</p><footer><?= e($comment['visitor_name']) ?> • <?= format_date($comment['created_at']) ?></footer></blockquote>
                <?php endforeach; ?>
            </div>
        </div>
        <aside class="form-card">
            <h2>Deixe sua mensagem</h2>
            <p>Nome e e-mail são obrigatórios. A mensagem será publicada imediatamente.</p>
            <form method="post">
                <input type="hidden" name="action" value="comment">
                <?= csrf_field() ?>
                <label>Nome<input name="visitor_name" maxlength="120" required></label>
                <label>E-mail<input name="email" type="email" maxlength="190" required></label>
                <label>Comentário<textarea name="message" rows="6" maxlength="1000" required></textarea></label>
                <button class="btn primary" type="submit">Publicar mensagem</button>
            </form>
        </aside>
    </div>
</section>

<?php elseif ($page === 'contato'):
    $socialLinks = db_table_exists($db, 'social_links') ? $db->query("SELECT * FROM social_links WHERE active=1 ORDER BY sort_order,id")->fetchAll() : [];
    $locations = $db->query('SELECT * FROM locations WHERE active=1 ORDER BY sort_order, unit_name')->fetchAll();
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Fale conosco</span><h1>Contato e unidades</h1></div></section>
<section class="section">
    <div class="container">
        <div class="section-head"><div><span class="eyebrow">Locais de treino</span><h2>Nossas unidades</h2></div></div>
        <div class="location-grid">
            <?php if (!$locations && !empty($siteSettings['address'])): ?>
                <article class="card location-card"><span class="tag">Unidade principal</span><h3><?= e($siteSettings['school_name']) ?></h3><p><?= nl2br(e($siteSettings['address'])) ?></p></article>
            <?php elseif (!$locations): ?>
                <p class="empty">Os endereços das unidades serão publicados em breve.</p>
            <?php endif; ?>
            <?php foreach ($locations as $location): ?>
                <article class="card location-card">
                    <span class="tag">Unidade</span>
                    <h3><?= e($location['unit_name']) ?></h3>
                    <p class="location-address">
                        <?= e($location['address_line']) ?>
                        <?php if ($location['neighborhood']): ?><br><?= e($location['neighborhood']) ?><?php endif; ?>
                        <?php if ($location['city'] || $location['state']): ?><br><?= e(trim($location['city'] . ' - ' . $location['state'], ' -')) ?><?php endif; ?>
                        <?php if ($location['postal_code']): ?><br>CEP: <?= e($location['postal_code']) ?><?php endif; ?>
                    </p>
                    <?php if ($location['training_info']): ?><p><?= nl2br(e($location['training_info'])) ?></p><?php endif; ?>
                    <?php if ($location['phone']): ?><p><strong>Telefone:</strong> <?= e($location['phone']) ?></p><?php endif; ?>
                    <?php if ($location['whatsapp']): ?><p><strong>WhatsApp:</strong> <?= e($location['whatsapp']) ?></p><?php endif; ?>
                    <?php if ($location['map_url']): ?><a class="btn" href="<?= e($location['map_url']) ?>" target="_blank" rel="noopener">Abrir no mapa</a><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="contact-summary">
            <article class="card"><h2>Contato geral</h2><p><?= e($siteSettings['phone']) ?></p><p><?= e($siteSettings['whatsapp']) ?></p></article>
            <article class="card"><h2>E-mail</h2><p><?= e($siteSettings['email']) ?></p></article>
            <article class="card"><h2>Redes sociais</h2><?php if (!$socialLinks): ?><p>Redes sociais em atualização.</p><?php endif; ?><?php foreach ($socialLinks as $social): ?><p><a class="text-link" href="<?= e($social['url']) ?>" target="_blank" rel="noopener"><?= e($social['title']) ?></a></p><?php endforeach; ?></article>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/templates/footer.php'; ?>
