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

namespace Horde\Date\Formatter;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Horde\Date\Date;
use Horde\Date\DateInterface;
use Horde\Date\FormatterInterface;
use Horde_Date;
use RuntimeException;
use Stringable;

/**
 * PHP DateTime formatter
 *
 * Uses DateTime::format() with PHP date() syntax.
 * Syntax: Y-m-d H:i:s (single letters)
 *
 * This is the default formatter, maintaining backward compatibility
 * with existing Horde_Date::format() behavior.
 *
 * Note: This formatter ignores the locale parameter as DateTime::format()
 * is not locale-aware. For locale-aware formatting, use IcuFormatter.
 *
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 */
class DateTimeFormatter implements FormatterInterface
{
    /**
     * Format using DateTime::format() syntax
     *
     * @param int|DateTimeInterface|DateInterface|Horde_Date $datetime  Unix timestamp, DateTime/DateTimeImmutable, DateInterface, or Horde_Date
     * @param string $pattern  PHP date() pattern (e.g., 'Y-m-d', 'l, F j, Y')
     * @param string|Stringable $locale  Ignored (DateTime is not locale-aware)
     * @param string|null $timezone  Timezone identifier (null = UTC)
     *
     * @return string  Formatted date string
     */
    public function format(
        int|DateTimeInterface|DateInterface|Horde_Date $datetime,
        string $pattern,
        string|Stringable $locale = 'en_US',
        ?string $timezone = null
    ) {
        // Convert Stringable to string (locale is ignored but we accept it)
        $locale = (string) $locale;

        // Convert Horde_Date to DateTimeInterface if needed
        if ($datetime instanceof Horde_Date) {
            $datetime = $datetime->toDateTime();
        }

        // Convert int timestamp to DateTime
        if (is_int($datetime)) {
            $dt = new DateTime('@' . $datetime);
        } else {
            // Clone to avoid modifying original
            $dt = clone $datetime;
        }

        // Set timezone if provided
        if ($timezone !== null) {
            $dt->setTimezone(new DateTimeZone($timezone));
        }

        return $dt->format($pattern);
    }

    /**
     * Parse DateTime formatted string back to Horde_Date
     *
     * Uses DateTime::createFromFormat() for parsing with PHP date() syntax.
     *
     * @param string $formattedString  Formatted date string
     * @param string $pattern  PHP date() pattern (e.g., 'Y-m-d', 'Y-m-d H:i:s')
     * @param string|Stringable $locale  Ignored (DateTime is not locale-aware)
     * @param string|null $timezone  Timezone identifier (null = UTC)
     *
     * @return DateInterface  Horde_Date object
     *
     * @throws RuntimeException if parsing fails
     */
    public function parse(
        string $formattedString,
        string $pattern,
        string|Stringable $locale = 'en_US',
        ?string $timezone = null
    ): DateInterface {
        // Convert Stringable to string (locale is ignored but we accept it)
        $locale = (string) $locale;

        // Create timezone for parsing
        $tz = $timezone ? new DateTimeZone($timezone) : new DateTimeZone('UTC');

        // Parse using DateTime::createFromFormat
        $dateTime = DateTime::createFromFormat($pattern, $formattedString, $tz);

        if ($dateTime === false) {
            throw new RuntimeException(
                "Failed to parse date string: $formattedString "
                . "(pattern: $pattern)"
            );
        }

        return Date::createFromInterface($dateTime);
    }
}
