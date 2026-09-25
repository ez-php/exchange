<?php

declare(strict_types=1);

namespace EzPhp\Exchange\Exception;

/**
 * Thrown when a provider has no rate for the requested currency pair,
 * or the provider's response could not be parsed into one.
 */
final class ExchangeRateNotFoundException extends ExchangeException
{
    /**
     * Build the exception for a currency pair with no known rate.
     */
    public static function forPair(string $baseCode, string $quoteCode): self
    {
        return new self("No exchange rate found for {$baseCode} -> {$quoteCode}");
    }

    /**
     * Build the exception for a provider response that could not be turned into a rate.
     */
    public static function unparseable(string $baseCode, string $quoteCode, string $reason): self
    {
        return new self("Could not parse exchange rate for {$baseCode} -> {$quoteCode}: {$reason}");
    }
}
