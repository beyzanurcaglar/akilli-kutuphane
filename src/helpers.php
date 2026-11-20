<?php
/**
 * Genel yardımcı fonksiyonlar.
 */

/** HTML çıktısı için güvenli kaçış (XSS koruması). */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Uygulama içi bir yola yönlendirir ve çıkar. */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Bir sonraki sayfa yüklemesinde gösterilecek mesaj kaydeder. */
function flash(string $message, string $type = 'basari'): void
{
    $_SESSION['flash'] = ['mesaj' => $message, 'tur' => $type];
}

/** Kaydedilmiş flash mesajını HTML olarak döndürür ve siler. */
function flash_render(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<div class="flash flash-' . e($f['tur']) . '">' . e($f['mesaj']) . '</div>';
}

/** JSON yanıtı gönderir ve çıkar. */
function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------- Kitap kapakları ---------- */

/**
 * Bir kitap için kapak <img> etiketi üretir. Kayıtlı kapak varsa doğrudan
 * kullanılır; yoksa yer tutucu gösterilir ve assets/js/app.js kapağı
 * Open Library / Google Books üzerinden bulmaya çalışır.
 *
 * @param string $assets "assets/" klasörünün göreli yolu (ör. "assets/" veya "../assets/")
 */
function kapak_img(array $kitap, string $assets = 'assets/', string $class = ''): string
{
    $placeholder = $assets . 'img/cover-placeholder.svg';
    $alt = ($kitap['kitap_adi'] ?? '') . ' kapağı';
    $attr = $class !== '' ? ' class="' . e($class) . '"' : '';

    if (!empty($kitap['kapak_url'])) {
        return '<img src="' . e($kitap['kapak_url']) . '" alt="' . e($alt) . '"' . $attr
            . ' onerror="this.onerror=null;this.src=\'' . e($placeholder) . '\'">';
    }

    return '<img src="' . e($placeholder) . '" alt="' . e($alt) . '"' . $attr
        . ' data-kapak-baslik="' . e($kitap['kitap_adi'] ?? '') . '"'
        . ' data-kapak-yazar="' . e($kitap['yazar'] ?? '') . '"'
        . ' data-kapak-isbn="' . e(preg_replace('/[^0-9X]/i', '', $kitap['isbn'] ?? '')) . '">';
}

/** Google Books API anahtarını (varsa) tarayıcıya iletir. */
function google_books_meta(): string
{
    global $config;
    $key = $config['google_books_api_key'] ?? '';
    return $key !== '' ? '<meta name="google-books-key" content="' . e($key) . '">' : '';
}

/* ---------- CSRF koruması ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Formlara eklenecek gizli alan. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Sayfa <head> kısmına eklenecek meta etiketi (fetch istekleri için). */
function csrf_meta(): string
{
    return '<meta name="csrf-token" content="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($token) && hash_equals(csrf_token(), $token);
}

/** POST isteğinde CSRF doğrulaması yapar; geçersizse isteği reddeder. */
function csrf_require(bool $json = false): void
{
    if (!csrf_valid()) {
        if ($json) {
            json_response(['ok' => false, 'mesaj' => 'Geçersiz istek (CSRF). Sayfayı yenileyip tekrar deneyin.'], 419);
        }
        http_response_code(419);
        exit('Geçersiz istek. Sayfayı yenileyip tekrar deneyin.');
    }
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}
