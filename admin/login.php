<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
if (Auth::check()) redirect('index.php');
$error=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    if(Auth::attempt($db,(string)($_POST['email']??''),(string)($_POST['password']??''))){redirect('index.php');}
    $error='E-mail ou senha inválidos.';
}
$s=settings($db);
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Entrar no painel</title><link rel="stylesheet" href="../assets/css/admin.css"><style>:root{--primary:<?=e($s['primary_color'])?>}</style></head><body class="login-page"><form method="post" class="login-card"><?php if($s['logo_path']):?><img src="../<?=e($s['logo_path'])?>" alt="Logo"><?php endif;?><h1>Painel administrativo</h1><p><?=e($s['school_name'])?></p><?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?><?=csrf_field()?><label>E-mail<input type="email" name="email" required autofocus></label><label>Senha<input type="password" name="password" required></label><button class="btn primary" type="submit">Entrar</button><a href="../index.php">Voltar ao site</a></form></body></html>
