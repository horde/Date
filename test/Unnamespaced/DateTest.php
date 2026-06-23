<?php

declare(strict_types=1);
/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */

namespace Horde\Date\Test;

use date_default_timezone_get;
use date_default_timezone_set;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Horde\Date\Format;
use Horde_Date;
use Horde_Date_Span;
use PHPUnit\Framework\TestCase;
use stdClass;
use Horde_Date_Exception;

/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 * @coversNothing
 */
class DateTest extends TestCase
{
    private string $_oldTimezone;

    public function setUp(): void
    {
        $this->_oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');
    }

    public function tearDown(): void
    {
        date_default_timezone_set($this->_oldTimezone);
    }

    public function testConstructor()
    {
        $date = new stdClass();
        $date->year = 2001;
        $date->month = 2;
        $date->mday = 3;
        $date->hour = 4;
        $date->min = 5;
        $date->sec = 6;

        $this->assertEquals('2001-02-03 04:05:06', (string) new Horde_Date($date));
        $this->assertEquals('2001-02-03 04:05:06', (string) new Horde_Date((array) $date));
        $this->assertEquals('2001-02-03 04:05:06', (string) new Horde_Date(['year' => 2001, 'month' => 2, 'day' => 3, 'hour' => 4, 'minute' => 5, 'sec' => 6]));
        $this->assertEquals('2001-02-03 04:05:06', (string) new Horde_Date('20010203040506'));
        $this->assertEquals('2001-02-03 04:05:06', (string) new Horde_Date('20010203T040506Z'));
        $this->assertEquals('2001-02-03 04:05:06', (string) new Horde_Date('2001-02-03 04:05:06'));
        $this->assertEquals('2001-02-03 04:05:06', (string) new Horde_Date(981169506));
        $date = new Horde_Date('2011-11-08 14:54:00 +0000');
        $date->setTimezone('UTC');
        $this->assertEquals('2011-11-08 14:54:00', (string) $date);

        $date = new Horde_Date('20010203T040506Z');
        $this->assertEquals('UTC', $date->timezone);
        $date->setTimezone('America/New_York');
        $newDate = new Horde_Date($date);
        $this->assertEquals('America/New_York', $newDate->timezone);
        $newDate->setTimezone('UTC');
        $this->assertEquals('2001-02-03 04:05:06', (string) $newDate);

        /* Test creating Horde_Date from DateTime with timezone explicitly set */
        $dt = new DateTime('2011-12-10T04:05:06', new DateTimeZone('Europe/Berlin'));
        $dt->setTimezone(new DateTimeZone('UTC'));
        $date = new Horde_Date($dt);
        $this->assertEquals('2011-12-10 03:05:06', (string) $date);

        $dti = new DateTimeImmutable('2026-06-23 12:00:00', new DateTimeZone('UTC'));
        $date = new Horde_Date($dti);
        $this->assertEquals(2026, $date->year);
        $this->assertEquals(6, $date->month);
        $this->assertEquals(23, $date->mday);

        $parsed = Format::parseDateTime(
            '23.06.2026 12:00',
            '%d.%m.%Y',
            'HH:mm',
            'de_DE'
        );
        $date = new Horde_Date($parsed->toDateTimeImmutable());
        $this->assertEquals(2026, $date->year);
        $this->assertEquals(6, $date->month);
        $this->assertEquals(23, $date->mday);
        $this->assertGreaterThan(0, $date->timestamp());

        // Test creating Horde_Date from a string that will use DateTime
        // internally to parse the date.
        $date = new Horde_Date('2014-03-20 5:00PM');
        $this->assertEquals('2014-03-20 17:00:00', (string) $date);
        $this->assertEquals('Europe/Berlin', $date->timezone);

        $date = new Horde_Date('2014-03-20 5:00PM', 'America/New_York');
        $this->assertEquals('2014-03-20 17:00:00', (string) $date);
        $this->assertEquals('America/New_York', $date->timezone);
    }

    /**
     * Test creating a Horde_Date object representing the transition time
     * from DST to Standard Time
     *
     */
    public function testTZChangeDuringTransition()
    {
        // Standardize tz
        $oldtz = date_default_timezone_get();
        date_default_timezone_set('America/New_York');

        // This is a transition for America/New_York from EDST -> EST as
        // returned from DateTimeZone::getTransitions()
        // This one fails
        // $date = new Horde_Date('2011-11-06T06:00:00+0000');
        // $date->setTimezone('UTC');
        // $this->assertEquals('2011-11-06 06:00:00', (string)$date);

        // Even adjusting the minutes so the time is after the transition
        // doesn't help
        // $date = new Horde_Date('2011-11-06T06:10:00+0000');
        // $date->setTimezone('UTC');
        // $this->assertEquals('2011-11-06 06:10:00', (string)$date);

        // Once we pass the actual hour, it works
        $date = new Horde_Date('2011-11-06T07:00:00+0000');
        $date->setTimezone('UTC');
        $this->assertEquals('2011-11-06 07:00:00', (string) $date);

        // This one works
        $date = new Horde_Date('2011-03-13T07:00:00+0000');
        $date->setTimezone('UTC');
        $this->assertEquals('2011-03-13 07:00:00', (string) $date);

        date_default_timezone_set($oldtz);
    }

    public function testDateCorrection()
    {
        $d = new Horde_Date('2008-01-01 00:00:00');
        $d->month -= 2;
        $this->assertEquals(2007, $d->year);

        $d = new Horde_Date('2008-01-01 00:00:00');
        $d->day -= 1;
        $this->assertEquals(2007, $d->year);
        $this->assertEquals(12, $d->month);

        $d = new Horde_Date('2008-01-01 00:00:00');
        $d->day += 370;
        $this->assertEquals(2009, $d->year);
        $this->assertEquals(1, $d->month);
        $d->day += 10000000;
        $this->assertEquals(29388, $d->year);
        $this->assertEquals(1, $d->month);

        $d = new Horde_Date('2008-01-01 00:00:00');
        $d->sec += 14400;
        $this->assertEquals(0, $d->sec);
        $this->assertEquals(0, $d->min);
        $this->assertEquals(4, $d->hour);

        $d = new Horde_Date('2011-03-31 00:00:00');
        $d->month += 1;
        $this->assertEquals(5, $d->month);
        $this->assertEquals(1, $d->day);

        $d = new Horde_Date('2011-03-31 00:00:00');
        $d->month -= 1;
        $this->assertEquals(2, $d->month);
        $this->assertEquals(28, $d->day);

        $d = new Horde_Date('2011-02-28 00:00:00');
        $d->day += 1;
        $this->assertEquals(3, $d->month);
        $this->assertEquals(1, $d->day);

        $d = new Horde_Date('2011-03-01 00:00:00');
        $d->day -= 1;
        $this->assertEquals(2, $d->month);
        $this->assertEquals(28, $d->day);
    }

    public function testSettingDatePropertiesFromEmptyDateObject()
    {
        $d = new Horde_Date();
        $d->year = 2013;
        $d->month = 12;
        $d->mday = 20;
        $this->assertEquals(12, $d->month);
        $this->assertEquals(20, $d->mday);

        $d = new Horde_Date();
        $d->year = 2013;
        $d->mday = 1;
        $d->month = 12;
        $this->assertEquals(12, $d->month);
        $this->assertEquals(1, $d->mday);

        $d = new Horde_Date();
        $d->mday = 1;
        $d->month = 12;
        $d->year = 2013;
        $this->assertEquals(12, $d->month);
        $this->assertEquals(1, $d->mday);
        $this->assertEquals(2013, $d->year);
    }

    public function testTimestamp()
    {
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set('America/New_York');

        $date = new Horde_Date(['mday' => 1, 'month' => 10, 'year' => 2004]);
        $this->assertEquals('1096603200', $date->timestamp());
        $this->assertEquals('1096603200', mktime(0, 0, 0, $date->month, $date->mday, $date->year));

        $date = new Horde_Date(['mday' => 1, 'month' => 5, 'year' => 1948]);
        $this->assertEquals('-683841600', $date->timestamp());
        $this->assertEquals('-683841600', mktime(0, 0, 0, $date->month, $date->mday, $date->year));

        date_default_timezone_set($oldTimezone);
    }

    public function testStrftime()
    {
        $date = new Horde_Date('2001-02-03 16:05:06');

        // The deprecated strftime() method now delegates to Format::formatDate()
        $format = '%d %H:%M:%S %Y';
        $this->assertEquals(
            Format::formatDate($date->timestamp(), $format),
            $date->strftime($format)
        );

        $format = '%y-%m-%d';
        $this->assertEquals(
            Format::formatDate($date->timestamp(), $format),
            $date->strftime($format)
        );

        $date->year = 1899;
        $format = '%d %H %I %m %M %S %y %Y %%';
        $this->assertEquals(
            Format::formatDate($date->timestamp(), $format),
            $date->strftime($format)
        );
    }

    public function testStrftimeDe()
    {
        $date = new Horde_Date('2001-02-03 16:05:06');
        $format = '%d.%m.%Y %H:%M';
        $this->assertEquals(
            Format::formatDate($date->timestamp(), $format, 'de_DE'),
            $date->strftime($format)
        );
    }

    public function testStrftimeCs()
    {
        $date = new Horde_Date('2001-02-03 16:05:06');
        $format = '%d.%m.%Y';
        $this->assertEquals(
            Format::formatDate($date->timestamp(), $format, 'cs_CZ'),
            $date->strftime($format)
        );
    }

    public function testStrftimeUnsupported()
    {
        $date = new Horde_Date('2001-02-03 16:05:06');

        // %a (abbreviated day name) is handled by Format::formatDate() via ICU
        $this->assertEquals(
            Format::formatDate($date->timestamp(), '%a'),
            $date->strftime('%a')
        );
    }

    public function testGetTimezoneAlias()
    {
        $this->assertEquals(
            'Europe/Berlin',
            Horde_Date::getTimezoneAlias('W. Europe Standard Time')
        );
        $this->assertEquals(
            'Europe/Berlin',
            Horde_Date::getTimezoneAlias('W. Europe')
        );
        $this->assertEquals(
            'Europe/Berlin',
            Horde_Date::getTimezoneAlias('CET')
        );
        $this->assertEquals(
            'UTC',
            Horde_Date::getTimezoneAlias('UTC')
        );
    }

    public function testSetTimezone()
    {
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set('America/New_York');

        $date = new Horde_Date('20010203040506');
        $this->assertEquals('2001-02-03 04:05:06', (string) $date);

        $date->setTimezone('Europe/Berlin');
        $this->assertEquals('2001-02-03 10:05:06', (string) $date);

        $date = new Horde_Date('20010203040506', 'UTC');
        $this->assertEquals('2001-02-03 04:05:06', (string) $date);

        $date->setTimezone('Europe/Berlin');
        $this->assertEquals('2001-02-03 05:05:06', (string) $date);

        $date->setTimezone('W. Europe');
        $this->assertEquals('2001-02-03 05:05:06', (string) $date);

        $date->setTimezone('CET');
        $this->assertEquals('2001-02-03 05:05:06', (string) $date);

        $date = new Horde_Date('20010203040506', 'CET');
        $this->assertEquals('2001-02-03 04:05:06', (string) $date);
        $date->setTimezone('Europe/Berlin');
        $this->assertEquals('2001-02-03 04:05:06', (string) $date);

        date_default_timezone_set($oldTimezone);
    }

    public function testDateMath()
    {
        $d = new Horde_Date('2008-01-01 00:00:00');

        $this->assertEquals('2007-12-31 00:00:00', (string) $d->sub(['day' => 1]));
        $this->assertEquals('2009-01-01 00:00:00', (string) $d->add(['year' => 1]));
        $this->assertEquals('2008-01-01 04:00:00', (string) $d->add(14400));

        $span = new Horde_Date_Span('2006-01-01 00:00:00', '2006-08-16 00:00:00');
        $this->assertEquals('2006-04-24 11:30:00', (string) $span->begin->add($span->width() / 2));
    }

    public function testSetNthWeekday()
    {
        $date = new Horde_Date('2004-10-01');

        $date->setNthWeekday(Horde_Date::DATE_SATURDAY);
        $this->assertEquals(2, $date->mday);

        $date->setNthWeekday(Horde_Date::DATE_SATURDAY, 2);
        $this->assertEquals(9, $date->mday);

        $date = new Horde_Date('2007-04-01');
        $date->setNthWeekday(Horde_Date::DATE_THURSDAY);
        $this->assertEquals(5, $date->mday);
    }

    public function testToiCalendar()
    {
        $test = new Horde_Date('20100101130000');
        $this->assertEquals('20100101T130000', $test->toiCalendar(true));
        $this->assertEquals('20100101T120000Z', $test->toiCalendar(false));

        $test = new Horde_Date('20100101130000', 'America/Argentina/Buenos_Aires');
        $this->assertEquals('20100101T130000', $test->toiCalendar(true));
        $this->assertEquals('20100101T160000Z', $test->toiCalendar(false));
    }

    public function testBug12843()
    {
        $date = new Horde_Date(1384880400, 'Europe/Berlin');
        $this->assertEquals(18, $date->hour);

        date_default_timezone_set('America/New_York');
        $date = new Horde_Date(1384880400, 'Europe/Berlin');
        $this->assertEquals(18, $date->hour);
    }

    /**
     * Verifies float values passed in constructor arrays are converted to int.
     *
     * Why: On PHP 8.3+, gregoriantojd() requires strict int arguments. If input data
     * carries floats (for example 19.5 or 2026.0), toDays() can raise TypeError.
     *
     * How this can happen in production:
     * - JSON deserialization where numeric type intent is lost
     * - Database columns typed as FLOAT/DOUBLE
     * - Arithmetic done earlier in the flow
     * - Recurrence calculations that pass float values
     *
     * Expected behavior:
     * - Values are truncated toward zero, not rounded (19.5 to 19, -19.5 to -19)
     * - Day/month/year components remain integer-only by definition
     * - Time precision belongs in hour/min/sec, not fractional date components
     *
     * Test data meaning:
     * - 2026.0: whole-number float common from JSON/DB
     * - 3.0: month as float to cover all date components
     * - 19.5: fractional day from the reported Kronolith case
     * - 10.0/30.0/45.0: time components as floats
     *
     * @see https://github.com/horde/Date/issues/5 gregoriantojd() TypeError
     */
    public function testFloatValuesInConstructorArrayAreConvertedToInt()
    {
        // Constructor should accept float input and normalize it.
        $date = new Horde_Date([
            'year' => 2026.0,
            'month' => 3.0,
            'mday' => 19.5,  // Float that will be truncated to 19
            'hour' => 10.0,
            'min' => 30.0,
            'sec' => 45.0,
        ]);

        // Values should be truncated to integers, not rounded.
        $this->assertSame(2026, $date->year);
        $this->assertSame(3, $date->month);
        $this->assertSame(19, $date->mday);  // 19.5 to 19 (truncated, not 20)
        $this->assertSame(10, $date->hour);
        $this->assertSame(30, $date->min);
        $this->assertSame(45, $date->sec);

        // toDays() is where the original TypeError surfaced.
        $days = $date->toDays();
        $this->assertIsInt($days, 'toDays() must return int for gregoriantojd() compat');

        // March 19, 2026 maps to Julian Day Number 2461119.
        $this->assertSame(2461119, $days);
    }

    /**
     * Verifies float values assigned through property setters are converted to int.
     *
     * Why: __set() (Horde/Date.php line 807) previously did not cast to int, while
     * _initializeFromArray() already did. That inconsistency made array construction
     * safe but property assignment unsafe.
     *
     * How this can happen in production:
     * - $date->mday += $some_calculation where calculation returns float
     * - $date->year = $json['year'] where JSON values are numeric
     * - Legacy code relying on loose typing
     *
     * Expected behavior: property assignment should enforce the same int handling as
     * array construction.
     *
     * Test data meaning:
     * - 2027.0: whole-number float should still be handled
     * - 6.0: month as float
     * - 15.7: fractional day truncated to 15
     *
     * @see https://github.com/horde/Date/issues/5 Horde_Date.php line 807 (__set())
     */
    public function testFloatValuesInPropertySetterAreConvertedToInt()
    {
        $date = new Horde_Date('2026-03-19 10:30:45');

        // Assigning floats should normalize cleanly.
        $date->year = 2027.0;
        $date->month = 6.0;
        $date->mday = 15.7;  // Should truncate to 15, not round to 16

        $this->assertSame(2027, $date->year);
        $this->assertSame(6, $date->month);
        $this->assertSame(15, $date->mday);  // Truncated: 15.7 to 15

        // toDays() should continue to work with normalized values.
        $days = $date->toDays();
        $this->assertIsInt($days);

        // June 15, 2027 maps to Julian Day Number 2461572.
        $this->assertSame(2461572, $days);
    }

    /**
     * Reproduces the Kronolith usage pattern that triggered the original bug.
     *
     * Why: In Kronolith_Driver_Sql->listAlarms() (kronolith/lib/Driver/Sql.php,
     * lines 109-118), code computes:
     *   $diff = $event->start->diff($event->end);
     *   $end = new Horde_Date(['mday' => $next->mday + $diff, ...]);
     *
     * Scenario covered here:
     * - Original event: March 19 10:00 to March 21 15:00 (2 days)
     * - Recurrence start: April 5
     * - Expected end date: April 7
     *
     * Expected behavior:
     * - diff() returns int days as documented (@return int)
     * - int + int stays int (no float contamination)
     * - Time fields (hour/min/sec) stay independent from day arithmetic
     */
    public function testKronolithPatternWithDiffAddedToMday()
    {
        // Original event: March 19 10:00 to March 21 15:00.
        $start = new Horde_Date('2026-03-19 10:00:00');
        $end = new Horde_Date('2026-03-21 15:00:00');

        // diff() should return 2 days for this span.
        $diff = $start->diff($end);
        $this->assertIsInt($diff, 'diff() must return int per @return annotation');
        $this->assertSame(2, $diff, 'March 19 to March 21 = 2 days');

        // Apply the same duration to the recurrence start date.
        $next = new Horde_Date('2026-04-05 00:00:00');  // Recurrence start
        $endDate = new Horde_Date([
            'year' => $next->year,    // 2026
            'month' => $next->month,  // 4 (April)
            'mday' => $next->mday + $diff,  // 5 + 2 = 7
            'hour' => $end->hour,     // 15 (preserve original end time)
            'min' => $end->min,       // 0
            'sec' => $end->sec,        // 0
        ]);

        // Expected end date is two days after recurrence start.
        $this->assertSame(2026, $endDate->year);
        $this->assertSame(4, $endDate->month);
        $this->assertSame(7, $endDate->mday);  // April 5 + 2 days = April 7
        $this->assertSame(15, $endDate->hour); // Original end time preserved

        // toDays() is where this failed in production.
        $days = $endDate->toDays();
        $this->assertIsInt($days);

        // April 7, 2026 maps to Julian Day Number 2461138.
        $this->assertSame(2461138, $days);
    }

    /**
     * Verifies month rollover still works when mday arrives as a float.
     *
     * Why: Horde_Date::_correct() normalizes out-of-range values. We need to
     * ensure float inputs do not break rollover logic when day exceeds month bounds.
     *
     * Scenario: an event near month end where duration pushes mday past March.
     * Example: 29 + 5 = 34 (or 34.8 with float contamination), which should roll
     * from March (31 days) to April 3.
     *
     * Expected behavior:
     * - 34.8 is truncated and normalized correctly
     * - boundary logic remains correct after float-to-int conversion
     *
     * Test data meaning:
     * - March 29: near boundary
     * - 34.8: simulated fractional result from arithmetic
     * - 2026: non-leap year, so February behavior is deterministic
     *
     * @see Horde_Date::_correct() line 1486-1513 day normalization logic
     */
    public function testFloatMdayWithMonthRollover()
    {
        // March has 31 days; 34.8 should roll to April 3 after normalization.
        $date = new Horde_Date([
            'year' => 2026,
            'month' => 3,      // March
            'mday' => 34.8,    // Invalid: truncates to 34, rolls to April 3
            'hour' => 12,
            'min' => 0,
            'sec' => 0,
        ]);

        // _correct() should normalize this to April 3.
        $this->assertSame(2026, $date->year);
        $this->assertSame(4, $date->month);    // April (month rolled over)
        $this->assertSame(3, $date->mday);     // Day 3 (34 - 31 = 3)

        // Normalization and toDays() should both remain type-safe.
        $days = $date->toDays();
        $this->assertIsInt($days);

        // April 3, 2026 maps to Julian Day Number 2461134.
        $this->assertSame(2461134, $days);
    }

    /**
     * Verifies diff() returns integer day counts as documented.
     *
     * Why: The contract says "@return integer The absolute number of days between
     * the two dates." If internal state drifts to float, callers could receive
     * non-integer values and break date arithmetic.
     *
     * This matters for Kronolith patterns such as:
     *   $diff = $event->start->diff($event->end);
     *   'mday' => $next->mday + $diff
     *
     * Expected behavior:
     * - diff() returns int
     * - time-of-day does not affect day count
     * - diff(a, b) is symmetric with diff(b, a)
     *
     * Note: fractional-day precision belongs in another method
     * (for example diffSeconds() / 86400), not diff().
     */
    public function testDiffReturnsInteger()
    {
        $date1 = new Horde_Date('2026-03-19 10:30:00');
        $date2 = new Horde_Date('2026-03-21 15:45:00');

        $diff = $date1->diff($date2);

        // Contract requires integer return type.
        $this->assertIsInt($diff, 'diff() must return integer per @return annotation');

        // Only date components count here; hours/minutes are ignored.
        $this->assertSame(2, $diff, 'diff() returns whole days only, ignoring time components');

        // Verify symmetry: diff(a, b) == diff(b, a).
        $this->assertSame($date1->diff($date2), $date2->diff($date1));
    }

    /**
     * Covers negative-float truncation and normalization.
     *
     * Why: subtraction paths can produce negative floats before correction
     * (for example $date->min -= $alarm_minutes). We need to confirm truncation
     * and rollover are consistent for negative values.
     *
     * Expected behavior:
     * - negative floats truncate toward zero (-5.7 to -5, not -6)
     * - resulting out-of-range day values are normalized by _correct()
     *
     * Test data meaning:
     * - March 1 forces a month rollback case
     * - mday = -0.7 truncates to 0, then normalizes to previous month end
     * - 2026 is non-leap year, so previous month end is Feb 28
     */
    public function testNegativeFloatTruncation()
    {
        // Day -0.7 truncates to 0, then normalizes to Feb 28.
        $date = new Horde_Date([
            'year' => 2026,
            'month' => 3,      // March
            'mday' => -0.7,    // Truncates to 0, corrects to Feb 28
            'hour' => 0,
            'min' => 0,
            'sec' => 0,
        ]);

        // Expected normalized date: February 28, 2026.
        $this->assertSame(2026, $date->year);
        $this->assertSame(2, $date->month);    // February
        $this->assertSame(28, $date->mday);    // Feb 28 (2026 not leap year)

        // toDays() should still be type-safe.
        $days = $date->toDays();
        $this->assertIsInt($days);
    }

    /**
     * Test numeric timestamp string handling (deprecated BC support)
     *
     * DEPRECATED: SOME numeric strings are silently accepted for BC compatibility.
     * This behavior will be removed in next major version.
     * Callers should pass objects or integer timestamps instead.
     *
     * The Horde_Date constructor is the wrong place to guess what a caller means.
     *
     * @link https://github.com/horde/Date/issues/6
     * @link https://github.com/horde/ActiveSync/pull/15
     */
    public function testNumericTimestampStringDeprecated(): void
    {
        // Pre-1970 timestamp as string - accepted for BC
        $date = new Horde_Date('-631152000'); // 1950-01-01
        $this->assertEquals(1950, $date->year);
        $this->assertEquals(1, $date->month);
        $this->assertEquals(1, $date->mday);
    }

    /**
     * Test positive numeric timestamp string (deprecated BC support)
     *
     * @link https://github.com/horde/Date/issues/6
     * @link https://github.com/horde/ActiveSync/pull/15
     */
    public function testPositiveNumericTimestampStringDeprecated(): void
    {
        $date = new Horde_Date('1773944669'); // 2026-03-19 (from ActiveSync PR)
        $this->assertEquals(2026, $date->year);
        $this->assertEquals(3, $date->month);
        $this->assertEquals(19, $date->mday);
    }

    /**
     * Test that integer timestamps still work without deprecation
     *
     * @link https://github.com/horde/Date/issues/6
     */
    public function testIntegerTimestampNoDeprecation(): void
    {
        // Should NOT trigger deprecation
        $date = new Horde_Date(1773944669);
        $this->assertEquals(2026, $date->year);

        // Pre-1970 integer
        $date = new Horde_Date(-631152000);
        $this->assertEquals(1950, $date->year);
    }

    /**
     * Test YYYYMMDD string format validation
     *
     * 8-digit strings should be interpreted as YYYYMMDD when:
     * - Exactly 8 digits
     * - Year >= 1000 (legitimate year)
     * - Month 1-12
     * - Day 1-31
     *
     * This prevents ambiguous strings like "19700101" from being
     * interpreted as Unix timestamps.
     *
     * @link https://github.com/horde/Date/pull/8
     */
    public function testYYYYMMDDStringFormat(): void
    {
        // Valid YYYYMMDD strings
        $date = new Horde_Date('19700101'); // 1970-01-01
        $this->assertEquals(1970, $date->year);
        $this->assertEquals(1, $date->month);
        $this->assertEquals(1, $date->mday);

        $date = new Horde_Date('20011231'); // 2001-12-31
        $this->assertEquals(2001, $date->year);
        $this->assertEquals(12, $date->month);
        $this->assertEquals(31, $date->mday);

        $date = new Horde_Date('10000101'); // 1000-01-01 (minimum valid year)
        $this->assertEquals(1000, $date->year);
        $this->assertEquals(1, $date->month);
        $this->assertEquals(1, $date->mday);

        $date = new Horde_Date('20260320'); // 2026-03-20
        $this->assertEquals(2026, $date->year);
        $this->assertEquals(3, $date->month);
        $this->assertEquals(20, $date->mday);
    }

    /**
     * Test that 9+ digit strings are still treated as timestamps (deprecated BC)
     *
     * @link https://github.com/horde/Date/issues/6
     * @link https://github.com/horde/ActiveSync/pull/15
     */
    public function testLongNumericStringsAsTimestamps(): void
    {
        // 10-digit positive timestamp
        $date = new Horde_Date('1773944669'); // 2026-03-19
        $this->assertEquals(2026, $date->year);
        $this->assertEquals(3, $date->month);
        $this->assertEquals(19, $date->mday);

        // 10-digit negative timestamp
        $date = new Horde_Date('-631152000'); // 1950-01-01
        $this->assertEquals(1950, $date->year);
        $this->assertEquals(1, $date->month);
        $this->assertEquals(1, $date->mday);

        // 9-digit timestamp (blocks years 1970-2001 from timestamp interpretation)
        $date = new Horde_Date('946684800'); // 2000-01-01 00:00:00 UTC
        $this->assertEquals(2000, $date->year);
        $this->assertEquals(1, $date->month);
        $this->assertEquals(1, $date->mday);
    }

    /**
     * Test ambiguous 8-digit strings - YYYYMMDD vs Unix timestamp
     *
     * 8-digit numeric strings are inherently ambiguous:
     * - Could be YYYYMMDD date format (e.g., "19700101" = 1970-01-01)
     * - Could be Unix timestamp (e.g., "19700101" = 228 days after epoch)
     *
     * Resolution strategy (implemented in this PR):
     * 1. If exactly 8 digits AND year >= 1000 AND valid month (1-12) AND valid day (1-31)
     *    -> Treat as YYYYMMDD
     * 2. Otherwise -> Fall through to other parsing logic (may fail or use DateTime)
     *
     * This prevents dates between 1970-2001 from being misinterpreted as timestamps.
     *
     * @link https://github.com/horde/Date/pull/8
     */
    public function testAmbiguousEightDigitStrings(): void
    {
        // Valid YYYYMMDD with valid date components - interpreted as date
        $date = new Horde_Date('19700101'); // 1970-01-01 (NOT timestamp 19700101)
        $this->assertEquals(1970, $date->year);
        $this->assertEquals(1, $date->month);
        $this->assertEquals(1, $date->mday);
        $this->assertEquals(0, $date->hour); // Time should be 00:00:00

        $date = new Horde_Date('20011231'); // 2001-12-31
        $this->assertEquals(2001, $date->year);
        $this->assertEquals(12, $date->month);
        $this->assertEquals(31, $date->mday);

        // Edge case: Minimum valid year (1000)
        $date = new Horde_Date('10000101'); // 1000-01-01
        $this->assertEquals(1000, $date->year);
        $this->assertEquals(1, $date->month);
        $this->assertEquals(1, $date->mday);
    }

    /**
     * Test invalid YYYYMMDD patterns that should NOT be treated as dates
     *
     * These 8-digit strings don't pass YYYYMMDD validation (year < 1000) and will
     * fall through to DateTime parsing, which may interpret them differently.
     *
     * @link https://github.com/horde/Date/pull/8
     */
    public function testInvalidYYYYMMDDPatterns(): void
    {
        // Year < 1000 - doesn't pass our YYYYMMDD validation (year >= 1000)
        // "09991231" fails year >= 1000 check, falls through to DateTime
        // DateTime correctly parses it as year 999
        $date = new Horde_Date('09991231');
        $this->assertEquals(999, $date->year); // DateTime handles it correctly
        $this->assertEquals(12, $date->month);
        $this->assertEquals(31, $date->mday);

        // Invalid month (month 13)
        // "19701399" fails month <= 12 check, falls through to DateTime
        // DateTime will reject or mangle this
        try {
            $date = new Horde_Date('19701399');
            // If it doesn't throw, it was interpreted as something else
            $this->assertNotEquals(13, $date->month); // Month 13 is invalid
        } catch (Horde_Date_Exception $e) {
            // Expected: DateTime rejects invalid month
            $this->assertStringContainsString('Failed to parse', $e->getMessage());
        }

        // Invalid day (day 32)
        // "19700132" fails day <= 31 check, falls through to DateTime
        try {
            $date = new Horde_Date('19700132');
            $this->assertNotEquals(32, $date->mday); // Day 32 is invalid
        } catch (Horde_Date_Exception $e) {
            // Expected: DateTime rejects invalid day
            $this->assertStringContainsString('Failed to parse', $e->getMessage());
        }

        // Invalid month and day (month 00)
        // "19700001" fails month >= 1 check
        try {
            $date = new Horde_Date('19700001');
            $this->assertNotEquals(0, $date->month); // Month 0 is invalid
        } catch (Horde_Date_Exception $e) {
            // Expected: DateTime rejects invalid month
            $this->assertStringContainsString('Failed to parse', $e->getMessage());
        }
    }

    /**
     * Test that 12 and 14-digit datetime strings work correctly
     *
     * These formats are handled by explicit regex patterns (line 599, 609):
     * - 14 digits: YYYYMMDDHHmmss (e.g., "20010203040506")
     * - 12 digits: YYYYMMDDHHmm - NOT explicitly handled, relies on DateTime
     *
     * @link https://github.com/horde/Date/pull/8
     */
    public function testLongNumericDateTimeStrings(): void
    {
        // 14-digit format: YYYYMMDDHHmmss (explicitly handled at line 599)
        $date = new Horde_Date('20010203040506'); // 2001-02-03 04:05:06
        $this->assertEquals(2001, $date->year);
        $this->assertEquals(2, $date->month);
        $this->assertEquals(3, $date->mday);
        $this->assertEquals(4, $date->hour);
        $this->assertEquals(5, $date->min);
        $this->assertEquals(6, $date->sec);

        // 12-digit format: YYYYMMDDHHmm (NOT explicitly handled)
        // Falls through to DateTime - behavior depends on PHP version
        // This documents that 12-digit format support is NOT guaranteed
        try {
            $date = new Horde_Date('197001010130'); // 1970-01-01 01:30
            // If DateTime accepts it, these should match
            $this->assertEquals(1970, $date->year);
            $this->assertEquals(1, $date->month);
            $this->assertEquals(1, $date->mday);
            // Note: hour/minute may not be parsed correctly by DateTime
        } catch (Horde_Date_Exception $e) {
            // DateTime may reject this format - that's acceptable
            $this->assertStringContainsString('Failed to parse', $e->getMessage());
        }
    }

    /**
     * Test edge cases around the 8-digit boundary
     *
     * Documents behavior of 7-digit, 8-digit, and 9-digit numeric strings.
     *
     * @link https://github.com/horde/Date/pull/8
     */
    public function testNumericStringLengthBoundaries(): void
    {
        // 7 digits - too short for YYYYMMDD or timestamp string BC
        // Falls through to DateTime parsing
        try {
            $date = new Horde_Date('1970101'); // 7 digits
            // DateTime may parse this in unexpected ways or reject it
            $this->assertTrue(true); // Just document that it doesn't crash
        } catch (Horde_Date_Exception $e) {
            // Rejection is acceptable
            $this->assertStringContainsString('Failed to parse', $e->getMessage());
        }

        // 8 digits with valid YYYYMMDD - handled as date
        $date = new Horde_Date('19700101');
        $this->assertEquals(1970, $date->year);
        $this->assertEquals(1, $date->month);
        $this->assertEquals(1, $date->mday);

        // 9 digits - handled as Unix timestamp (deprecated BC)
        $date = new Horde_Date('946684800'); // 9 digits
        $this->assertEquals(2000, $date->year); // Timestamp interpretation

        // 11 digits - handled as Unix timestamp
        $date = new Horde_Date('17739446699'); // 11 digits (far future)
        $this->assertTrue($date->year > 2500); // Timestamp interpretation

        // 12 digits - NOT handled by timestamp BC, falls to explicit regex/DateTime
        $date = new Horde_Date('200102030405'); // 12 digits
        // Behavior depends on DateTime
        $this->assertTrue($date->year >= 1970);
    }
}
