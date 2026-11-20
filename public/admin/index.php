<?php
/**
 * Admin paneli: kitap ekleme/silme, ödünç takibi, kullanıcı yönetimi.
 * Bu sayfaya yalnızca "admin" rolündeki kullanıcılar erişebilir.
 */
require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../src/layout.php';

$user = require_admin();

$menu = [
    'kitap-ekle'      => 'Kitap Ekle',
    'kitaplar'        => 'Kitaplar',
    'oduncler'        => 'Ödünç Takibi',
    'kullanicilar'    => 'Kullanıcılar',
    'hesap-degistir'  => 'Admin Değiştir',
];
$sekme = array_key_exists($_GET['sekme'] ?? '', $menu) ? $_GET['sekme'] : 'kitap-ekle';

// ======================================================================
// Form işlemleri (POST → işlem → yönlendirme)
// ======================================================================
if (is_post()) {
    csrf_require();
    $islem = $_POST['islem'] ?? '';

    switch ($islem) {
        case 'kitap_ekle':
            $kitapAdi = trim($_POST['kitap_adi'] ?? '');
            if ($kitapAdi === '') {
                flash('Kitap adı boş olamaz.', 'hata');
                redirect('?sekme=kitap-ekle');
            }
            // Yalnızca https ile başlayan kapak adresleri kabul edilir
            $kapakUrl = trim($_POST['kapak_url'] ?? '');
            $kapakUrl = preg_match('#^https://#i', $kapakUrl) ? mb_substr($kapakUrl, 0, 500) : null;

            // "2005-03-01" gibi tarihlerden yalnızca yılı al
            $yil = preg_match('/\d{4}/', $_POST['yayin_yili'] ?? '', $m) ? (int) $m[0] : null;

            db_query(
                $conn,
                'INSERT INTO kitaplar (kitap_adi, yazar, yayin_yili, yayin_evi, tur, isbn, kapak_url) VALUES (?, ?, ?, ?, ?, ?, ?)',
                'ssissss',
                [
                    $kitapAdi,
                    trim($_POST['yazar'] ?? ''),
                    $yil,
                    trim($_POST['yayin_evi'] ?? ''),
                    trim($_POST['tur'] ?? ''),
                    trim($_POST['isbn'] ?? ''),
                    $kapakUrl,
                ]
            );
            flash("“{$kitapAdi}” kitabı eklendi.");
            redirect('?sekme=kitap-ekle');

        case 'kitap_sil':
            $silindi = db_query($conn, 'DELETE FROM kitaplar WHERE id = ?', 'i', [(int) ($_POST['kitap_id'] ?? 0)]) > 0;
            flash($silindi ? 'Kitap silindi.' : 'Kitap bulunamadı.', $silindi ? 'basari' : 'hata');
            redirect('?sekme=kitaplar');

        case 'admin_yap':
            $yapildi = db_query(
                $conn,
                'UPDATE users SET rol = ? WHERE id = ? AND rol <> ?',
                'sis',
                [ROL_ADMIN, (int) ($_POST['user_id'] ?? 0), ROL_ADMIN]
            ) > 0;
            flash($yapildi ? 'Kullanıcı admin yapıldı.' : 'Kullanıcı bulunamadı veya zaten admin.', $yapildi ? 'basari' : 'hata');
            redirect('?sekme=kullanicilar');

        case 'hesap_degistir':
            $hedef = db_fetch_one(
                $conn,
                'SELECT * FROM users WHERE id = ? AND rol = ?',
                'is',
                [(int) ($_POST['admin_id'] ?? 0), ROL_ADMIN]
            );
            if (!$hedef) {
                flash('Admin bulunamadı.', 'hata');
                redirect('?sekme=hesap-degistir');
            }
            $bekle = giris_engel_suresi($conn, $hedef['eposta']);
            if ($bekle > 0) {
                flash("Çok fazla hatalı deneme yapıldı. Lütfen {$bekle} dakika sonra tekrar deneyin.", 'hata');
                redirect('?sekme=hesap-degistir');
            }
            if (password_verify($_POST['sifre'] ?? '', $hedef['sifre'])) {
                giris_denemelerini_temizle($conn, $hedef['eposta']);
                login_user($hedef);
                flash('Hesap değiştirildi: ' . $hedef['ad'] . ' ' . $hedef['soyad']);
                redirect('?sekme=kitap-ekle');
            }
            giris_denemesi_kaydet($conn, $hedef['eposta']);
            flash('Şifre hatalı.', 'hata');
            redirect('?sekme=hesap-degistir');
    }

    flash('Geçersiz işlem.', 'hata');
    redirect('?');
}

panel_baslat('Admin Paneli', $menu, $sekme, 'avatar-admin.png');

// ======================================================================
if ($sekme === 'kitap-ekle'):
?>
  <div class="kart">
    <h2>Kitap Ekle</h2>
    <p>Kitap adını yazıp <strong>Bilgileri Getir</strong>'e basarak diğer alanları Google Books'tan otomatik doldurabilirsiniz.</p>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="islem" value="kitap_ekle">
      <div class="form-group">
        <label for="kitap_adi">Kitap Adı</label>
        <div class="satir">
          <input type="text" id="kitap_adi" name="kitap_adi" maxlength="255" required>
          <button type="button" class="btn" id="btn-bilgi-getir">Bilgileri Getir</button>
        </div>
      </div>
      <div class="form-group"><label for="yazar">Yazar</label><input type="text" id="yazar" name="yazar" maxlength="255"></div>
      <div class="form-group"><label for="yayin_yili">Yayın Yılı</label><input type="text" id="yayin_yili" name="yayin_yili" maxlength="10"></div>
      <div class="form-group"><label for="yayin_evi">Yayınevi</label><input type="text" id="yayin_evi" name="yayin_evi" maxlength="255"></div>
      <div class="form-group"><label for="tur">Tür</label><input type="text" id="tur" name="tur" maxlength="255"></div>
      <div class="form-group"><label for="isbn">ISBN</label><input type="text" id="isbn" name="isbn" maxlength="20"></div>
      <input type="hidden" id="kapak_url" name="kapak_url">
      <div class="kapak" id="kapak"></div>
      <button type="submit" class="btn btn-tam">Kaydet</button>
    </form>
  </div>

  <script>
    document.getElementById('btn-bilgi-getir').addEventListener('click', async () => {
      const ad = document.getElementById('kitap_adi').value.trim();
      if (!ad) return bildirim('Önce kitap adını girin.', 'hata');

      try {
        const veri = await googleBooksAra('intitle:' + ad);
        if (veri === null) return bildirim('Google Books şu anda yanıt vermiyor (kota dolmuş olabilir). Alanları elle doldurabilirsiniz.', 'hata');
        if (!veri.totalItems) return bildirim('Kitap bulunamadı.', 'hata');

        const k = veri.items[0].volumeInfo;
        document.getElementById('yazar').value = k.authors?.join(', ') || '';
        document.getElementById('yayin_yili').value = (k.publishedDate || '').slice(0, 4);
        document.getElementById('yayin_evi').value = k.publisher || '';
        document.getElementById('tur').value = k.categories?.join(', ') || '';
        document.getElementById('isbn').value = k.industryIdentifiers?.[0]?.identifier || '';

        const kapak = document.getElementById('kapak');
        const kapakUrl = k.imageLinks?.thumbnail?.replace('http://', 'https://') || '';
        document.getElementById('kapak_url').value = kapakUrl;
        kapak.textContent = '';
        bildirim('Kitap bilgileri getirildi.');
        if (kapakUrl) {
          const img = document.createElement('img');
          img.src = kapakUrl;
          img.alt = 'Kapak';
          kapak.appendChild(img);
        } else {
          kapak.textContent = 'Kapak bulunamadı.';
        }
      } catch (err) {
        console.error(err);
        bildirim('Kitap bilgileri alınamadı.', 'hata');
      }
    });
  </script>

<?php
// ======================================================================
elseif ($sekme === 'kitaplar'):
    $kitaplar = db_fetch_all($conn, 'SELECT id, kitap_adi, yazar, tur, yayin_yili FROM kitaplar ORDER BY id DESC');
?>
  <h2>Kitaplar (<?= count($kitaplar) ?>)</h2>
  <?php if (!$kitaplar): ?>
    <p>Hiç kitap bulunamadı.</p>
  <?php else: ?>
    <div class="tablo-kapsayici">
      <table class="kitap-tablo">
        <tr><th>Kod</th><th>Adı</th><th>Yazar</th><th>Tür</th><th>Yıl</th><th>İşlem</th></tr>
        <?php foreach ($kitaplar as $kitap): ?>
          <tr>
            <td><?= (int) $kitap['id'] ?></td>
            <td><?= e($kitap['kitap_adi']) ?></td>
            <td><?= e($kitap['yazar']) ?></td>
            <td><?= e($kitap['tur']) ?></td>
            <td><?= e($kitap['yayin_yili']) ?></td>
            <td>
              <form method="post" onsubmit="return confirm('Bu kitabı silmek istediğinize emin misiniz? Kitaba ait ödünç ve favori kayıtları da silinir.');">
                <?= csrf_field() ?>
                <input type="hidden" name="islem" value="kitap_sil">
                <input type="hidden" name="kitap_id" value="<?= (int) $kitap['id'] ?>">
                <button type="submit">Sil</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endif; ?>

<?php
// ======================================================================
elseif ($sekme === 'oduncler'):
    $oduncler = db_fetch_all(
        $conn,
        'SELECT u.ad, u.soyad, k.kitap_adi, k.yazar, o.alis_tarihi, o.iade_tarihi, o.durum
           FROM odunc_kitaplar o
           JOIN users u ON u.id = o.user_id
           JOIN kitaplar k ON k.id = o.kitap_id
          ORDER BY o.alis_tarihi DESC, o.id DESC'
    );
?>
  <h2>Ödünç Takibi</h2>
  <?php if (!$oduncler): ?>
    <p>Hiç ödünç kaydı bulunamadı.</p>
  <?php else: ?>
    <div class="tablo-kapsayici">
      <table class="kitap-tablo">
        <tr><th>Ad Soyad</th><th>Kitap Adı</th><th>Yazar</th><th>Alış</th><th>İade</th><th>Durum</th></tr>
        <?php foreach ($oduncler as $row): ?>
          <tr>
            <td><?= e($row['ad'] . ' ' . $row['soyad']) ?></td>
            <td><?= e($row['kitap_adi']) ?></td>
            <td><?= e($row['yazar']) ?></td>
            <td><?= e($row['alis_tarihi']) ?></td>
            <td><?= e($row['iade_tarihi'] ?: '-') ?></td>
            <td><?= e($row['durum']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endif; ?>

<?php
// ======================================================================
elseif ($sekme === 'kullanicilar'):
    // Şifre hash'leri asla listelenmez
    $kullanicilar = db_fetch_all($conn, 'SELECT id, ad, soyad, eposta, rol FROM users ORDER BY rol, ad');
?>
  <h2>Kullanıcılar</h2>
  <div class="tablo-kapsayici">
    <table class="kitap-tablo">
      <tr><th>Ad</th><th>Soyad</th><th>E-posta</th><th>Rol</th><th>İşlem</th></tr>
      <?php foreach ($kullanicilar as $u): ?>
        <tr>
          <td><?= e($u['ad']) ?></td>
          <td><?= e($u['soyad']) ?></td>
          <td><?= e($u['eposta']) ?></td>
          <td><?= e($u['rol']) ?></td>
          <td>
            <?php if ($u['rol'] === ROL_ADMIN): ?>
              Zaten Admin
            <?php else: ?>
              <form method="post" onsubmit="return confirm('Bu kullanıcıyı admin yapmak istediğinize emin misiniz?');">
                <?= csrf_field() ?>
                <input type="hidden" name="islem" value="admin_yap">
                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                <button type="submit">Admin Yap</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

<?php
// ======================================================================
elseif ($sekme === 'hesap-degistir'):
    $adminler = db_fetch_all($conn, 'SELECT id, ad, soyad FROM users WHERE rol = ? AND id <> ? ORDER BY ad', 'si', [ROL_ADMIN, $user['id']]);
?>
  <div class="kart">
    <h2>Admin Değiştir</h2>
    <p>Başka bir admin hesabına geçmek için hesabı seçip o hesabın şifresini girin.</p>
    <?php if (!$adminler): ?>
      <p>Geçiş yapılabilecek başka admin yok.</p>
    <?php else: ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="islem" value="hesap_degistir">
        <div class="form-group">
          <label for="admin_id">Admin</label>
          <select id="admin_id" name="admin_id" required>
            <?php foreach ($adminler as $a): ?>
              <option value="<?= (int) $a['id'] ?>"><?= e($a['ad'] . ' ' . $a['soyad']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="sifre">Şifre</label>
          <input type="password" id="sifre" name="sifre" required>
        </div>
        <button type="submit" class="btn btn-tam">Geçiş Yap</button>
      </form>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php
panel_bitir();
