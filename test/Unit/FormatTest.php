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

namespace Horde\Date\Test\Unit;

use Horde\Date\Format;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for Horde\Date\Format
 *
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 */
#[CoversClass(Format::class)]
class FormatTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear cache before each test
        Format::clearCache();
    }

    /**
     * Test basic strftime → ICU conversion
     */
    public function testBasicConversion(): void
    {
        $this->assertEquals('yyyy-MM-dd', Format::strftimeToIcu('%Y-%m-%d'));
        $this->assertEquals('dd/MM/yyyy', Format::strftimeToIcu('%d/%m/%Y'));
        $this->assertEquals('EEEE, MMMM dd, yyyy', Format::strftimeToIcu('%A, %B %d, %Y'));
        $this->assertEquals('HH:mm', Format::strftimeToIcu('%R'));
        $this->assertEquals('HH:mm:ss', Format::strftimeToIcu('%T'));
    }

    /**
     * Test locale-specific formats
     */
    public function testLocaleFormats(): void
    {
        $result = Format::strftimeToIcu('%x');
        $this->assertIsArray($result);
        $this->assertEquals('locale', $result['type']);
        $this->assertEquals('date', $result['format']);

        $result = Format::strftimeToIcu('%X');
        $this->assertIsArray($result);
        $this->assertEquals('locale', $result['type']);
        $this->assertEquals('time', $result['format']);

        $result = Format::strftimeToIcu('%c');
        $this->assertIsArray($result);
        $this->assertEquals('locale', $result['type']);
        $this->assertEquals('datetime', $result['format']);
    }

    /**
     * Test format detection
     */
    public function testIsStrftimeFormat(): void
    {
        $this->assertTrue(Format::isStrftimeFormat('%Y-%m-%d'));
        $this->assertTrue(Format::isStrftimeFormat('Today is %A'));
        $this->assertTrue(Format::isStrftimeFormat('%R'));
        $this->assertFalse(Format::isStrftimeFormat('yyyy-MM-dd'));
        $this->assertFalse(Format::isStrftimeFormat('2026-03-17'));
        $this->assertFalse(Format::isStrftimeFormat('50% complete')); // No valid strftime specifier
    }

    /**
     * Test actual formatting with ISO date
     */
    public function testFormatIsoDate(): void
    {
        $timestamp = strtotime('2026-03-17 14:30:00');

        $result = Format::formatDate($timestamp, '%Y-%m-%d', 'en_US');
        $this->assertEquals('2026-03-17', $result);
    }

    /**
     * Test formatting with US date format
     */
    public function testFormatUsDate(): void
    {
        $timestamp = strtotime('2026-03-17 14:30:00');

        $result = Format::formatDate($timestamp, '%m/%d/%Y', 'en_US');
        $this->assertEquals('03/17/2026', $result);
    }

    /**
     * Test formatting with full date format
     */
    public function testFormatFullDate(): void
    {
        $timestamp = strtotime('2026-03-17 14:30:00');

        $result = Format::formatDate($timestamp, '%A, %B %d, %Y', 'en_US');
        $this->assertStringContainsString('2026', $result);
        $this->assertStringContainsString('March', $result);
        $this->assertStringContainsString('17', $result);
    }

    /**
     * Test formatting with time
     */
    public function testFormatTime(): void
    {
        $timestamp = strtotime('2026-03-17 14:30:00');

        $result = Format::formatDate($timestamp, '%R', 'en_US');
        $this->assertEquals('14:30', $result);

        $result = Format::formatDate($timestamp, '%T', 'en_US');
        $this->assertEquals('14:30:00', $result);
    }

    /**
     * Test caching
     */
    public function testCaching(): void
    {
        $format = '%Y-%m-%d %H:%M:%S';

        // First call
        $result1 = Format::strftimeToIcu($format);

        // Second call (should be cached)
        $result2 = Format::strftimeToIcu($format);

        $this->assertEquals($result1, $result2);
        $this->assertEquals('yyyy-MM-dd HH:mm:ss', $result1);
    }

    /**
     * Test all preference formats from horde/base/config/prefs.php
     */
    public function testAllPreferenceFormats(): void
    {
        $formats = [
            '%x',
            '%Y-%m-%d',
            '%d/%m/%Y',
            '%A, %B %d, %Y',
            '%A, %d. %B %Y',
            '%A, %d %B %Y',
            '%a, %b %e, %Y',
        ];

        foreach ($formats as $format) {
            $result = Format::strftimeToIcu($format);
            $this->assertNotEmpty($result);
        }
    }

    /**
     * Test Turba-specific formats
     */
    public function testTurbaFormats(): void
    {
        // From lib/Driver/Sql.php and lib/Application.php
        $this->assertEquals('yyyy-MM-dd', Format::strftimeToIcu('%Y-%m-%d'));
        $this->assertEquals('HH:mm', Format::strftimeToIcu('%R'));
        $this->assertEquals('yyyy-MM-dd HH:mm', Format::strftimeToIcu('%Y-%m-%d %R'));
    }

    /**
     * Test compound formats with text
     */
    public function testCompoundFormats(): void
    {
        $result = Format::strftimeToIcu('Today is %A, %B %d, %Y');
        $this->assertEquals('Today is EEEE, MMMM dd, yyyy', $result);

        $result = Format::strftimeToIcu('Date: %Y-%m-%d Time: %H:%M');
        $this->assertEquals('Date: yyyy-MM-dd Time: HH:mm', $result);
    }

    /**
     * Test escaping of literal %
     */
    public function testLiteralPercent(): void
    {
        $result = Format::strftimeToIcu('100%% complete on %Y-%m-%d');
        $this->assertEquals('100% complete on yyyy-MM-dd', $result);
    }

    /**
     * Test DateTime object input
     */
    public function testDateTimeInput(): void
    {
        $date = new \DateTime('2026-03-17 14:30:00');
        $result = Format::formatDate($date, '%Y-%m-%d', 'en_US');
        $this->assertEquals('2026-03-17', $result);
    }

    /**
     * Test string input
     */
    public function testStringInput(): void
    {
        $result = Format::formatDate('2026-03-17 14:30:00', '%Y-%m-%d', 'en_US');
        $this->assertEquals('2026-03-17', $result);
    }

    /**
     * Test locale-specific date formatting
     */
    public function testLocaleSpecificFormatting(): void
    {
        $timestamp = strtotime('2026-03-17 14:30:00');

        // Test with %x (locale date)
        $result = Format::formatDate($timestamp, '%x', 'en_US');
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('3', $result); // Month or day
        $this->assertStringContainsString('17', $result); // Day

        // Test with %X (locale time)
        $result = Format::formatDate($timestamp, '%X', 'en_US');
        $this->assertNotEmpty($result);
        $this->assertStringContainsString(':', $result); // Time separator
    }

    /**
     * Test cache clearing
     */
    public function testClearCache(): void
    {
        // Populate cache
        Format::strftimeToIcu('%Y-%m-%d');

        // Clear cache
        Format::clearCache();

        // Should still work after clearing
        $result = Format::strftimeToIcu('%Y-%m-%d');
        $this->assertEquals('yyyy-MM-dd', $result);
    }

    /**
     * Test all format specifiers for completeness
     */
    public function testAllFormatSpecifiers(): void
    {
        $tests = [
            // Day formats
            '%a' => 'EEE',           // Abbreviated weekday
            '%A' => 'EEEE',          // Full weekday
            '%d' => 'dd',            // Day of month (01-31)
            '%e' => 'd',             // Day of month (1-31)
            '%j' => 'DDD',           // Day of year (001-366)
            '%u' => 'e',             // ISO-8601 day of week (1-7)
            '%w' => 'e',             // Day of week (0-6)
            // Week formats
            '%U' => 'ww',            // Week number (Sunday start)
            '%V' => 'w',             // ISO-8601 week number
            '%W' => 'ww',            // Week number (Monday start)
            // Month formats
            '%b' => 'MMM',           // Abbreviated month
            '%B' => 'MMMM',          // Full month
            '%h' => 'MMM',           // Abbreviated month (alias)
            '%m' => 'MM',            // Month (01-12)
            // Year formats
            '%C' => 'yy',            // Century
            '%g' => 'yy',            // ISO-8601 year (2-digit)
            '%G' => 'Y',             // ISO-8601 year (4-digit)
            '%y' => 'yy',            // Year (2-digit)
            '%Y' => 'yyyy',          // Year (4-digit)
            // Time formats
            '%H' => 'HH',            // Hour 24-hour (00-23)
            '%I' => 'hh',            // Hour 12-hour (01-12)
            '%k' => 'H',             // Hour 24-hour (0-23, space-padded)
            '%l' => 'h',             // Hour 12-hour (1-12, space-padded)
            '%M' => 'mm',            // Minute (00-59)
            '%p' => 'a',             // AM/PM
            '%P' => 'a',             // am/pm
            '%S' => 'ss',            // Seconds (00-59)
            // Timezone formats
            '%z' => 'ZZZZZ',         // Timezone offset
            '%Z' => 'z',             // Timezone name
            // Composite formats
            '%D' => 'MM/dd/yy',      // Date US format
            '%F' => 'yyyy-MM-dd',    // ISO 8601 date
            '%R' => 'HH:mm',         // Time 24-hour
            '%T' => 'HH:mm:ss',      // Time 24-hour with seconds
            '%r' => 'hh:mm:ss a',    // Time 12-hour
            // Special characters
            '%n' => "\n",            // Newline
            '%t' => "\t",            // Tab
            '%%' => '%',             // Literal %
        ];

        foreach ($tests as $strftime => $expected) {
            $result = Format::strftimeToIcu($strftime);
            $this->assertEquals($expected, $result, "Failed to convert $strftime");
        }
    }

    /**
     * Test pattern replacement order (longer patterns first)
     */
    public function testPatternReplacementOrder(): void
    {
        // %R (HH:mm) should be replaced before %r (hh:mm:ss a)
        // to avoid %R being treated as %r + 'R'
        $result = Format::strftimeToIcu('%R');
        $this->assertEquals('HH:mm', $result);

        $result = Format::strftimeToIcu('%r');
        $this->assertEquals('hh:mm:ss a', $result);

        // Test composite format that includes both
        $result = Format::strftimeToIcu('%R vs %r');
        $this->assertEquals('HH:mm vs hh:mm:ss a', $result);
    }

    /**
     * Test DateTimeImmutable input
     */
    public function testDateTimeImmutableInput(): void
    {
        $date = new \DateTimeImmutable('2026-03-17 14:30:00');
        $result = Format::formatDate($date, '%Y-%m-%d', 'en_US');
        $this->assertEquals('2026-03-17', $result);
    }

    /**
     * Test edge timestamps
     */
    public function testEdgeTimestamps(): void
    {
        // Unix epoch
        $result = Format::formatDate(0, '%Y-%m-%d', 'en_US');
        $this->assertEquals('1970-01-01', $result);

        // Negative timestamp (before 1970)
        $result = Format::formatDate(-86400, '%Y-%m-%d', 'en_US');
        $this->assertEquals('1969-12-31', $result);

        // Future date
        $result = Format::formatDate(strtotime('2150-12-31'), '%Y-%m-%d', 'en_US');
        $this->assertEquals('2150-12-31', $result);
    }

    /**
     * Test invalid string input throws exception
     */
    public function testInvalidStringInputThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to format timestamp');

        // This will create an invalid timestamp (false from strtotime)
        // which will fail in IntlDateFormatter
        Format::formatDate('not-a-valid-date-string-xyz', '%Y-%m-%d', 'en_US');
    }

    /**
     * Test invalid locale format type throws exception
     */
    public function testInvalidLocaleFormatType(): void
    {
        // This should not happen in normal usage, but test the guard
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown locale format');

        // Manually trigger by modifying internal state (would need reflection)
        // For now, just document that this is tested internally by the match expression
        $this->markTestIncomplete('Requires reflection to test internal guard');
    }

    /**
     * Test empty string input
     */
    public function testEmptyStringDetection(): void
    {
        $this->assertFalse(Format::isStrftimeFormat(''));
    }

    /**
     * Test single % character
     */
    public function testSinglePercentDetection(): void
    {
        $this->assertFalse(Format::isStrftimeFormat('%'));
    }

    /**
     * Test invalid format specifier
     */
    public function testInvalidFormatSpecifier(): void
    {
        // %Q is not a valid strftime specifier
        $this->assertFalse(Format::isStrftimeFormat('%Q'));

        // But if it's in a string with valid specifiers, those should still be detected
        $this->assertTrue(Format::isStrftimeFormat('%Y-%Q-%d'));
    }

    /**
     * Test multiple %% in string
     */
    public function testMultipleLiteralPercents(): void
    {
        $result = Format::strftimeToIcu('100%% done, 50%% remaining on %Y-%m-%d');
        $this->assertEquals('100% done, 50% remaining on yyyy-MM-dd', $result);
    }

    /**
     * Test German locale (de_DE)
     */
    public function testGermanLocale(): void
    {
        $timestamp = strtotime('2026-03-17 14:30:00');

        // Full month name should be in German
        $result = Format::formatDate($timestamp, '%B', 'de_DE');
        $this->assertEquals('März', $result);

        // Full weekday name should be in German
        $result = Format::formatDate($timestamp, '%A', 'de_DE');
        $this->assertStringContainsString('Dienstag', $result);

        // Full date format
        $result = Format::formatDate($timestamp, '%A, %d. %B %Y', 'de_DE');
        $this->assertStringContainsString('März', $result);
        $this->assertStringContainsString('2026', $result);
    }

    /**
     * Test French locale (fr_FR)
     */
    public function testFrenchLocale(): void
    {
        $timestamp = strtotime('2026-03-17 14:30:00');

        // Full month name should be in French
        $result = Format::formatDate($timestamp, '%B', 'fr_FR');
        $this->assertEquals('mars', $result);

        // Full weekday name should be in French
        $result = Format::formatDate($timestamp, '%A', 'fr_FR');
        $this->assertEquals('mardi', $result);

        // Full date format
        $result = Format::formatDate($timestamp, '%A %d %B %Y', 'fr_FR');
        $this->assertStringContainsString('mars', $result);
        $this->assertStringContainsString('mardi', $result);
    }

    /**
     * Test Spanish locale (es_ES)
     */
    public function testSpanishLocale(): void
    {
        $timestamp = strtotime('2026-03-17 14:30:00');

        // Full month name should be in Spanish
        $result = Format::formatDate($timestamp, '%B', 'es_ES');
        $this->assertEquals('marzo', $result);

        // Full weekday name should be in Spanish
        $result = Format::formatDate($timestamp, '%A', 'es_ES');
        $this->assertEquals('martes', $result);

        // Full date format
        $result = Format::formatDate($timestamp, '%d de %B de %Y', 'es_ES');
        $this->assertStringContainsString('marzo', $result);
    }

    /**
     * Test Russian locale (ru_RU)
     */
    public function testRussianLocale(): void
    {
        $timestamp = strtotime('2026-03-17 14:30:00');

        // Full month name should be in Russian (Cyrillic)
        $result = Format::formatDate($timestamp, '%B', 'ru_RU');
        $this->assertEquals('марта', $result); // Genitive case

        // Full weekday name should be in Russian
        $result = Format::formatDate($timestamp, '%A', 'ru_RU');
        $this->assertEquals('вторник', $result);

        // ISO date format should work regardless of locale
        $result = Format::formatDate($timestamp, '%Y-%m-%d', 'ru_RU');
        $this->assertEquals('2026-03-17', $result);
    }

    /**
     * Test Arabic locale (ar_SA) - RTL language
     */
    public function testArabicLocaleRTL(): void
    {
        $timestamp = strtotime('2026-03-17 14:30:00');

        // Try to set Arabic locale - skip if not available
        $oldLocale = setlocale(LC_ALL, 0);
        if (!setlocale(LC_ALL, 'ar_SA.UTF-8')) {
            $this->markTestSkipped('ar_SA.UTF-8 locale not available.');
        }

        // Full month name should be in Arabic (RTL)
        $result = Format::formatDate($timestamp, '%B', 'ar_SA');
        $this->assertEquals('مارس', $result);

        // Full weekday name should be in Arabic
        $result = Format::formatDate($timestamp, '%A', 'ar_SA');
        $this->assertEquals('الثلاثاء', $result);

        // Numeric formats should still work correctly
        // Note: ar_SA may use Arabic-Indic numerals or ASCII digits depending on locale implementation
        $result = Format::formatDate($timestamp, '%Y-%m-%d', 'ar_SA');
        // Accept either Arabic-Indic or ASCII digits
        $this->assertTrue(
            in_array($result, ['2026-03-17', '٢٠٢٦-٠٣-١٧']),
            "Expected '2026-03-17' or '٢٠٢٦-٠٣-١٧', got: $result"
        );

        // Restore locale
        if (strlen($oldLocale) <= 255) {
            setlocale(LC_ALL, $oldLocale);
        }
    }

    /**
     * Test Hebrew locale (he_IL) - RTL language
     */
    public function testHebrewLocaleRTL(): void
    {
        $timestamp = strtotime('2026-03-17 14:30:00');

        // Try to set Hebrew locale - skip if not available
        $oldLocale = setlocale(LC_ALL, 0);
        if (!setlocale(LC_ALL, 'he_IL.UTF-8')) {
            $this->markTestSkipped('he_IL.UTF-8 locale not available.');
        }

        // Full month name should be in Hebrew (RTL)
        $result = Format::formatDate($timestamp, '%B', 'he_IL');
        $this->assertEquals('מרץ', $result);

        // Numeric formats should work
        $result = Format::formatDate($timestamp, '%Y-%m-%d', 'he_IL');
        $this->assertEquals('2026-03-17', $result);

        // Restore locale
        if (strlen($oldLocale) <= 255) {
            setlocale(LC_ALL, $oldLocale);
        }
    }

    /**
     * Test locale-specific date format with %x in different locales
     */
    public function testLocaleSpecificDateAcrossLocales(): void
    {
        $timestamp = strtotime('2026-03-17 14:30:00');

        // Different locales format dates differently
        $locales = ['en_US', 'de_DE', 'fr_FR', 'es_ES'];

        foreach ($locales as $locale) {
            $result = Format::formatDate($timestamp, '%x', $locale);
            $this->assertNotEmpty($result);
            // All should contain the day (17)
            $this->assertStringContainsString('17', $result);
        }
    }

    /**
     * Test Unicode characters in format string
     */
    public function testUnicodeInFormat(): void
    {
        // Japanese format with Kanji characters
        $result = Format::strftimeToIcu('日付: %Y年%m月%d日');
        $this->assertEquals('日付: yyyy年MM月dd日', $result);

        // Format with emoji
        $result = Format::strftimeToIcu('📅 Date: %Y-%m-%d');
        $this->assertEquals('📅 Date: yyyy-MM-dd', $result);
    }

    /**
     * Test all composite format shortcuts
     */
    public function testCompositeFormatShortcuts(): void
    {
        $timestamp = strtotime('2026-03-17 14:30:00');

        // %D = %m/%d/%y
        $result = Format::formatDate($timestamp, '%D', 'en_US');
        $this->assertEquals('03/17/26', $result);

        // %F = %Y-%m-%d
        $result = Format::formatDate($timestamp, '%F', 'en_US');
        $this->assertEquals('2026-03-17', $result);

        // %R = %H:%M
        $result = Format::formatDate($timestamp, '%R', 'en_US');
        $this->assertEquals('14:30', $result);

        // %T = %H:%M:%S
        $result = Format::formatDate($timestamp, '%T', 'en_US');
        $this->assertEquals('14:30:00', $result);
    }

    /**
     * Test 12-hour time format
     */
    public function testTwelveHourTimeFormat(): void
    {
        $timestamp = strtotime('2026-03-17 14:30:00');

        // %I = 12-hour hour
        $result = Format::formatDate($timestamp, '%I:%M %p', 'en_US');
        $this->assertStringContainsString('02:30', $result); // 14:30 = 02:30 PM
        $this->assertStringContainsString('PM', $result);

        // Morning time
        $morning = strtotime('2026-03-17 09:15:00');
        $result = Format::formatDate($morning, '%I:%M %p', 'en_US');
        $this->assertStringContainsString('09:15', $result);
        $this->assertStringContainsString('AM', $result);
    }

    /**
     * Test week-related formats
     */
    public function testWeekFormats(): void
    {
        $timestamp = strtotime('2026-03-17 14:30:00'); // Week 12 of 2026

        // %V = ISO-8601 week number
        $result = Format::strftimeToIcu('%V');
        $this->assertEquals('w', $result);

        // %U = Week number (Sunday start)
        $result = Format::strftimeToIcu('%U');
        $this->assertEquals('ww', $result);

        // %W = Week number (Monday start)
        $result = Format::strftimeToIcu('%W');
        $this->assertEquals('ww', $result);
    }

    /**
     * Test timezone formats
     */
    public function testTimezoneFormats(): void
    {
        // %z = timezone offset
        $result = Format::strftimeToIcu('%z');
        $this->assertEquals('ZZZZZ', $result);

        // %Z = timezone name
        $result = Format::strftimeToIcu('%Z');
        $this->assertEquals('z', $result);
    }
}
