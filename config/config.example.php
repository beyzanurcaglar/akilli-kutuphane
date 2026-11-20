<?php
/**
 * Örnek yapılandırma dosyası.
 *
 * Kullanım: Bu dosyayı aynı klasöre `config.php` adıyla kopyalayın ve
 * kendi veritabanı bilgilerinizi girin. `config.php` git'e eklenmez.
 */
return [
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'kutuphane',
        'user'     => 'root',
        'password' => '',
    ],

    // İsteğe bağlı: Google Books API anahtarı. Anahtarsız istekler herkesle ortak
    // bir günlük kotayı paylaşır ve kota dolunca kapaklar yüklenemez.
    // Anahtar tarayıcıya gönderildiği için Google Cloud'da HTTP referrer kısıtlaması ekleyin.
    'google_books_api_key' => '',

    // Bu kadar dakika işlem yapılmazsa oturum otomatik kapanır.
    'oturum_suresi_dk' => 30,

    // Hata ayıklama modu: true ise PHP hataları ekranda gösterilir.
    // Canlı ortamda mutlaka false olmalıdır.
    'debug' => false,
];
