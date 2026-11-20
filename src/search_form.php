<?php
/**
 * Anasayfada ve arama sayfasında kullanılan arama kutusu.
 * Arama sayfasında önceki arama değerleri korunur.
 */
$aramaAlani = $_GET['keyword'] ?? 'kitap';
$aramaMetni = $_GET['query'] ?? '';
$alanlar = [
    'kitap' => 'Kitap Adı',
    'yazar' => 'Yazar Adı',
    'tur'   => 'Kitap Türü',
    'id'    => 'Kod',
];
?>
<form action="search.php" method="GET" class="arama-formu" role="search">
  <select name="keyword" aria-label="Arama alanı">
    <?php foreach ($alanlar as $deger => $etiket): ?>
      <option value="<?= $deger ?>"<?= $aramaAlani === $deger ? ' selected' : '' ?>><?= $etiket ?></option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="query" value="<?= e($aramaMetni) ?>" placeholder="Aramak istediğiniz kelimeyi giriniz..." aria-label="Arama metni" required>
  <button type="submit">Ara</button>
</form>
