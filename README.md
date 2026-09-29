# NK Teknoloji — E-Ticaret Sitesi

[NK Teknoloji](https://www.nkteknoloji.com.tr) için geliştirilen, PHP tabanlı, WhatsApp üzerinden sipariş alan bir e-ticaret altyapısı. Site; ürünleri döviz (USD) bazlı fiyatlandırır, günlük TCMB kuruyla TL karşılığını otomatik hesaplar ve KDV dahil/hariç tutarları müşteriye şeffaf şekilde gösterir.

## Proje Hakkında

- **Site:** www.nkteknoloji.com.tr
- **Sipariş akışı:** Müşteri ürünleri sepete ekler → sepet sayfasında fiyat özeti (KDV dahil/hariç, USD + TL) görür → "WhatsApp ile Sipariş Ver" butonuyla hazır bir sipariş mesajıyla doğrudan işletmenin WhatsApp hattına yönlendirilir.
- **Para birimi mantığı:** Ürünler USD üzerinden tanımlanır, TCMB günlük kuru ile TL karşılığı anlık hesaplanır. Kur, veritabanında önbelleğe alınır ve 6 saatte bir otomatik güncellenir.
- **Vergilendirme:** Tüm fiyatlar KDV hariç gösterilir, sepet ve WhatsApp mesajında KDV tutarı ile KDV dahil genel toplam ayrıca belirtilir.

## Klasördeki Dosyalar

| Dosya | Açıklama |
|---|---|
| `doviz-fiyat-sistemi.php` | TCMB kur çekme, döviz çevirme ve KDV hesaplama fonksiyonlarını içeren çekirdek modül. Detaylar için [DOVIZ-FIYAT-SISTEMI.md](DOVIZ-FIYAT-SISTEMI.md) dosyasına bakın. |
| `sepet.php` | Sepet sayfası: ürün ekleme/güncelleme/silme, sepet özeti ve WhatsApp sipariş entegrasyonu. |

> Not: `sepet.php`, `doviz-fiyat-sistemi.php` içindeki `formatPriceDual()` ve `dovizToTL()` fonksiyonlarını kullanır; bu yüzden iki dosya birlikte projeye dahil edilmelidir. Ayrıca projede henüz bu depoya eklenmemiş bir `config.php` dosyası (veritabanı bağlantısı, `$db`, sabitler vb.) gereklidir.

## Teknik Altyapı

- **Dil / Ortam:** PHP 7+, MySQL (cPanel barındırma üzerinde çalışacak şekilde tasarlandı)
- **Oturum yönetimi:** Sepet verileri PHP `$_SESSION` üzerinde tutulur
- **Dış servisler:** TCMB günlük kur XML servisi, WhatsApp (`wa.me` linki üzerinden sipariş)
- **Veritabanı tabloları (bilinenler):**
  - `doviz_kurlari` — döviz kurları
  - `urunler` — ürün bilgileri (fiyat, stok, resim, döviz cinsinden fiyat vb.)
  - `kategoriler` — ürün kategorileri

## Kurulum Notları

1. `config.php` dosyasını oluşturun: veritabanı bağlantısı (`$db`), `SITE_NAME`, `KDV_ORANI`, `UPLOAD_PATH`, `UPLOAD_URL` gibi sabitleri tanımlayın.
2. `doviz_kurlari`, `urunler`, `kategoriler` tablolarını veritabanınızda oluşturun (bkz. `DOVIZ-FIYAT-SISTEMI.md`).
3. WhatsApp numarasını `getSetting('whatsapp_number', ...)` üzerinden yönetin ya da ayarlar tablonuzda tanımlayın.
4. `updateKurTCMB()` fonksiyonunun düzenli çalışması için cron kurulumu opsiyonel olarak yapılabilir (detay: `DOVIZ-FIYAT-SISTEMI.md`).

## Yol Haritası / Eklenmesi Planlanan Dosyalar

Bu depo şu an projenin tamamını değil, üzerinde çalışılan modülleri içeriyor. Aşağıdaki dosyalar projenin çalışması için gereklidir ancak henüz bu depoya eklenmemiştir:

- `config.php`
- `index.php`
- `urunler.php`
- Ürün/kategori yönetim paneli dosyaları (varsa)
