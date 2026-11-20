/**
 * Ortak istemci tarafı yardımcıları.
 */

/** Sayfadaki CSRF anahtarını döndürür. */
function csrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.content : '';
}

/**
 * Sayfanın sağ üst köşesinde birkaç saniye görünen bir bildirim gösterir.
 * @param {string} mesaj
 * @param {'basari'|'hata'} tur
 */
function bildirim(mesaj, tur = 'basari') {
  let kutu = document.querySelector('.bildirimler');
  if (!kutu) {
    kutu = document.createElement('div');
    kutu.className = 'bildirimler';
    kutu.setAttribute('aria-live', 'polite');
    document.body.appendChild(kutu);
  }

  const el = document.createElement('div');
  el.className = 'bildirim bildirim-' + tur;
  el.textContent = mesaj;
  el.addEventListener('click', () => el.remove());
  kutu.appendChild(el);

  setTimeout(() => {
    el.classList.add('kapaniyor');
    setTimeout(() => el.remove(), 300);
  }, 3500);
}

/** Sayfa yenilendikten sonra gösterilecek bildirimi kaydeder. */
function bildirimSonra(mesaj, tur = 'basari') {
  try { sessionStorage.setItem('bekleyenBildirim', JSON.stringify({ mesaj, tur })); } catch (_) { /* yoksay */ }
}

document.addEventListener('DOMContentLoaded', () => {
  try {
    const kayit = sessionStorage.getItem('bekleyenBildirim');
    if (kayit) {
      sessionStorage.removeItem('bekleyenBildirim');
      const { mesaj, tur } = JSON.parse(kayit);
      bildirim(mesaj, tur);
    }
  } catch (_) { /* yoksay */ }
});

/**
 * Sunucuya POST isteği gönderir ve JSON yanıtını döndürür.
 * Yanıt biçimi: { ok: boolean, mesaj: string, ... }
 */
async function postJson(url, data) {
  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrfToken() },
      body: new URLSearchParams(data),
    });
    return await res.json();
  } catch (err) {
    console.error(err);
    return { ok: false, mesaj: 'Sunucuya ulaşılamadı.' };
  }
}

/**
 * Google Books'ta arama yapar. Hata/kota aşımı durumunda null döndürür.
 * config.php'de API anahtarı tanımlıysa kullanılır.
 */
let googleKotaDoldu = false;

async function googleBooksAra(sorgu) {
  if (googleKotaDoldu) return null;
  const meta = document.querySelector('meta[name="google-books-key"]');
  let url = 'https://www.googleapis.com/books/v1/volumes?maxResults=1&q=' + encodeURIComponent(sorgu);
  if (meta) url += '&key=' + encodeURIComponent(meta.content);
  try {
    const res = await fetch(url);
    // Kota aşıldıysa bu sayfada Google'a tekrar istek gönderme
    if (res.status === 429) googleKotaDoldu = true;
    return res.ok ? await res.json() : null;
  } catch (_) {
    return null;
  }
}

/* ---------------------------------------------------------------------
 * Kitap kapakları
 * Sırasıyla şu kaynaklar denenir:
 *   1. Open Library (ISBN ile)
 *   2. Google Books (başlık + yazar)
 *   3. Open Library (başlık + yazar araması)
 * Bulunan adres tarayıcıda önbelleğe alınır; bulunamazsa yer tutucu kalır.
 * ------------------------------------------------------------------- */

/** Görselin gerçekten yüklenebildiğini kontrol eder. */
function gorselYuklenirMi(url) {
  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => resolve(img.naturalWidth > 1);
    img.onerror = () => resolve(false);
    img.src = url;
  });
}

async function kapakBul(baslik, yazar, isbn) {
  if (isbn) {
    const url = `https://covers.openlibrary.org/b/isbn/${isbn}-M.jpg?default=false`;
    if (await gorselYuklenirMi(url)) return url;
  }

  const gb = await googleBooksAra('intitle:' + baslik + (yazar ? ' inauthor:' + yazar : ''));
  const gbUrl = gb?.items?.[0]?.volumeInfo?.imageLinks?.thumbnail;
  if (gbUrl) return gbUrl.replace('http://', 'https://');

  // Önce başlık + yazar, bulunamazsa yalnızca başlık ile ara
  for (const yazarIle of yazar ? [true, false] : [false]) {
    try {
      const params = new URLSearchParams({ title: baslik, limit: '5', fields: 'cover_i' });
      if (yazarIle) params.set('author', yazar);
      const res = await fetch('https://openlibrary.org/search.json?' + params);
      const veri = await res.json();
      const id = veri.docs?.find((d) => d.cover_i)?.cover_i;
      if (id) return `https://covers.openlibrary.org/b/id/${id}-M.jpg`;
    } catch (_) { /* yoksay */ }
  }

  return null;
}

async function kapaklariYukle() {
  const resimler = document.querySelectorAll('img[data-kapak-baslik]');
  await Promise.all([...resimler].map(async (img) => {
    const { kapakBaslik: baslik, kapakYazar: yazar, kapakIsbn: isbn } = img.dataset;
    if (!baslik) return;

    const anahtar = 'kapak:' + (isbn || baslik);
    let url = null;
    try { url = localStorage.getItem(anahtar); } catch (_) { /* yoksay */ }

    if (!url) {
      url = await kapakBul(baslik, yazar, isbn);
      // Yalnızca başarılı sonuçlar önbelleğe alınır; böylece geçici
      // hatalar (ör. API kotası) kalıcı olarak "kapak yok" sonucuna yol açmaz.
      if (url) {
        try { localStorage.setItem(anahtar, url); } catch (_) { /* yoksay */ }
      }
    }

    if (url) img.src = url;
  }));
}

document.addEventListener('DOMContentLoaded', kapaklariYukle);
