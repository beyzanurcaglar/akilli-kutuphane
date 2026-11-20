<?php
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/layout.php';

// Son eklenen 10 kitap (slider için)
$sonKitaplar = db_fetch_all($conn, 'SELECT id, kitap_adi, yazar, isbn, kapak_url FROM kitaplar ORDER BY id DESC LIMIT 10');

// İstatistikler
$kitapSayisi = (int) db_fetch_one($conn, 'SELECT COUNT(*) AS sayi FROM kitaplar')['sayi'];
$uyeSayisi   = (int) db_fetch_one($conn, 'SELECT COUNT(*) AS sayi FROM users')['sayi'];
$oduncSayisi = (int) db_fetch_one($conn, "SELECT COUNT(*) AS sayi FROM odunc_kitaplar WHERE durum = 'Teslim Edilmedi'")['sayi'];

sayfa_baslat('Anasayfa', ['home.css'], '', 'anasayfa');
?>
  <section class="welcome">
    <h1>Akıllı Kütüphane Sistemine Hoş Geldiniz</h1>
    <p>Kitap arayın, keşfedin ve yönetin. Modern bir kütüphane deneyimi sizi bekliyor!</p>
    <?php require __DIR__ . '/../src/search_form.php'; ?>
  </section>

  <section class="slider-container" aria-label="Yeni gelen kitaplar">
    <div class="slider-track">
      <div class="slide banner-slide">
        <h2>YENİ GELENLER</h2>
        <p>Son eklenen kitaplara göz atın!</p>
      </div>
      <?php foreach ($sonKitaplar as $kitap): ?>
        <div class="slide">
          <?= kapak_img($kitap) ?>
          <p><?= e($kitap['kitap_adi']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="istatistikler">
    <div class="istatistik-kutu">
      <img src="assets/img/stat-books.png" alt="">
      <h3>Toplam Kitap</h3>
      <p><?= $kitapSayisi ?></p>
    </div>
    <div class="istatistik-kutu">
      <img src="assets/img/stat-members.png" alt="">
      <h3>Toplam Üye</h3>
      <p><?= $uyeSayisi ?></p>
    </div>
    <div class="istatistik-kutu">
      <img src="assets/img/stat-loans.png" alt="">
      <h3>Ödünç Alınan Kitap</h3>
      <p><?= $oduncSayisi ?></p>
    </div>
  </section>

  <section id="about" class="bilgi-bolumu">
    <h2>Hakkımızda</h2>
    <p>Akıllı Kütüphane Sistemi, kullanıcıların kitaplara daha hızlı ve etkili şekilde ulaşmasını sağlayan modern bir platformdur.</p>
  </section>

  <section id="contact" class="bilgi-bolumu">
    <h2>
      <img src="assets/img/icon-contact.jpg" alt="" class="iletisim-ikon">
      İletişim
    </h2>
    <p>Her türlü soru, öneri veya destek talebiniz için bizimle iletişime geçebilirsiniz.</p>
    <p>info@kutuphane.example</p>
  </section>
<?php
sayfa_bitir();
