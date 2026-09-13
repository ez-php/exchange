<?php

declare(strict_types=1);

namespace EzPhp\Exchange\Exception;

/**
 * Base exception for the ez-php/exchange package.
 *
 * Extended by every other exception in this package so callers can catch
 * this single type to cover all exchange-related failures.
 */
class ExchangeException extends \RuntimeException
{
}
