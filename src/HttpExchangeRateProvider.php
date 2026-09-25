<?php

declare(strict_types=1);

namespace EzPhp\Exchange;

use EzPhp\BigNum\BigDecimal;
use EzPhp\Exchange\Exception\ExchangeRateNotFoundException;
use EzPhp\HttpClient\HttpClient;
use EzPhp\HttpClient\HttpClientException;
use EzPhp\Money\Currency;

/**
 * Exchange rate provider backed by an HTTP API.
 *
 * Generic over the response shape: the caller supplies a URL template with
 * `{base}` and `{quote}` placeholders, plus a dot-notation path to the rate
 * value in the decoded JSON response. This covers most rate APIs without
 * this package taking a dependency on any one of them.
 *
 * Example (a provider returning `{"result": "success", "rate": "1.08"}`):
 *
 *   new HttpExchangeRateProvider(
 *       $httpClient,
 *       'https://api.example.com/rate?from={base}&to={quote}',
 *       'rate',
 *   );
 */
final class HttpExchangeRateProvider implements ExchangeRateProviderInterface
{
    /**
     * @param string $urlTemplate Must contain the literal placeholders `{base}` and `{quote}`.
     * @param string $ratePath    Dot-notation path to the rate value in the decoded JSON body.
     *
     * @throws \InvalidArgumentException When `$urlTemplate` is missing `{base}` or `{quote}`.
     */
    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly string $urlTemplate,
        private readonly string $ratePath = 'rate',
    ) {
        if (!str_contains($urlTemplate, '{base}') || !str_contains($urlTemplate, '{quote}')) {
            throw new \InvalidArgumentException(
                "URL template must contain both '{base}' and '{quote}' placeholders, got: {$urlTemplate}",
            );
        }
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

        $url = str_replace(['{base}', '{quote}'], [$baseCode, $quoteCode], $this->urlTemplate);

        try {
            $response = $this->httpClient->get($url)->send();
        } catch (HttpClientException $e) {
            throw ExchangeRateNotFoundException::unparseable($baseCode, $quoteCode, $e->getMessage());
        }

        if (!$response->ok()) {
            throw ExchangeRateNotFoundException::unparseable(
                $baseCode,
                $quoteCode,
                'HTTP status ' . $response->status(),
            );
        }

        $value = self::extract($response->json(), $this->ratePath);

        if (!is_int($value) && !is_float($value) && !is_string($value)) {
            throw ExchangeRateNotFoundException::unparseable(
                $baseCode,
                $quoteCode,
                "path '{$this->ratePath}' not found in response",
            );
        }

        try {
            return BigDecimal::of($value);
        } catch (\InvalidArgumentException $e) {
            throw ExchangeRateNotFoundException::unparseable($baseCode, $quoteCode, $e->getMessage());
        }
    }

    private static function extract(mixed $data, string $path): mixed
    {
        foreach (explode('.', $path) as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return null;
            }

            $data = $data[$segment];
        }

        return $data;
    }

    private static function codeOf(Currency|string $currency): string
    {
        return $currency instanceof Currency ? $currency->getCode() : strtoupper($currency);
    }
}
