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

namespace Horde\Date;

use DateTime;
use DateTimeInterface;
use IntlDateFormatter;

/**
 * Date format conversion utilities
 *
 * Converts deprecated strftime format patterns to ICU patterns
 * for use with IntlDateFormatter.
 *
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 * @since     3.1.0
 */
class Format
{
    /**
     * Mapping of strftime specifiers to ICU patterns
     */
    protected static array $strftimeToIcuMap = [
        '%a' => 'EEE',           // Abbreviated weekday name
        '%A' => 'EEEE',          // Full weekday name
        '%b' => 'MMM',           // Abbreviated month name
        '%B' => 'MMMM',          // Full month name
        '%h' => 'MMM',           // Abbreviated month name (alias of %b)
        '%d' => 'dd',            // Day of month (01-31)
        '%e' => 'd',             // Day of month (1-31)
        '%j' => 'DDD',           // Day of year (001-366)
        '%u' => 'e',             // ISO-8601 day of week (1-7, Monday=1)
        '%w' => 'e',             // Day of week (0-6, Sunday=0) - maps to e
        '%U' => 'ww',            // Week number (00-53, Sunday start)
        '%V' => 'w',             // ISO-8601 week number (01-53)
        '%W' => 'ww',            // Week number (00-53, Monday start)
        '%m' => 'MM',            // Month (01-12)
        '%C' => 'yy',            // Century (year divided by 100)
        '%g' => 'yy',            // ISO-8601 year (2-digit)
        '%G' => 'Y',             // ISO-8601 year (4-digit)
        '%y' => 'yy',            // Year (2-digit)
        '%Y' => 'yyyy',          // Year (4-digit)
        '%H' => 'HH',            // Hour 24-hour (00-23)
        '%I' => 'hh',            // Hour 12-hour (01-12)
        '%k' => 'H',             // Hour 24-hour (0-23, space-padded)
        '%l' => 'h',             // Hour 12-hour (1-12, space-padded)
        '%M' => 'mm',            // Minute (00-59)
        '%p' => 'a',             // AM/PM (uppercase)
        '%P' => 'a',             // am/pm (lowercase) - ICU respects locale
        '%S' => 'ss',            // Seconds (00-59)
        '%z' => 'ZZZZZ',         // Timezone offset (-0800)
        '%Z' => 'z',             // Timezone name (EST)
        '%D' => 'MM/dd/yy',      // Date US format (%m/%d/%y)
        '%F' => 'yyyy-MM-dd',    // ISO 8601 date (%Y-%m-%d)
        '%R' => 'HH:mm',         // Time 24-hour (%H:%M)
        '%T' => 'HH:mm:ss',      // Time 24-hour (%H:%M:%S)
        '%r' => 'hh:mm:ss a',    // Time 12-hour (%I:%M:%S %p)
        '%n' => "\n",            // Newline
        '%t' => "\t",            // Tab
        '%%' => '%',             // Literal %
    ];

    /**
     * Locale-specific formats that need IntlDateFormatter constants
     */
    protected static array $localeFormats = [
        '%x' => 'date',      // IntlDateFormatter::SHORT for date
        '%X' => 'time',      // IntlDateFormatter::SHORT for time
        '%c' => 'datetime',  // IntlDateFormatter::SHORT for both
    ];

    /**
     * Cache converted formats
     */
    protected static array $conversionCache = [];

    /**
     * Convert strftime format to ICU format
     *
     * @param string $strftimeFormat  strftime format string
     * @return string|array  ICU format pattern, or ['type' => 'locale', 'format' => ...] for locale formats
     */
    public static function strftimeToIcu(string $strftimeFormat): string|array
    {
        // Check cache
        if (isset(self::$conversionCache[$strftimeFormat])) {
            return self::$conversionCache[$strftimeFormat];
        }

        // Check if it's a locale-specific format
        if (isset(self::$localeFormats[$strftimeFormat])) {
            $result = ['type' => 'locale', 'format' => self::$localeFormats[$strftimeFormat]];
            self::$conversionCache[$strftimeFormat] = $result;
            return $result;
        }

        // Convert pattern using string replacement
        // Use placeholders to track boundaries for separator insertion
        $patterns = self::$strftimeToIcuMap;
        uksort($patterns, fn ($a, $b) => strlen($b) <=> strlen($a));

        $icuFormat = $strftimeFormat;
        foreach ($patterns as $strftime => $icu) {
            // Wrap each ICU pattern with boundary markers
            $placeholder = "\x00" . $icu . "\x00";
            $icuFormat = str_replace($strftime, $placeholder, $icuFormat);
        }

        // Now insert \x01 separators where adjacent patterns use same letter
        // Example: "\x00yyyy\x00\x00yy\x00" → "yyyy\x01yy"
        $icuFormat = self::insertAdjacentSeparators($icuFormat);

        // Check for unconverted patterns (edge cases)
        if (preg_match('/%[a-zA-Z]/', $icuFormat)) {
            // Log warning about unsupported pattern
            error_log("Horde\\Date\\Format: Unsupported strftime pattern in format: $strftimeFormat");
        }

        self::$conversionCache[$strftimeFormat] = $icuFormat;
        return $icuFormat;
    }

    /**
     * Insert non-printable separators between adjacent same-letter ICU patterns
     *
     * Processes a string with \x00 boundary markers around ICU patterns.
     * When two patterns are adjacent (\x00pattern1\x00\x00pattern2\x00) and
     * start with the same letter, inserts \x01 separator between them.
     *
     * Example: "\x00yyyy\x00\x00yy\x00" → "yyyy\x01yy"
     * Example: "\x00yyyy\x00\x00MM\x00" → "yyyyMM" (different letters, no separator)
     *
     * @param string $format  Format string with \x00 boundary markers
     * @return string  Format string with \x01 separators and \x00 markers removed
     */
    protected static function insertAdjacentSeparators(string $format): string
    {
        // Split by \x00 to get patterns and literals
        $parts = explode("\x00", $format);

        $result = '';
        $prevPattern = null;

        for ($i = 0; $i < count($parts); $i++) {
            $part = $parts[$i];

            if ($part === '') {
                // Empty part from adjacent \x00 markers, skip
                continue;
            }

            // Check if this part is a pattern (starts with pattern letter)
            if (preg_match('/^[yMdHhmsSDEwWazZ]/', $part)) {
                // This is a pattern
                $patternLetter = $part[0];

                // If previous was also a pattern with same letter, insert separator
                if ($prevPattern !== null && $prevPattern[0] === $patternLetter) {
                    $result .= "\x01";
                }

                $result .= $part;
                $prevPattern = $part;
            } else {
                // This is a literal
                $result .= $part;
                $prevPattern = null; // Reset tracking
            }
        }

        return $result;
    }

    /**
     * Format a date using strftime format (auto-converts to ICU)
     *
     * @param int|string|DateTime|DateTimeInterface $timestamp  Timestamp or date object
     * @param string $format  strftime or ICU format string
     * @param string $locale  ICU locale (default: 'en_US')
     * @return string  Formatted date
     */
    public static function formatDate(
        int|string|DateTime|DateTimeInterface $timestamp,
        string $format,
        string $locale = 'en_US'
    ): string {
        // Convert timestamp to int if needed
        if ($timestamp instanceof DateTime || $timestamp instanceof DateTimeInterface) {
            $timestamp = $timestamp->getTimestamp();
        } elseif (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        // Only convert if format is strftime
        if (self::isStrftimeFormat($format)) {
            $icuFormat = self::strftimeToIcu($format);
        } else {
            // Already ICU format, use as-is
            $icuFormat = $format;
        }

        // Handle locale-specific formats
        if (is_array($icuFormat) && $icuFormat['type'] === 'locale') {
            $formatter = match ($icuFormat['format']) {
                'date' => IntlDateFormatter::create(
                    $locale,
                    IntlDateFormatter::SHORT,
                    IntlDateFormatter::NONE
                ),
                'time' => IntlDateFormatter::create(
                    $locale,
                    IntlDateFormatter::NONE,
                    IntlDateFormatter::SHORT
                ),
                'datetime' => IntlDateFormatter::create(
                    $locale,
                    IntlDateFormatter::SHORT,
                    IntlDateFormatter::SHORT
                ),
                default => throw new \InvalidArgumentException("Unknown locale format: {$icuFormat['format']}")
            };
        } else {
            // Custom pattern
            $formatter = IntlDateFormatter::create(
                $locale,
                IntlDateFormatter::NONE,
                IntlDateFormatter::NONE,
                null,
                null,
                $icuFormat
            );
        }

        if (!$formatter) {
            throw new \RuntimeException("Failed to create IntlDateFormatter for format: $format");
        }

        $result = $formatter->format($timestamp);
        if ($result === false) {
            throw new \RuntimeException("Failed to format timestamp with format: $format");
        }

        return $result;
    }

    /**
     * Check if format string is strftime (contains %)
     *
     * More sophisticated check that avoids false positives like "50% complete"
     *
     * @param string $format  Format string to check
     * @return bool  True if strftime format
     */
    public static function isStrftimeFormat(string $format): bool
    {
        // Must contain % character
        if (!str_contains($format, '%')) {
            return false;
        }

        // Check for known strftime specifiers
        // This avoids false positives like "50% complete"
        $strftimePattern = '/%[aAbBCdDeHIjmMnpPrRStTuUVwWxXyYzZFGghklZ%]/';
        return preg_match($strftimePattern, $format) === 1;
    }

    /**
     * Clear the conversion cache
     *
     * Useful for testing or when format definitions change at runtime
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$conversionCache = [];
    }
}
