-- Örnek (demo) veriler. Yalnızca yerel geliştirme içindir.
-- Kullanım: mysql -u root -p kutuphane < database/seed.sql
--
-- Demo hesaplar:
--   Admin: admin@example.com / admin123
--   Üye  : uye@example.com   / uye123

SET NAMES utf8mb4;
USE kutuphane;

INSERT INTO users (ad, soyad, eposta, sifre, rol) VALUES
  ('Demo', 'Admin', 'admin@example.com', '$2y$12$xIHe9e.OUEzyUWI40nfNauVLp/6DqIstphS0HX9LQhYRMLNm0bPGS', 'admin'),
  ('Demo', 'Üye',   'uye@example.com',   '$2y$12$/Svm9mh.Xo2h0YUzR4v7J.LqCP1dNgzNyPZ5ZdIvuYp3GBfoLL16i', 'uye');

INSERT INTO kitaplar (kitap_adi, yazar, yayin_yili, yayin_evi, tur) VALUES
  ('Kürk Mantolu Madonna', 'Sabahattin Ali', 1943, 'Yapı Kredi Yayınları', 'Roman'),
  ('Tutunamayanlar', 'Oğuz Atay', 1972, 'İletişim Yayınları', 'Roman'),
  ('Saatleri Ayarlama Enstitüsü', 'Ahmet Hamdi Tanpınar', 1961, 'Dergâh Yayınları', 'Roman'),
  ('İnce Memed', 'Yaşar Kemal', 1955, 'Yapı Kredi Yayınları', 'Roman'),
  ('Suç ve Ceza', 'Fyodor Dostoyevski', 1866, 'İş Bankası Kültür Yayınları', 'Klasik'),
  ('1984', 'George Orwell', 1949, 'Can Yayınları', 'Distopya'),
  ('Simyacı', 'Paulo Coelho', 1988, 'Can Yayınları', 'Roman'),
  ('Küçük Prens', 'Antoine de Saint-Exupéry', 1943, 'Can Çocuk', 'Çocuk');
