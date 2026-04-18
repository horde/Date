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

use Horde\Date\Formatter\DateTimeFormatter;
use Horde\Date\Formatter\IcuFormatter;
use Horde_Date;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use Stringable;
use stdClass;

/**
 * Tests for Horde_Date::format() with pluggable formatters
 *
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 * @coversNothing
 */
class DatePluggableFormatterTest extends TestCase
{
    protected string $oldTimezone;
    protected string|false $oldLocale;

    public function setUp(): void
    {
        $this->oldTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');

        // Save current locale
        $this->oldLocale = setlocale(LC_ALL, '0');

        // Set a known locale for consistent test results
        setlocale(LC_ALL, 'en_US.UTF-8');
    }

    public function tearDown(): void
    {
        date_default_timezone_set($this->oldTimezone);

        // Restore original locale
        if ($this->oldLocale !== false) {
            if (strlen($this->oldLocale) <= 255) {
                setlocale(LC_ALL, $this->oldLocale);
            } else {
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
     * Test using DateTimeFormatter by class name
     */
    public function testDateTimeFormatterByClassName(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:45');

        $result = $date->format('Y-m-d H:i:s', DateTimeFormatter::class);
        $this->assertEquals('2026-03-18 14:30:45', $result);
    }

    /**
     * Test using DateTimeFormatter instance
     */
    public function testDateTimeFormatterByInstance(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:45');
        $formatter = new DateTimeFormatter();

        $result = $date->format('Y-m-d H:i:s', $formatter);
        $this->assertEquals('2026-03-18 14:30:45', $result);
    }

    /**
     * Test using IcuFormatter by class name
     */
    public function testIcuFormatterByClassName(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:45');

        $result = $date->format('yyyy-MM-dd HH:mm:ss', IcuFormatter::class);
        $this->assertEquals('2026-03-18 14:30:45', $result);
    }

    /**
     * Test using IcuFormatter instance
     */
    public function testIcuFormatterByInstance(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:45');
        $formatter = new IcuFormatter();

        $result = $date->format('yyyy-MM-dd HH:mm:ss', $formatter);
        $this->assertEquals('2026-03-18 14:30:45', $result);
    }

    /**
     * Test IcuFormatter with locale parameter
     */
    public function testIcuFormatterWithLocale(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:45');

        // English
        $result = $date->format('EEEE, MMMM dd, yyyy', IcuFormatter::class, 'en_US');
        $this->assertEquals('Wednesday, March 18, 2026', $result);

        // German
        $result = $date->format('EEEE, dd. MMMM yyyy', IcuFormatter::class, 'de_DE');
        $this->assertEquals('Mittwoch, 18. März 2026', $result);

        // French
        $result = $date->format('EEEE dd MMMM yyyy', IcuFormatter::class, 'fr_FR');
        $this->assertEquals('mercredi 18 mars 2026', $result);
    }

    /**
     * Test locale set in constructor
     */
    public function testLocaleInConstructor(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:45', null, 'de_DE');

        // Should use de_DE from constructor
        $result = $date->format('MMMM', IcuFormatter::class);
        $this->assertEquals('März', $result);
    }

    /**
     * Test locale parameter overrides constructor locale
     */
    public function testLocaleParameterOverridesConstructor(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:45', null, 'de_DE');

        // Constructor has de_DE, but parameter should override
        $result = $date->format('MMMM', IcuFormatter::class, 'fr_FR');
        $this->assertEquals('mars', $result);
    }

    /**
     * Test variadic constructor with positional locale parameter (8th arg)
     */
    public function testVariadicConstructorWithPositionalLocale(): void
    {
        // Pass locale as 8th positional argument: year, month, day, hour, min, sec, timezone, locale
        $date = new Horde_Date(2026, 3, 18, 14, 30, 45, null, 'de_DE');

        // Should use de_DE from constructor
        $result = $date->format('EEEE, dd. MMMM yyyy HH:mm:ss', IcuFormatter::class);
        $this->assertEquals('Mittwoch, 18. März 2026 14:30:45', $result);

        // Verify date components
        $this->assertEquals(2026, $date->year);
        $this->assertEquals(3, $date->month);
        $this->assertEquals(18, $date->mday);
        $this->assertEquals(14, $date->hour);
        $this->assertEquals(30, $date->min);
        $this->assertEquals(45, $date->sec);
    }

    /**
     * Test constructor with first arg and named locale parameter (PHP 8+)
     */
    public function testConstructorWithNamedLocale(): void
    {
        // Use only first positional arg, then named parameters (PHP 8+)
        $date = new Horde_Date('2026-03-18 14:30:45', timezone: null, locale: 'de_DE');

        // Should use de_DE from constructor
        $result = $date->format('EEEE, dd. MMMM yyyy HH:mm:ss', IcuFormatter::class);
        $this->assertEquals('Mittwoch, 18. März 2026 14:30:45', $result);
    }

    /**
     * Test timezone from constructor is respected
     */
    public function testTimezoneFromConstructor(): void
    {
        // Create dates in different timezones from same UTC moment
        $timestamp = strtotime('2026-03-18 12:00:00 UTC');
        $utc = new Horde_Date($timestamp, 'UTC');
        $ny = new Horde_Date($timestamp, 'America/New_York');

        $utcResult = $utc->format('HH:mm', IcuFormatter::class, 'en_US');
        $this->assertEquals('12:00', $utcResult);

        // Same UTC moment, different timezone = different wall clock time
        $nyResult = $ny->format('HH:mm', IcuFormatter::class, 'en_US');
        $this->assertNotEquals('12:00', $nyResult);
        $this->assertNotEquals($utcResult, $nyResult);
    }

    /**
     * Test invalid formatter class name throws exception
     */
    public function testInvalidFormatterClassThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter class not found');

        $date = new Horde_Date('2026-03-18 14:30:45');
        $date->format('Y-m-d', 'NonExistentFormatter');
    }

    /**
     * Test formatter not implementing interface throws exception
     */
    public function testFormatterNotImplementingInterfaceThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter must implement FormatterInterface');

        $date = new Horde_Date('2026-03-18 14:30:45');
        $date->format('Y-m-d', new stdClass());
    }

    /**
     * Test Stringable pattern
     */
    public function testStringablePattern(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:45');

        // Create a simple Stringable
        $pattern = new class implements Stringable {
            public function __toString(): string
            {
                return 'yyyy-MM-dd';
            }
        };

        $result = $date->format($pattern, IcuFormatter::class);
        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test that old single-argument usage still works (BC)
     */
    public function testBackwardCompatibilitySingleArgument(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:45');

        // Old usage should still work
        $result = $date->format('Y-m-d H:i:s');
        $this->assertEquals('2026-03-18 14:30:45', $result);
    }

    /**
     * Test that format caching still works for BC path
     */
    public function testFormatCachingStillWorks(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:45');

        // Call twice with same pattern
        $result1 = $date->format('Y-m-d H:i:s');
        $result2 = $date->format('Y-m-d H:i:s');

        $this->assertEquals($result1, $result2);
        $this->assertEquals('2026-03-18 14:30:45', $result1);
    }

    /**
     * Test explicit null formatter uses default DateTimeFormatter
     */
    public function testExplicitNullFormatterUsesDefault(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:45');

        // Explicitly pass null formatter
        $result = $date->format('Y-m-d', null);
        $this->assertEquals('2026-03-18', $result);
    }

    /**
     * Test locale fallback chain
     */
    public function testLocaleFallbackChain(): void
    {
        // Set system locale
        setlocale(LC_ALL, 'de_DE.UTF-8');

        // No locale in constructor, should fall back to setlocale()
        $date = new Horde_Date('2026-03-18 14:30:45');

        $result = $date->format('MMMM', IcuFormatter::class);
        // Should use de_DE from setlocale()
        $this->assertEquals('März', $result);
    }

    /**
     * Test that DateInterface is implemented (via duck typing)
     */
    public function testImplementsDateInterface(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:45');

        // Check method signature compatibility
        $this->assertTrue(method_exists($date, 'format'));
        $this->assertTrue(method_exists($date, 'timestamp'));

        // Verify it works like DateInterface
        $timestamp = $date->timestamp();
        $this->assertIsInt($timestamp);

        $formatted = $date->format('yyyy-MM-dd', IcuFormatter::class, 'en_US');
        $this->assertIsString($formatted);
    }

    /**
     * Test IcuFormatter shortcut formats
     */
    public function testIcuFormatterShortcuts(): void
    {
        $date = new Horde_Date('2026-03-18 14:30:45');

        $short = $date->format('short', IcuFormatter::class, 'en_US');
        $this->assertNotEmpty($short);

        $medium = $date->format('medium', IcuFormatter::class, 'en_US');
        $this->assertNotEmpty($medium);

        $long = $date->format('long', IcuFormatter::class, 'en_US');
        $this->assertNotEmpty($long);

        $full = $date->format('full', IcuFormatter::class, 'en_US');
        $this->assertNotEmpty($full);
    }
}
