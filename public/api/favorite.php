<?php
/**
 * Favori kitap ekleme / çıkarma.
 * POST: kitap_id, islem = ekle | sil
 */
require __DIR__ . '/../../src/bootstrap.php';

if (!is_post()) {
    json_response(['ok' => false, 'mesaj' => 'Geçersiz istek.'], 405);
}
$user = require_login('../login.php', true);
csrf_require(true);

$kitapId = (int) ($_POST['kitap_id'] ?? 0);
$islem   = $_POST['islem'] ?? '';

if (!db_fetch_one($conn, 'SELECT id FROM kitaplar WHERE id = ?', 'i', [$kitapId])) {
    json_response(['ok' => false, 'mesaj' => 'Kitap bulunamadı.'], 404);
}

if ($islem === 'ekle') {
    // UNIQUE (kitap_id, user_id) kısıtı sayesinde tekrar eklenemez
    $eklendi = db_query($conn, 'INSERT IGNORE INTO favori_kitaplar (kitap_id, user_id) VALUES (?, ?)', 'ii', [$kitapId, $user['id']]) > 0;
    json_response([
        'ok'    => $eklendi,
        'mesaj' => $eklendi ? 'Kitap favorilere eklendi.' : 'Bu kitap zaten favorilerde.',
    ]);
}

if ($islem === 'sil') {
    db_query($conn, 'DELETE FROM favori_kitaplar WHERE kitap_id = ? AND user_id = ?', 'ii', [$kitapId, $user['id']]);
    json_response(['ok' => true, 'mesaj' => 'Kitap favorilerden çıkarıldı.']);
}

json_response(['ok' => false, 'mesaj' => 'Geçersiz işlem.'], 400);
