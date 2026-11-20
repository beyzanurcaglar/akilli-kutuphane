<?php
/**
 * Kitap ödünç alma.
 * POST: kitap_id
 */
require __DIR__ . '/../../src/bootstrap.php';

if (!is_post()) {
    json_response(['ok' => false, 'mesaj' => 'Geçersiz istek.'], 405);
}
$user = require_login('../login.php', true);
csrf_require(true);

$kitapId = (int) ($_POST['kitap_id'] ?? 0);

// Aynı anda iki kişinin aynı kitabı alamaması için işlem (transaction) içinde
// kitap satırı kilitlenir.
$conn->begin_transaction();
try {
    $kitap = db_fetch_one($conn, 'SELECT id FROM kitaplar WHERE id = ? FOR UPDATE', 'i', [$kitapId]);
    if (!$kitap) {
        $conn->rollback();
        json_response(['ok' => false, 'mesaj' => 'Kitap bulunamadı.'], 404);
    }

    $oduncte = db_fetch_one(
        $conn,
        "SELECT id FROM odunc_kitaplar WHERE kitap_id = ? AND durum = 'Teslim Edilmedi'",
        'i',
        [$kitapId]
    );
    if ($oduncte) {
        $conn->rollback();
        json_response(['ok' => false, 'mesaj' => 'Bu kitap şu anda ödünçte.'], 409);
    }

    db_query(
        $conn,
        "INSERT INTO odunc_kitaplar (kitap_id, user_id, alis_tarihi, durum) VALUES (?, ?, CURDATE(), 'Teslim Edilmedi')",
        'ii',
        [$kitapId, $user['id']]
    );
    $conn->commit();
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log($e->getMessage());
    json_response(['ok' => false, 'mesaj' => 'Ödünç alma işlemi başarısız.'], 500);
}

json_response(['ok' => true, 'mesaj' => 'Kitap başarıyla ödünç alındı.']);
