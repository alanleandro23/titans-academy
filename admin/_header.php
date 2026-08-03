<?php
Auth::requireLogin();
$adminSettings = settings($db);
$current = basename($_SERVER['PHP_SELF']);
$flash = pull_flash();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Painel | <?= e($adminSettings['school_name']) ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>:root{--primary:<?= e($adminSettings['primary_color']) ?>;--secondary:<?= e($adminSettings['secondary_color']) ?>}</style>
</head>
<body>
<div class="admin-shell">
    <aside class="sidebar">
        <a class="admin-brand" href="index.php">
            <?php if ($adminSettings['logo_path']): ?><img src="../<?= e($adminSettings['logo_path']) ?>" alt="Logo"><?php endif; ?>
            <span><?= e($adminSettings['school_name']) ?></span>
        </a>
        <nav>
        <?php
        $links = [
            'index.php' => 'Visão geral',
            'settings.php' => 'Personalização',
            'locations.php' => 'Endereços e unidades',
            'coaches.php' => 'Professores',
            'categories.php' => 'Categorias Sub',
            'news.php' => 'Notícias',
            'athletes.php' => 'Atletas',
            'teams.php' => 'Times',
            'competitions.php' => 'Competições',
            'registrations.php' => 'Times inscritos',
            'matches.php' => 'Partidas e súmulas',
            'multimedia.php' => 'Multimídia',
            'comments.php' => 'Mural',
            'users.php' => 'Usuários',
        ];
        foreach ($links as $file => $label): ?>
            <a class="<?= $current === $file ? 'active' : '' ?>" href="<?= $file ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
            <a href="../index.php" target="_blank" rel="noopener">Abrir site</a>
            <a href="logout.php">Sair</a>
        </nav>
    </aside>
    <section class="admin-main">
        <header class="topbar">
            <button data-sidebar-button type="button" aria-label="Abrir menu">☰</button>
            <div><strong><?= e(Auth::user()['name']) ?></strong><small><?= e(Auth::user()['role']) ?></small></div>
        </header>
        <main class="admin-content">
            <?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
