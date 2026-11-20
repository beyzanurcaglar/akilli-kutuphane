<?php
/**
 * Tüm sayfaların ortak iskeleti: <head>, üst menü ve alt bilgi.
 *
 * $kok: public/ klasörüne göre göreli yol. Kök sayfalar için '',
 *       alt klasördeki sayfalar (member/, admin/) için '../'.
 */

/**
 * @param string   $baslik Sayfa başlığı
 * @param string[] $cssler Ek stil dosyaları (assets/css altında)
 * @param string   $kok    public/ klasörüne göreli yol
 * @param string   $aktif  Üst menüde vurgulanacak bağlantı (anasayfa, giris, panel)
 */
function sayfa_baslat(string $baslik, array $cssler = [], string $kok = '', string $aktif = ''): void
{
    $user = current_user();
    $panelUrl = $kok . (is_admin() ? 'admin/' : 'member/');
    $sinif = fn (string $ad) => $ad === $aktif ? ' class="aktif"' : '';
    ?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?= csrf_meta() ?>
  <?= google_books_meta() ?>
  <title><?= e($baslik) ?> - Akıllı Kütüphane</title>
  <link rel="stylesheet" href="<?= $kok ?>assets/css/base.css">
  <?php foreach ($cssler as $css): ?>
    <link rel="stylesheet" href="<?= $kok ?>assets/css/<?= e($css) ?>">
  <?php endforeach; ?>
  <link rel="icon" type="image/png" href="<?= $kok ?>assets/img/favicon.png">
</head>
<body>
  <header class="site-header">
    <a href="<?= $kok ?>index.php" class="site-logo">
      <img src="<?= $kok ?>assets/img/logo.png" alt="Akıllı Kütüphane - Anasayfa">
    </a>
    <nav class="site-nav">
      <a href="<?= $kok ?>index.php"<?= $sinif('anasayfa') ?>>Anasayfa</a>
      <a href="<?= $kok ?>index.php#about">Hakkımızda</a>
      <a href="<?= $kok ?>index.php#contact">İletişim</a>
      <?php if ($user): ?>
        <a href="<?= $panelUrl ?>"<?= $sinif('panel') ?>>
          <img src="<?= $kok ?>assets/img/icon-user.png" alt="" class="nav-ikon">
          <?= e(mb_convert_case($user['ad'], MB_CASE_TITLE, 'UTF-8')) ?>
        </a>
        <a href="<?= $kok ?>logout.php" class="nav-cikis">Çıkış</a>
      <?php else: ?>
        <a href="<?= $kok ?>login.php"<?= $sinif('giris') ?>>Oturum Aç</a>
      <?php endif; ?>
    </nav>
  </header>

  <div class="site-icerik">
<?php
}

function sayfa_bitir(string $kok = ''): void
{
    ?>
  </div>

  <footer class="site-footer">
    <p>&copy; 2025 Akıllı Kütüphane Sistemi</p>
  </footer>

  <script src="<?= $kok ?>assets/js/app.js"></script>
</body>
</html>
<?php
}

/* ---------------------------------------------------------------------
 * Üye ve admin panelleri: ortak iskelete ek olarak sol menü içerir.
 * ------------------------------------------------------------------- */

/**
 * @param array  $menu      [sekme_anahtari => etiket]
 * @param string $aktif     Seçili sekme
 * @param string $profilImg Kenar çubuğundaki görsel (assets/img altında)
 */
function panel_baslat(string $baslik, array $menu, string $aktif, string $profilImg): void
{
    $user = current_user();
    sayfa_baslat($baslik, ['panel.css'], '../', 'panel');
    ?>
    <div class="panel">
      <aside class="sidebar">
        <div class="sidebar-profil">
          <img src="../assets/img/<?= e($profilImg) ?>" alt="">
          <strong><?= e($user['ad'] . ' ' . $user['soyad']) ?></strong>
          <small><?= e($user['eposta']) ?></small>
        </div>
        <nav class="sidebar-menu">
          <?php foreach ($menu as $anahtar => $etiket): ?>
            <a href="?sekme=<?= e($anahtar) ?>" class="sidebar-link<?= $anahtar === $aktif ? ' aktif' : '' ?>"><?= e($etiket) ?></a>
          <?php endforeach; ?>
        </nav>
      </aside>

      <main class="panel-icerik">
        <?= flash_render() ?>
<?php
}

function panel_bitir(): void
{
    ?>
      </main>
    </div>
<?php
    sayfa_bitir('../');
}
