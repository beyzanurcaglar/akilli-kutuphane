<?php
/**
 * Veritabanı bağlantısı (mysqli). Hatalar istisna olarak fırlatılır.
 */

function db_connect(array $db): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn = new mysqli(
            $db['host'],
            $db['user'],
            $db['password'],
            $db['name'],
            (int) ($db['port'] ?? 3306)
        );
        $conn->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $e) {
        error_log('Veritabanı bağlantı hatası: ' . $e->getMessage());
        http_response_code(500);
        exit('Veritabanına bağlanılamadı. Lütfen config/config.php ayarlarını kontrol edin.');
    }

    return $conn;
}

/**
 * Hazırlanmış (prepared) sorgu çalıştırır ve sonucu döndürür.
 *
 * @param string $types  bind_param tip dizisi (ör. "is")
 * @param array  $params Parametreler
 * @return mysqli_result|int SELECT için sonuç kümesi; INSERT/UPDATE/DELETE için
 *                           etkilenen satır sayısı
 */
function db_query(mysqli $conn, string $sql, string $types = '', array $params = [])
{
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();

    // Sonuç kümesi döndürmeyen sorgularda get_result() çağrılmaz;
    // aksi hâlde affected_rows bilgisi kaybolur.
    if ($stmt->result_metadata() === false) {
        return $stmt->affected_rows;
    }
    return $stmt->get_result();
}

/** Tek satır döndürür (yoksa null). */
function db_fetch_one(mysqli $conn, string $sql, string $types = '', array $params = []): ?array
{
    $result = db_query($conn, $sql, $types, $params);
    $row = $result->fetch_assoc();
    return $row ?: null;
}

/** Tüm satırları dizi olarak döndürür. */
function db_fetch_all(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    return db_query($conn, $sql, $types, $params)->fetch_all(MYSQLI_ASSOC);
}
