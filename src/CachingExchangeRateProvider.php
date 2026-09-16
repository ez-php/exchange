<?php

declare(strict_types=1);

namespace EzPhp\Exchange;

use EzPhp\BigNum\BigDecimal;
use EzPhp\Cache\CacheInterface;
use EzPhp\Money\Currency;

/**
 * Decorator caching another provider's getRate() results via ez-php/cache.
 *
 * Composition, not configuration — this module deliberately has no built-in
 * caching (see CLAUDE.md Design Decisions); wrap whichever provider needs it:
 *
 *   $cached = new CachingExchangeRateProvider(
 *       new HttpExchangeRateProvider(...),
 *       $cache,
 *       ttl: 3600,
 *   );
 *
 * Requires: ez-php/cache (soft dependency — require-dev only; this class is
 * only autoloaded when actually referenced).
 */
final class CachingExchangeRateProvider implements ExchangeRateProviderInterface
{
    /**
     * @param ExchangeRateProviderInterface $provider The provider to cache.
     * @param CacheInterface                $cache
     * @param int                           $ttl      Seconds until expiry; 0 means never expire.
     */
    public function __construct(
        private readonly ExchangeRateProviderInterface $provider,
        private readonly CacheInterface $cache,
        private readonly int $ttl,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getRate(Currency|string $base, Currency|string $quote): BigDecimal
    {
        $baseCode = $base instanceof Currency ? (string) $base : $base;
        $quoteCode = $quote instanceof Currency ? (string) $quote : $quote;

        $key = 'exchange_rate:' . $baseCode . ':' . $quoteCode;

        /** @var BigDecimal $cached */
        $cached = $this->cache->remember(
            $key,
            $this->ttl,
            fn (): BigDecimal => $this->provider->getRate($base, $quote),
        );

        return $cached;
    }
}
