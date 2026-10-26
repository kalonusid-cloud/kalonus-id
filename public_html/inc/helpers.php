<?php
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function setting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT setting_key, setting_value FROM cms_settings') as $r) {
            $cache[$r['setting_key']] = $r['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

function buttons(string $location): array {
    $st = db()->prepare('SELECT * FROM cms_buttons WHERE location=? AND is_active=1 ORDER BY sort_order, id');
    $st->execute([$location]);
    return $st->fetchAll();
}

function audit(string $action, string $type, ?string $id = null, array $details = []): void {
    $uid = $_SESSION['user']['id'] ?? null;
    $ip  = $_SERVER['REMOTE_ADDR'] ?? null;
    $st = db()->prepare('INSERT INTO audit_log (user_id,action,object_type,object_id,details,ip_address) VALUES (?,?,?,?,?,?)');
    $st->execute([$uid, $action, $type, $id, json_encode($details, JSON_UNESCAPED_UNICODE), $ip]);
}

function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

function slugify(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

/* ---------- Upload helper ---------- */
function upload_image(array $file, string $subdir = 'misc', int $maxBytes = 2097152): ?string {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > $maxBytes) return null;

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!isset($allowed[$mime])) return null;

    $cfg = require __DIR__ . '/config.php';
    $dir = rtrim($cfg['app']['upload_dir'], '/') . '/' . $subdir;
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) return null;
    return rtrim($cfg['app']['upload_url'], '/') . "/$subdir/$name";
}

/* ---------- Update setting ---------- */
function update_setting(string $key, string $value, string $group = 'general'): void {
    $st = db()->prepare(
        'INSERT INTO cms_settings (setting_key, setting_value, setting_group, updated_by)
         VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),
                                 setting_group=VALUES(setting_group),
                                 updated_by=VALUES(updated_by)'
    );
    $st->execute([$key, $value, $group, $_SESSION['user']['id'] ?? null]);
}

/* ---------- Unique slug untuk berita ---------- */
function unique_slug(string $title, int $excludeId = 0): string {
    $base = slugify($title) ?: 'berita';
    $slug = $base; $i = 1;
    while (true) {
        $st = db()->prepare('SELECT id FROM news WHERE slug=? AND id<>?');
        $st->execute([$slug, $excludeId]);
        if (!$st->fetch()) return $slug;
        $slug = $base . '-' . (++$i);
    }
}