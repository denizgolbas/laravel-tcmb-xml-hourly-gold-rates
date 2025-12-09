<?php

namespace DenizTezc\TcmbGold\Tests\Feature;

use DenizTezc\TcmbGold\Tests\TestCase;
use DenizTezc\TcmbGold\Facades\TcmbGold;
use Illuminate\Support\Facades\Http;

class GoldRateFetchTest extends TestCase
{
    /** @test */
    public function it_can_fetch_gold_rates_from_xml()
    {
        // Mock the XML response (matching real TCMB XML structure)
        $xmlContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tcmbVeri>
    <baslik_bilgi>
        <kod>DV009</kod>
        <veri_tipi>TCMB 12:00 Kurları</veri_tipi>
        <zaman_etiketi>2025-12-09T12:01:50+03:00</zaman_etiketi>
    </baslik_bilgi>
    <doviz_kur_liste gecerlilik_tarihi="2025-12-09" saat="12:00">
        <kur>
            <doviz_cinsi_tabani>TRY</doviz_cinsi_tabani>
            <doviz_cinsi>USD</doviz_cinsi>
            <birim>1</birim>
            <alis>30,5000</alis>
            <sira_no>1</sira_no>
        </kur>
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
            <alis>1850,50</alis>
            <sira_no>9998</sira_no>
        </kur>
    </doviz_kur_liste>
</tcmbVeri>
XML;

        // The service generates dynamic URLs based on date and checking hours (12:00, 14:00, 16:00).
        // specific URL depends on current date/time mocking.
        // We can just wildchar the URL because logic inside uses specific URL generation.
        
        Http::fake([
            'https://www.tcmb.gov.tr/reeskontkur/*' => Http::response($xmlContent, 200),
        ]);

        $service = new \DenizTezc\TcmbGold\Services\GoldService();
        
        // We need to ensure we are testing with a known date if possible, but service defaults to now().
        // Let's pass a date to ensure consistent URL generation in test if needed, or rely on 'now' being mocked if we did.
        // But here we mocked Http with wildcard, so it should catch any request.
        
        $data = $service->all();

        $this->assertNotEmpty($data);
        $this->assertCount(2, $data);
        
        // Test XAS (SAF / Has Altın)
        $xas = $data->firstWhere('code', 'XAS');
        $this->assertNotNull($xas);
        $this->assertEquals(1850.50, $xas['buying']);
        $this->assertEquals('SAF (Has) Altın', $xas['name']);
        $this->assertEquals(1, $xas['unit']);
        
        // Test XAU (24 Ayar Altın)
        $xau = $data->firstWhere('code', 'XAU');
        $this->assertNotNull($xau);
        $this->assertEquals(5734.7, $xau['buying']);
        $this->assertEquals('24 Ayar Altın', $xau['name']);
        $this->assertEquals(1, $xau['unit']);
    }

    /** @test */
    public function it_can_fetch_real_gold_rates_from_tcmb_xml()
    {
        // Bu test gerçek TCMB XML servisinden veri çeker
        // Not: Bu test internet bağlantısı gerektirir ve TCMB servisinin erişilebilir olması gerekir
        
        $service = new \DenizTezc\TcmbGold\Services\GoldService();
        
        // Bugünün tarihini kullan (veya geçmiş bir tarih)
        $date = \Illuminate\Support\Carbon::now();
        
        // Eğer bugün hafta sonu ise, son iş gününü kullan
        if ($date->isWeekend()) {
            $date = $date->subDays($date->dayOfWeek === 0 ? 2 : 1);
        }
        
        $data = $service->all($date);
        
        // Eğer bugün için veri yoksa (örneğin hafta sonu veya henüz yayınlanmamışsa)
        // test'i skip et
        if ($data->isEmpty()) {
            $this->markTestSkipped('TCMB XML servisinden bugün için veri alınamadı. Servis erişilebilir olmayabilir veya henüz yayınlanmamış olabilir.');
        }
        
        // Veri varsa test et
        $this->assertNotEmpty($data, 'TCMB XML\'den altın fiyatları çekilemedi');
        $this->assertGreaterThanOrEqual(1, $data->count(), 'En az bir altın türü dönmeli');
        
        // XAU veya XAS'den en az biri olmalı
        $hasXAU = $data->contains(function ($item) {
            return $item['code'] === 'XAU';
        });
        
        $hasXAS = $data->contains(function ($item) {
            return $item['code'] === 'XAS';
        });
        
        $this->assertTrue($hasXAU || $hasXAS, 'XAU veya XAS altın türlerinden en az biri dönmeli');
        
        // Her bir altın türü için veri yapısını kontrol et
        foreach ($data as $gold) {
            $this->assertArrayHasKey('code', $gold, 'Altın verisi code anahtarı içermeli');
            $this->assertArrayHasKey('name', $gold, 'Altın verisi name anahtarı içermeli');
            $this->assertArrayHasKey('buying', $gold, 'Altın verisi buying anahtarı içermeli');
            $this->assertArrayHasKey('unit', $gold, 'Altın verisi unit anahtarı içermeli');
            
            $this->assertContains($gold['code'], ['XAU', 'XAS'], 'Altın kodu XAU veya XAS olmalı');
            $this->assertIsString($gold['name'], 'Altın adı string olmalı');
            $this->assertIsFloat($gold['buying'], 'Alış fiyatı float olmalı');
            $this->assertGreaterThan(0, $gold['buying'], 'Alış fiyatı 0\'dan büyük olmalı');
            $this->assertIsInt($gold['unit'], 'Birim integer olmalı');
            $this->assertEquals(1, $gold['unit'], 'Birim değeri 1 olmalı');
            
            // Tarih kontrolü
            if (isset($gold['date'])) {
                $this->assertNotNull($gold['date'], 'Tarih null olmamalı');
            }
        }
        
        // XAU varsa test et
        if ($hasXAU) {
            $xau = $data->firstWhere('code', 'XAU');
            $this->assertEquals('24 Ayar Altın', $xau['name']);
            $this->assertGreaterThan(1000, $xau['buying'], 'XAU fiyatı makul bir değer olmalı (1000 TL üzeri)');
        }
        
        // XAS varsa test et
        if ($hasXAS) {
            $xas = $data->firstWhere('code', 'XAS');
            $this->assertEquals('SAF (Has) Altın', $xas['name']);
            $this->assertGreaterThan(100, $xas['buying'], 'XAS fiyatı makul bir değer olmalı (100 TL üzeri)');
        }
    }
}
