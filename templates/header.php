<?php
$s = $siteSettings;
$currentPage = $page ?? 'inicio';
$navItems = [
    'inicio' => ['Início', 'index.php'],
    'sobre' => ['Sobre nós', 'index.php?page=sobre'],
    'professores' => ['Professores', 'index.php?page=professores'],
    'atletas' => ['Atletas', 'index.php?page=atletas'],
    'conquistas' => ['Conquistas', 'index.php?page=conquistas'],
    'agenda' => ['Competições', 'index.php?page=agenda'],
    'multimidia' => ['Multimídia', 'index.php?page=multimidia'],
    'mural' => ['Mural', 'index.php?page=mural'],
    'contato' => ['Contato', 'index.php?page=contato'],
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="<?= e($s['slogan'] ?? '') ?>">
<meta name="theme-color" content="<?= e($s['primary_color'] ?? '#171b52') ?>">
<title><?= e($pageTitle ?? $s['school_name']) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
<style>:root{--primary:<?= e($s['primary_color'] ?? '#171b52') ?>;--secondary:<?= e($s['secondary_color'] ?? '#25a9d6') ?>}</style>
</head>
<body class="page-<?= e($currentPage) ?>">
<header class="site-header">
<div class="container nav-wrap">
<a class="brand" href="index.php" aria-label="Página inicial da <?= e($s['school_name']) ?>">
<?php if (!empty($s['logo_path'])): ?><img src="<?= e($s['logo_path']) ?>" alt="Logo <?= e($s['school_name']) ?>"><?php endif; ?>
<span><strong><?= e($s['school_name']) ?></strong><small><?= e($s['slogan']) ?></small></span>
</a>
<button class="menu-button" type="button" aria-label="Abrir menu" aria-expanded="false" data-menu-button><span></span><span></span><span></span></button>
<nav data-menu aria-label="Navegação principal">
<?php foreach ($navItems as $key => [$label, $url]): ?>
<a class="<?= $currentPage === $key ? 'active' : '' ?>" href="<?= e($url) ?>"><?= e($label) ?></a>
<?php endforeach; ?>
<a class="nav-cta" href="index.php?page=contato">Faça parte</a>
</nav>
</div>
</header>
<main>
