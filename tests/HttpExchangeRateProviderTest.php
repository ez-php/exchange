<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\BigNum\BigDecimal;
use EzPhp\Exchange\Exception\ExchangeRateNotFoundException;
use EzPhp\Exchange\HttpExchangeRateProvider;
use EzPhp\HttpClient\FakeTransport;
use EzPhp\HttpClient\HttpClient;
use EzPhp\HttpClient\HttpClientException;
use EzPhp\HttpClient\HttpResponse;

final class HttpExchangeRateProviderTest extends TestCase
{
    public function test_extracts_rate_from_configured_path(): void
    {
        $client = new HttpClient(new FakeTransport([
            'https://api.example.com/rate?from=EUR&to=USD' => HttpResponse::fake(['rate' => '1.08']),
        ]));
        $provider = new HttpExchangeRateProvider(
            $client,
            'https://api.example.com/rate?from={base}&to={quote}',
            'rate',
        );

        self::assertTrue(BigDecimal::of('1.08')->compareTo($provider->getRate('EUR', 'USD')) === 0);
    }

    public function test_extracts_rate_from_nested_dot_path(): void
    {
        $client = new HttpClient(new FakeTransport([
            '*' => HttpResponse::fake(['data' => ['rate' => '0.92']]),
        ]));
        $provider = new HttpExchangeRateProvider($client, 'https://api.example.com?{base}-{quote}', 'data.rate');

        self::assertTrue(BigDecimal::of('0.92')->compareTo($provider->getRate('USD', 'EUR')) === 0);
    }

    public function test_same_currency_returns_one_without_a_request(): void
    {
        $client = new HttpClient(new FakeTransport(['*' => new HttpClientException('should not be called')]));
        $provider = new HttpExchangeRateProvider($client, 'https://api.example.com?{base}-{quote}');

        self::assertTrue(BigDecimal::of(1)->compareTo($provider->getRate('EUR', 'EUR')) === 0);
    }

    public function test_missing_path_throws_exchange_rate_not_found(): void
    {
        $client = new HttpClient(new FakeTransport(['*' => HttpResponse::fake(['other' => '1.08'])]));
        $provider = new HttpExchangeRateProvider($client, 'https://api.example.com?{base}-{quote}', 'rate');

        $this->expectException(ExchangeRateNotFoundException::class);

        $provider->getRate('EUR', 'USD');
    }

    public function test_non_ok_response_throws_exchange_rate_not_found(): void
    {
        $client = new HttpClient(new FakeTransport(['*' => HttpResponse::fake(['rate' => '1.08'], 500)]));
        $provider = new HttpExchangeRateProvider($client, 'https://api.example.com?{base}-{quote}', 'rate');

        $this->expectException(ExchangeRateNotFoundException::class);

        $provider->getRate('EUR', 'USD');
    }

    public function test_transport_failure_throws_exchange_rate_not_found(): void
    {
        $client = new HttpClient(new FakeTransport(['*' => new HttpClientException('connection refused')]));
        $provider = new HttpExchangeRateProvider($client, 'https://api.example.com?{base}-{quote}', 'rate');

        $this->expectException(ExchangeRateNotFoundException::class);

        $provider->getRate('EUR', 'USD');
    }

    public function test_url_template_placeholders_are_substituted(): void
    {
        $transport = new FakeTransport(['*' => HttpResponse::fake(['rate' => '1.08'])]);
        $client = new HttpClient($transport);
        $provider = new HttpExchangeRateProvider($client, 'https://api.example.com/{base}/{quote}', 'rate');

        $provider->getRate('EUR', 'USD');

        self::assertSame('https://api.example.com/EUR/USD', $transport->getRecorded()[0]['url']);
    }

    public function test_constructor_rejects_template_missing_base_placeholder(): void
    {
        $client = new HttpClient(new FakeTransport([]));

        $this->expectException(\InvalidArgumentException::class);

        new HttpExchangeRateProvider($client, 'https://api.example.com?to={quote}');
    }

    public function test_constructor_rejects_template_missing_quote_placeholder(): void
    {
        $client = new HttpClient(new FakeTransport([]));

        $this->expectException(\InvalidArgumentException::class);

        new HttpExchangeRateProvider($client, 'https://api.example.com?from={base}');
    }

    public function test_constructor_rejects_template_missing_both_placeholders(): void
    {
        $client = new HttpClient(new FakeTransport([]));

        $this->expectException(\InvalidArgumentException::class);

        new HttpExchangeRateProvider($client, 'https://api.example.com?rate');
    }
}
