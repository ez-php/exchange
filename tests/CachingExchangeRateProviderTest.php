<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\BigNum\BigDecimal;
use EzPhp\Cache\ArrayDriver;
use EzPhp\Exchange\CachingExchangeRateProvider;
use EzPhp\Exchange\Exception\ExchangeRateNotFoundException;
use EzPhp\Exchange\StaticExchangeRateProvider;

final class CachingExchangeRateProviderTest extends TestCase
{
    public function test_returns_rate_from_wrapped_provider(): void
    {
        $inner = new StaticExchangeRateProvider(['EUR' => ['USD' => '1.08']]);
        $provider = new CachingExchangeRateProvider($inner, new ArrayDriver(), 60);

        $rate = $provider->getRate('EUR', 'USD');

        self::assertTrue(BigDecimal::of('1.08')->compareTo($rate) === 0);
    }

    public function test_second_call_is_served_from_cache(): void
    {
        $inner = new StaticExchangeRateProvider(['EUR' => ['USD' => '1.08']]);
        $cache = new ArrayDriver();
        $provider = new CachingExchangeRateProvider($inner, $cache, 60);

        $provider->getRate('EUR', 'USD');
        $inner->setRate('EUR', 'USD', '2.00');
        $rate = $provider->getRate('EUR', 'USD');

        // Still 1.08 — the second call hit the cache, not the (now-changed) inner provider.
        self::assertTrue(BigDecimal::of('1.08')->compareTo($rate) === 0);

        $stats = $cache->stats();
        self::assertSame(1, $stats->misses);
        self::assertSame(1, $stats->hits);
    }

    public function test_cache_is_keyed_by_currency_pair(): void
    {
        $inner = new StaticExchangeRateProvider(['EUR' => ['USD' => '1.08', 'GBP' => '0.85']]);
        $provider = new CachingExchangeRateProvider($inner, new ArrayDriver(), 60);

        $usd = $provider->getRate('EUR', 'USD');
        $gbp = $provider->getRate('EUR', 'GBP');

        self::assertTrue(BigDecimal::of('1.08')->compareTo($usd) === 0);
        self::assertTrue(BigDecimal::of('0.85')->compareTo($gbp) === 0);
    }

    public function test_missing_pair_still_throws(): void
    {
        $inner = new StaticExchangeRateProvider();
        $provider = new CachingExchangeRateProvider($inner, new ArrayDriver(), 60);

        $this->expectException(ExchangeRateNotFoundException::class);

        $provider->getRate('EUR', 'USD');
    }
}
