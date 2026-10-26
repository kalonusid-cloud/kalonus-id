<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';
require_login('super_admin','sekretaris','ketua_harian','ketua_umum');

$page_title = 'Konten & Section';
$active     = 'cont';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $fields = [
        'hero_kicker'          => 'hero',
        'hero_heading'         => 'hero',
        'hero_subheading'      => 'hero',
        'impact_beneficiaries' => 'impact',
        'impact_retention'     => 'impact',
        'impact_cost_per'      => 'impact',
        'impact_locations'     => 'impact',
        'site_title'           => 'brand',
        'site_tagline'         => 'brand',
        'contact_email'        => 'contact',
        'contact_partnership'  => 'contact',
        'contact_phone'        => 'contact',
        'contact_address'      => 'contact',
        'footer_copyright'     => 'footer',
    ];
    foreach ($fields as $k => $g) {
        if (isset($_POST[$k])) update_setting($k, trim((string)$_POST[$k]), $g);
    }

    if (!empty($_FILES['hero_cover']['tmp_name'])) {
        if ($path = upload_image($_FILES['hero_cover'], 'hero')) {
            update_setting('hero_cover', $path, 'hero');
        }
    }

    if (!empty($_POST['sections']) && is_array($_POST['sections'])) {
        $st = db()->prepare('UPDATE cms_sections SET kicker=?, heading=?, body=? WHERE section_key=?');
        foreach ($_POST['sections'] as $key => $data) {
            $st->execute([
                trim($data['kicker'] ?? ''),
                trim($data['heading'] ?? ''),
                trim($data['body'] ?? ''),
                $key,
            ]);
        }
    }

    audit('UPDATE_CONTENT', 'cms_settings');
    redirect('/admin/content.php?saved=1');
}

$sections = [];
foreach (db()->query('SELECT * FROM cms_sections ORDER BY sort_order') as $s) {
    $sections[$s['section_key']] = $s;
}

require __DIR__ . '/partials/header.php';
?>
<?php if (isset($_GET['saved'])): ?><div class="alert-ok">Konten tersimpan.</div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-form">
  <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">

  <section class="card">
    <h2>Hero</h2>
    <label>Kicker <input name="hero_kicker" maxlength="120" value="<?= e(setting('hero_kicker')) ?>"></label>
    <label>Heading <textarea name="hero_heading" rows="2" maxlength="255"><?= e(setting('hero_heading')) ?></textarea></label>
    <label>Sub-heading <textarea name="hero_subheading" rows="2"><?= e(setting('hero_subheading')) ?></textarea></label>
    <label>Cover Image
      <?php if ($cv = setting('hero_cover')): ?>
        <img src="<?= e($cv) ?>" alt="" style="max-width:280px;border-radius:8px;margin:8px 0">
      <?php endif; ?>
      <input type="file" name="hero_cover" accept="image/*">
    </label>
  </section>

  <section class="card">
    <h2>Impact (Statistik)</h2>
    <label>Penerima Manfaat <input name="impact_beneficiaries" value="<?= e(setting('impact_beneficiaries')) ?>"></label>
    <label>Retensi <input name="impact_retention" value="<?= e(setting('impact_retention')) ?>"></label>
    <label>Biaya / Penerima <input name="impact_cost_per" value="<?= e(setting('impact_cost_per')) ?>"></label>
    <label>Lokasi <input name="impact_locations" value="<?= e(setting('impact_locations')) ?>"></label>
  </section>

  <section class="card">
    <h2>Section Landing</h2>
    <?php foreach (['impact','program','culture','faq','footer'] as $key): if (!isset($sections[$key])) continue; ?>
      <fieldset>
        <legend><?= ucfirst($key) ?></legend>
        <label>Kicker <input name="sections[<?= $key ?>][kicker]" maxlength="120" value="<?= e($sections[$key]['kicker'] ?? '') ?>"></label>
        <label>Heading <input name="sections[<?= $key ?>][heading]" maxlength="255" value="<?= e($sections[$key]['heading'] ?? '') ?>"></label>
        <label>Body <textarea name="sections[<?= $key ?>][body]" rows="3"><?= e($sections[$key]['body'] ?? '') ?></textarea></label>
      </fieldset>
    <?php endforeach; ?>
  </section>

  <section class="card">
    <h2>Kontak</h2>
    <label>Email <input name="contact_email" type="email" value="<?= e(setting('contact_email')) ?>"></label>
    <label>Partnership Email <input name="contact_partnership" type="email" value="<?= e(setting('contact_partnership')) ?>"></label>
    <label>Telepon <input name="contact_phone" value="<?= e(setting('contact_phone')) ?>"></label>
    <label>Alamat <textarea name="contact_address" rows="2"><?= e(setting('contact_address')) ?></textarea></label>
  </section>

  <section class="card">
    <h2>Brand & Footer</h2>
    <label>Site Title <input name="site_title" value="<?= e(setting('site_title')) ?>"></label>
    <label>Tagline <input name="site_tagline" value="<?= e(setting('site_tagline')) ?>"></label>
    <label>Copyright <input name="footer_copyright" value="<?= e(setting('footer_copyright')) ?>"></label>
  </section>

  <div class="form-actions"><button class="btn btn-dark" type="submit">Simpan Semua</button></div>
</form>

<?php require __DIR__ . '/partials/footer.php'; ?>