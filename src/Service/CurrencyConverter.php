<?php

// src/Service/CurrencyConverter.php
// Domain service handling multi-currency conversions, exchange rate lookup, and regional tax calculations.
// Connects to: src/Entity/CurrencyRate.php, src/Entity/TaxZone.php
// Created: 2026-08-05

namespace App\Service;

use App\Entity\CurrencyRate;
use App\Entity\TaxZone;
use App\Repository\CurrencyRateRepository;
use App\Repository\TaxZoneRepository;

class CurrencyConverter
{
    public function __construct(
        private readonly CurrencyRateRepository $currencyRateRepository,
        private readonly TaxZoneRepository $taxZoneRepository
    ) {
    }

    /**
     * Converts base USD price into target currency and computes regional gross/net tax breakdown.
     */
    public function convertAndCalculateTax(
        float $amountInUsd,
        string $targetCurrencyCode = 'USD',
        ?string $taxZoneCode = null
    ): array {
        $this->ensureSeededRatesAndTaxZones();

        $code = strtoupper(trim($targetCurrencyCode));
        $currencyRate = $this->currencyRateRepository->findOneBy(['code' => $code]);

        if (!$currencyRate) {
            $currencyRate = new CurrencyRate();
            $currencyRate->setCode($code);
            $currencyRate->setSymbol($code);
            $currencyRate->setRateToBase(1.0);
        }

        $rateMultiplier = (float)$currencyRate->getRateToBase();
        $netAmount = round($amountInUsd * $rateMultiplier, 2);

        $taxRatePercent = 0.0;
        $taxZoneName = 'Tax Exempt / Default';

        if ($taxZoneCode !== null && $taxZoneCode !== '') {
            $zone = $this->taxZoneRepository->findOneBy(['code' => strtoupper(trim($taxZoneCode))]);
            if ($zone) {
                $taxRatePercent = (float)$zone->getRatePercent();
                $taxZoneName = $zone->getName();
            }
        }

        $taxAmount = round($netAmount * ($taxRatePercent / 100.0), 2);
        $grossAmount = round($netAmount + $taxAmount, 2);

        return [
            'base_price_usd' => number_format($amountInUsd, 2, '.', ''),
            'currency_code' => $currencyRate->getCode(),
            'currency_symbol' => $currencyRate->getSymbol(),
            'exchange_rate' => $currencyRate->getRateToBase(),
            'net_price' => number_format($netAmount, 2, '.', ''),
            'tax_zone' => [
                'code' => $taxZoneCode,
                'name' => $taxZoneName,
                'rate_percent' => number_format($taxRatePercent, 2, '.', ''),
            ],
            'tax_amount' => number_format($taxAmount, 2, '.', ''),
            'gross_price' => number_format($grossAmount, 2, '.', ''),
            'formatted_gross' => sprintf('%s%s', $currencyRate->getSymbol(), number_format($grossAmount, 2, '.', ',')),
        ];
    }

    public function ensureSeededRatesAndTaxZones(): void
    {
        if ($this->currencyRateRepository->count([]) === 0) {
            $usd = new CurrencyRate();
            $usd->setCode('USD');
            $usd->setSymbol('$');
            $usd->setRateToBase(1.000000);
            $this->currencyRateRepository->save($usd, false);

            $eur = new CurrencyRate();
            $eur->setCode('EUR');
            $eur->setSymbol('€');
            $eur->setRateToBase(0.920000);
            $this->currencyRateRepository->save($eur, false);

            $gbp = new CurrencyRate();
            $gbp->setCode('GBP');
            $gbp->setSymbol('£');
            $gbp->setRateToBase(0.790000);
            $this->currencyRateRepository->save($gbp, false);

            $cad = new CurrencyRate();
            $cad->setCode('CAD');
            $cad->setSymbol('CA$');
            $cad->setRateToBase(1.360000);
            $this->currencyRateRepository->save($cad, true);
        }

        if ($this->taxZoneRepository->count([]) === 0) {
            $usCa = new TaxZone();
            $usCa->setCode('US-CA');
            $usCa->setName('California State Sales Tax');
            $usCa->setRatePercent(7.25);
            $this->taxZoneRepository->save($usCa, false);

            $euDe = new TaxZone();
            $euDe->setCode('EU-DE');
            $euDe->setName('Germany VAT (MwSt)');
            $euDe->setRatePercent(19.00);
            $this->taxZoneRepository->save($euDe, false);

            $ukVat = new TaxZone();
            $ukVat->setCode('UK-VAT');
            $ukVat->setName('United Kingdom Standard VAT');
            $ukVat->setRatePercent(20.00);
            $this->taxZoneRepository->save($ukVat, true);
        }
    }
}
