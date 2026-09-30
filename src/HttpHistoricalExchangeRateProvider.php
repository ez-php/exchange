<?php

declare(strict_types=1);

namespace EzPhp\Exchange;

use DateTimeInterface;
use EzPhp\BigNum\BigDecimal;
use EzPhp\HttpClient\HttpClient;
use EzPhp\Money\Currency;

/**
 * Historical rates from an HTTP API: like HttpExchangeRateProvider, plus a
 * `{date}` placeholder (formatted with `$dateFormat`, default `Y-m-d`).
 *
 *   new HttpHistoricalExchangeRateProvider($client, 'https://api.example.com/{date}?from={base}&to={quote}', 'rate');
 *
 * No caching — wrap the calls yourself; a past day's rate never changes.
 */
final readonly class HttpHistoricalExchangeRateProvider implements HistoricalExchangeRateProviderInterface
{
    /**
     * @param string $urlTemplate Must contain `{base}`, `{quote}` and `{date}`.
     * @param string $ratePath    Dot-notation path to the rate value in the decoded JSON body.
     * @param string $dateFormat  PHP date format for `{date}`.
     *
     * @throws \InvalidArgumentException When a placeholder is missing.
     */
    public function __construct(
        private HttpClient $httpClient,
        private string $urlTemplate,
        private string $ratePath = 'rate',
        private string $dateFormat = 'Y-m-d',
    ) {
        foreach (['{base}', '{quote}', '{date}'] as $placeholder) {
            if (!str_contains($urlTemplate, $placeholder)) {
                throw new \InvalidArgumentException(
                    "URL template must contain '{base}', '{quote}' and '{date}' placeholders, got: {$urlTemplate}",
                );
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getRateAt(Currency|string $base, Currency|string $quote, DateTimeInterface $date): BigDecimal
    {
        $template = str_replace('{date}', rawurlencode($date->format($this->dateFormat)), $this->urlTemplate);

        return (new HttpExchangeRateProvider($this->httpClient, $template, $this->ratePath))->getRate($base, $quote);
    }
}
