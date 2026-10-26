<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
?>
<!DOCTYPE html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kebijakan Privasi — KYN</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head><body>
<header class="site-nav">
  <a class="brand" href="/">KYN<span class="brand-dot"></span></a>
  <a href="/" class="btn btn-ghost btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
</header>
<section class="section">
  <div class="container container-narrow">
    <span class="kicker">Legal</span>
    <h1 class="display" style="font-size:clamp(2rem,4vw,3rem)">Kebijakan Privasi</h1>
    <div class="body" style="max-width:none;color:var(--ink)">
      <p>Yayasan Kalofian Yusi Nusantara (KYN) berkomitmen melindungi data pribadi sesuai UU No. 27/2022 tentang Perlindungan Data Pribadi.</p>
      <h3>Data yang Kami Kumpulkan</h3>
      <p>Form pendaftaran mengumpulkan: nama lengkap, nomor WhatsApp, email (opsional), jenis pendaftaran, program, dan motivasi.</p>
      <h3>Tujuan Pemrosesan</h3>
      <p>Data digunakan untuk memproses pendaftaran, menghubungi Anda, dan keperluan administrasi program.</p>
      <h3>Retensi</h3>
      <p>Data disimpan maksimal 5 tahun setelah program selesai, lalu dianonimkan.</p>
      <h3>Hak Anda</h3>
      <p>Anda berhak mengakses, mengoreksi, menghapus, atau menarik persetujuan kapan saja via <a href="mailto:privacy@kyn.or.id">privacy@kyn.or.id</a>.</p>
      <h3>Kontak</h3>
      <p>Data Protection Officer: <a href="mailto:dpo@kyn.or.id">dpo@kyn.or.id</a></p>
    </div>
  </div>
</section>
<footer class="site-footer">
  <div class="footer-bottom"><?= e(setting('footer_copyright','© 2024 Yayasan Kalofian Yusi Nusantara')) ?></div>
</footer>
</body></html>