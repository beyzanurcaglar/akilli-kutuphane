<?php
/**
 * Oturum ve yetkilendirme yardımcıları.
 * Oturumda giriş yapan kullanıcının bilgileri $_SESSION['user'] altında tutulur.
 */

const ROL_UYE   = 'uye';
const ROL_ADMIN = 'admin';

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    return (current_user()['rol'] ?? null) === ROL_ADMIN;
}

/**
 * Belirtilen süre boyunca işlem yapılmamışsa kullanıcının oturumunu kapatır.
 * Her sayfa yüklemesinde bootstrap.php tarafından çağrılır.
 */
function oturum_zaman_asimi_kontrol(int $dakika): void
{
    if (!is_logged_in()) {
        return;
    }
    $son = $_SESSION['son_aktivite'] ?? time();
    if (time() - $son > $dakika * 60) {
        unset($_SESSION['user'], $_SESSION['son_aktivite']);
        session_regenerate_id(true);
        flash('Uzun süre işlem yapılmadığı için oturumunuz kapatıldı. Lütfen tekrar giriş yapın.', 'hata');
        return;
    }
    $_SESSION['son_aktivite'] = time();
}

/* ---------- Giriş denemesi sınırı ---------- */

const GIRIS_SINIRI_EPOSTA = 5;   // aynı e-posta için izin verilen hatalı deneme
const GIRIS_SINIRI_IP     = 20;  // aynı IP adresi için izin verilen hatalı deneme
const GIRIS_BEKLEME_DK    = 15;  // denemelerin sayıldığı süre (dakika)

function istemci_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

/**
 * Çok fazla hatalı deneme yapıldıysa beklenmesi gereken dakikayı döndürür,
 * yapılmadıysa 0 döndürür.
 */
function giris_engel_suresi(mysqli $conn, string $eposta): int
{
    // Süre hesabı tamamen MySQL'de yapılır; böylece PHP ile veritabanının
    // saat dilimleri farklı olsa bile sonuç doğru çıkar.
    $dk = GIRIS_BEKLEME_DK;
    $satir = db_fetch_one(
        $conn,
        "SELECT
            SUM(eposta = ?) AS eposta_sayi,
            SUM(ip = ?)     AS ip_sayi,
            TIMESTAMPDIFF(SECOND, NOW(), MIN(CASE WHEN eposta = ? THEN zaman END) + INTERVAL $dk MINUTE) AS eposta_kalan,
            TIMESTAMPDIFF(SECOND, NOW(), MIN(CASE WHEN ip = ? THEN zaman END) + INTERVAL $dk MINUTE)     AS ip_kalan
           FROM giris_denemeleri
          WHERE zaman > NOW() - INTERVAL $dk MINUTE AND (eposta = ? OR ip = ?)",
        'ssssss',
        [$eposta, istemci_ip(), $eposta, istemci_ip(), $eposta, istemci_ip()]
    );

    // En eski deneme süre dışına çıkınca engel kalkar
    if ((int) $satir['eposta_sayi'] >= GIRIS_SINIRI_EPOSTA) {
        $kalanSn = (int) $satir['eposta_kalan'];
    } elseif ((int) $satir['ip_sayi'] >= GIRIS_SINIRI_IP) {
        $kalanSn = (int) $satir['ip_kalan'];
    } else {
        return 0;
    }
    return max(1, (int) ceil($kalanSn / 60));
}

function giris_denemesi_kaydet(mysqli $conn, string $eposta): void
{
    db_query($conn, 'INSERT INTO giris_denemeleri (eposta, ip) VALUES (?, ?)', 'ss', [$eposta, istemci_ip()]);
    // Eski kayıtları temizle
    db_query($conn, 'DELETE FROM giris_denemeleri WHERE zaman < DATE_SUB(NOW(), INTERVAL 1 DAY)');
}

function giris_denemelerini_temizle(mysqli $conn, string $eposta): void
{
    db_query($conn, 'DELETE FROM giris_denemeleri WHERE eposta = ?', 's', [$eposta]);
}

/** Kullanıcıyı oturuma kaydeder. */
function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['son_aktivite'] = time();
    $_SESSION['user'] = [
        'id'     => (int) $user['id'],
        'ad'     => $user['ad'],
        'soyad'  => $user['soyad'],
        'eposta' => $user['eposta'],
        'rol'    => trim((string) $user['rol']),
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Giriş zorunlu sayfalar için. Giriş yoksa login sayfasına yönlendirir
 * ($json = true ise JSON hata döndürür).
 */
function require_login(string $loginUrl = '../login.php', bool $json = false): array
{
    if (!is_logged_in()) {
        if ($json) {
            json_response(['ok' => false, 'mesaj' => 'Bu işlem için giriş yapmalısınız.'], 401);
        }
        redirect($loginUrl);
    }
    return current_user();
}

/** Yalnızca adminlerin erişebileceği sayfalar için. */
function require_admin(string $loginUrl = '../login.php', bool $json = false): array
{
    $user = require_login($loginUrl, $json);
    if (!is_admin()) {
        if ($json) {
            json_response(['ok' => false, 'mesaj' => 'Bu işlem için yetkiniz yok.'], 403);
        }
        http_response_code(403);
        exit('Bu sayfaya erişim yetkiniz yok.');
    }
    return $user;
}
