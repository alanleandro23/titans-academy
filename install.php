<?php
declare(strict_types=1);

session_start();
$alreadyInstalled = file_exists(__DIR__ . '/config/config.php');
$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyInstalled) {
    $host = trim($_POST['db_host'] ?? 'localhost');
    $port = (int)($_POST['db_port'] ?? 3306);
    $name = trim($_POST['db_name'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    $pass = (string)($_POST['db_pass'] ?? '');
    $adminName = trim($_POST['admin_name'] ?? 'Administrador');
    $adminEmail = mb_strtolower(trim($_POST['admin_email'] ?? ''));
    $adminPassword = (string)($_POST['admin_password'] ?? '');

    try {
        if (!$name || !$user || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPassword) < 8) {
            throw new RuntimeException('Preencha o banco, um e-mail válido e uma senha com pelo menos 8 caracteres.');
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $sql = file_get_contents(__DIR__ . '/database/schema.sql');
        foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql))) as $statement) {
            $pdo->exec($statement);
        }

        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, active) VALUES (?, ?, ?, "admin", 1)');
        $stmt->execute([$adminName, $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT)]);

        $config = "<?php\nreturn " . var_export([
            'db' => [
                'host' => $host,
                'port' => $port,
                'name' => $name,
                'user' => $user,
                'pass' => $pass,
                'charset' => 'utf8mb4',
            ],
            'app' => [
                'base_url' => '',
                'timezone' => 'America/Sao_Paulo',
            ],
        ], true) . ";\n";

        if (file_put_contents(__DIR__ . '/config/config.php', $config, LOCK_EX) === false) {
            throw new RuntimeException('O banco foi criado, mas não foi possível gravar config/config.php. Ajuste a permissão da pasta config.');
        }

        $success = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Instalação | Titans Academy Futebol</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="installer-page">
<main class="installer-card">
<h1>Instalar o portal da Titans Academy Futebol</h1>
<p>Crie o banco no cPanel antes de continuar.</p>
<?php if ($alreadyInstalled): ?>
<div class="alert success">O sistema já está instalado. <a href="admin/">Acessar o painel</a>.</div>
<?php elseif ($success): ?>
<div class="alert success">Instalação concluída. <a href="admin/">Entrar no painel administrativo</a>.</div>
<?php else: ?>
<?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" class="form-grid">
<h2>Banco de dados</h2>
<label>Servidor<input name="db_host" value="localhost" required></label>
<label>Porta<input name="db_port" type="number" value="3306" required></label>
<label>Nome do banco<input name="db_name" required></label>
<label>Usuário do banco<input name="db_user" required></label>
<label>Senha do banco<input name="db_pass" type="password"></label>
<h2>Administrador</h2>
<label>Nome<input name="admin_name" value="Administrador" required></label>
<label>E-mail<input name="admin_email" type="email" required></label>
<label>Senha<input name="admin_password" type="password" minlength="8" required></label>
<button class="btn primary" type="submit">Instalar sistema</button>
</form>
<?php endif; ?>
</main>
</body>
</html>
