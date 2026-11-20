<?php
/**
 * Üye paneli: bilgiler, ödünç/iade ve favoriler.
 */
require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../src/layout.php';

$user = require_login();

$menu = [
    'bilgiler'  => 'Üye Bilgileri',
    'oduncler'  => 'Ödünç / İade',
    'favoriler' => 'Favorilerim',
];
$sekme = array_key_exists($_GET['sekme'] ?? '', $menu) ? $_GET['sekme'] : 'bilgiler';

// ---------- Bilgi güncelleme (form gönderimi) ----------
if (is_post() && ($_POST['islem'] ?? '') === 'bilgi_guncelle') {
    csrf_require();

    $ad          = trim($_POST['ad'] ?? '');
    $soyad       = trim($_POST['soyad'] ?? '');
    $eposta      = trim($_POST['eposta'] ?? '');
    $yeniSifre   = $_POST['yeni_sifre'] ?? '';
    $mevcutSifre = $_POST['mevcut_sifre'] ?? '';

    $kayit = db_fetch_one($conn, 'SELECT * FROM users WHERE id = ?', 'i', [$user['id']]);

    if ($ad === '' || $soyad === '' || !filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
        flash('Lütfen tüm alanları geçerli şekilde doldurun.', 'hata');
    } elseif (!password_verify($mevcutSifre, $kayit['sifre'])) {
        flash('Mevcut şifreniz hatalı.', 'hata');
    } elseif ($yeniSifre !== '' && mb_strlen($yeniSifre) < 6) {
        flash('Yeni şifre en az 6 karakter olmalıdır.', 'hata');
    } elseif (db_fetch_one($conn, 'SELECT id FROM users WHERE eposta = ? AND id <> ?', 'si', [$eposta, $user['id']])) {
        flash('Bu e-posta adresi başka bir hesapta kullanılıyor.', 'hata');
    } else {
        $sifreHash = $yeniSifre !== '' ? password_hash($yeniSifre, PASSWORD_DEFAULT) : $kayit['sifre'];
        db_query(
            $conn,
            'UPDATE users SET ad = ?, soyad = ?, eposta = ?, sifre = ? WHERE id = ?',
            'ssssi',
            [$ad, $soyad, $eposta, $sifreHash, $user['id']]
        );
        login_user(db_fetch_one($conn, 'SELECT * FROM users WHERE id = ?', 'i', [$user['id']]));
        flash('Bilgileriniz güncellendi.');
        redirect('?sekme=bilgiler');
    }
    // Hata varsa düzenleme formunda kal
    redirect('?sekme=bilgiler&duzenle=1');
}

panel_baslat('Üye Paneli', $menu, $sekme, 'avatar-member.png');

// ======================================================================
if ($sekme === 'bilgiler'):
    $duzenle = isset($_GET['duzenle']);
?>
  <div class="kart">
    <h2>Üye Bilgileri</h2>
    <?php if (!$duzenle): ?>
      <table class="kitap-tablo">
        <tr><th>Ad</th><td><?= e($user['ad']) ?></td></tr>
        <tr><th>Soyad</th><td><?= e($user['soyad']) ?></td></tr>
        <tr><th>E-posta</th><td><?= e($user['eposta']) ?></td></tr>
      </table>
      <a href="?sekme=bilgiler&amp;duzenle=1" class="btn btn-tam">Bilgileri Düzenle</a>
    <?php else: ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="islem" value="bilgi_guncelle">
        <div class="form-group">
          <label for="ad">Ad</label>
          <input type="text" id="ad" name="ad" value="<?= e($user['ad']) ?>" maxlength="50" required>
        </div>
        <div class="form-group">
          <label for="soyad">Soyad</label>
          <input type="text" id="soyad" name="soyad" value="<?= e($user['soyad']) ?>" maxlength="50" required>
        </div>
        <div class="form-group">
          <label for="eposta">E-posta</label>
          <input type="email" id="eposta" name="eposta" value="<?= e($user['eposta']) ?>" maxlength="100" required>
        </div>
        <div class="form-group">
          <label for="yeni_sifre">Yeni Şifre <small>(değiştirmek istemiyorsanız boş bırakın)</small></label>
          <input type="password" id="yeni_sifre" name="yeni_sifre" minlength="6" autocomplete="new-password">
        </div>
        <div class="form-group">
          <label for="mevcut_sifre">Mevcut Şifre <small>(onay için gerekli)</small></label>
          <input type="password" id="mevcut_sifre" name="mevcut_sifre" required autocomplete="current-password">
        </div>
        <div class="satir">
          <button type="submit" class="btn">Kaydet</button>
          <a href="?sekme=bilgiler" class="btn btn-ikincil">Vazgeç</a>
        </div>
      </form>
    <?php endif; ?>
  </div>

<?php
// ======================================================================
elseif ($sekme === 'oduncler'):
    $aktifOdunc = db_fetch_all(
        $conn,
        "SELECT ok.id AS odunc_id, k.kitap_adi, k.yazar, ok.alis_tarihi
           FROM odunc_kitaplar ok
           JOIN kitaplar k ON k.id = ok.kitap_id
          WHERE ok.user_id = ? AND ok.durum = 'Teslim Edilmedi'
          ORDER BY ok.alis_tarihi DESC",
        'i',
        [$user['id']]
    );
    $gecmis = db_fetch_all(
        $conn,
        "SELECT k.kitap_adi, k.yazar, ok.alis_tarihi, ok.iade_tarihi
           FROM odunc_kitaplar ok
           JOIN kitaplar k ON k.id = ok.kitap_id
          WHERE ok.user_id = ? AND ok.durum = 'Teslim Edildi'
          ORDER BY ok.iade_tarihi DESC",
        'i',
        [$user['id']]
    );
?>
  <h2>Şu Anda Ödünçte Olan Kitaplar</h2>
  <?php if ($aktifOdunc): ?>
    <div class="tablo-kapsayici">
      <table class="kitap-tablo">
        <tr><th>Kitap Adı</th><th>Yazar</th><th>Alış Tarihi</th><th>İşlem</th></tr>
        <?php foreach ($aktifOdunc as $row): ?>
          <tr>
            <td><?= e($row['kitap_adi']) ?></td>
            <td><?= e($row['yazar']) ?></td>
            <td><?= e($row['alis_tarihi']) ?></td>
            <td><button class="btn-iade" data-id="<?= (int) $row['odunc_id'] ?>">İade Et</button></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php else: ?>
    <p>Şu anda ödünç alınmış kitabınız yok. <a href="../index.php">Kitap aramak için tıklayın.</a></p>
  <?php endif; ?>

  <h2>Geçmişte İade Edilen Kitaplar</h2>
  <?php if ($gecmis): ?>
    <div class="tablo-kapsayici">
      <table class="kitap-tablo">
        <tr><th>Kitap Adı</th><th>Yazar</th><th>Alış Tarihi</th><th>İade Tarihi</th></tr>
        <?php foreach ($gecmis as $row): ?>
          <tr>
            <td><?= e($row['kitap_adi']) ?></td>
            <td><?= e($row['yazar']) ?></td>
            <td><?= e($row['alis_tarihi']) ?></td>
            <td><?= e($row['iade_tarihi'] ?: '-') ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php else: ?>
    <p>Henüz iade edilmiş kitabınız bulunmamaktadır.</p>
  <?php endif; ?>

<?php
// ======================================================================
elseif ($sekme === 'favoriler'):
    $favoriler = db_fetch_all(
        $conn,
        "SELECT k.id AS kitap_id, k.kitap_adi, k.yazar,
                EXISTS(SELECT 1 FROM odunc_kitaplar o
                        WHERE o.kitap_id = k.id AND o.durum = 'Teslim Edilmedi') AS oduncte
           FROM favori_kitaplar f
           JOIN kitaplar k ON k.id = f.kitap_id
          WHERE f.user_id = ?
          ORDER BY k.kitap_adi",
        'i',
        [$user['id']]
    );
?>
  <h2>Favori Kitaplarım</h2>
  <?php if ($favoriler): ?>
    <div class="tablo-kapsayici">
      <table class="kitap-tablo">
        <tr><th>Kitap Adı</th><th>Yazar</th><th>İşlem</th></tr>
        <?php foreach ($favoriler as $row): ?>
          <tr>
            <td><?= e($row['kitap_adi']) ?></td>
            <td><?= e($row['yazar']) ?></td>
            <td>
              <?php if ($row['oduncte']): ?>
                <em>Şu an ödünçte</em>
              <?php else: ?>
                <button class="btn-odunc" data-id="<?= (int) $row['kitap_id'] ?>">Ödünç Al</button>
              <?php endif; ?>
              <button class="btn-favori-sil btn-ikincil" data-id="<?= (int) $row['kitap_id'] ?>">Favoriden Çıkar</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php else: ?>
    <p>Henüz favori kitap eklenmemiş. Arama sonuçlarından kitapları favorilerinize ekleyebilirsiniz.</p>
  <?php endif; ?>
<?php endif; ?>

  <script>
    document.addEventListener('click', async (e) => {
      const btn = e.target.closest('button');
      if (!btn) return;
      let sonuc = null;

      if (btn.classList.contains('btn-iade')) {
        if (!confirm('Bu kitabı iade etmek istiyor musunuz?')) return;
        sonuc = await postJson('../api/return.php', { odunc_id: btn.dataset.id });
      } else if (btn.classList.contains('btn-odunc')) {
        sonuc = await postJson('../api/borrow.php', { kitap_id: btn.dataset.id });
      } else if (btn.classList.contains('btn-favori-sil')) {
        if (!confirm('Bu kitabı favorilerden çıkarmak istiyor musunuz?')) return;
        sonuc = await postJson('../api/favorite.php', { kitap_id: btn.dataset.id, islem: 'sil' });
      } else {
        return;
      }

      if (sonuc.ok) {
        // Sayfa yenilendikten sonra gösterilir
        bildirimSonra(sonuc.mesaj);
        location.reload();
      } else {
        bildirim(sonuc.mesaj, 'hata');
      }
    });
  </script>
<?php
panel_bitir();
