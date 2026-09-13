<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\BigNum\RoundingMode;
use EzPhp\Exchange\Converter;
use EzPhp\Exchange\Exception\ExchangeRateNotFoundException;
use EzPhp\Exchange\StaticExchangeRateProvider;
use EzPhp\Money\Money;

final class ConverterTest extends TestCase
{
    public function test_converts_using_the_provider_rate(): void
    {
        $converter = new Converter(new StaticExchangeRateProvider(['EUR' => ['USD' => '1.08']]));

        $result = $converter->convert(Money::of('10.00', 'EUR'), 'USD');

        self::assertTrue($result->isEqualTo(Money::of('10.80', 'USD')));
    }

    public function test_rounds_to_the_target_currencys_scale(): void
    {
        // JPY has a scale of 0.
        $converter = new Converter(new StaticExchangeRateProvider(['EUR' => ['JPY' => '160.567']]));

        $result = $converter->convert(Money::of('1.00', 'EUR'), 'JPY');

        self::assertTrue($result->isEqualTo(Money::of('161', 'JPY')));
    }

    public function test_honors_an_explicit_rounding_mode(): void
    {
        $converter = new Converter(new StaticExchangeRateProvider(['EUR' => ['JPY' => '160.567']]));

        $result = $converter->convert(Money::of('1.00', 'EUR'), 'JPY', RoundingMode::DOWN);

        self::assertTrue($result->isEqualTo(Money::of('160', 'JPY')));
    }

    public function test_same_currency_returns_an_equal_money_without_consulting_the_provider(): void
    {
        $converter = new Converter(new StaticExchangeRateProvider());

        $money = Money::of('10.00', 'EUR');
        $result = $converter->convert($money, 'EUR');

        self::assertTrue($result->isEqualTo($money));
    }

    public function test_missing_rate_throws(): void
    {
        $converter = new Converter(new StaticExchangeRateProvider());

        $this->expectException(ExchangeRateNotFoundException::class);

        $converter->convert(Money::of('10.00', 'EUR'), 'USD');
    }
}
