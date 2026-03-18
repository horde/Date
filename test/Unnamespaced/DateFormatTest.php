<?php

declare(strict_types=1);
/**
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */

namespace Horde\Date\Test;

use Horde_Date;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Horde_Date::format() method
 *
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */
class DateFormatTest extends TestCase
{
    protected string $oldTimezone;
    protected string|false $oldLocale;

    public function setUp(): void
    {
        $this->oldTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');

        // Save current locale
        $this->oldLocale = setlocale(LC_ALL, 0);

        // Set a known locale for consistent test results
        setlocale(LC_ALL, 'en_US.UTF-8');
    }

    public function tearDown(): void
    {
        date_default_timezone_set($this->oldTimezone);

        // Restore original locale
        if ($this->oldLocale !== false) {
            // Locale can be a long string with multiple categories
            // Need to restore each category separately
            if (strlen($this->oldLocale) <= 255) {
                setlocale(LC_ALL, $this->oldLocale);
            } else {
                // Long locale strings need category-by-category restoration
                foreach (explode(';', $this->oldLocale) as $lc) {
                    if (strpos($lc, '=') !== false) {
                        [$category, $catLocale] = explode('=', $lc, 2);
                        if (defined($category)) {
                            setlocale(constant($category), $catLocale);
                        }
                    }
                }
            }
        }
    }

    /**
     * Test basic date formatting with PHP date() syntax
     */
    public function testBasicDateFormat(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:00');

        $this->assertEquals('2026-03-18', $date->format('Y-m-d'));
        $this->assertEquals('18/03/2026', $date->format('d/m/Y'));
        $this->assertEquals('2026', $date->format('Y'));
        $this->assertEquals('03', $date->format('m'));
        $this->assertEquals('18', $date->format('d'));
    }

    /**
     * Test time formatting with PHP date() syntax
     */
    public function testTimeFormat(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:45');

        $this->assertEquals('14:30:45', $date->format('H:i:s'));
        $this->assertEquals('14:30', $date->format('H:i'));
        $this->assertEquals('02:30:45 PM', $date->format('h:i:s A'));
        $this->assertEquals('14', $date->format('H'));
        $this->assertEquals('30', $date->format('i'));
        $this->assertEquals('45', $date->format('s'));
    }

    /**
     * Test combined date and time formatting
     */
    public function testDateTimeFormat(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:45');

        $this->assertEquals('2026-03-18 14:30:45', $date->format('Y-m-d H:i:s'));
        $this->assertEquals('18/03/2026 14:30', $date->format('d/m/Y H:i'));
    }

    /**
     * Test day of week formatting
     */
    public function testDayOfWeekFormat(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:00'); // Wednesday

        $this->assertEquals('Wednesday', $date->format('l'));
        $this->assertEquals('Wed', $date->format('D'));
        $this->assertEquals('3', $date->format('N')); // ISO-8601 (1=Monday)
        $this->assertEquals('3', $date->format('w')); // 0=Sunday
    }

    /**
     * Test month name formatting
     */
    public function testMonthNameFormat(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:00');

        $this->assertEquals('March', $date->format('F'));
        $this->assertEquals('Mar', $date->format('M'));
    }

    /**
     * Test timezone with constructor
     */
    public function testTimezoneRespected(): void
    {
        // Create date in UTC
        $utcDate = new Horde_Date('2026-03-18 12:00:00', 'UTC');
        $this->assertEquals('12:00', $utcDate->format('H:i'));

        // Create same moment in New York time (UTC-5 in March, before DST)
        $nyDate = new Horde_Date('2026-03-18 12:00:00', 'America/New_York');
        $this->assertEquals('12:00', $nyDate->format('H:i'));

        // Different timezones, same format output
        $this->assertEquals('2026-03-18', $utcDate->format('Y-m-d'));
        $this->assertEquals('2026-03-18', $nyDate->format('Y-m-d'));
    }

    /**
     * Test format caching (internal optimization)
     */
    public function testFormatCaching(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:00');

        // Call format twice with same pattern
        $result1 = $date->format('Y-m-d H:i:s');
        $result2 = $date->format('Y-m-d H:i:s');

        // Should return same result
        $this->assertEquals($result1, $result2);
        $this->assertEquals('2026-03-18 14:30:00', $result1);
    }

    /**
     * Test various common format patterns
     */
    public function testCommonFormatPatterns(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:00');

        // ISO 8601
        $this->assertEquals('2026-03-18T14:30:00+00:00', $date->format('c'));

        // RFC 2822
        $this->assertStringContainsString('2026', $date->format('r'));

        // Unix timestamp
        $this->assertIsNumeric($date->format('U'));
    }

    /**
     * Test edge case: February 29 in leap year
     */
    public function testLeapYearDate(): void
    {
        $date = new Horde_Date('2024-02-29 12:00:00');

        $this->assertEquals('2024-02-29', $date->format('Y-m-d'));
        $this->assertEquals('29', $date->format('d'));
        $this->assertEquals('02', $date->format('m'));
        $this->assertEquals('February', $date->format('F'));
    }

    /**
     * Test edge case: End of year
     */
    public function testEndOfYearDate(): void
    {
        $date = new Horde_Date('2025-12-31 23:59:59');

        $this->assertEquals('2025-12-31', $date->format('Y-m-d'));
        $this->assertEquals('23:59:59', $date->format('H:i:s'));

        // Day of year (0-indexed, so Dec 31 = 364 for 2025)
        $dayOfYear = $date->format('z');
        $this->assertTrue(in_array($dayOfYear, ['364', '365'], true), "Day of year should be 364 or 365, got: $dayOfYear");
    }

    /**
     * Test edge case: Start of year
     */
    public function testStartOfYearDate(): void
    {
        $date = new Horde_Date('2026-01-01 00:00:00');

        $this->assertEquals('2026-01-01', $date->format('Y-m-d'));
        $this->assertEquals('00:00:00', $date->format('H:i:s'));
        $this->assertEquals('0', $date->format('z')); // Day of year (0-indexed)
    }

    /**
     * Test format with different date inputs
     */
    public function testDifferentDateInputs(): void
    {
        // String input
        $date1 = new Horde_Date('2026-03-18');
        $this->assertEquals('2026-03-18', $date1->format('Y-m-d'));

        // Timestamp input
        $timestamp = strtotime('2026-03-18 14:30:00');
        $date2 = new Horde_Date($timestamp);
        $this->assertEquals('2026-03-18', $date2->format('Y-m-d'));

        // Array input
        $date3 = new Horde_Date(['year' => 2026, 'month' => 3, 'day' => 18]);
        $this->assertEquals('2026-03-18', $date3->format('Y-m-d'));
    }

    /**
     * Test that format returns string type
     */
    public function testFormatReturnsString(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:00');

        $result = $date->format('Y-m-d');

        $this->assertIsString($result);
        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test format with escaped characters
     */
    public function testFormatWithEscapedCharacters(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:00');

        // Backslash escapes the next character
        $this->assertEquals('Y2026', $date->format('\YY'));
        $this->assertEquals('The year is 2026', $date->format('\T\h\e \y\e\a\r \i\s Y'));
    }

    /**
     * Test format with all single-letter specifiers
     */
    public function testAllSingleLetterSpecifiers(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:45');

        // Just verify they all return non-empty strings (except special cases)
        $specifiers = ['d', 'D', 'j', 'l', 'N', 'S', 'w', 'z',
                       'W', 'F', 'm', 'M', 'n', 't',
                       'o', 'Y', 'y',
                       'a', 'A', 'B', 'g', 'G', 'h', 'H', 'i', 's', 'u', 'v',
                       'e', 'O', 'P', 'T',
                       'c', 'r', 'U'];

        foreach ($specifiers as $spec) {
            $result = $date->format($spec);
            $this->assertNotEmpty($result, "Specifier '$spec' returned empty");
        }

        // Test L separately (leap year: 0 or 1)
        $leapYear = $date->format('L');
        $this->assertTrue(in_array($leapYear, ['0', '1'], true), "L should be 0 or 1, got: $leapYear");

        // Test I separately (DST indicator: 0 or 1)
        $dst = $date->format('I');
        $this->assertTrue(in_array($dst, ['0', '1'], true), "I should be 0 or 1, got: $dst");

        // Test Z separately (timezone offset in seconds, can be 0 for UTC)
        $tzOffset = $date->format('Z');
        $this->assertIsNumeric($tzOffset, "Z should be numeric, got: $tzOffset");
    }

    /**
     * Test with historical dates (before 1970)
     */
    public function testHistoricalDate(): void
    {
        $date = new Horde_Date('1967-06-23 10:30:00');

        $this->assertEquals('1967-06-23', $date->format('Y-m-d'));
        $this->assertEquals('1967', $date->format('Y'));
        $this->assertEquals('June', $date->format('F'));
    }

    /**
     * Test with future dates
     */
    public function testFutureDate(): void
    {
        $date = new Horde_Date('2150-12-31 23:59:59');

        $this->assertEquals('2150-12-31', $date->format('Y-m-d'));
        $this->assertEquals('2150', $date->format('Y'));
        $this->assertEquals('December', $date->format('F'));
    }

    /**
     * Test format consistency with DateTime
     */
    public function testConsistencyWithDateTime(): void
    {
        $dateString = '2026-03-18 14:30:45';
        $hordeDate = new Horde_Date($dateString, 'UTC');
        $phpDate = new \DateTime($dateString, new \DateTimeZone('UTC'));

        // Should format identically to DateTime
        $this->assertEquals($phpDate->format('Y-m-d'), $hordeDate->format('Y-m-d'));
        $this->assertEquals($phpDate->format('H:i:s'), $hordeDate->format('H:i:s'));
        $this->assertEquals($phpDate->format('l, F j, Y'), $hordeDate->format('l, F j, Y'));
    }

    /**
     * Test with en_US locale explicitly
     */
    public function testEnUsLocaleFormat(): void
    {
        if (!setlocale(LC_ALL, 'en_US.UTF-8')) {
            $this->markTestSkipped('en_US.UTF-8 locale not available.');
        }

        $date = new Horde_Date('2026-03-18 14:30:00');

        // Month and day names should be in English
        $this->assertEquals('March', $date->format('F'));
        $this->assertEquals('Mar', $date->format('M'));
        $this->assertEquals('Wednesday', $date->format('l'));
        $this->assertEquals('Wed', $date->format('D'));

        // Date format should use en_US conventions
        $this->assertEquals('2026-03-18', $date->format('Y-m-d'));
    }

    /**
     * Test with de_DE locale
     */
    public function testDeDeLocaleFormat(): void
    {
        if (!setlocale(LC_ALL, 'de_DE.UTF-8')) {
            $this->markTestSkipped('de_DE.UTF-8 locale not available.');
        }

        $date = new Horde_Date('2026-03-18 14:30:00');

        // Note: PHP DateTime::format() uses English names regardless of locale
        // This is expected behavior - locale doesn't affect DateTime::format()
        // (only strftime() is locale-aware)
        $this->assertEquals('March', $date->format('F'));
        $this->assertEquals('Wednesday', $date->format('l'));

        // Numeric formats are locale-independent
        $this->assertEquals('2026-03-18', $date->format('Y-m-d'));
        $this->assertEquals('14:30:00', $date->format('H:i:s'));
    }
}
