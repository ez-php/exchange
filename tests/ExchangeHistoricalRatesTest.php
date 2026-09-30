<?php

declare(strict_types=1);

namespace Tests;

use DateTimeImmutable;
use EzPhp\BigNum\BigDecimal;
use EzPhp\Exchange\Converter;
use EzPhp\Exchange\Exception\ExchangeException;
use EzPhp\Exchange\Exception\ExchangeRateNotFoundException;
use EzPhp\Exchange\HttpHistoricalExchangeRateProvider;
use EzPhp\Exchange\StaticExchangeRateProvider;
use EzPhp\Exchange\StaticHistoricalExchangeRateProvider;
use EzPhp\HttpClient\FakeTransport;
use EzPhp\HttpClient\HttpClient;
use EzPhp\HttpClient\HttpResponse;
use EzPhp\Money\Money;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Historical exchange rates: static table, HTTP template, Converter::convertAt().
 *
 * @package Tests
 */
#[CoversClass(StaticHistoricalExchangeRateProvider::class)]
#[CoversClass(HttpHistoricalExchangeRateProvider::class)]
#[CoversClass(Converter::class)]
final class ExchangeHistoricalRatesTest extends TestCase
{
    private function table(): StaticHistoricalExchangeRateProvider
    {
        return new StaticHistoricalExchangeRateProvider([
            '2026-01-02' => ['EUR' => ['USD' => '1.10']],
            '2026-01-05' => ['EUR' => ['USD' => '1.20']],
        ]);
    }

    public function test_static_returns_the_rate_of_that_day(): void
    {
        self::assertSame('1.20', $this->table()->getRateAt('EUR', 'USD', new DateTimeImmutable('2026-01-05 18:00'))->toString());
    }

    public function test_static_uses_the_latest_earlier_rate_for_days_without_one(): void
    {
        // Saturday 2026-01-03 → Friday's rate.
        self::assertSame('1.10', $this->table()->getRateAt('eur', 'usd', new DateTimeImmutable('2026-01-03'))->toString());
    }

    public function test_static_throws_before_the_first_known_date_and_for_unknown_pairs(): void
    {
        try {
            $this->table()->getRateAt('EUR', 'USD', new DateTimeImmutable('2025-12-31'));
            self::fail('expected ExchangeRateNotFoundException');
        } catch (ExchangeRateNotFoundException) {
        }

        $this->expectException(ExchangeRateNotFoundException::class);
        $this->table()->getRateAt('EUR', 'GBP', new DateTimeImmutable('2026-01-05'));
    }

    public function test_static_same_currency_is_one(): void
    {
        self::assertSame('1', (new StaticHistoricalExchangeRateProvider())->getRateAt('EUR', 'EUR', new DateTimeImmutable())->toString());
    }

    public function test_static_set_rate_at(): void
    {
        $provider = new StaticHistoricalExchangeRateProvider();
        $provider->setRateAt('GBP', 'EUR', new DateTimeImmutable('2026-02-01'), BigDecimal::of('1.17'));

        self::assertSame('1.17', $provider->getRateAt('GBP', 'EUR', new DateTimeImmutable('2026-02-10'))->toString());
    }

    public function test_http_fills_the_date_into_the_url(): void
    {
        $transport = new FakeTransport(['https://rates.test/2026-01-05?from=EUR&to=USD' => new HttpResponse(200, '{"data":{"rate":"1.2345"}}')]);
        $provider = new HttpHistoricalExchangeRateProvider(new HttpClient($transport), 'https://rates.test/{date}?from={base}&to={quote}', 'data.rate');

        self::assertSame('1.2345', $provider->getRateAt('EUR', 'USD', new DateTimeImmutable('2026-01-05 23:59'))->toString());
    }

    public function test_http_date_format_is_configurable_and_errors_are_wrapped(): void
    {
        $transport = new FakeTransport(['*' => new HttpResponse(404, '')]);
        $provider = new HttpHistoricalExchangeRateProvider(new HttpClient($transport), 'https://rates.test/{date}/{base}{quote}', 'rate', 'Ymd');

        try {
            $provider->getRateAt('EUR', 'USD', new DateTimeImmutable('2026-01-05'));
            self::fail('expected ExchangeRateNotFoundException');
        } catch (ExchangeRateNotFoundException) {
            self::assertSame('https://rates.test/20260105/EURUSD', $transport->getRecorded()[0]['url']);
        }
    }

    public function test_http_requires_all_placeholders(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new HttpHistoricalExchangeRateProvider(new HttpClient(new FakeTransport()), 'https://rates.test/{base}/{quote}');
    }

    public function test_converter_convert_at_uses_the_historical_rate(): void
    {
        $converter = new Converter(new StaticExchangeRateProvider(['EUR' => ['USD' => '2.00']]), $this->table());

        $converted = $converter->convertAt(Money::of('100.00', 'EUR'), 'USD', new DateTimeImmutable('2026-01-03'));

        self::assertSame('110.00', $converted->getAmount()->toString());
        self::assertSame('200.00', $converter->convert(Money::of('100.00', 'EUR'), 'USD')->getAmount()->toString());
    }

    public function test_converter_convert_at_without_a_historical_provider_throws(): void
    {
        $this->expectException(ExchangeException::class);

        (new Converter(new StaticExchangeRateProvider()))->convertAt(Money::of('1.00', 'EUR'), 'USD', new DateTimeImmutable());
    }
}
