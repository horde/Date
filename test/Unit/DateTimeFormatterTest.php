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

use Horde\Date\Formatter\DateTimeFormatter;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for DateTimeFormatter
 *
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 */
#[CoversClass(DateTimeFormatter::class)]
class DateTimeFormatterTest extends TestCase
{
    protected string $oldTimezone;

    protected function setUp(): void
    {
        $this->oldTimezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->oldTimezone);
    }

    /**
     * Test basic date formatting
     */
    public function testBasicDateFormat(): void
    {
        date_default_timezone_set('UTC');
        $formatter = new DateTimeFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45');

        $this->assertEquals('2026-03-18', $formatter->format($timestamp, 'Y-m-d'));
        $this->assertEquals('18/03/2026', $formatter->format($timestamp, 'd/m/Y'));
        $this->assertEquals('2026', $formatter->format($timestamp, 'Y'));
    }

    /**
     * Test time formatting
     */
    public function testTimeFormat(): void
    {
        date_default_timezone_set('UTC');
        $formatter = new DateTimeFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45');

        $this->assertEquals('14:30:45', $formatter->format($timestamp, 'H:i:s'));
        $this->assertEquals('14:30', $formatter->format($timestamp, 'H:i'));
        $this->assertEquals('02:30:45 PM', $formatter->format($timestamp, 'h:i:s A'));
    }

    /**
     * Test that locale parameter is ignored
     */
    public function testLocaleIsIgnored(): void
    {
        date_default_timezone_set('UTC');
        $formatter = new DateTimeFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45');

        // DateTime::format() always uses English month names regardless of locale
        $enResult = $formatter->format($timestamp, 'F', 'en_US');
        $deResult = $formatter->format($timestamp, 'F', 'de_DE');
        $frResult = $formatter->format($timestamp, 'F', 'fr_FR');

        $this->assertEquals('March', $enResult);
        $this->assertEquals('March', $deResult);  // Still English
        $this->assertEquals('March', $frResult);  // Still English
    }

    /**
     * Test timezone handling
     */
    public function testTimezoneHandling(): void
    {
        date_default_timezone_set('UTC');
        $formatter = new DateTimeFormatter();
        $timestamp = strtotime('2026-03-18 12:00:00 UTC');

        // Format in UTC
        $utcResult = $formatter->format($timestamp, 'Y-m-d H:i:s', 'en_US', 'UTC');
        $this->assertEquals('2026-03-18 12:00:00', $utcResult);

        // Format in New York time (UTC-5 in March, EDT)
        $nyResult = $formatter->format($timestamp, 'Y-m-d H:i:s', 'en_US', 'America/New_York');
        $this->assertEquals('2026-03-18 08:00:00', $nyResult);

        // Format in Tokyo time (UTC+9)
        $tokyoResult = $formatter->format($timestamp, 'Y-m-d H:i:s', 'en_US', 'Asia/Tokyo');
        $this->assertEquals('2026-03-18 21:00:00', $tokyoResult);
    }

    /**
     * Test timezone null defaults to UTC
     */
    public function testTimezoneNullDefaultsToUtc(): void
    {
        date_default_timezone_set('America/New_York');
        $formatter = new DateTimeFormatter();
        $timestamp = strtotime('2026-03-18 12:00:00 UTC');

        // With null timezone, should format in UTC regardless of default timezone
        $result = $formatter->format($timestamp, 'Y-m-d H:i:s', 'en_US', null);
        $this->assertEquals('2026-03-18 12:00:00', $result);
    }

    /**
     * Test various format specifiers
     */
    public function testFormatSpecifiers(): void
    {
        date_default_timezone_set('UTC');
        $formatter = new DateTimeFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45');

        // Day
        $this->assertEquals('Wednesday', $formatter->format($timestamp, 'l'));
        $this->assertEquals('Wed', $formatter->format($timestamp, 'D'));
        $this->assertEquals('18', $formatter->format($timestamp, 'd'));

        // Month
        $this->assertEquals('March', $formatter->format($timestamp, 'F'));
        $this->assertEquals('Mar', $formatter->format($timestamp, 'M'));
        $this->assertEquals('03', $formatter->format($timestamp, 'm'));

        // Year
        $this->assertEquals('2026', $formatter->format($timestamp, 'Y'));
        $this->assertEquals('26', $formatter->format($timestamp, 'y'));

        // Time
        $this->assertEquals('14', $formatter->format($timestamp, 'H'));
        $this->assertEquals('30', $formatter->format($timestamp, 'i'));
        $this->assertEquals('45', $formatter->format($timestamp, 's'));
    }

    /**
     * Test ISO 8601 format
     */
    public function testIso8601Format(): void
    {
        date_default_timezone_set('UTC');
        $formatter = new DateTimeFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45 UTC');

        $result = $formatter->format($timestamp, 'c', 'en_US', 'UTC');
        $this->assertStringStartsWith('2026-03-18T14:30:45', $result);
    }

    /**
     * Test RFC 2822 format
     */
    public function testRfc2822Format(): void
    {
        date_default_timezone_set('UTC');
        $formatter = new DateTimeFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45 UTC');

        $result = $formatter->format($timestamp, 'r', 'en_US', 'UTC');
        $this->assertStringContainsString('2026', $result);
        $this->assertStringContainsString('14:30:45', $result);
    }

    /**
     * Test Unix timestamp format
     */
    public function testUnixTimestampFormat(): void
    {
        date_default_timezone_set('UTC');
        $formatter = new DateTimeFormatter();
        $timestamp = 1742565045;

        $result = $formatter->format($timestamp, 'U');
        $this->assertEquals((string)$timestamp, $result);
    }

    /**
     * Test historical dates (before 1970)
     */
    public function testHistoricalDate(): void
    {
        date_default_timezone_set('UTC');
        $formatter = new DateTimeFormatter();
        $timestamp = strtotime('1967-06-23 10:30:00');

        $this->assertEquals('1967-06-23', $formatter->format($timestamp, 'Y-m-d'));
        $this->assertEquals('June', $formatter->format($timestamp, 'F'));
    }

    /**
     * Test future dates
     */
    public function testFutureDate(): void
    {
        date_default_timezone_set('UTC');
        $formatter = new DateTimeFormatter();
        $timestamp = strtotime('2150-12-31 23:59:59');

        $this->assertEquals('2150-12-31', $formatter->format($timestamp, 'Y-m-d'));
        $this->assertEquals('December', $formatter->format($timestamp, 'F'));
    }

    /**
     * Test that formatter implements FormatterInterface
     */
    public function testImplementsFormatterInterface(): void
    {
        $formatter = new DateTimeFormatter();
        $this->assertInstanceOf(\Horde\Date\FormatterInterface::class, $formatter);
    }

    /**
     * Test escaped characters
     */
    public function testEscapedCharacters(): void
    {
        date_default_timezone_set('UTC');
        $formatter = new DateTimeFormatter();
        $timestamp = strtotime('2026-03-18 14:30:45');

        $this->assertEquals('Y2026', $formatter->format($timestamp, '\YY'));
        $this->assertEquals('The year is 2026', $formatter->format($timestamp, '\T\h\e \y\e\a\r \i\s Y'));
    }

    /**
     * Test leap year
     */
    public function testLeapYear(): void
    {
        date_default_timezone_set('UTC');
        $formatter = new DateTimeFormatter();

        $leapYear = strtotime('2024-02-29 12:00:00');
        $this->assertEquals('2024-02-29', $formatter->format($leapYear, 'Y-m-d'));
        $this->assertEquals('1', $formatter->format($leapYear, 'L'));

        $nonLeapYear = strtotime('2026-03-18 12:00:00');
        $this->assertEquals('0', $formatter->format($nonLeapYear, 'L'));
    }

    /**
     * Test parse() with basic ISO format
     */
    public function testParseIsoFormat(): void
    {
        $formatter = new DateTimeFormatter();

        $date = $formatter->parse('2026-03-18', 'Y-m-d', 'en_US');

        $this->assertEquals(2026, $date->year);
        $this->assertEquals(3, $date->month);
        $this->assertEquals(18, $date->mday);
    }

    /**
     * Test parse() with time components
     */
    public function testParseWithTime(): void
    {
        $formatter = new DateTimeFormatter();

        $date = $formatter->parse('2026-03-18 14:30:45', 'Y-m-d H:i:s', 'en_US');

        $this->assertEquals(2026, $date->year);
        $this->assertEquals(3, $date->month);
        $this->assertEquals(18, $date->mday);
        $this->assertEquals(14, $date->hour);
        $this->assertEquals(30, $date->min);
        $this->assertEquals(45, $date->sec);
    }

    /**
     * Test parse() with different date format
     */
    public function testParseDifferentFormat(): void
    {
        $formatter = new DateTimeFormatter();

        // US format: m/d/Y
        $date = $formatter->parse('03/18/2026', 'm/d/Y', 'en_US');

        $this->assertEquals(2026, $date->year);
        $this->assertEquals(3, $date->month);
        $this->assertEquals(18, $date->mday);
    }

    /**
     * Test parse() with timezone
     */
    public function testParseWithTimezone(): void
    {
        $formatter = new DateTimeFormatter();

        $date = $formatter->parse('2026-03-18 14:30', 'Y-m-d H:i', 'en_US', 'America/New_York');

        $this->assertEquals(2026, $date->year);
        $this->assertEquals(3, $date->month);
        $this->assertEquals(18, $date->mday);
        $this->assertEquals(14, $date->hour);
        $this->assertEquals(30, $date->min);
    }

    /**
     * Test parse() round-trip (format → parse → format)
     */
    public function testParseRoundTrip(): void
    {
        date_default_timezone_set('UTC');
        $formatter = new DateTimeFormatter();
        $originalTimestamp = strtotime('2026-03-18 14:30:00');

        // Format
        $formatted = $formatter->format($originalTimestamp, 'Y-m-d H:i:s');

        // Parse back
        $parsed = $formatter->parse($formatted, 'Y-m-d H:i:s');

        // Verify round-trip
        $this->assertEquals(2026, $parsed->year);
        $this->assertEquals(3, $parsed->month);
        $this->assertEquals(18, $parsed->mday);
        $this->assertEquals(14, $parsed->hour);
        $this->assertEquals(30, $parsed->min);
        $this->assertEquals(0, $parsed->sec);
    }

    /**
     * Test parse() with 12-hour format
     */
    public function testParseTwelveHourFormat(): void
    {
        $formatter = new DateTimeFormatter();

        $date = $formatter->parse('2026-03-18 2:30 PM', 'Y-m-d g:i A', 'en_US');

        $this->assertEquals(2026, $date->year);
        $this->assertEquals(3, $date->month);
        $this->assertEquals(18, $date->mday);
        $this->assertEquals(14, $date->hour);  // 2 PM = 14:00
        $this->assertEquals(30, $date->min);
    }

    /**
     * Test parse() with invalid date string
     */
    public function testParseInvalidString(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse date string');

        $formatter = new DateTimeFormatter();
        $formatter->parse('invalid date', 'Y-m-d', 'en_US');
    }

    /**
     * Test parse() with mismatched pattern
     */
    public function testParseMismatchedPattern(): void
    {
        $this->expectException(\RuntimeException::class);

        $formatter = new DateTimeFormatter();
        // Try to parse ISO format with US m/d/Y pattern
        $formatter->parse('2026-03-18', 'm/d/Y', 'en_US');
    }

    /**
     * Test parse() complex format round-trip
     */
    public function testParseComplexFormatRoundTrip(): void
    {
        date_default_timezone_set('UTC');
        $formatter = new DateTimeFormatter();
        $originalTimestamp = strtotime('2026-03-18 14:30:00');

        // Complex format with day name
        $formatted = $formatter->format($originalTimestamp, 'l, F j, Y g:i A');
        // Expected: "Wednesday, March 18, 2026 2:30 PM"

        // Parse back
        $parsed = $formatter->parse($formatted, 'l, F j, Y g:i A');

        $this->assertEquals(2026, $parsed->year);
        $this->assertEquals(3, $parsed->month);
        $this->assertEquals(18, $parsed->mday);
        $this->assertEquals(14, $parsed->hour);
        $this->assertEquals(30, $parsed->min);
    }
}

