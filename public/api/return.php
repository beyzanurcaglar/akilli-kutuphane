<?php
/**
 * Ödünç alınan kitabı iade etme. Kullanıcı yalnızca kendi ödüncünü iade edebilir.
 * POST: odunc_id
 */
require __DIR__ . '/../../src/bootstrap.php';

if (!is_post()) {
    json_response(['ok' => false, 'mesaj' => 'Geçersiz istek.'], 405);
}
$user = require_login('../login.php', true);
csrf_require(true);

$oduncId = (int) ($_POST['odunc_id'] ?? 0);

$guncellenen = db_query(
    $conn,
    "UPDATE odunc_kitaplar
        SET durum = 'Teslim Edildi', iade_tarihi = CURDATE()
      WHERE id = ? AND user_id = ? AND durum = 'Teslim Edilmedi'",
    'ii',
    [$oduncId, $user['id']]
);

if ($guncellenen === 0) {
    json_response(['ok' => false, 'mesaj' => 'İade edilecek ödünç kaydı bulunamadı.'], 404);
}

json_response(['ok' => true, 'mesaj' => 'İade işlemi başarılı.']);
