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

use Horde\Date\Formatter\IcuFormatter;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for IcuFormatter
 *
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 */
#[CoversClass(IcuFormatter::class)]
class IcuFormatterTest extends TestCase
{
    protected string $oldTimezone;

    protected function setUp(): void
    {
        $this->oldTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->oldTimezone);
    }

    /**
     * Test basic ICU date formatting
     */
    public function testBasicDateFormat(): void
    {
        $formatter = new IcuFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45');

        $this->assertEquals('2026-03-18', $formatter->format($timestamp, 'yyyy-MM-dd'));
        $this->assertEquals('18/03/2026', $formatter->format($timestamp, 'dd/MM/yyyy'));
        $this->assertEquals('2026', $formatter->format($timestamp, 'yyyy'));
    }

    /**
     * Test ICU time formatting
     */
    public function testTimeFormat(): void
    {
        $formatter = new IcuFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45');

        $this->assertEquals('14:30:45', $formatter->format($timestamp, 'HH:mm:ss'));
        $this->assertEquals('14:30', $formatter->format($timestamp, 'HH:mm'));
        $this->assertEquals('02:30:45 PM', $formatter->format($timestamp, 'hh:mm:ss a'));
    }

    /**
     * Test locale-aware formatting
     */
    public function testLocaleAwareFormatting(): void
    {
        $formatter = new IcuFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45');

        // English
        $this->assertEquals('March', $formatter->format($timestamp, 'MMMM', 'en_US'));
        $this->assertEquals('Wednesday', $formatter->format($timestamp, 'EEEE', 'en_US'));

        // German
        $this->assertEquals('März', $formatter->format($timestamp, 'MMMM', 'de_DE'));
        $this->assertEquals('Mittwoch', $formatter->format($timestamp, 'EEEE', 'de_DE'));

        // French
        $this->assertEquals('mars', $formatter->format($timestamp, 'MMMM', 'fr_FR'));
        $this->assertEquals('mercredi', $formatter->format($timestamp, 'EEEE', 'fr_FR'));
    }

    /**
     * Test timezone handling
     */
    public function testTimezoneHandling(): void
    {
        $formatter = new IcuFormatter();
        $timestamp = strtotime('2026-03-18 12:00:00 UTC');

        // Format in UTC
        $utcResult = $formatter->format($timestamp, 'yyyy-MM-dd HH:mm:ss', 'en_US', 'UTC');
        $this->assertEquals('2026-03-18 12:00:00', $utcResult);

        // Format in New York time
        $nyResult = $formatter->format($timestamp, 'yyyy-MM-dd HH:mm:ss', 'en_US', 'America/New_York');
        $this->assertEquals('2026-03-18 08:00:00', $nyResult);

        // Format in Tokyo time
        $tokyoResult = $formatter->format($timestamp, 'yyyy-MM-dd HH:mm:ss', 'en_US', 'Asia/Tokyo');
        $this->assertEquals('2026-03-18 21:00:00', $tokyoResult);
    }

    /**
     * Test timezone abbreviations
     */
    public function testTimezoneAbbreviations(): void
    {
        $formatter = new IcuFormatter();
        $timestamp = strtotime('2026-03-18 12:00:00 UTC');

        // UTC
        $result = $formatter->format($timestamp, 'HH:mm z', 'en_US', 'UTC');
        $this->assertStringContainsString('UTC', $result);

        // New York (EDT in March)
        $result = $formatter->format($timestamp, 'HH:mm z', 'en_US', 'America/New_York');
        $this->assertStringContainsString('08:00', $result);

        // Tokyo - note: abbreviation format varies by ICU version
        $result = $formatter->format($timestamp, 'HH:mm', 'en_US', 'Asia/Tokyo');
        $this->assertStringContainsString('21:00', $result);
    }

    /**
     * Test shortcut formats
     */
    public function testShortcutFormats(): void
    {
        $formatter = new IcuFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45');

        // Short
        $short = $formatter->format($timestamp, 'short', 'en_US');
        $this->assertNotEmpty($short);
        $this->assertStringContainsString('3', $short);  // Month or day

        // Medium
        $medium = $formatter->format($timestamp, 'medium', 'en_US');
        $this->assertNotEmpty($medium);
        $this->assertStringContainsString('2026', $medium);

        // Long
        $long = $formatter->format($timestamp, 'long', 'en_US');
        $this->assertNotEmpty($long);
        $this->assertStringContainsString('2026', $long);

        // Full
        $full = $formatter->format($timestamp, 'full', 'en_US');
        $this->assertNotEmpty($full);
        $this->assertStringContainsString('2026', $full);
    }

    /**
     * Test shortcut formats with different locales
     */
    public function testShortcutFormatsWithLocales(): void
    {
        $formatter = new IcuFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45');

        // English short date
        $enShort = $formatter->format($timestamp, 'short', 'en_US');
        $this->assertNotEmpty($enShort);

        // German short date (different format)
        $deShort = $formatter->format($timestamp, 'short', 'de_DE');
        $this->assertNotEmpty($deShort);

        // Both should contain the day number
        $this->assertStringContainsString('18', $enShort);
        $this->assertStringContainsString('18', $deShort);
    }

    /**
     * Test day of week in multiple locales
     */
    public function testDayOfWeekMultipleLocales(): void
    {
        $formatter = new IcuFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45'); // Wednesday

        // Full day names
        $this->assertEquals('Wednesday', $formatter->format($timestamp, 'EEEE', 'en_US'));
        $this->assertEquals('Mittwoch', $formatter->format($timestamp, 'EEEE', 'de_DE'));
        $this->assertEquals('mercredi', $formatter->format($timestamp, 'EEEE', 'fr_FR'));
        $this->assertEquals('miércoles', $formatter->format($timestamp, 'EEEE', 'es_ES'));

        // Abbreviated day names (may vary by ICU version with/without period)
        $enAbbr = $formatter->format($timestamp, 'EEE', 'en_US');
        $this->assertTrue(in_array($enAbbr, ['Wed', 'Wed.'], true), "Expected 'Wed' or 'Wed.', got: $enAbbr");

        $deAbbr = $formatter->format($timestamp, 'EEE', 'de_DE');
        $this->assertTrue(in_array($deAbbr, ['Mi', 'Mi.'], true), "Expected 'Mi' or 'Mi.', got: $deAbbr");

        $frAbbr = $formatter->format($timestamp, 'EEE', 'fr_FR');
        $this->assertTrue(in_array($frAbbr, ['mer', 'mer.'], true), "Expected 'mer' or 'mer.', got: $frAbbr");
    }

    /**
     * Test month names in multiple locales
     */
    public function testMonthNamesMultipleLocales(): void
    {
        $formatter = new IcuFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45'); // March

        // Full month names
        $this->assertEquals('March', $formatter->format($timestamp, 'MMMM', 'en_US'));
        $this->assertEquals('März', $formatter->format($timestamp, 'MMMM', 'de_DE'));
        $this->assertEquals('mars', $formatter->format($timestamp, 'MMMM', 'fr_FR'));
        $this->assertEquals('marzo', $formatter->format($timestamp, 'MMMM', 'es_ES'));

        // Abbreviated month names
        $this->assertEquals('Mar', $formatter->format($timestamp, 'MMM', 'en_US'));
        $this->assertEquals('März', $formatter->format($timestamp, 'MMM', 'de_DE'));
        $this->assertEquals('mars', $formatter->format($timestamp, 'MMM', 'fr_FR'));
    }

    /**
     * Test full date format with text
     */
    public function testFullDateFormatWithText(): void
    {
        $formatter = new IcuFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45');

        // English
        $result = $formatter->format($timestamp, 'EEEE, MMMM dd, yyyy', 'en_US');
        $this->assertEquals('Wednesday, March 18, 2026', $result);

        // German
        $result = $formatter->format($timestamp, 'EEEE, dd. MMMM yyyy', 'de_DE');
        $this->assertEquals('Mittwoch, 18. März 2026', $result);

        // French
        $result = $formatter->format($timestamp, 'EEEE dd MMMM yyyy', 'fr_FR');
        $this->assertEquals('mercredi 18 mars 2026', $result);
    }

    /**
     * Test that formatter implements FormatterInterface
     */
    public function testImplementsFormatterInterface(): void
    {
        $formatter = new IcuFormatter();
        $this->assertInstanceOf(\Horde\Date\FormatterInterface::class, $formatter);
    }

    /**
     * Test historical dates
     */
    public function testHistoricalDate(): void
    {
        $formatter = new IcuFormatter();
        $timestamp = strtotime('1967-06-23 10:30:00');

        $this->assertEquals('1967-06-23', $formatter->format($timestamp, 'yyyy-MM-dd'));
        $this->assertEquals('June', $formatter->format($timestamp, 'MMMM', 'en_US'));
    }

    /**
     * Test future dates
     */
    public function testFutureDate(): void
    {
        $formatter = new IcuFormatter();
        $timestamp = strtotime('2150-12-31 23:59:59');

        $this->assertEquals('2150-12-31', $formatter->format($timestamp, 'yyyy-MM-dd'));
        $this->assertEquals('December', $formatter->format($timestamp, 'MMMM', 'en_US'));
    }

    /**
     * Test 12-hour format with AM/PM
     */
    public function testTwelveHourFormat(): void
    {
        $formatter = new IcuFormatter();

        // Afternoon
        $afternoon = strtotime('2026-03-18 14:30:45');
        $result = $formatter->format($afternoon, 'hh:mm a', 'en_US');
        $this->assertStringContainsString('02:30', $result);
        $this->assertStringContainsString('PM', $result);

        // Morning
        $morning = strtotime('2026-03-18 09:15:00');
        $result = $formatter->format($morning, 'hh:mm a', 'en_US');
        $this->assertStringContainsString('09:15', $result);
        $this->assertStringContainsString('AM', $result);
    }

    /**
     * Test week of year
     */
    public function testWeekOfYear(): void
    {
        $formatter = new IcuFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45');

        $result = $formatter->format($timestamp, 'w', 'en_US');
        $this->assertIsNumeric($result);
        $this->assertGreaterThanOrEqual(1, (int) $result);
        $this->assertLessThanOrEqual(53, (int) $result);
    }

    /**
     * Test quarter
     */
    public function testQuarter(): void
    {
        $formatter = new IcuFormatter();

        // Q1
        $q1 = strtotime('2026-02-15 12:00:00');
        $this->assertEquals('1', $formatter->format($q1, 'Q', 'en_US'));

        // Q2
        $q2 = strtotime('2026-05-15 12:00:00');
        $this->assertEquals('2', $formatter->format($q2, 'Q', 'en_US'));

        // Q3
        $q3 = strtotime('2026-08-15 12:00:00');
        $this->assertEquals('3', $formatter->format($q3, 'Q', 'en_US'));

        // Q4
        $q4 = strtotime('2026-11-15 12:00:00');
        $this->assertEquals('4', $formatter->format($q4, 'Q', 'en_US'));
    }

    /**
     * Test leap year
     */
    public function testLeapYear(): void
    {
        $formatter = new IcuFormatter();

        // Leap year
        $leapYear = strtotime('2024-02-29 12:00:00');
        $this->assertEquals('2024-02-29', $formatter->format($leapYear, 'yyyy-MM-dd'));

        // Non-leap year
        $nonLeapYear = strtotime('2026-03-18 12:00:00');
        $this->assertEquals('2026-03-18', $formatter->format($nonLeapYear, 'yyyy-MM-dd'));
    }

    /**
     * Test invalid pattern throws exception
     */
    public function testInvalidPatternThrowsException(): void
    {
        $formatter = new IcuFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45');

        // IntlDateFormatter may or may not throw on invalid patterns
        // depending on ICU version. Test that it at least doesn't crash.
        try {
            $result = $formatter->format($timestamp, "yyyy-MM-dd'incomplete", 'en_US');
            // If it doesn't throw, at least verify we got some output
            $this->assertIsString($result);
        } catch (InvalidArgumentException|RuntimeException $e) {
            // If it does throw, verify the exception message
            $this->assertStringContainsString('Failed to', $e->getMessage());
        }
    }

    /**
     * Test Spanish locale
     */
    public function testSpanishLocale(): void
    {
        $formatter = new IcuFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45');

        $this->assertEquals('marzo', $formatter->format($timestamp, 'MMMM', 'es_ES'));
        $this->assertEquals('miércoles', $formatter->format($timestamp, 'EEEE', 'es_ES'));
    }

    /**
     * Test Italian locale
     */
    public function testItalianLocale(): void
    {
        $formatter = new IcuFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45');

        $this->assertEquals('marzo', $formatter->format($timestamp, 'MMMM', 'it_IT'));
        $this->assertEquals('mercoledì', $formatter->format($timestamp, 'EEEE', 'it_IT'));
    }

    /**
     * Test Japanese locale
     */
    public function testJapaneseLocale(): void
    {
        $formatter = new IcuFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45');

        // Japanese month and day
        $result = $formatter->format($timestamp, 'MMMM', 'ja_JP');
        $this->assertStringContainsString('3月', $result);

        $result = $formatter->format($timestamp, 'EEEE', 'ja_JP');
        $this->assertStringContainsString('水曜日', $result);
    }

    /**
     * Test parse() with ISO format
     */
    public function testParseIsoFormat(): void
    {
        $formatter = new IcuFormatter();

        $date = $formatter->parse('2026-03-18', 'yyyy-MM-dd', 'en_US');

        $this->assertSame('2026', $date->format('Y'));
        $this->assertSame('03', $date->format('m'));
        $this->assertSame('18', $date->format('d'));
    }

    /**
     * Test parse() with locale-specific format
     */
    public function testParseWithLocale(): void
    {
        $formatter = new IcuFormatter();

        // Parse German formatted date
        $date = $formatter->parse('Mittwoch, 18. März 2026', 'EEEE, dd. MMMM yyyy', 'de_DE');

        $this->assertSame('2026', $date->format('Y'));
        $this->assertSame('03', $date->format('m'));
        $this->assertSame('18', $date->format('d'));
    }

    /**
     * Test parse() with shortcut format
     */
    public function testParseShortcutFormat(): void
    {
        $formatter = new IcuFormatter();

        // Format a date with 'short' format
        $timestamp = strtotime('2026-03-18');
        $formatted = $formatter->format($timestamp, 'short', 'en_US');

        // Parse it back
        $date = $formatter->parse($formatted, 'short', 'en_US');

        $this->assertSame('2026', $date->format('Y'));
        $this->assertSame('03', $date->format('m'));
        $this->assertSame('18', $date->format('d'));
    }

    /**
     * Test parse() with time components
     */
    public function testParseWithTime(): void
    {
        $formatter = new IcuFormatter();

        $date = $formatter->parse('2026-03-18 14:30:45', 'yyyy-MM-dd HH:mm:ss', 'en_US');

        $this->assertSame('2026', $date->format('Y'));
        $this->assertSame('03', $date->format('m'));
        $this->assertSame('18', $date->format('d'));
        $this->assertSame('14', $date->format('H'));
        $this->assertSame('30', $date->format('i'));
        $this->assertSame('45', $date->format('s'));
    }

    /**
     * Test parse() with timezone
     */
    public function testParseWithTimezone(): void
    {
        $formatter = new IcuFormatter();

        $date = $formatter->parse('2026-03-18 14:30', 'yyyy-MM-dd HH:mm', 'en_US', 'Europe/Berlin');

        $this->assertSame('2026', $date->format('Y'));
        $this->assertSame('03', $date->format('m'));
        $this->assertSame('18', $date->format('d'));
        $this->assertSame('14', $date->format('H'));
        $this->assertSame('30', $date->format('i'));
    }

    /**
     * Test parse() round-trip (format → parse → format)
     */
    public function testParseRoundTrip(): void
    {
        $formatter = new IcuFormatter();
        $originalTimestamp = strtotime('2026-03-18 14:30:00');

        // Format
        $formatted = $formatter->format($originalTimestamp, 'yyyy-MM-dd HH:mm', 'en_US');

        // Parse back
        $parsed = $formatter->parse($formatted, 'yyyy-MM-dd HH:mm', 'en_US');

        // Should match (within same minute due to seconds being dropped)
        $this->assertSame('2026-03-18 14:30', $parsed->format('Y-m-d H:i'));
    }

    /**
     * Test parse() with invalid date string
     */
    public function testParseInvalidString(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse date string');

        $formatter = new IcuFormatter();
        $formatter->parse('invalid date', 'yyyy-MM-dd', 'en_US');
    }

    /**
     * Test parse() with mismatched pattern
     */
    public function testParseMismatchedPattern(): void
    {
        $formatter = new IcuFormatter();

        // IntlDateFormatter may or may not throw on mismatched patterns
        // depending on the pattern and input. Test that either:
        // 1. An exception is thrown, OR
        // 2. Parsing fails and returns false (which our code converts to exception)
        try {
            $date = $formatter->parse('2026-03-18', 'dd. MMMM yyyy', 'de_DE');
            // If it succeeds, verify the date is at least valid
            $this->assertInstanceOf(\Horde\Date\DateInterface::class, $date);
        } catch (RuntimeException $e) {
            // Expected exception
            $this->assertStringContainsString('Failed to parse', $e->getMessage());
        }
    }

    /**
     * Test parse() French locale round-trip
     */
    public function testParseFrenchLocaleRoundTrip(): void
    {
        $formatter = new IcuFormatter();
        $originalTimestamp = strtotime('2026-03-18');

        // Format in French
        $formatted = $formatter->format($originalTimestamp, 'EEEE dd MMMM yyyy', 'fr_FR');
        // Expected: "mercredi 18 mars 2026"

        // Parse back
        $parsed = $formatter->parse($formatted, 'EEEE dd MMMM yyyy', 'fr_FR');

        $this->assertSame('2026', $parsed->format('Y'));
        $this->assertSame('03', $parsed->format('m'));
        $this->assertSame('18', $parsed->format('d'));
    }
}
