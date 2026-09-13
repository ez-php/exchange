<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\BigNum\BigDecimal;
use EzPhp\Exchange\Exception\ExchangeRateNotFoundException;
use EzPhp\Exchange\StaticExchangeRateProvider;
use EzPhp\Money\Currency;

final class StaticExchangeRateProviderTest extends TestCase
{
    public function test_returns_configured_rate(): void
    {
        $provider = new StaticExchangeRateProvider(['EUR' => ['USD' => '1.08']]);

        self::assertTrue(BigDecimal::of('1.08')->compareTo($provider->getRate('EUR', 'USD')) === 0);
    }

    public function test_accepts_currency_instances(): void
    {
        $provider = new StaticExchangeRateProvider(['EUR' => ['USD' => '1.08']]);

        $rate = $provider->getRate(Currency::of('EUR'), Currency::of('USD'));

        self::assertTrue(BigDecimal::of('1.08')->compareTo($rate) === 0);
    }

    public function test_same_currency_returns_one_without_lookup(): void
    {
        $provider = new StaticExchangeRateProvider();

        self::assertTrue(BigDecimal::of(1)->compareTo($provider->getRate('EUR', 'EUR')) === 0);
    }

    public function test_missing_pair_throws(): void
    {
        $provider = new StaticExchangeRateProvider();

        $this->expectException(ExchangeRateNotFoundException::class);

        $provider->getRate('EUR', 'USD');
    }

    public function test_set_rate_overwrites_existing_rate(): void
    {
        $provider = new StaticExchangeRateProvider(['EUR' => ['USD' => '1.08']]);
        $provider->setRate('EUR', 'USD', '1.10');

        self::assertTrue(BigDecimal::of('1.10')->compareTo($provider->getRate('EUR', 'USD')) === 0);
    }

    public function test_set_rate_accepts_a_big_decimal(): void
    {
        $provider = new StaticExchangeRateProvider();
        $provider->setRate('EUR', 'USD', BigDecimal::of('1.08'));

        self::assertTrue(BigDecimal::of('1.08')->compareTo($provider->getRate('EUR', 'USD')) === 0);
    }

    public function test_currency_codes_are_case_insensitive(): void
    {
        $provider = new StaticExchangeRateProvider(['EUR' => ['USD' => '1.08']]);

        self::assertTrue(BigDecimal::of('1.08')->compareTo($provider->getRate('eur', 'usd')) === 0);
    }
}
