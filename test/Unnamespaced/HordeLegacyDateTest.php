<?php

declare(strict_types=1);

namespace Horde\Date\Test;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Horde_Date;
use Horde_Date_Span;
use Horde\Date\Date;
use Horde\Date\HordeLegacyDate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

use function PHP81_BC\strftime;

#[CoversClass(HordeLegacyDate::class)]
class HordeLegacyDateTest extends TestCase
{
    // =========================================================================
    // Construction
    // =========================================================================

    public function testConstructFromString(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:45');
        $this->assertSame(2026, $d->year);
        $this->assertSame(4, $d->month);
        $this->assertSame(17, $d->mday);
        $this->assertSame(10, $d->hour);
        $this->assertSame(30, $d->min);
        $this->assertSame(45, $d->sec);
    }

    public function testConstructFromArray(): void
    {
        $d = new HordeLegacyDate(['year' => 2026, 'month' => 4, 'mday' => 17]);
        $this->assertSame(2026, $d->year);
        $this->assertSame(4, $d->month);
        $this->assertSame(17, $d->mday);
        $this->assertSame(0, $d->hour);
    }

    public function testConstructFromTimestamp(): void
    {
        $ts = mktime(10, 30, 0, 4, 17, 2026);
        $d = new HordeLegacyDate($ts);
        $this->assertSame(2026, $d->year);
        $this->assertSame(4, $d->month);
        $this->assertSame(17, $d->mday);
    }

    public function testConstructFromHordeDate(): void
    {
        $legacy = new Horde_Date('2026-04-17 10:30:00');
        $d = new HordeLegacyDate($legacy);
        $this->assertSame(2026, $d->year);
        $this->assertSame(10, $d->hour);
    }

    public function testConstructFromDateTime(): void
    {
        $dt = new DateTime('2026-04-17 10:30:00', new DateTimeZone('UTC'));
        $d = new HordeLegacyDate($dt);
        $this->assertSame(2026, $d->year);
        $this->assertSame('UTC', $d->timezone);
    }

    public function testConstructWithTimezone(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:00', 'America/New_York');
        $this->assertSame('America/New_York', $d->timezone);
        $this->assertSame(10, $d->hour);
    }

    public function testConstructFromYYYYMMDD(): void
    {
        $d = new HordeLegacyDate('20260417');
        $this->assertSame(2026, $d->year);
        $this->assertSame(4, $d->month);
        $this->assertSame(17, $d->mday);
    }

    public function testConstructNull(): void
    {
        $d = new HordeLegacyDate(null, 'UTC');
        $this->assertSame('UTC', $d->timezone);
        // Matches legacy behavior: uninitialized fields format as -0001-11-30
        $legacy = new Horde_Date(null, 'UTC');
        $this->assertSame((string) $legacy, (string) $d);
    }

    // =========================================================================
    // Magic properties: __get, __set, __isset
    // =========================================================================

    public function testDayAlias(): void
    {
        $d = new HordeLegacyDate('2026-04-17');
        $this->assertSame(17, $d->day);
        $d->day = 20;
        $this->assertSame(20, $d->mday);
    }

    public function testIsset(): void
    {
        $d = new HordeLegacyDate('2026-04-17');
        $this->assertTrue(isset($d->year));
        $this->assertTrue(isset($d->month));
        $this->assertTrue(isset($d->mday));
        $this->assertTrue(isset($d->day));
        $this->assertTrue(isset($d->hour));
        $this->assertTrue(isset($d->min));
        $this->assertTrue(isset($d->sec));
        $this->assertFalse(isset($d->foo));
    }

    public function testGetUnknownProperty(): void
    {
        $d = new HordeLegacyDate('2026-04-17');
        $this->assertNull($d->nonexistent);
    }

    public function testSetInvalidProperty(): void
    {
        $d = new HordeLegacyDate('2026-04-17');
        $this->expectException(InvalidArgumentException::class);
        $d->foo = 42;
    }

    // =========================================================================
    // Overflow normalization (replaces _correct with PHP date math)
    // =========================================================================

    public function testSecondOverflowPositive(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:00');
        $d->sec = 90;
        $this->assertSame(10, $d->hour);
        $this->assertSame(31, $d->min);
        $this->assertSame(30, $d->sec);
    }

    public function testSecondOverflowNegative(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:30');
        $d->sec = -30;
        $this->assertSame(29, $d->min);
        $this->assertSame(30, $d->sec);
    }

    public function testMinuteOverflow(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:00');
        $d->min = 90;
        $this->assertSame(11, $d->hour);
        $this->assertSame(30, $d->min);
    }

    public function testHourOverflow(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:00');
        $d->hour = 25;
        $this->assertSame(18, $d->mday);
        $this->assertSame(1, $d->hour);
        $this->assertSame(30, $d->min);
    }

    public function testHourUnderflow(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:00');
        $d->hour = -1;
        $this->assertSame(16, $d->mday);
        $this->assertSame(23, $d->hour);
        $this->assertSame(30, $d->min);
    }

    public function testDayOverflowEndOfMonth(): void
    {
        $d = new HordeLegacyDate(['year' => 2026, 'month' => 4, 'mday' => 1]);
        $d->mday = 31;
        $this->assertSame(5, $d->month);
        $this->assertSame(1, $d->mday);
    }

    public function testDayOverflowFebruary(): void
    {
        $d = new HordeLegacyDate(['year' => 2025, 'month' => 2, 'mday' => 1]);
        $d->mday = 29;
        $this->assertSame(3, $d->month);
        $this->assertSame(1, $d->mday);
    }

    public function testDayOverflowFebruaryLeap(): void
    {
        $d = new HordeLegacyDate(['year' => 2024, 'month' => 2, 'mday' => 1]);
        $d->mday = 29;
        $this->assertSame(2, $d->month);
        $this->assertSame(29, $d->mday);
    }

    public function testDayUnderflow(): void
    {
        $d = new HordeLegacyDate(['year' => 2026, 'month' => 4, 'mday' => 1]);
        $d->mday = 0;
        $this->assertSame(3, $d->month);
        $this->assertSame(31, $d->mday);
    }

    public function testMonthOverflow(): void
    {
        $d = new HordeLegacyDate(['year' => 2026, 'month' => 1, 'mday' => 15]);
        $d->month = 13;
        $this->assertSame(2027, $d->year);
        $this->assertSame(1, $d->month);
    }

    public function testMonthUnderflow(): void
    {
        $d = new HordeLegacyDate(['year' => 2026, 'month' => 3, 'mday' => 15]);
        $d->month = -1;
        $this->assertSame(2025, $d->year);
        $this->assertSame(11, $d->month);
    }

    public function testMonthSetToZero(): void
    {
        $d = new HordeLegacyDate(['year' => 2026, 'month' => 6, 'mday' => 15]);
        $d->month = 0;
        $this->assertSame(2025, $d->year);
        $this->assertSame(12, $d->month);
    }

    // =========================================================================
    // Timezone: __set vs setTimezone
    // =========================================================================

    public function testTimezonePropertyReinterprets(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:00:00', 'UTC');
        $d->timezone = 'America/New_York';
        $this->assertSame(10, $d->hour);
        $this->assertSame('America/New_York', $d->timezone);
    }

    public function testSetTimezoneConverts(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:00:00', 'UTC');
        $d->setTimezone('America/New_York');
        $this->assertSame(6, $d->hour);
        $this->assertSame('America/New_York', $d->timezone);
    }

    public function testSetTimezoneReturnsThis(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:00:00', 'UTC');
        $result = $d->setTimezone('Europe/Berlin');
        $this->assertSame($d, $result);
    }

    public function testTimezoneAlias(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:00:00', 'Eastern Standard Time');
        $this->assertSame('America/New_York', $d->timezone);
    }

    // =========================================================================
    // Arithmetic: add/sub
    // =========================================================================

    public function testAddSeconds(): void
    {
        $d = new HordeLegacyDate('2026-12-31 23:59:59');
        $r = $d->add(1);
        $this->assertInstanceOf(HordeLegacyDate::class, $r);
        $this->assertSame(2027, $r->year);
        $this->assertSame(1, $r->month);
        $this->assertSame(1, $r->mday);
        $this->assertSame(0, $r->hour);
        $this->assertSame(0, $r->min);
        $this->assertSame(0, $r->sec);
    }

    public function testSubSeconds(): void
    {
        $d = new HordeLegacyDate('2026-01-01 00:00:00');
        $r = $d->sub(1);
        $this->assertSame(2025, $r->year);
        $this->assertSame(12, $r->month);
        $this->assertSame(31, $r->mday);
        $this->assertSame(23, $r->hour);
        $this->assertSame(59, $r->min);
        $this->assertSame(59, $r->sec);
    }

    public function testAddMonthArray(): void
    {
        $d = new HordeLegacyDate('2026-01-31 12:00:00');
        $r = $d->add(['month' => 1]);
        $this->assertSame(2026, $r->year);
        $this->assertSame(3, $r->month);
        $this->assertSame(3, $r->mday);
    }

    public function testSubMonthArray(): void
    {
        $d = new HordeLegacyDate('2026-03-31 12:00:00');
        $r = $d->sub(['month' => 1]);
        // PHP date math: setDate(2026, 2, 31) overflows to Mar 3
        // (differs from legacy _correct which clamped to Feb 28)
        $this->assertSame(2026, $r->year);
        $this->assertSame(3, $r->month);
        $this->assertSame(3, $r->mday);
    }

    public function testAddMultipleParts(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:00:00');
        $r = $d->add(['year' => 1, 'month' => 2, 'mday' => 5]);
        $this->assertSame(2027, $r->year);
        $this->assertSame(6, $r->month);
        $this->assertSame(22, $r->mday);
    }

    public function testAddDoesNotMutateOriginal(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:00:00');
        $d->add(3600);
        $this->assertSame(10, $d->hour);
    }

    public function testAddManyDays(): void
    {
        $d = new HordeLegacyDate('2026-01-01 00:00:00');
        $r = $d->add(['mday' => 365]);
        $this->assertSame(2027, $r->year);
        $this->assertSame(1, $r->month);
        $this->assertSame(1, $r->mday);
    }

    // =========================================================================
    // Calendar calculations
    // =========================================================================

    public function testToDaysFromDaysRoundTrip(): void
    {
        $d = new HordeLegacyDate('2026-04-17');
        $days = $d->toDays();
        $back = HordeLegacyDate::fromDays($days);
        $this->assertInstanceOf(HordeLegacyDate::class, $back);
        $this->assertSame(2026, $back->year);
        $this->assertSame(4, $back->month);
        $this->assertSame(17, $back->mday);
    }

    #[DataProvider('dayOfWeekProvider')]
    public function testDayOfWeek(string $date, int $expected): void
    {
        $d = new HordeLegacyDate($date);
        $this->assertSame($expected, $d->dayOfWeek());
    }

    public static function dayOfWeekProvider(): array
    {
        return [
            ['2026-04-12', Horde_Date::DATE_SUNDAY],
            ['2026-04-13', Horde_Date::DATE_MONDAY],
            ['2026-04-14', Horde_Date::DATE_TUESDAY],
            ['2026-04-15', Horde_Date::DATE_WEDNESDAY],
            ['2026-04-16', Horde_Date::DATE_THURSDAY],
            ['2026-04-17', Horde_Date::DATE_FRIDAY],
            ['2026-04-18', Horde_Date::DATE_SATURDAY],
        ];
    }

    public function testDayOfYear(): void
    {
        $d = new HordeLegacyDate('2026-01-01');
        $this->assertSame(1, $d->dayOfYear());

        $d2 = new HordeLegacyDate('2026-12-31');
        $this->assertSame(365, $d2->dayOfYear());
    }

    public function testWeekOfMonth(): void
    {
        $d = new HordeLegacyDate('2026-04-01');
        $this->assertSame(1, $d->weekOfMonth());

        $d2 = new HordeLegacyDate('2026-04-28');
        $this->assertSame(4, $d2->weekOfMonth());
    }

    public function testWeekOfYear(): void
    {
        $d = new HordeLegacyDate('2026-01-05');
        $this->assertSame(2, $d->weekOfYear());
    }

    public function testWeeksInYear(): void
    {
        $this->assertSame(53, HordeLegacyDate::weeksInYear(2004));
        $this->assertSame(53, HordeLegacyDate::weeksInYear(2026));
    }

    public function testSetNthWeekday(): void
    {
        $d = new HordeLegacyDate('2026-04-01');
        $d->setNthWeekday(Horde_Date::DATE_SATURDAY);
        $this->assertSame(4, $d->mday);

        $d2 = new HordeLegacyDate('2026-04-01');
        $d2->setNthWeekday(Horde_Date::DATE_SATURDAY, 2);
        $this->assertSame(11, $d2->mday);
    }

    public function testSetNthWeekdayNegative(): void
    {
        $d = new HordeLegacyDate('2026-04-15');
        $d->setNthWeekday(Horde_Date::DATE_FRIDAY, -1);
        $this->assertSame(24, $d->mday);
    }

    public function testIsValid(): void
    {
        $d = new HordeLegacyDate('2026-04-17');
        $this->assertTrue($d->isValid());
    }

    public function testDiff(): void
    {
        $a = new HordeLegacyDate('2026-01-01');
        $b = new HordeLegacyDate('2026-01-11');
        $this->assertSame(10, $a->diff($b));
        $this->assertSame(10, $b->diff($a));
    }

    // =========================================================================
    // Comparison
    // =========================================================================

    public function testCompareDate(): void
    {
        $a = new HordeLegacyDate('2026-04-17 10:00:00');
        $b = new HordeLegacyDate('2026-04-18 10:00:00');
        $this->assertLessThan(0, $a->compareDate($b));
        $this->assertGreaterThan(0, $b->compareDate($a));

        $c = new HordeLegacyDate('2026-04-17 23:00:00');
        $this->assertSame(0, $a->compareDate($c));
    }

    public function testCompareTime(): void
    {
        $a = new HordeLegacyDate('2026-04-17 10:00:00');
        $b = new HordeLegacyDate('2026-04-17 11:00:00');
        $this->assertLessThan(0, $a->compareTime($b));
    }

    public function testCompareDateTime(): void
    {
        $a = new HordeLegacyDate('2026-04-17 10:00:00');
        $b = new HordeLegacyDate('2026-04-17 10:00:01');
        $this->assertLessThan(0, $a->compareDateTime($b));
    }

    public function testBeforeAfterEquals(): void
    {
        $a = new HordeLegacyDate('2026-04-17');
        $b = new HordeLegacyDate('2026-04-18');
        $this->assertTrue($a->before($b));
        $this->assertTrue($b->after($a));
        $this->assertTrue($a->equals(new HordeLegacyDate('2026-04-17')));
    }

    public function testCompareWithLegacyDate(): void
    {
        $a = new HordeLegacyDate('2026-04-17 10:00:00');
        $b = new Horde_Date('2026-04-17 10:00:00');
        $this->assertSame(0, $a->compareDateTime($b));
    }

    // =========================================================================
    // Conversion methods
    // =========================================================================

    public function testToDateTime(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:00', 'UTC');
        $dt = $d->toDateTime();
        $this->assertInstanceOf(DateTime::class, $dt);
        $this->assertSame('2026-04-17 10:30:00', $dt->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $dt->getTimezone()->getName());
    }

    public function testToDateTimeImmutable(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:00', 'UTC');
        $dti = $d->toDateTimeImmutable();
        $this->assertInstanceOf(DateTimeImmutable::class, $dti);
        $this->assertSame('2026-04-17 10:30:00', $dti->format('Y-m-d H:i:s'));
    }

    public function testToDate(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:00', 'UTC');
        $date = $d->toDate();
        $this->assertInstanceOf(Date::class, $date);
        $this->assertSame('2026-04-17 10:30:00', $date->format('Y-m-d H:i:s'));
    }

    public function testGetTimezone(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:00', 'Europe/Berlin');
        $tz = $d->getTimezone();
        $this->assertInstanceOf(DateTimeZone::class, $tz);
        $this->assertSame('Europe/Berlin', $tz->getName());
    }

    // =========================================================================
    // Timestamps & serialization
    // =========================================================================

    public function testTimestamp(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:00:00', 'UTC');
        $this->assertSame(
            mktime(10, 0, 0, 4, 17, 2026),
            $d->timestamp(),
        );
    }

    public function testDatestamp(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:00', 'UTC');
        $expected = mktime(0, 0, 0, 4, 17, 2026);
        $this->assertSame($expected, $d->datestamp());
    }

    public function testDateString(): void
    {
        $d = new HordeLegacyDate('2026-04-17');
        $this->assertSame('20260417', $d->dateString());
    }

    public function testToJson(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:00');
        $this->assertSame('2026-04-17T10:30:00', $d->toJson());
    }

    public function testToiCalendarFloating(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:00');
        $this->assertSame('20260417T103000', $d->toiCalendar(true));
    }

    public function testToiCalendarUTC(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:00', 'UTC');
        $this->assertSame('20260417T103000Z', $d->toiCalendar(false));
    }

    public function testTzOffset(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:00:00', 'UTC');
        $this->assertSame('+00:00', $d->tzOffset(true));
        $this->assertSame('+0000', $d->tzOffset(false));
    }

    // =========================================================================
    // Formatting
    // =========================================================================

    public function testFormatBasic(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:45');
        $this->assertSame('2026', $d->format('Y'));
        $this->assertSame('04', $d->format('m'));
        $this->assertSame('17', $d->format('d'));
        $this->assertSame('10:30:45', $d->format('H:i:s'));
    }

    public function testFormatWithDateTimeFormatter(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:00', 'UTC');
        $result = $d->format('Y-m-d', \Horde\Date\Formatter\DateTimeFormatter::class);
        $this->assertSame('2026-04-17', $result);
    }

    public function testToString(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:00');
        $this->assertSame('2026-04-17 10:30:00', (string) $d);
    }

    public function testSetDefaultFormat(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:00');
        $d->setDefaultFormat('d/m/Y');
        $this->assertSame('17/04/2026', (string) $d);
    }

    // =========================================================================
    // strftime
    // =========================================================================

    public function testStrftimeBasic(): void
    {
        $d = new HordeLegacyDate('2026-04-17 10:30:45');
        $this->assertSame('2026', $d->strftime('%Y'));
        $this->assertSame('04', $d->strftime('%m'));
        $this->assertSame('17', $d->strftime('%d'));
        $this->assertSame('10', $d->strftime('%H'));
        $this->assertSame('30', $d->strftime('%M'));
        $this->assertSame('45', $d->strftime('%S'));
    }

    public function testStrftimeComposite(): void
    {
        $d = new HordeLegacyDate('2026-04-17 14:30:00');
        $this->assertSame('14:30', $d->strftime('%R'));
        $this->assertSame('14:30:00', $d->strftime('%T'));
    }

    public function testStrftime12Hour(): void
    {
        $d = new HordeLegacyDate('2026-04-17 14:00:00');
        $this->assertSame('02', $d->strftime('%I'));

        $d2 = new HordeLegacyDate('2026-04-17 00:00:00');
        $this->assertSame('12', $d2->strftime('%I'));
    }

    // =========================================================================
    // Clone behavior
    // =========================================================================

    public function testCloneIsIndependent(): void
    {
        $a = new HordeLegacyDate('2026-04-17 10:00:00');
        $b = clone $a;
        $b->hour = 20;
        $this->assertSame(10, $a->hour);
        $this->assertSame(20, $b->hour);
    }

    // =========================================================================
    // Consistency with Horde_Date (excluding known divergences)
    // =========================================================================

    #[DataProvider('overflowConsistencyProvider')]
    public function testOverflowConsistency(
        string $initial,
        string $property,
        int $value,
    ): void {
        $legacy = new Horde_Date($initial);
        $modern = new HordeLegacyDate($initial);

        $legacy->$property = $value;
        $modern->$property = $value;

        $this->assertSame(
            $legacy->format('Y-m-d H:i:s'),
            $modern->format('Y-m-d H:i:s'),
            "Divergence on $property=$value from $initial",
        );
    }

    public static function overflowConsistencyProvider(): array
    {
        return [
            'sec +90' => ['2026-04-17 10:30:00', 'sec', 90],
            'sec -30' => ['2026-04-17 10:30:30', 'sec', -30],
            'min +90' => ['2026-04-17 10:30:00', 'min', 90],
            'min -5' => ['2026-04-17 10:30:00', 'min', -5],
            'hour +25' => ['2026-04-17 10:30:00', 'hour', 25],
            'hour -1' => ['2026-04-17 10:30:00', 'hour', -1],
            'day +32 apr' => ['2026-04-01 00:00:00', 'mday', 32],
            'day 0' => ['2026-04-01 00:00:00', 'mday', 0],
            'day -27' => ['2026-03-01 00:00:00', 'mday', -27],
            'month +13' => ['2026-01-15 00:00:00', 'month', 13],
            'month -1' => ['2026-03-15 00:00:00', 'month', -1],
            'month 25' => ['2026-01-15 00:00:00', 'month', 25],
            'month 0' => ['2026-06-15 00:00:00', 'month', 0],
            'feb 29 non-leap' => ['2025-02-01 00:00:00', 'mday', 29],
            'feb 29 leap' => ['2024-02-01 00:00:00', 'mday', 29],
            'day +400' => ['2026-01-15 00:00:00', 'mday', 400],
        ];
    }

    // =========================================================================
    // Full cascade: add/sub consistency
    // =========================================================================

    public function testFullCascadeViaAdd(): void
    {
        $d = new HordeLegacyDate('2026-04-17 23:59:59');
        $r = $d->add(1);
        $this->assertSame(2026, $r->year);
        $this->assertSame(4, $r->month);
        $this->assertSame(18, $r->mday);
        $this->assertSame(0, $r->hour);
        $this->assertSame(0, $r->min);
        $this->assertSame(0, $r->sec);
    }

    public function testFullCascadeYearBoundary(): void
    {
        $d = new HordeLegacyDate('2026-12-31 23:59:59');
        $r = $d->add(1);
        $this->assertSame(2027, $r->year);
        $this->assertSame(1, $r->month);
        $this->assertSame(1, $r->mday);
    }

    // =========================================================================
    // Leap year edge cases
    // =========================================================================

    public function testAddYearFromFeb29(): void
    {
        $d = new HordeLegacyDate('2024-02-29 12:00:00');
        $r = $d->add(['year' => 1]);
        $this->assertSame(2025, $r->year);
        $this->assertSame(3, $r->month);
        $this->assertSame(1, $r->mday);
    }

    public function testAddMonthFromJan31(): void
    {
        $d = new HordeLegacyDate('2026-01-31 12:00:00');
        $r = $d->add(['month' => 1]);
        $this->assertSame(3, $r->month);
        $this->assertSame(3, $r->mday);
    }

    // =========================================================================
    // Float handling (from DateTest)
    // =========================================================================

    public function testFloatInConstructorArray(): void
    {
        $d = new HordeLegacyDate([
            'year' => 2026.7,
            'month' => 4.9,
            'mday' => 17.3,
            'hour' => 10.5,
            'min' => 30.9,
            'sec' => 45.1,
        ]);
        $this->assertSame(2026, $d->year);
        $this->assertSame(4, $d->month);
        $this->assertSame(17, $d->mday);
    }

    public function testFloatInPropertySetter(): void
    {
        $d = new HordeLegacyDate('2026-04-17');
        $d->month = 6.7;
        $this->assertSame(6, $d->month);
    }
}
