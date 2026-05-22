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

use DateTime;
use DateTimeInterface;
use Horde\Date\Formatter\DateTimeFormatter;
use Horde\Date\Formatter\IcuFormatter;
use IntlDateFormatter;
use InvalidArgumentException;
use RuntimeException;
use Stringable;

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
     * Cache converted formats (keyed by locale:format)
     */
    protected static array $conversionCache = [];

    /**
     * Cache resolved locale patterns (keyed by locale)
     */
    protected static array $localePatternCache = [];

    /**
     * Convert strftime format to ICU format
     *
     * Resolves locale-specific tokens (%x, %X, %c) to the locale's actual
     * ICU patterns inline, so compound formats like '%a %x' work correctly.
     *
     * @param string $strftimeFormat  strftime format string
     * @param string $locale  ICU locale for resolving %x/%X/%c (default: 'en_US')
     * @return string  ICU format pattern
     */
    public static function strftimeToIcu(string $strftimeFormat, string $locale = 'en_US'): string
    {
        $cacheKey = $locale . ':' . $strftimeFormat;

        if (isset(self::$conversionCache[$cacheKey])) {
            return self::$conversionCache[$cacheKey];
        }

        // Resolve locale-specific tokens (%x, %X, %c) to actual ICU patterns
        $localePatterns = self::resolveLocalePatterns($locale);
        $format = str_replace(
            ['%x', '%X', '%c'],
            [$localePatterns['date'], $localePatterns['time'], $localePatterns['datetime']],
            $strftimeFormat
        );

        // If no strftime specifiers remain after locale resolution, return as-is
        if (!str_contains($format, '%')) {
            self::$conversionCache[$cacheKey] = $format;
            return $format;
        }

        // Convert remaining pattern using string replacement
        $patterns = self::$strftimeToIcuMap;
        uksort($patterns, fn($a, $b) => strlen($b) <=> strlen($a));

        $icuFormat = $format;
        foreach ($patterns as $strftime => $icu) {
            $placeholder = "\x00" . $icu . "\x00";
            $icuFormat = str_replace($strftime, $placeholder, $icuFormat);
        }

        // Insert separators where adjacent patterns use same letter
        $icuFormat = self::insertAdjacentSeparators($icuFormat);

        // Warn about unconverted patterns
        if (preg_match('/%[a-zA-Z]/', $icuFormat)) {
            error_log("Horde\\Date\\Format: Unsupported strftime pattern in format: $strftimeFormat");
        }

        self::$conversionCache[$cacheKey] = $icuFormat;
        return $icuFormat;
    }

    /**
     * Resolve locale-specific strftime tokens to their actual ICU patterns
     *
     * Queries IntlDateFormatter for the locale's SHORT date and MEDIUM time
     * patterns and returns them for inline substitution.
     *
     * @param string $locale  ICU locale identifier
     * @return array{date: string, time: string, datetime: string}
     */
    protected static function resolveLocalePatterns(string $locale): array
    {
        if (isset(self::$localePatternCache[$locale])) {
            return self::$localePatternCache[$locale];
        }

        $dateFmt = IntlDateFormatter::create($locale, IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
        $timeFmt = IntlDateFormatter::create($locale, IntlDateFormatter::NONE, IntlDateFormatter::MEDIUM);
        $dateTimeFmt = IntlDateFormatter::create($locale, IntlDateFormatter::SHORT, IntlDateFormatter::MEDIUM);

        $result = [
            'date' => $dateFmt ? $dateFmt->getPattern() : 'M/d/yy',
            'time' => $timeFmt ? $timeFmt->getPattern() : 'h:mm:ss a',
            'datetime' => $dateTimeFmt ? $dateTimeFmt->getPattern() : 'M/d/yy, h:mm:ss a',
        ];

        self::$localePatternCache[$locale] = $result;
        return $result;
    }

    /**
     * Insert non-printable separators between adjacent same-letter ICU patterns
     *
     * Processes a string with \x00 boundary markers around ICU patterns.
     * When two patterns are adjacent and start with the same letter,
     * inserts \x01 separator between them.
     *
     * @param string $format  Format string with \x00 boundary markers
     * @return string  Format string with \x01 separators and \x00 markers removed
     */
    protected static function insertAdjacentSeparators(string $format): string
    {
        $parts = explode("\x00", $format);

        $result = '';
        $prevPattern = null;

        for ($i = 0; $i < count($parts); $i++) {
            $part = $parts[$i];

            if ($part === '') {
                continue;
            }

            if (preg_match('/^[yMdHhmsSDEwWazZ]/', $part)) {
                $patternLetter = $part[0];

                if ($prevPattern !== null && $prevPattern[0] === $patternLetter) {
                    $result .= "\x01";
                }

                $result .= $part;
                $prevPattern = $part;
            } else {
                $result .= $part;
                $prevPattern = null;
            }
        }

        return $result;
    }

    /**
     * Format a date using strftime or ICU format (auto-converts strftime to ICU)
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
        if ($timestamp instanceof DateTime || $timestamp instanceof DateTimeInterface) {
            $timestamp = $timestamp->getTimestamp();
        } elseif (is_string($timestamp)) {
            if (is_numeric($timestamp)) {
                $timestamp = (int) $timestamp;
            } else {
                $timestamp = strtotime($timestamp);
            }
        }

        if ($timestamp === false || !is_int($timestamp)) {
            throw new InvalidArgumentException("Invalid timestamp value");
        }

        // Convert strftime to ICU if needed
        if (self::isStrftimeFormat($format)) {
            $icuFormat = self::strftimeToIcu($format, $locale);
        } else {
            $icuFormat = $format;
        }

        // Handle IcuFormatter shortcuts
        if (in_array($icuFormat, ['short', 'medium', 'long', 'full'], true)) {
            $dateStyle = match ($icuFormat) {
                'short' => IntlDateFormatter::SHORT,
                'medium' => IntlDateFormatter::MEDIUM,
                'long' => IntlDateFormatter::LONG,
                'full' => IntlDateFormatter::FULL,
            };
            $formatter = IntlDateFormatter::create(
                $locale,
                $dateStyle,
                IntlDateFormatter::NONE
            );
        } else {
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
            throw new RuntimeException("Failed to create IntlDateFormatter for format: $format");
        }

        $result = $formatter->format($timestamp);
        if ($result === false) {
            throw new RuntimeException("Failed to format timestamp with format: $format");
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
        if (!str_contains($format, '%')) {
            return false;
        }

        $strftimePattern = '/%[aAbBCdDeHIjmMnpPrRStTuUVwWxXyYzZFGghklZ%]/';
        return preg_match($strftimePattern, $format) === 1;
    }

    /**
     * Parse a formatted date string to a DateInterface object
     *
     * Detects the pattern type (strftime, ICU, or PHP date()) and dispatches
     * to the appropriate formatter's parse method.
     *
     * @param string $formattedString  The date string to parse
     * @param string $pattern  Format pattern (strftime, ICU, or PHP date() syntax)
     * @param string|Stringable $locale  Locale for parsing (default: 'en_US')
     * @param string|null $timezone  Timezone identifier (null = UTC)
     *
     * @return DateInterface  Parsed date object
     *
     * @throws RuntimeException if parsing fails
     */
    public static function parse(
        string $formattedString,
        string $pattern,
        string|Stringable $locale = 'en_US',
        ?string $timezone = null
    ): DateInterface {
        $locale = (string) $locale;

        if (self::isStrftimeFormat($pattern)) {
            $icuPattern = self::strftimeToIcu($pattern, $locale);
            $formatter = new IcuFormatter();
            return $formatter->parse($formattedString, $icuPattern, $locale, $timezone);
        }

        if (self::isPhpDateFormat($pattern)) {
            $formatter = new DateTimeFormatter();
            return $formatter->parse($formattedString, $pattern, $locale, $timezone);
        }

        // Default: treat as ICU pattern
        $formatter = new IcuFormatter();
        return $formatter->parse($formattedString, $pattern, $locale, $timezone);
    }

    /**
     * Parse a formatted date+time string using separate date and time patterns
     *
     * Combines date and time patterns into a single ICU pattern and parses
     * the string atomically. This avoids regex-based string splitting which
     * breaks with AM/PM markers and other multi-word tokens.
     *
     * @param string $formattedString  The date+time string to parse
     * @param string $datePattern  Date format pattern (strftime, ICU, or PHP date())
     * @param string $timePattern  Time format pattern (strftime, ICU, or PHP date())
     * @param string|Stringable $locale  Locale for parsing (default: 'en_US')
     * @param string|null $timezone  Timezone identifier (null = UTC)
     *
     * @return DateInterface  Parsed date object
     *
     * @throws RuntimeException if parsing fails
     */
    public static function parseDateTime(
        string $formattedString,
        string $datePattern,
        string $timePattern,
        string|Stringable $locale = 'en_US',
        ?string $timezone = null
    ): DateInterface {
        $locale = (string) $locale;

        // Convert both patterns to ICU if needed
        $icuDate = self::isStrftimeFormat($datePattern)
            ? self::strftimeToIcu($datePattern, $locale)
            : $datePattern;

        $icuTime = self::isStrftimeFormat($timePattern)
            ? self::strftimeToIcu($timePattern, $locale)
            : $timePattern;

        // Combine into a single ICU pattern with space separator
        $combinedPattern = $icuDate . ' ' . $icuTime;

        $formatter = new IcuFormatter();
        return $formatter->parse($formattedString, $combinedPattern, $locale, $timezone);
    }

    /**
     * Detect if a pattern uses PHP date() syntax (single letters like Y, m, d, H, i, s)
     *
     * Distinguishes from ICU patterns which use repeated letters (yyyy, MM, dd).
     * A pattern is considered PHP date() if it contains characteristic PHP date
     * letters that do not appear in ICU patterns as single characters.
     *
     * @param string $pattern  Pattern to check
     * @return bool  True if the pattern appears to be PHP date() syntax
     */
    public static function isPhpDateFormat(string $pattern): bool
    {
        // ICU locale shortcuts (handled by IcuFormatter, not PHP date())
        if (in_array($pattern, ['short', 'medium', 'long', 'full'], true)) {
            return false;
        }

        // These characters are unique to PHP date() and don't appear as single
        // letters in ICU patterns in the same way
        $phpOnlyChars = ['i', 'j', 'n', 'g', 'A', 'N', 'L', 'o', 'U', 'u'];
        foreach ($phpOnlyChars as $char) {
            if (str_contains($pattern, $char)) {
                return true;
            }
        }

        // Single Y/m/d/H/s without repetition is PHP style
        // ICU uses yyyy, MM, dd, HH, ss (repeated)
        if (preg_match('/(?<![a-zA-Z])([YmdHsG])(?![a-zA-Z])/', $pattern)
            && !preg_match('/(yyyy|MM|dd|HH|mm|ss|EEEE|EEE)/', $pattern)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Clear the conversion cache
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$conversionCache = [];
        self::$localePatternCache = [];
    }
}
