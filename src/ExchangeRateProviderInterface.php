<?php

declare(strict_types=1);

namespace EzPhp\Exchange;

use EzPhp\BigNum\BigDecimal;
use EzPhp\Exchange\Exception\ExchangeRateNotFoundException;
use EzPhp\Money\Currency;

/**
 * Looks up the exchange rate between two currencies.
 *
 * A rate of R means: 1 unit of $base = R units of $quote.
 */
interface ExchangeRateProviderInterface
{
    /**
     * @throws ExchangeRateNotFoundException if no rate is available for the pair
     */
    public function getRate(Currency|string $base, Currency|string $quote): BigDecimal;
}
