<?php

declare(strict_types=1);

/**
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 */

namespace Horde\Date;

use DateTimeImmutable;
use DateTimeZone;
use Stringable;

/**
 * Interface for date objects with pluggable formatters
 *
 * Provides a format() method that accepts pluggable formatters for
 * flexible date formatting with locale and timezone support.
 *
 * This interface uses "lax input, strict output" - the first parameter
 * allows flexible types for backward compatibility, while new parameters
 * use strict typing.
 *
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 */
interface DateInterface
{
    /**
     * Format date using specified formatter
     *
     * Lax input for first parameter (backward compatibility):
     * - Accepts string|Stringable for pattern
     *
     * Strict input for new parameters:
     * - string|FormatterInterface|null for formatter
     * - string|Stringable|null for locale
     *
     * No return type for backward compatibility with existing Horde_Date::format()
     *
     * @param string|Stringable $pattern  Format pattern
     * @param string|FormatterInterface|null $formatter  Formatter class name or instance:
     *   - null: DateTimeFormatter (default, backward compatible)
     *   - string: Formatter class name (e.g., \Horde\Date\Formatter\IcuFormatter::class)
     *   - FormatterInterface: Formatter instance
     * @param string|Stringable|null $locale  Locale for formatting (null = use instance locale or setlocale())
     *
     * @return string  Formatted date string (no return type for BC)
     */
    public function format(
        string|Stringable $pattern,
        string|FormatterInterface|null $formatter = null,
        string|Stringable|null $locale = null
    );

    /**
     * Get Unix timestamp
     *
     * @return int  Unix timestamp
     */
    public function timestamp();

    /**
     * Convert to DateTimeImmutable
     *
     * @return DateTimeImmutable
     */
    public function toDateTimeImmutable(): DateTimeImmutable;

    /**
     * Get the timezone of this date
     *
     * @return DateTimeZone|false
     */
    public function getTimezone(): DateTimeZone|false;
}
