<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Sessão expirada ou formulário inválido. Atualize a página e tente novamente.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function pull_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function settings(PDO $db): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }
    $row = $db->query('SELECT * FROM settings WHERE id = 1')->fetch();
    $cache = $row ?: [];
    return $cache;
}

function format_date(?string $date, bool $withTime = false): string
{
    if (!$date) {
        return 'A definir';
    }
    try {
        $dt = new DateTime($date);
        return $dt->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
    } catch (Throwable) {
        return $date;
    }
}

function upload_image(string $field, ?string $current = null): ?string
{
    if (empty($_FILES[$field]['name'])) {
        return $current;
    }

    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Não foi possível enviar a imagem.');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('A imagem deve ter no máximo 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Use uma imagem JPG, PNG ou WEBP.');
    }

    $dir = dirname(__DIR__) . '/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Não foi possível criar a pasta de uploads.');
    }

    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $destination = $dir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Falha ao salvar a imagem.');
    }

    if ($current && str_starts_with($current, 'uploads/')) {
        $old = dirname(__DIR__) . '/' . $current;
        if (is_file($old)) {
            @unlink($old);
        }
    }
    return 'uploads/' . $name;
}

function nullable_int(mixed $value): ?int
{
    return ($value === '' || $value === null) ? null : (int)$value;
}

/**
 * Verifica se uma tabela existe no banco atual.
 */
function db_table_exists(PDO $db, string $table): bool
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        return false;
    }

    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Gera um slug simples e estável para URLs e registros.
 */
function slugify(string $value): string
{
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($converted !== false) {
        $value = $converted;
    }
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-') ?: 'noticia';
}

/**
 * Converte o valor de datetime-local para o formato do MySQL.
 */
function normalize_datetime_local(?string $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return date('Y-m-d H:i:s');
    }

    try {
        return (new DateTime($value))->format('Y-m-d H:i:s');
    } catch (Throwable) {
        return date('Y-m-d H:i:s');
    }
}

/**
 * Remove somente imagens geradas na pasta uploads do projeto.
 */
function delete_uploaded_image(?string $path): void
{
    if (!$path || !str_starts_with($path, 'uploads/')) {
        return;
    }

    $fullPath = dirname(__DIR__) . '/' . $path;
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}


/**
 * Retorna a abreviação do mês em português.
 */
function month_short_pt(?string $date): string
{
    if (!$date) {
        return 'DATA';
    }

    $months = [1 => 'JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];
    try {
        $month = (int)(new DateTime($date))->format('n');
        return $months[$month] ?? 'DATA';
    } catch (Throwable) {
        return 'DATA';
    }
}
