<?php

declare(strict_types=1);

/**
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 */

namespace Horde\Date\Formatter;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Horde\Date\DateInterface;
use Horde\Date\FormatterInterface;
use Horde_Date;
use IntlDateFormatter;
use IntlTimeZone;
use InvalidArgumentException;
use RuntimeException;

/**
 * ICU pattern formatter using IntlDateFormatter
 *
 * Syntax: yyyy-MM-dd HH:mm:ss (repeated letters)
 * Examples: 'yyyy-MM-dd', 'EEEE, MMMM dd, yyyy'
 *
 * Locale-aware and recommended for new code requiring i18n.
 *
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 */
class IcuFormatter implements FormatterInterface
{
    /**
     * Format using ICU pattern syntax
     *
     * @param int|DateTimeInterface|Horde_Date $datetime  Unix timestamp, DateTime, DateTimeImmutable, or Horde_Date
     * @param string $pattern  ICU pattern or shortcut ('short', 'medium', 'long', 'full')
     * @param string|\Stringable $locale  ICU locale (e.g., 'en_US', 'de_DE')
     * @param string|null $timezone  Timezone identifier (null = UTC)
     *
     * @return string  Formatted date string
     *
     * @throws InvalidArgumentException if pattern is invalid
     * @throws RuntimeException if formatting fails
     */
    public function format(
        int|DateTimeInterface|Horde_Date $datetime,
        string $pattern,
        string|\Stringable $locale = 'en_US',
        ?string $timezone = null
    ) {
        // Convert Stringable to string
        $locale = (string)$locale;

        // Convert Horde_Date to DateTimeInterface if needed
        if ($datetime instanceof Horde_Date) {
            $datetime = $datetime->toDateTime();
        }

        // Create IntlTimeZone if timezone provided
        $intlTimezone = $timezone ? IntlTimeZone::createTimeZone($timezone) : null;

        // Handle locale-specific shortcuts
        if (in_array($pattern, ['short', 'medium', 'long', 'full'], true)) {
            $dateStyle = match ($pattern) {
                'short' => IntlDateFormatter::SHORT,
                'medium' => IntlDateFormatter::MEDIUM,
                'long' => IntlDateFormatter::LONG,
                'full' => IntlDateFormatter::FULL,
            };

            $formatter = IntlDateFormatter::create(
                $locale,
                $dateStyle,
                IntlDateFormatter::NONE,
                $intlTimezone
            );
        } else {
            // Custom ICU pattern
            $formatter = IntlDateFormatter::create(
                $locale,
                IntlDateFormatter::NONE,
                IntlDateFormatter::NONE,
                $intlTimezone,
                null,
                $pattern
            );
        }

        if (!$formatter) {
            throw new InvalidArgumentException("Failed to create IntlDateFormatter for pattern: $pattern");
        }

        $result = $formatter->format($datetime);

        if ($result === false) {
            throw new RuntimeException("Failed to format timestamp with pattern: $pattern");
        }

        return $result;
    }

    /**
     * Parse ICU formatted string back to Horde_Date
     *
     * IMPORTANT: Always converts through DateTime object for proper timezone handling
     *
     * @param string $formattedString  ICU formatted date string
     * @param string $pattern  ICU pattern (or shortcut: short/medium/long/full)
     * @param string|\Stringable $locale  ICU locale (e.g., 'en_US', 'de_DE')
     * @param string|null $timezone  Timezone identifier (null = UTC)
     *
     * @return DateInterface  Horde_Date object
     *
     * @throws InvalidArgumentException if pattern is invalid
     * @throws RuntimeException if parsing fails
     */
    public function parse(
        string $formattedString,
        string $pattern,
        string|\Stringable $locale = 'en_US',
        ?string $timezone = null
    ): DateInterface {
        // Convert Stringable to string
        $locale = (string)$locale;

        // Create IntlTimeZone if timezone provided
        $intlTimezone = $timezone ? IntlTimeZone::createTimeZone($timezone) : null;

        // Handle locale-specific shortcuts
        if (in_array($pattern, ['short', 'medium', 'long', 'full'], true)) {
            $dateStyle = match ($pattern) {
                'short' => IntlDateFormatter::SHORT,
                'medium' => IntlDateFormatter::MEDIUM,
                'long' => IntlDateFormatter::LONG,
                'full' => IntlDateFormatter::FULL,
            };

            $parser = IntlDateFormatter::create(
                $locale,
                $dateStyle,
                IntlDateFormatter::NONE,
                $intlTimezone
            );
        } else {
            // Custom ICU pattern
            $parser = IntlDateFormatter::create(
                $locale,
                IntlDateFormatter::NONE,
                IntlDateFormatter::NONE,
                $intlTimezone,
                null,
                $pattern
            );
        }

        if (!$parser) {
            throw new InvalidArgumentException("Failed to create IntlDateFormatter parser for pattern: $pattern");
        }

        // Parse to timestamp
        $timestamp = $parser->parse($formattedString);

        if ($timestamp === false) {
            throw new RuntimeException(
                "Failed to parse date string: $formattedString " .
                "(pattern: $pattern, locale: $locale)"
            );
        }

        // CRITICAL: Convert through DateTime object with timezone
        // This ensures proper timezone handling and consistency with Horde_Date expectations
        if ($timezone) {
            $dateTime = new DateTime('@' . $timestamp);
            $dateTime->setTimezone(new DateTimeZone($timezone));
        } else {
            $dateTime = new DateTime('@' . $timestamp);
        }

        return new Horde_Date($dateTime);
    }
}
