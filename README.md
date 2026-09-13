# ez-php/exchange

Currency exchange-rate lookup and conversion for Money values

---

## Installation

```bash
composer require ez-php/exchange
```

---

## Usage

### Static rate table (tests, or a self-maintained table)

```php
use EzPhp\Exchange\Converter;
use EzPhp\Exchange\StaticExchangeRateProvider;
use EzPhp\Money\Money;

$provider = new StaticExchangeRateProvider([
    'EUR' => ['USD' => '1.08'],
]);

$converter = new Converter($provider);
$usd = $converter->convert(Money::of('10.00', 'EUR'), 'USD'); // 10.80 USD
```

### HTTP-backed provider

```php
use EzPhp\Exchange\Converter;
use EzPhp\Exchange\HttpExchangeRateProvider;
use EzPhp\HttpClient\Http;
use EzPhp\Money\Money;

$provider = new HttpExchangeRateProvider(
    Http::getClient(),
    'https://api.example.com/rate?from={base}&to={quote}',
    'rate', // dot-notation path to the rate value in the JSON response
);

$converter = new Converter($provider);
$jpy = $converter->convert(Money::of('1.00', 'EUR'), 'JPY'); // rounded to JPY's scale (0)
```

Rate caching and historical/time-series rates are out of scope — compose `ez-php/cache` around a provider if you need caching.

---

## License

MIT