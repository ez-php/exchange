<?php

declare(strict_types=1);

namespace EzPhp\Exchange;

use DateTimeInterface;
use EzPhp\BigNum\BigDecimal;
use EzPhp\Exchange\Exception\ExchangeRateNotFoundException;
use EzPhp\Money\Currency;

/**
 * In-memory historical rate table: date → base → quote → rate.
 *
 * A day without an entry for the pair (weekend, holiday) uses the latest
 * earlier day that has one — the usual convention for reference rates. Before
 * the first known day there is no rate.
 */
final class StaticHistoricalExchangeRateProvider implements HistoricalExchangeRateProviderInterface
{
    /**
     * @var array<string, array<string, array<string, BigDecimal>>> 'Y-m-d' → base → quote → rate
     */
    private array $rates = [];

    /**
     * @param array<string, array<string, array<string, int|string|BigDecimal>>> $rates
     *   `$rates['2026-01-02']['EUR']['USD'] = '1.10'` means 1 EUR = 1.10 USD on that day.
     */
    public function __construct(array $rates = [])
    {
        foreach ($rates as $day => $bases) {
            foreach ($bases as $base => $quotes) {
                foreach ($quotes as $quote => $rate) {
                    $this->setRateAt($base, $quote, new \DateTimeImmutable($day), $rate);
                }
            }
        }
    }

    /**
     * Set (or overwrite) the rate of a pair for one day.
     */
    public function setRateAt(Currency|string $base, Currency|string $quote, DateTimeInterface $date, int|string|BigDecimal $rate): void
    {
        $this->rates[$date->format('Y-m-d')][self::codeOf($base)][self::codeOf($quote)] = $rate instanceof BigDecimal ? $rate : BigDecimal::of($rate);
        ksort($this->rates);
    }

    /**
     * {@inheritDoc}
     */
    public function getRateAt(Currency|string $base, Currency|string $quote, DateTimeInterface $date): BigDecimal
    {
        $baseCode = self::codeOf($base);
        $quoteCode = self::codeOf($quote);

        if ($baseCode === $quoteCode) {
            return BigDecimal::of(1);
        }

        $day = $date->format('Y-m-d');
        $found = null;

        foreach ($this->rates as $candidate => $bases) {
            if ($candidate > $day) {
                break;
            }

            $found = $bases[$baseCode][$quoteCode] ?? $found;
        }

        if ($found === null) {
            throw ExchangeRateNotFoundException::forPair($baseCode, $quoteCode);
        }

        return $found;
    }

    private static function codeOf(Currency|string $currency): string
    {
        return $currency instanceof Currency ? $currency->getCode() : strtoupper($currency);
    }
}
