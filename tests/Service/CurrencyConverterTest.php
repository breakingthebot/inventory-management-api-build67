<?php

// tests/Service/CurrencyConverterTest.php
// Unit tests for CurrencyConverter service verifying exchange rate conversions and gross tax matrix calculations.
// Connects to: src/Service/CurrencyConverter.php, src/Entity/CurrencyRate.php, src/Entity/TaxZone.php
// Created: 2026-08-05

namespace App\Tests\Service;

use App\Entity\CurrencyRate;
use App\Entity\TaxZone;
use App\Repository\CurrencyRateRepository;
use App\Repository\TaxZoneRepository;
use App\Service\CurrencyConverter;
use PHPUnit\Framework\TestCase;

class CurrencyConverterTest extends TestCase
{
    private CurrencyRateRepository $currencyRateRepository;
    private TaxZoneRepository $taxZoneRepository;
    private CurrencyConverter $converter;

    protected function setUp(): void
    {
        $this->currencyRateRepository = $this->createMock(CurrencyRateRepository::class);
        $this->taxZoneRepository = $this->createMock(TaxZoneRepository::class);

        $this->converter = new CurrencyConverter(
            $this->currencyRateRepository,
            $this->taxZoneRepository
        );
    }

    public function testCurrencyConversionWithTaxZone(): void
    {
        $eur = new CurrencyRate();
        $eur->setCode('EUR');
        $eur->setSymbol('€');
        $eur->setRateToBase(0.920000);

        $taxZone = new TaxZone();
        $taxZone->setCode('EU-DE');
        $taxZone->setName('Germany VAT');
        $taxZone->setRatePercent(19.00);

        $this->currencyRateRepository->method('findOneBy')->with(['code' => 'EUR'])->willReturn($eur);
        $this->taxZoneRepository->method('findOneBy')->with(['code' => 'EU-DE'])->willReturn($taxZone);

        // $100.00 USD * 0.92 = 92.00 EUR
        // Tax 19% on 92.00 EUR = 17.48 EUR
        // Gross = 109.48 EUR
        $result = $this->converter->convertAndCalculateTax(100.00, 'EUR', 'EU-DE');

        $this->assertEquals('100.00', $result['base_price_usd']);
        $this->assertEquals('EUR', $result['currency_code']);
        $this->assertEquals('€', $result['currency_symbol']);
        $this->assertEquals('92.00', $result['net_price']);
        $this->assertEquals('17.48', $result['tax_amount']);
        $this->assertEquals('109.48', $result['gross_price']);
        $this->assertEquals('€109.48', $result['formatted_gross']);
    }
}
