<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/auth.php';

start_session();

/* ============================================================
   1. HANDLE FORM PENDAFTARAN PUBLIK
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_form'] ?? '') === 'registration') {
    csrf_check();

    // honeypot — field tersembunyi harus kosong
    if (!empty($_POST['website'])) {
        $_SESSION['flash'] = ['type' => 'bad', 'msg' => 'Terima kasih.'];
        redirect('/#pendaftaran');
    }

    // rate limit: max 5 submit / IP / menit
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $st = db()->prepare('SELECT COUNT(*) FROM registrations WHERE ip_address=? AND created_at > (NOW() - INTERVAL 1 MINUTE)');
    $st->execute([$ip]);
    if ((int)$st->fetchColumn() >= 5) {
        $_SESSION['flash'] = ['type' => 'bad', 'msg' => 'Terlalu banyak percobaan. Coba lagi dalam 1 menit.'];
        redirect('/#pendaftaran');
    }

    $fullName   = trim((string)($_POST['full_name'] ?? ''));
    $phoneWa    = trim((string)($_POST['phone_wa'] ?? ''));
    $email      = trim((string)($_POST['email'] ?? ''));
    $type       = (string)($_POST['type'] ?? '');
    $program    = trim((string)($_POST['program'] ?? ''));
    $motivation = trim((string)($_POST['motivation'] ?? ''));
    $consent    = isset($_POST['consent']) ? 1 : 0;

    $errors = [];
    if ($fullName === '' || mb_strlen($fullName) < 3) $errors[] = 'Nama lengkap wajib diisi (min. 3 karakter).';
    if (!preg_match('/^\+?[0-9]{9,15}$/', preg_replace('/[\s\-]/', '', $phoneWa))) $errors[] = 'Nomor WhatsApp tidak valid.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (!in_array($type, ['bimbel','pelatihan','relawan','mitra'], true)) $errors[] = 'Jenis pendaftaran tidak valid.';
    if (!$consent) $errors[] = 'Anda harus menyetujui pemrosesan data pribadi.';

    if ($errors) {
        $_SESSION['flash'] = ['type' => 'bad', 'msg' => implode(' ', $errors)];
        $_SESSION['old']   = $_POST;
    } else {
        $st = db()->prepare('INSERT INTO registrations
            (full_name, phone_wa, email, type, program, motivation, consent, ip_address, user_agent)
            VALUES (?,?,?,?,?,?,?,?,?)');
        $st->execute([
            $fullName, $phoneWa, $email ?: null, $type, $program ?: null,
            $motivation ?: null, $consent, $ip,
            mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
        $id = (int)db()->lastInsertId();
        audit('CREATE_REGISTRATION', 'registration', (string)$id, ['type' => $type]);
        $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Pendaftaran terkirim. Tim kami akan menghubungi via WhatsApp.'];
        redirect('/#pendaftaran');
    }
}

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$old   = $_SESSION['old']   ?? []; unset($_SESSION['old']);

/* ============================================================
   2. LOAD DATA DARI DB
   ============================================================ */
$heroButtons = buttons('hero');
$convButtons = buttons('conversion');

$sections = [];
foreach (db()->query('SELECT * FROM cms_sections') as $s) {
    $sections[$s['section_key']] = $s;
}

$programs = db()->query(
    'SELECT * FROM programs WHERE is_active=1 ORDER BY is_featured DESC, sort_order, id'
)->fetchAll();

$faqs = db()->query(
    'SELECT * FROM faq WHERE is_active=1 ORDER BY sort_order, id'
)->fetchAll();

// Helper untuk render style tombol sesuai STYLEGUIDE
function btnClass(string $style): string {
    return match($style) {
        'white'  => 'btn btn-white',
        'accent' => 'btn btn-accent',
        'ghost'  => 'btn btn-ghost',
        default  => 'btn btn-dark',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(setting('site_title', 'KYN — Yayasan Kalofian Yusi Nusantara')) ?></title>
<meta name="description" content="<?= e(setting('site_tagline', 'Berakar pada Asal. Merawat Generasi.')) ?>">
<meta property="og:title" content="<?= e(setting('site_title')) ?>">
<meta property="og:description" content="<?= e(setting('site_tagline')) ?>">
<?php if ($cover = setting('hero_cover')): ?>
<meta property="og:image" content="<?= e($cover) ?>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<!-- ============ NAV ============ -->
<header class="site-nav">
  <a class="brand" href="/">KYN<span class="brand-dot"></span></a>
  <nav class="nav-links">
    <a href="#dampak">Dampak</a>
    <a href="#program">Program</a>
    <a href="#budaya">Budaya</a>
    <a href="#pendaftaran">Daftar</a>
    <a href="#faq">FAQ</a>
  </nav>
  <a href="/admin/login.php" class="btn btn-dark btn-sm">
    <i class="fa-solid fa-arrow-right-to-bracket"></i> Portal Internal
  </a>
</header>

<?php if ($flash): ?>
  <div class="toast toast-<?= e($flash['type']) ?>" role="status"><?= e($flash['msg']) ?></div>
<?php endif; ?>

<!-- ============ HERO ============ -->
<section class="hero" id="hero" <?php if ($cover = setting('hero_cover')): ?>
  style="background-image: linear-gradient(180deg, rgba(8,8,8,.35), rgba(8,8,8,.75)), url('<?= e($cover) ?>'); background-size: cover; background-position: center;"
<?php endif; ?>>
  <div class="hero-inner">
    <span class="kicker"><?= e(setting('hero_kicker', 'Ekosistem SDM Unggul')) ?></span>
    <h1 class="display"><?= nl2br(e(setting('hero_heading', 'Sepuluh ribu jiwa. Terlayani.'))) ?></h1>
    <p class="subhead"><?= e(setting('hero_subheading', 'Memadukan pendidikan, pelestarian budaya, dan pemberdayaan ekonomi.')) ?></p>

    <div class="hero-actions">
      <?php foreach ($heroButtons as $b): ?>
        <a href="<?= e($b['url']) ?>"
           target="<?= e($b['target']) ?>"
           rel="<?= $b['target'] === '_blank' ? 'noopener' : '' ?>"
           class="<?= btnClass($b['style']) ?>">
          <?php if ($b['icon']): ?><i class="fa-solid <?= e($b['icon']) ?>"></i><?php endif; ?>
          <?= e($b['label']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="scroll-hint"><span></span></div>
</section>

<!-- ============ IMPACT ============ -->
<section class="section" id="dampak">
  <div class="container">
    <span class="kicker"><?= e($sections['impact']['kicker'] ?? 'Dampak Terukur') ?></span>
    <h2 class="heading"><?= e($sections['impact']['heading'] ?? 'Bukan sekadar angka.') ?></h2>
    <p class="body"><?= nl2br(e($sections['impact']['body'] ?? '')) ?></p>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
        <div class="stat-value"><?= e(setting('impact_beneficiaries', '10.000+')) ?></div>
        <div class="stat-label">Penerima Manfaat</div>
        <div class="stat-delta ok"><i class="fa-solid fa-arrow-trend-up"></i> +18% YoY</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-graduation-cap"></i></div>
        <div class="stat-value"><?= e(setting('impact_retention', '92%')) ?></div>
        <div class="stat-label">Retensi Siswa</div>
        <div class="stat-delta ok">di atas rata-rata NGO</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-wallet"></i></div>
        <div class="stat-value"><?= e(setting('impact_cost_per', 'Rp 240rb')) ?></div>
        <div class="stat-label">Biaya per Penerima</div>
        <div class="stat-delta ok"><i class="fa-solid fa-arrow-trend-down"></i> hemat 35%</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-map-location-dot"></i></div>
        <div class="stat-value"><?= e(setting('impact_locations', '12')) ?></div>
        <div class="stat-label">Lokasi Program</div>
        <div class="stat-delta">Sulawesi Barat & sekitarnya</div>
      </div>
    </div>
  </div>
</section>

<!-- ============ PROGRAM SHOWCASE ============ -->
<section class="section section-panel" id="program">
  <div class="container">
    <span class="kicker"><?= e($sections['program']['kicker'] ?? 'Program Unggulan') ?></span>
    <h2 class="heading"><?= e($sections['program']['heading'] ?? 'Tiga pilar. Satu ekosistem.') ?></h2>
    <p class="body"><?= e($sections['program']['body'] ?? '') ?></p>

    <div class="program-filter" data-filter-group>
      <button class="chip active" data-filter="all">Semua</button>
      <button class="chip" data-filter="pendidikan">Pendidikan</button>
      <button class="chip" data-filter="budaya">Budaya</button>
      <button class="chip" data-filter="ekonomi">Ekonomi</button>
      <button class="chip" data-filter="digital">Digital</button>
      <button class="chip" data-filter="sosial">Sosial</button>
    </div>

    <div class="program-grid">
      <?php if (!$programs): ?>
        <p class="muted">Belum ada program. Tambah melalui CMS.</p>
      <?php endif; ?>
      <?php foreach ($programs as $p): ?>
        <article class="program-card" data-category="<?= e($p['category']) ?>">
          <?php if ($p['image_path']): ?>
            <div class="program-media" style="background-image:url('<?= e($p['image_path']) ?>')"></div>
          <?php else: ?>
            <div class="program-media program-media-fallback">
              <i class="fa-solid fa-seedling"></i>
            </div>
          <?php endif; ?>
          <div class="program-body">
            <span class="badge"><?= e($p['category']) ?></span>
            <?php if ($p['is_featured']): ?><span class="badge ok">Unggulan</span><?php endif; ?>
            <h3><?= e($p['name']) ?></h3>
            <p class="muted"><?= e(mb_substr((string)$p['description'], 0, 140)) ?><?= mb_strlen((string)$p['description']) > 140 ? '…' : '' ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ CULTURE ============ -->
<section class="section section-dark" id="budaya">
  <div class="container container-split">
    <div>
      <span class="kicker"><?= e($sections['culture']['kicker'] ?? 'Pelestarian Budaya') ?></span>
      <h2 class="heading"><?= e($sections['culture']['heading'] ?? 'Merawat yang hampir hilang.') ?></h2>
      <p class="body"><?= nl2br(e($sections['culture']['body'] ?? '')) ?></p>
    </div>
    <div class="culture-visual">
      <i class="fa-solid fa-mask fa-4x"></i>
    </div>
  </div>
</section>

<!-- ============ CONVERSION ============ -->
<section class="section" id="konversi">
  <div class="container">
    <span class="kicker">Kolaborasi</span>
    <h2 class="heading">Mari tumbuh bersama.</h2>
    <p class="body">Menjadi mitra, relawan, atau donatur — tiga cara untuk ikut merawat generasi.</p>
    <div class="hero-actions">
      <?php foreach ($convButtons as $b): ?>
        <a href="<?= e($b['url']) ?>"
           target="<?= e($b['target']) ?>"
           rel="<?= $b['target'] === '_blank' ? 'noopener' : '' ?>"
           class="<?= btnClass($b['style']) ?>">
          <?php if ($b['icon']): ?><i class="fa-solid <?= e($b['icon']) ?>"></i><?php endif; ?>
          <?= e($b['label']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ FORM PENDAFTARAN ============ -->
<section class="section section-panel" id="pendaftaran">
  <div class="container container-narrow">
    <span class="kicker">Form Pendaftaran</span>
    <h2 class="heading">Daftar Bimbel, Pelatihan, Relawan, atau Mitra.</h2>
    <p class="body">Isi form di bawah. Tim kami akan menghubungi Anda via WhatsApp.</p>

    <form method="post" action="/#pendaftaran" class="form-grid" novalidate>
      <input type="hidden" name="_form" value="registration">
      <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
      <input type="text" name="website" value="" tabindex="-1" autocomplete="off"
             style="position:absolute;left:-9999px" aria-hidden="true">

      <label>Nama Lengkap *
        <input name="full_name" required maxlength="160" value="<?= e($old['full_name'] ?? '') ?>">
      </label>

      <label>No. WhatsApp *
        <input name="phone_wa" required maxlength="30" inputmode="tel"
               placeholder="+62 812..." value="<?= e($old['phone_wa'] ?? '') ?>">
      </label>

      <label>Email (opsional)
        <input name="email" type="email" maxlength="160" value="<?= e($old['email'] ?? '') ?>">
      </label>

      <label>Jenis Pendaftaran *
        <select name="type" required>
          <?php foreach (['bimbel'=>'Bimbel','pelatihan'=>'Pelatihan','relawan'=>'Relawan','mitra'=>'Mitra'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= ($old['type'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Program / Bidang
        <input name="program" maxlength="160"
               placeholder="mis. Matematika SD, Tenun Mamuju..."
               value="<?= e($old['program'] ?? '') ?>">
      </label>

      <label class="full">Motivasi Singkat
        <textarea name="motivation" rows="4" maxlength="1000"><?= e($old['motivation'] ?? '') ?></textarea>
      </label>

      <label class="full checkbox">
        <input type="checkbox" name="consent" value="1" <?= !empty($old['consent']) ? 'checked' : '' ?> required>
        Saya menyetujui pemrosesan data pribadi sesuai <a href="/privacy.php" target="_blank">Kebijakan Privasi</a>.
      </label>

      <div class="full form-actions">
        <button class="btn btn-accent" type="submit">
          <i class="fa-solid fa-paper-plane"></i> Kirim Pendaftaran
        </button>
      </div>
    </form>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section class="section" id="faq">
  <div class="container container-narrow">
    <span class="kicker"><?= e($sections['faq']['kicker'] ?? 'FAQ') ?></span>
    <h2 class="heading"><?= e($sections['faq']['heading'] ?? 'Pertanyaan yang sering diajukan.') ?></h2>

    <div class="faq-list">
      <?php if (!$faqs): ?>
        <p class="muted">Belum ada FAQ.</p>
      <?php endif; ?>
      <?php foreach ($faqs as $f): ?>
        <details class="faq-item">
          <summary><?= e($f['question']) ?></summary>
          <div class="faq-answer"><?= nl2br(e($f['answer'])) ?></div>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ FOOTER ============ -->
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <a class="brand" href="/">KYN<span class="brand-dot"></span></a>
      <p class="body"><?= e($sections['footer']['body'] ?? 'Yayasan Kalofian Yusi Nusantara — Mamuju, Sulawesi Barat.') ?></p>
    </div>
    <div>
      <h4>Kontak</h4>
      <p>Email: <a href="mailto:<?= e(setting('contact_email','halo@kyn.or.id')) ?>"><?= e(setting('contact_email','halo@kyn.or.id')) ?></a></p>
      <p>Partnership: <a href="mailto:<?= e(setting('contact_partnership','partnership@kyn.or.id')) ?>"><?= e(setting('contact_partnership','partnership@kyn.or.id')) ?></a></p>
      <p>Telp: <?= e(setting('contact_phone')) ?></p>
      <p><?= e(setting('contact_address','Mamuju, Sulawesi Barat')) ?></p>
    </div>
    <div>
      <h4>Tautan</h4>
      <p><a href="/company-profile.php">Company Profile</a></p>
      <p><a href="/privacy.php">Kebijakan Privasi</a></p>
      <p><a href="/admin/login.php">Portal Internal</a></p>
    </div>
  </div>
  <div class="footer-bottom">
    <span><?= e(setting('footer_copyright','© 2024 Yayasan Kalofian Yusi Nusantara')) ?></span>
  </div>
</footer>

<script src="/assets/js/main.js" defer></script>
</body>
</html>