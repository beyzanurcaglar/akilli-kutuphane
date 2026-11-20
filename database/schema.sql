-- Akıllı Kütüphane Sistemi - veritabanı şeması
-- Kullanım: mysql -u root -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS kutuphane
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_turkish_ci;

SET NAMES utf8mb4;
USE kutuphane;

CREATE TABLE IF NOT EXISTS users (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ad         VARCHAR(50)  NOT NULL,
  soyad      VARCHAR(50)  NOT NULL,
  eposta     VARCHAR(100) NOT NULL UNIQUE,
  sifre      VARCHAR(255) NOT NULL,              -- password_hash() çıktısı
  rol        ENUM('uye', 'admin') NOT NULL DEFAULT 'uye',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kitaplar (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kitap_adi  VARCHAR(255) NOT NULL,
  yazar      VARCHAR(255) NULL,
  yayin_yili SMALLINT     NULL,
  yayin_evi  VARCHAR(255) NULL,
  tur        VARCHAR(255) NULL,
  isbn       VARCHAR(20)  NULL,
  kapak_url  VARCHAR(500) NULL,                   -- kitap eklenirken kaydedilen kapak görseli
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_kitap_adi (kitap_adi),
  INDEX idx_yazar (yazar)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS favori_kitaplar (
  id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kitap_id INT UNSIGNED NOT NULL,
  user_id  INT UNSIGNED NOT NULL,
  UNIQUE KEY uq_favori (kitap_id, user_id),
  FOREIGN KEY (kitap_id) REFERENCES kitaplar(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)  REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS odunc_kitaplar (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kitap_id    INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NOT NULL,
  alis_tarihi DATE NOT NULL,
  iade_tarihi DATE NULL,
  durum       ENUM('Teslim Edilmedi', 'Teslim Edildi') NOT NULL DEFAULT 'Teslim Edilmedi',
  INDEX idx_durum (kitap_id, durum),
  FOREIGN KEY (kitap_id) REFERENCES kitaplar(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)  REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- Başarısız giriş denemeleri (kaba kuvvet / şifre deneme saldırılarına karşı)
CREATE TABLE IF NOT EXISTS giris_denemeleri (
  id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  eposta VARCHAR(100) NOT NULL,
  ip     VARCHAR(45)  NOT NULL,
  zaman  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_eposta_zaman (eposta, zaman),
  INDEX idx_ip_zaman (ip, zaman)
) ENGINE=InnoDB;
