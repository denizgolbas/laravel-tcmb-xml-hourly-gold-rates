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
        
        // Test XAS (9998'lik Altın / Has Altın)
        $xas = $data->firstWhere('code', 'XAS');
        $this->assertNotNull($xas);
        $this->assertEquals(1850.50, $xas['buying']);
        $this->assertEquals('9998\'lik Altın (Has Altın - 24 Ayar)', $xas['name']);
        $this->assertEquals(1, $xas['unit']);
        
        // Test XAU (9999'luk Altın)
        $xau = $data->firstWhere('code', 'XAU');
        $this->assertNotNull($xau);
        $this->assertEquals(5734.7, $xau['buying']);
        $this->assertEquals('9999\'luk Altın', $xau['name']);
        $this->assertEquals(1, $xau['unit']);
    }
}
