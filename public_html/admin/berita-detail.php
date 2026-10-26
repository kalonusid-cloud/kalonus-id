<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$n = null;
if ($slug !== '') {
    $st = db()->prepare("SELECT * FROM news WHERE slug=? AND status='published' LIMIT 1");
    $st->execute([$slug]);
    $n = $st->fetch();
}
if (!$n) { http_response_code(404); }
?>
<!DOCTYPE html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $n ? e($n['title']) . ' — KYN' : 'Berita tidak ditemukan' ?></title>
<?php if ($n && $n['excerpt']): ?><meta name="description" content="<?= e($n['excerpt']) ?>"><?php endif; ?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/assets/css/style.css">
</head><body>
<header class="site-nav">
  <a class="brand" href="/">KYN<span class="brand-dot"></span></a>
  <a href="/berita.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-arrow-left"></i> Semua Berita</a>
</header>

<section class="section">
  <div class="container container-narrow">
    <?php if (!$n): ?>
      <h1 class="display">Berita tidak ditemukan.</h1>
      <a href="/berita.php" class="btn btn-dark">Lihat semua berita</a>
    <?php else: ?>
      <span class="kicker"><?= e($n['category']) ?></span>
      <h1 class="display" style="font-size:clamp(2rem,4.5vw,3.4rem)"><?= e($n['title']) ?></h1>
      <p class="muted"><?= e(date('d F Y', strtotime($n['published_at'] ?? $n['created_at']))) ?></p>
      <?php if ($n['thumbnail']): ?>
        <img src="<?= e($n['thumbnail']) ?>" alt="" style="border-radius:16px;margin:24px 0;width:100%">
      <?php endif; ?>
      <?php if ($n['excerpt']): ?><p class="subhead"><?= e($n['excerpt']) ?></p><?php endif; ?>
      <div class="body" style="max-width:none;white-space:pre-line"><?= e($n['body']) ?></div>
    <?php endif; ?>
  </div>
</section>

<footer class="site-footer">
  <div class="footer-bottom"><?= e(setting('footer_copyright','© 2024 Yayasan Kalofian Yusi Nusantara')) ?></div>
</footer>
</body></html>