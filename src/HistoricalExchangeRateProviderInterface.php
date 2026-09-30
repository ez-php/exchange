<?php

declare(strict_types=1);

namespace EzPhp\Exchange;

use DateTimeInterface;
use EzPhp\BigNum\BigDecimal;
use EzPhp\Exchange\Exception\ExchangeRateNotFoundException;
use EzPhp\Money\Currency;

/**
 * Interface HistoricalExchangeRateProviderInterface
 *
 * Rates as of a past date — e.g. to convert an old invoice at the rate of its
 * issue day. Separate from ExchangeRateProviderInterface ("now") so existing
 * providers are unaffected; a class may implement both.
 *
 * @package EzPhp\Exchange
 */
interface HistoricalExchangeRateProviderInterface
{
    /**
     * The rate valid on `$date` (only the calendar day matters): 1 base = R quote.
     *
     * @param Currency|string   $base
     * @param Currency|string   $quote
     * @param DateTimeInterface $date
     *
     * @throws ExchangeRateNotFoundException When no rate is known for that pair and day.
     *
     * @return BigDecimal
     */
    public function getRateAt(Currency|string $base, Currency|string $quote, DateTimeInterface $date): BigDecimal;
}
