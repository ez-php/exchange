<?php

declare(strict_types=1);

namespace EzPhp\Exchange;

use EzPhp\BigNum\RoundingMode;
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
    public function __construct(private ExchangeRateProviderInterface $provider)
    {
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
        $target = $targetCurrency instanceof Currency ? $targetCurrency : Currency::of($targetCurrency);

        if ($money->getCurrency()->isEqualTo($target)) {
            return $money;
        }

        $rate = $this->provider->getRate($money->getCurrency(), $target);
        $converted = $money->getAmount()->multiply($rate)->toScale($target->getScale(), $roundingMode);

        return Money::of($converted->toString(), $target, $roundingMode);
    }
}
