<?php
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/layout.php';

// Zaten giriş yapılmışsa ilgili panele gönder
if (is_logged_in()) {
    redirect(is_admin() ? 'admin/' : 'member/');
}

$hatalar = ['login' => '', 'register' => ''];
$eski = ['ad' => '', 'soyad' => '', 'eposta' => ''];

if (is_post()) {
    csrf_require();

    // ---------- GİRİŞ ----------
    if (isset($_POST['login'])) {
        $eposta = trim($_POST['eposta'] ?? '');
        $sifre  = $_POST['sifre'] ?? '';

        $bekle = giris_engel_suresi($conn, $eposta);
        if ($bekle > 0) {
            $hatalar['login'] = "Çok fazla hatalı giriş denemesi yapıldı. Lütfen {$bekle} dakika sonra tekrar deneyin.";
        } else {
            $user = db_fetch_one($conn, 'SELECT * FROM users WHERE eposta = ?', 's', [$eposta]);

            // Kullanıcı yok / şifre yanlış ayrımı yapılmaz (hesap tahminini zorlaştırır)
            if ($user && password_verify($sifre, $user['sifre'])) {
                giris_denemelerini_temizle($conn, $eposta);
                login_user($user);
                redirect(is_admin() ? 'admin/' : 'member/');
            }
            giris_denemesi_kaydet($conn, $eposta);
            $hatalar['login'] = 'E-posta veya şifre hatalı.';
        }
    }

    // ---------- KAYIT ----------
    if (isset($_POST['register'])) {
        $ad     = trim($_POST['ad'] ?? '');
        $soyad  = trim($_POST['soyad'] ?? '');
        $eposta = trim($_POST['eposta'] ?? '');
        $sifre  = $_POST['sifre'] ?? '';
        $eski   = compact('ad', 'soyad', 'eposta');

        if ($ad === '' || $soyad === '' || !filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
            $hatalar['register'] = 'Lütfen tüm alanları geçerli şekilde doldurun.';
        } elseif (mb_strlen($sifre) < 6) {
            $hatalar['register'] = 'Şifre en az 6 karakter olmalıdır.';
        } elseif (db_fetch_one($conn, 'SELECT id FROM users WHERE eposta = ?', 's', [$eposta])) {
            $hatalar['register'] = 'Bu e-posta adresi zaten kayıtlı.';
        } else {
            db_query(
                $conn,
                'INSERT INTO users (ad, soyad, eposta, sifre, rol) VALUES (?, ?, ?, ?, ?)',
                'sssss',
                [$ad, $soyad, $eposta, password_hash($sifre, PASSWORD_DEFAULT), ROL_UYE]
            );
            flash('Kayıt başarılı! Şimdi giriş yapabilirsiniz.');
            redirect('login.php');
        }
    }
}
sayfa_baslat('Oturum Aç', ['auth.css'], '', 'giris');
?>
  <div class="kapsayici">
    <?= flash_render() ?>

    <div class="auth-izgara">
      <section class="kart">
        <h2>Oturum Aç</h2>
        <?php if ($hatalar['login']): ?>
          <div class="error-message"><?= e($hatalar['login']) ?></div>
        <?php endif; ?>
        <form action="login.php" method="post">
          <?= csrf_field() ?>
          <div class="form-group">
            <label for="giris-eposta">E-posta</label>
            <input type="email" id="giris-eposta" name="eposta" maxlength="100" autofocus placeholder="E-posta adresinizi giriniz." required>
          </div>
          <div class="form-group">
            <label for="giris-sifre">Şifre</label>
            <input type="password" id="giris-sifre" name="sifre" placeholder="Şifrenizi giriniz." required>
          </div>
          <button type="submit" name="login" class="btn-tam">Giriş Yap</button>
        </form>
      </section>

      <section class="kart">
        <h2>Üye Ol</h2>
        <?php if ($hatalar['register']): ?>
          <div class="error-message"><?= e($hatalar['register']) ?></div>
        <?php endif; ?>
        <form action="login.php" method="post">
          <?= csrf_field() ?>
          <div class="form-satir">
            <div class="form-group">
              <label for="kayit-ad">Ad</label>
              <input type="text" id="kayit-ad" name="ad" value="<?= e($eski['ad']) ?>" placeholder="Adınız" maxlength="50" required>
            </div>
            <div class="form-group">
              <label for="kayit-soyad">Soyad</label>
              <input type="text" id="kayit-soyad" name="soyad" value="<?= e($eski['soyad']) ?>" placeholder="Soyadınız" maxlength="50" required>
            </div>
          </div>
          <div class="form-group">
            <label for="kayit-eposta">E-posta</label>
            <input type="email" id="kayit-eposta" name="eposta" value="<?= e($eski['eposta']) ?>" placeholder="E-posta adresinizi giriniz." maxlength="100" required>
          </div>
          <div class="form-group">
            <label for="kayit-sifre">Şifre</label>
            <input type="password" id="kayit-sifre" name="sifre" placeholder="En az 6 karakter." minlength="6" required>
          </div>
          <label class="onay-kutusu">
            <input type="checkbox" required>
            Kullanım koşullarını kabul ediyorum.
          </label>
          <button type="submit" name="register" class="btn-tam">Kayıt Ol</button>
        </form>
      </section>
    </div>
  </div>
<?php
sayfa_bitir();
