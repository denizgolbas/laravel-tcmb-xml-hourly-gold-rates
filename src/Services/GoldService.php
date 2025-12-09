<?php

namespace DenizTezc\TcmbGold\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use SimpleXMLElement;
use Exception;

class GoldService
{
    protected $baseUrl;
    protected $checkHours;
    protected $cacheDuration;
    protected $cachePrefix;

    public function __construct()
    {
        $this->baseUrl = config('tcmb-gold.base_url');
        $this->checkHours = config('tcmb-gold.check_hours', ['12:00', '14:00', '16:00']);
        $this->cacheDuration = config('tcmb-gold.cache_duration', 120);
        $this->cachePrefix = config('tcmb-gold.cache_prefix', 'tcmb_gold_');
    }

    /**
     * Get gold rates (specifically XAS).
     *
     * @param Carbon|null $date
     * @return \Illuminate\Support\Collection
     */
    public function all(?Carbon $date = null): Collection
    {
        $date = $date ?? now();
        // Format for cache key
        $dateStr = $date->format('Y-m-d');
        
        // We try to fetch successfully from one of the hours. 
        // We can cache the *result* of the day if we found it, but the reference code caches *per url*.
        // To keep it simple and efficient, let's try to find data.
        
        foreach ($this->checkHours as $hour) {
            $cacheKey = "{$this->cachePrefix}{$dateStr}_{$hour}";
            
            $data = Cache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () use ($date, $hour) {
                return $this->fetchFromUrl($date, $hour);
            });

            if ($data && $data->isNotEmpty()) {
                return $data;
            }
        }

        return collect();
    }

    protected function fetchFromUrl(Carbon $date, string $hour): ?Collection
    {
        $url = $this->buildXmlUrl($date, $hour);

        try {
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                return null;
            }
            
            return $this->parseXml($response->body());

        } catch (\Throwable $e) {
            // Log error if needed, or just return null to try next hour
            return null;
        }
    }

    protected function buildXmlUrl(Carbon $date, string $hour): string
    {
        // Reference Logic:
        // URL format: /YYYYMM/DDMMYYYY-HHMM.xml
        // Example: /202511/25112025-1200.xml
        
        $yearMonth = $date->format('Ym');
        $dayMonthYearHour = $date->format('dmY') . '-' . str_replace(':', '', $hour);
        
        return sprintf('%s/%s/%s.xml', $this->baseUrl, $yearMonth, $dayMonthYearHour);
    }

    protected function parseXml(string $xmlContent): Collection
    {
        try {
            $xml = new SimpleXMLElement($xmlContent);
            $rates = collect();

            $recordedDateTime = null;

            // Date parsing from XML metadata
            if (isset($xml->baslik_bilgi->zaman_etiketi)) {
                $recordedDateTime = Carbon::parse((string) $xml->baslik_bilgi->zaman_etiketi);
            } elseif (isset($xml->doviz_kur_liste)) {
                $gecerlilikTarihi = (string) $xml->doviz_kur_liste['gecerlilik_tarihi'];
                $saat = (string) $xml->doviz_kur_liste['saat'];
                
                if ($gecerlilikTarihi && $saat) {
                    $recordedDateTime = Carbon::createFromFormat('Y-m-d H:i', $gecerlilikTarihi . ' ' . $saat);
                }
            }

            if (isset($xml->doviz_kur_liste)) {
                foreach ($xml->doviz_kur_liste->kur as $kur) {
                    $code = (string) $kur->doviz_cinsi;
                    
                    // Support both gold types from TCMB
                    // XAU = 9999'luk Altın (sira_no: 9999)
                    // XAS = 9998'lik Altın / Has Altın (sira_no: 9998)
                    
                    $goldTypes = [
                        'XAU' => '9999\'luk Altın',
                        'XAS' => '9998\'lik Altın (Has Altın - 24 Ayar)',
                    ];
                    
                    if (array_key_exists($code, $goldTypes)) {
                        $alis = (string) $kur->alis;
                        $alis = str_replace(',', '.', $alis);
                        
                        $rates->push([
                            'code' => $code,
                            'name' => $goldTypes[$code],
                            'buying' => (float) $alis,
                            'unit' => isset($kur->birim) ? (int) $kur->birim : 1,
                            'date' => $recordedDateTime ? $recordedDateTime->toDateString() : null,
                            'timestamp' => $recordedDateTime,
                        ]);
                    }
                }
            }
            
            return $rates;

        } catch (\Throwable $e) {
            return collect();
        }
    }
}
