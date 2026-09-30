<?php

declare(strict_types=1);

namespace EzPhp\Exchange;

use DateTimeInterface;
use EzPhp\BigNum\BigDecimal;
use EzPhp\BigNum\RoundingMode;
use EzPhp\Exchange\Exception\ExchangeException;
use EzPhp\Money\Currency;
use EzPhp\Money\Money;

/**
 * Converts Money values between currencies using an ExchangeRateProviderInterface.
 *
 * `Money` deliberately has no `convertTo()` — conversion needs a rate source,
 * which is an external concern this package supplies instead.
 */
final readonly class Converter
{
    /**
     * Converter Constructor
     *
     * @param ExchangeRateProviderInterface                $provider   Current rates, for convert().
     * @param HistoricalExchangeRateProviderInterface|null $historical Past rates, for convertAt().
     */
    public function __construct(
        private ExchangeRateProviderInterface $provider,
        private ?HistoricalExchangeRateProviderInterface $historical = null,
    ) {
    }

    /**
     * Convert a Money value to the target currency.
     *
     * The rate is applied to the source amount and the result is rounded to
     * the target currency's scale using $roundingMode. Converting to the
     * same currency returns an equal Money without consulting the provider.
     */
    public function convert(
        Money $money,
        Currency|string $targetCurrency,
        RoundingMode $roundingMode = RoundingMode::HALF_UP,
    ): Money {
        $target = self::currency($targetCurrency);

        if ($money->getCurrency()->isEqualTo($target)) {
            return $money;
        }

        return self::apply($money, $target, $this->provider->getRate($money->getCurrency(), $target), $roundingMode);
    }

    /**
     * Convert a Money value at the rate of a past day (e.g. an invoice's issue date).
     *
     * @throws ExchangeException When the Converter was built without a historical provider.
     */
    public function convertAt(
        Money $money,
        Currency|string $targetCurrency,
        DateTimeInterface $date,
        RoundingMode $roundingMode = RoundingMode::HALF_UP,
    ): Money {
        $target = self::currency($targetCurrency);

        if ($money->getCurrency()->isEqualTo($target)) {
            return $money;
        }

        if ($this->historical === null) {
            throw new ExchangeException('convertAt() needs a HistoricalExchangeRateProviderInterface — pass one to the Converter.');
        }

        return self::apply($money, $target, $this->historical->getRateAt($money->getCurrency(), $target, $date), $roundingMode);
    }

    private static function currency(Currency|string $currency): Currency
    {
        return $currency instanceof Currency ? $currency : Currency::of($currency);
    }

    private static function apply(Money $money, Currency $target, BigDecimal $rate, RoundingMode $roundingMode): Money
    {
        $converted = $money->getAmount()->multiply($rate)->toScale($target->getScale(), $roundingMode);

        return Money::of($converted->toString(), $target, $roundingMode);
    }
}
