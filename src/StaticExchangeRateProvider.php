<?php

declare(strict_types=1);

namespace EzPhp\Exchange;

use EzPhp\BigNum\BigDecimal;
use EzPhp\Exchange\Exception\ExchangeRateNotFoundException;
use EzPhp\Money\Currency;

/**
 * In-memory exchange rate provider backed by a fixed rate table.
 *
 * Intended for tests and for applications that maintain their own static
 * or periodically-refreshed rate table rather than calling a live API.
 */
final class StaticExchangeRateProvider implements ExchangeRateProviderInterface
{
    /**
     * @var array<string, array<string, BigDecimal>>
     */
    private array $rates = [];

    /**
     * @param array<string, array<string, int|string|BigDecimal>> $rates
     *   Nested map: `$rates['EUR']['USD'] = '1.08'` means 1 EUR = 1.08 USD.
     */
    public function __construct(array $rates = [])
    {
        foreach ($rates as $baseCode => $quotes) {
            foreach ($quotes as $quoteCode => $rate) {
                $this->setRate($baseCode, $quoteCode, $rate);
            }
        }
    }

    /**
     * Set (or overwrite) the rate for a currency pair.
     */
    public function setRate(Currency|string $base, Currency|string $quote, int|string|BigDecimal $rate): void
    {
        $baseCode = self::codeOf($base);
        $quoteCode = self::codeOf($quote);

        $this->rates[$baseCode][$quoteCode] = $rate instanceof BigDecimal ? $rate : BigDecimal::of($rate);
    }

    /**
     * {@inheritDoc}
     */
    public function getRate(Currency|string $base, Currency|string $quote): BigDecimal
    {
        $baseCode = self::codeOf($base);
        $quoteCode = self::codeOf($quote);

        if ($baseCode === $quoteCode) {
            return BigDecimal::of(1);
        }

        $rate = $this->rates[$baseCode][$quoteCode] ?? null;

        if ($rate === null) {
            throw ExchangeRateNotFoundException::forPair($baseCode, $quoteCode);
        }

        return $rate;
    }

    private static function codeOf(Currency|string $currency): string
    {
        return $currency instanceof Currency ? $currency->getCode() : strtoupper($currency);
    }
}
