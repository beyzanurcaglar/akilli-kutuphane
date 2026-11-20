<?php
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/layout.php';

$user    = current_user();
$keyword = $_GET['keyword'] ?? 'kitap';
$query   = trim($_GET['query'] ?? '');

// Aranabilecek sütunlar beyaz listede tutulur (SQL enjeksiyonuna karşı)
$sutun = match ($keyword) {
    'yazar' => 'yazar',
    'tur'   => 'tur',
    'id'    => 'id',
    default => 'kitap_adi',
};

$sonuclar = [];
if ($query !== '') {
    if ($sutun === 'id') {
        $sonuclar = db_fetch_all($conn, 'SELECT * FROM kitaplar WHERE id = ?', 'i', [(int) $query]);
    } else {
        $sonuclar = db_fetch_all($conn, "SELECT * FROM kitaplar WHERE $sutun LIKE ?", 's', ['%' . $query . '%']);
    }
}

$favoriler = [];
$oduncteOlanlar = [];
if ($user) {
    $favoriler = array_map('intval', array_column(
        db_fetch_all($conn, 'SELECT kitap_id FROM favori_kitaplar WHERE user_id = ?', 'i', [$user['id']]),
        'kitap_id'
    ));
    $oduncteOlanlar = array_map('intval', array_column(
        db_fetch_all($conn, "SELECT kitap_id FROM odunc_kitaplar WHERE durum = 'Teslim Edilmedi'"),
        'kitap_id'
    ));
}

sayfa_baslat('Arama Sonuçları', ['search.css']);
?>
  <div class="kapsayici">
    <h1 class="sayfa-basligi">Arama Sonuçları</h1>
    <?php require __DIR__ . '/../src/search_form.php'; ?>

    <?php if (!$sonuclar): ?>
      <p class="sonuc-yok">Aradığınız kriterlere uygun kitap bulunamadı.</p>
    <?php else: ?>
      <p class="sonuc-ozeti">"<?= e($query) ?>" için <strong><?= count($sonuclar) ?></strong> sonuç bulundu</p>

      <div class="kitap-izgara">
        <?php foreach ($sonuclar as $kitap):
          $kitapId  = (int) $kitap['id'];
          $favoride = in_array($kitapId, $favoriler, true);
          $oduncte  = in_array($kitapId, $oduncteOlanlar, true);
        ?>
          <article class="kitap-karti">
            <div class="kitap-kapak"><?= kapak_img($kitap) ?></div>
            <div class="kitap-bilgi">
              <h3><?= e($kitap['kitap_adi']) ?></h3>
              <p>Yazar: <?= e($kitap['yazar'] ?: 'Bilinmiyor') ?></p>
              <p>Tür: <?= e($kitap['tur'] ?: 'Belirtilmemiş') ?></p>
              <p>Yayın Yılı: <?= e($kitap['yayin_yili'] ?: '-') ?></p>

              <?php if ($user): ?>
                <div class="kitap-butonlar">
                  <button class="btn-favori btn-ikincil" data-id="<?= $kitapId ?>" data-favoride="<?= $favoride ? '1' : '0' ?>">
                    <?= $favoride ? '❌ Favoriden Çıkar' : '⭐ Favorilere Ekle' ?>
                  </button>
                  <?php if ($oduncte): ?>
                    <p class="oduncte-uyari">Şu anda ödünçte</p>
                  <?php else: ?>
                    <button class="btn-odunc" data-id="<?= $kitapId ?>">📚 Ödünç Al</button>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <p class="giris-uyari"><a href="login.php"><strong>Giriş yapın</strong></a> ➜ favorilemek ya da ödünç almak için</p>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <script>
    document.addEventListener('click', async (e) => {
      const btn = e.target.closest('button');
      if (!btn) return;

      // Favori ekle / çıkar
      if (btn.classList.contains('btn-favori')) {
        const favoride = btn.dataset.favoride === '1';
        const sonuc = await postJson('api/favorite.php', {
          kitap_id: btn.dataset.id,
          islem: favoride ? 'sil' : 'ekle',
        });
        bildirim(sonuc.mesaj, sonuc.ok ? 'basari' : 'hata');
        if (sonuc.ok) {
          btn.dataset.favoride = favoride ? '0' : '1';
          btn.textContent = favoride ? '⭐ Favorilere Ekle' : '❌ Favoriden Çıkar';
        }
      }

      // Ödünç al
      if (btn.classList.contains('btn-odunc')) {
        const sonuc = await postJson('api/borrow.php', { kitap_id: btn.dataset.id });
        bildirim(sonuc.mesaj, sonuc.ok ? 'basari' : 'hata');
        if (sonuc.ok) {
          btn.outerHTML = '<p class="oduncte-uyari">Şu anda ödünçte</p>';
        }
      }
    });
  </script>
<?php
sayfa_bitir();
