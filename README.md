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

Historical/time-series rates are out of scope.

### Caching a provider

```php
use EzPhp\Cache\ArrayDriver;
use EzPhp\Exchange\CachingExchangeRateProvider;
use EzPhp\Exchange\Converter;
use EzPhp\Exchange\HttpExchangeRateProvider;

$cached = new CachingExchangeRateProvider(
    new HttpExchangeRateProvider(/* ... */),
    new ArrayDriver(), // or any ez-php/cache driver
    ttl: 3600,
);

$converter = new Converter($cached);
```

Requires `ez-php/cache` (a soft dependency — declared in `require-dev` here, install it
separately in applications that want this decorator).

---

## License

MIT