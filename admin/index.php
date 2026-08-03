<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
Auth::requireLogin();

$requiredV2Tables = ['age_categories', 'locations'];
$missingV2Tables = [];
$tableCheck = $db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
foreach ($requiredV2Tables as $requiredTable) {
    $tableCheck->execute([$requiredTable]);
    if ((int)$tableCheck->fetchColumn() === 0) {
        $missingV2Tables[] = $requiredTable;
    }
}

if ($missingV2Tables) {
    require __DIR__ . '/_header.php';
    ?>
    <div class="page-title"><div><h1>Atualização necessária</h1><p>Os arquivos da V2 já foram aplicados, mas o banco ainda precisa ser atualizado.</p></div></div>
    <div class="panel">
        <h2>Concluir atualização TAF V2</h2>
        <p>Clique no botão abaixo para criar as categorias Sub, os múltiplos endereços e migrar os dados antigos.</p>
        <a class="btn primary" href="../upgrade_v2.php">Abrir atualização do banco</a>
    </div>
    <?php
    require __DIR__ . '/_footer.php';
    exit;
}

if (!db_table_exists($db, 'news')) {
    require __DIR__ . '/_header.php';
    ?>
    <div class="page-title"><div><h1>Atualização V3 necessária</h1><p>O novo layout já foi aplicado, mas o banco ainda precisa receber o módulo de notícias.</p></div></div>
    <div class="panel">
        <h2>Concluir atualização TAF V3</h2>
        <p>A atualização cria o cadastro de notícias e ativa o carrossel da página inicial sem apagar os dados existentes.</p>
        <a class="btn primary" href="../upgrade_v3.php">Atualizar banco para V3</a>
    </div>
    <?php
    require __DIR__ . '/_footer.php';
    exit;
}

$counts = [];
foreach (['coaches', 'athletes', 'teams', 'competitions', 'matches', 'locations', 'age_categories', 'news'] as $table) {
    $counts[$table] = (int)$db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}
$counts['pending'] = (int)$db->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn();
$next = $db->query("SELECT * FROM matches WHERE status='agendada' ORDER BY match_date IS NULL, match_date ASC LIMIT 5")->fetchAll();

require __DIR__ . '/_header.php';
?>
<div class="page-title"><div><h1>Visão geral</h1><p>Administre todo o conteúdo da Titans Academy Futebol.</p></div></div>
<div class="stats">
<?php foreach ([
    ['Professores', $counts['coaches']],
    ['Atletas', $counts['athletes']],
    ['Notícias', $counts['news']],
    ['Categorias Sub', $counts['age_categories']],
    ['Times', $counts['teams']],
    ['Unidades', $counts['locations']],
    ['Competições', $counts['competitions']],
    ['Partidas', $counts['matches']],
    ['Comentários pendentes', $counts['pending']],
] as [$label, $value]): ?>
    <article><strong><?= $value ?></strong><span><?= e($label) ?></span></article>
<?php endforeach; ?>
</div>
<div class="panel">
    <div class="panel-head"><h2>Próximas partidas</h2><a class="btn small" href="matches.php">Gerenciar</a></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Data</th><th>Jogo</th><th>Local</th></tr></thead>
            <tbody>
            <?php if (!$next): ?><tr><td colspan="3">Nenhuma partida agendada.</td></tr><?php endif; ?>
            <?php foreach ($next as $match): ?><tr><td><?= format_date($match['match_date'], true) ?></td><td><?= e($match['home_team']) ?> x <?= e($match['away_team']) ?></td><td><?= e($match['location']) ?></td></tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
