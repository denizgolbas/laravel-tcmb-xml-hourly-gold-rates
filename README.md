# Laravel TCMB XML Gold Rates

[![Latest Version on Packagist](https://img.shields.io/packagist/v/denizgolbas/laravel-tcmb-gold.svg?style=flat-square)](https://packagist.org/packages/denizgolbas/laravel-tcmb-gold)
[![Total Downloads](https://img.shields.io/packagist/dt/denizgolbas/laravel-tcmb-gold.svg?style=flat-square)](https://packagist.org/packages/denizgolbas/laravel-tcmb-gold)
[![Run Tests](https://github.com/denizgolbas/laravel-tcmb-gold/actions/workflows/run-tests.yml/badge.svg)](https://github.com/denizgolbas/laravel-tcmb-gold/actions)
[![License](https://img.shields.io/packagist/l/denizgolbas/laravel-tcmb-gold.svg?style=flat-square)](https://packagist.org/packages/denizgolbas/laravel-tcmb-gold)

TCMB (Türkiye Cumhuriyet Merkez Bankası) Reeskont Kurları XML servisinden altın fiyatlarını çeken Laravel paketi.

## ⚠️ Önemli Bilgi

Bu paket TCMB'nin saatlik yayınladığı **Reeskont Kurları** XML servisini kullanır. Bu serviste sadece **alış fiyatı** bulunur, satış fiyatı yoktur.

## 🏦 TCMB XML'inde Dönen Altın Türleri

TCMB Reeskont Kurları XML servisinde aşağıdaki altın türleri bulunmaktadır:

| Kod | Açıklama | Birim | Kullanım Alanı |
|-----|----------|-------|----------------|
| `XAU` | 24 Ayar Altın | 1 | TCMB tarafından belirlenen altın fiyatı |
| `XAS` | SAF (Has) Altın | 1 gram | Türkiye'deki kuyumculuk sektöründe referans fiyat |

### XAU vs XAS Farkı

- **XAU (24 Ayar Altın)**: TCMB tarafından belirlenen 24 ayar altın fiyatıdır.

- **XAS (SAF / Has Altın)**: TCMB'nin hesapladığı 1 gram saf (has) altın fiyatıdır. Türkiye'deki kuyumculuk sektöründe referans olarak kullanılır.

### XML'de Dönen Tüm Alanlar

TCMB XML'inde her altın türü için aşağıdaki bilgiler döner:

- `doviz_cinsi_tabani`: Base currency (genellikle "TRY")
- `doviz_cinsi`: Altın kodu (XAU veya XAS)
- `birim`: Birim değeri (her zaman 1)
- `alis`: Alış fiyatı (TL cinsinden, virgülle ayrılmış)

**Not:** TCMB Reeskont Kurları XML'inde sadece **alış fiyatı** bulunur, satış fiyatı yoktur.

## 🚀 Özellikler

- 🏦 **TCMB Reeskont Kurları** - Resmi altın fiyatları
  ```php
  $rates = TcmbGold::all();
  // XAU (24 Ayar) ve XAS (SAF/Has) altın fiyatlarını getirir
  ```

- ⏰ **Saatlik Güncelleme** - Gün içinde 12:00, 14:00, 16:00 saatlerinde kontrol
  ```php
  // Paket otomatik olarak 12:00, 14:00, 16:00 saatlerinde XML'i kontrol eder
  // İlk bulduğu geçerli veriyi döner
  $rates = TcmbGold::all();
  ```

- 💾 **Önbellekleme** - Performans için yapılandırılabilir cache (varsayılan 2 saat)
  ```php
  // config/tcmb-gold.php
  'cache_duration' => 120, // 2 saat (dakika cinsinden)
  
  // Cache'i temizlemek için
  Cache::forget('tcmb_gold_2025-12-09_12:00');
  ```

- 📊 **Veritabanı Desteği** - Opsiyonel olarak fiyatları kaydetme
  ```php
  use DenizTezc\TcmbGold\Models\GoldRate;
  
  $rates = TcmbGold::all();
  foreach ($rates as $rate) {
      GoldRate::updateOrCreate(
          ['code' => $rate['code'], 'date' => $rate['date']],
          $rate
      );
  }
  ```

- 🧪 **Matrix Testler** - PHP 8.1/8.2/8.3 + Laravel 10/11
  ```bash
  # GitHub Actions'da otomatik test edilir
  # 5 farklı kombinasyon: PHP 8.1 (L10), PHP 8.2/8.3 (L10+L11)
  # Not: Laravel 11 PHP 8.2+ gerektirir
  ```

## 📦 Kurulum

```bash
composer require denizgolbas/laravel-tcmb-gold
```

### Config Dosyasını Publish Etme

```bash
php artisan vendor:publish --provider="DenizTezc\TcmbGold\TcmbGoldServiceProvider" --tag="config"
```

### Migration'ları Publish Etme (Opsiyonel)

```bash
php artisan vendor:publish --provider="DenizTezc\TcmbGold\TcmbGoldServiceProvider" --tag="migrations"
php artisan migrate
```

## 🛠️ Kullanım

### Temel Kullanım

```php
use DenizTezc\TcmbGold\Facades\TcmbGold;

// Bugünün tüm altın fiyatlarını al (XAU + XAS)
$rates = TcmbGold::all();

// Tüm altın türlerini listele
foreach ($rates as $gold) {
    echo "{$gold['name']}: {$gold['buying']} TL\n";
}
// Output:
// 24 Ayar Altın: 5734.7 TL
// SAF (Has) Altın: 5763.52 TL
```

### Belirli Altın Türünü Alma

```php
// SAF (Has) Altın (gram fiyatı)
$hasAltin = $rates->firstWhere('code', 'XAS');
echo "1 gram saf altın: {$hasAltin['buying']} TL";

// 24 Ayar Altın
$xau = $rates->firstWhere('code', 'XAU');
echo "24 ayar altın: {$xau['buying']} TL";
```

### Belirli Bir Tarih İçin

```php
use Illuminate\Support\Carbon;

$date = Carbon::parse('2025-12-01');
$rates = TcmbGold::all($date);
```

### Dönen Veri Yapısı

```php
[
    [
        'code' => 'XAU',
        'name' => '24 Ayar Altın',
        'buying' => 5734.70,
        'unit' => 1,
        'date' => '2025-12-09',
        'timestamp' => Carbon::instance,
    ],
    [
        'code' => 'XAS',
        'name' => 'SAF (Has) Altın',
        'buying' => 5763.52,
        'unit' => 1,
        'date' => '2025-12-09',
        'timestamp' => Carbon::instance,
    ],
]
```

## ⚙️ Konfigürasyon

`config/tcmb-gold.php`:

```php
return [
    // TCMB Reeskont Kurları base URL
    'base_url' => env('TCMB_GOLD_BASE_URL', 'https://www.tcmb.gov.tr/reeskontkur'),
    
    // Kontrol edilecek saatler (TCMB bu saatlerde XML yayınlar)
    'check_hours' => ['12:00', '14:00', '16:00'],
    
    // Cache ayarları
    'cache_driver' => env('TCMB_GOLD_CACHE_DRIVER', 'file'),
    'cache_duration' => 120, // dakika (2 saat)
    'cache_prefix' => 'tcmb_gold_',
];
```

## 🔗 XML Servisi Hakkında

### URL Formatı

TCMB XML servisi şu URL formatını kullanır:

```
https://www.tcmb.gov.tr/reeskontkur/{YYYYMM}/{DDMMYYYY}-{HHMM}.xml
```

**URL Yapısı:**
- `{YYYYMM}`: Yıl ve ay (örn: 202512)
- `{DDMMYYYY}`: Gün, ay, yıl (örn: 09122025)
- `{HHMM}`: Saat ve dakika (örn: 1200, 1400, 1600)

**Örnek URL'ler:**

```bash
# Bugün 12:00 kurları
https://www.tcmb.gov.tr/reeskontkur/202512/09122025-1200.xml

# Bugün 14:00 kurları
https://www.tcmb.gov.tr/reeskontkur/202512/09122025-1400.xml

# Bugün 16:00 kurları
https://www.tcmb.gov.tr/reeskontkur/202512/09122025-1600.xml

# Farklı bir tarih (1 Aralık 2025, 12:00)
https://www.tcmb.gov.tr/reeskontkur/202512/01122025-1200.xml
```

**Canlı Test:**
Tarayıcınızda veya terminal'de test edebilirsiniz:

```bash
curl "https://www.tcmb.gov.tr/reeskontkur/202512/09122025-1200.xml"
```

### XML Yapısı

TCMB XML'inde dönen tam yapı:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<tcmbVeri>
    <baslik_bilgi>
        <kod>DV009</kod>
        <veri_tipi>TCMB 12:00 Kurları</veri_tipi>
        <veri_tanim>TCMB 12:00 Kurları</veri_tanim>
        <yayimlayan>TCMB Piyasalar Genel Müdürlüğü - Döviz Piyasaları Müdürlüğü</yayimlayan>
        <tel>+903125075200-27</tel>
        <faks>+903125075228</faks>
        <eposta>dovef@tcmb.gov.tr</eposta>
        <zaman_etiketi>2025-12-09T12:01:50+03:00</zaman_etiketi>
    </baslik_bilgi>
    <doviz_kur_liste gecerlilik_tarihi="2025-12-9" saat="12:00">
        <kur>
            <doviz_cinsi_tabani>TRY</doviz_cinsi_tabani>
            <doviz_cinsi>XAU</doviz_cinsi>
            <birim>1</birim>
            <alis>5734,7</alis>
            <sira_no>9999</sira_no>
        </kur>
        <kur>
            <doviz_cinsi_tabani>TRY</doviz_cinsi_tabani>
            <doviz_cinsi>XAS</doviz_cinsi>
            <birim>1</birim>
            <alis>5763,52</alis>
            <sira_no>9998</sira_no>
        </kur>
    </doviz_kur_liste>
    <aciklama></aciklama>
</tcmbVeri>
```

**XML'de Dönen Tüm Altın Türleri:**
- `XAU` - 24 Ayar Altın
- `XAS` - SAF (Has) Altın

**Not:** TCMB Reeskont Kurları XML'inde sadece bu iki altın türü bulunmaktadır. Diğer değerli metaller (gümüş, platin, paladyum) bu serviste yer almaz.

## 🧪 Testler

```bash
./vendor/bin/phpunit
```

### Matrix Test Kapsamı

| PHP | Laravel 10 | Laravel 11 |
|-----|------------|------------|
| 8.1 | ✅ | ❌ (PHP 8.2+ gerekli) |
| 8.2 | ✅ | ✅ |
| 8.3 | ✅ | ✅ |

**Not:** Laravel 11 PHP 8.2 veya üzeri gerektirir, bu yüzden PHP 8.1 ile Laravel 11 test edilmez.

## 📄 Lisans

MIT

## 🙏 Teşekkürler

- [TCMB](https://www.tcmb.gov.tr) - Veri kaynağı