<?php

declare(strict_types=1);

namespace Horde\Date\Test\Unit;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Horde\Date\Date;
use Horde\Date\DateInterface;
use Horde\Date\Formatter\DateTimeFormatter;
use Horde\Date\Formatter\IcuFormatter;
use Horde_Date;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Date::class)]
class DateTest extends TestCase
{
    // =========================================================================
    // Construction
    // =========================================================================

    public function testCreateFromString(): void
    {
        $d = new Date('2026-04-17 14:30:00');
        $this->assertSame('2026-04-17 14:30:00', $d->format('Y-m-d H:i:s'));
    }

    public function testCreateFromInterface(): void
    {
        $dt = new DateTime('2026-04-17 14:30:00', new DateTimeZone('UTC'));
        $d = Date::createFromInterface($dt);
        $this->assertInstanceOf(Date::class, $d);
        $this->assertSame('2026-04-17 14:30:00', $d->format('Y-m-d H:i:s'));

        $dti = new DateTimeImmutable('2026-04-17 14:30:00', new DateTimeZone('UTC'));
        $d2 = Date::createFromInterface($dti);
        $this->assertInstanceOf(Date::class, $d2);
        $this->assertSame('2026-04-17 14:30:00', $d2->format('Y-m-d H:i:s'));
    }

    public function testCreateFromInterfacePreservesTimezone(): void
    {
        $dt = new DateTime('2026-04-17 14:30:00', new DateTimeZone('America/New_York'));
        $d = Date::createFromInterface($dt);
        $this->assertSame('America/New_York', $d->getTimezone()->getName());
    }

    public function testCreateFromTimestamp(): void
    {
        $ts = 1776700200;
        $d = Date::createFromTimestamp($ts);
        $this->assertInstanceOf(Date::class, $d);
        $this->assertSame($ts, $d->getTimestamp());
    }

    public function testImplementsInterfaces(): void
    {
        $d = new Date('2026-04-17');
        $this->assertInstanceOf(DateInterface::class, $d);
        $this->assertInstanceOf(DateTimeInterface::class, $d);
        $this->assertInstanceOf(DateTimeImmutable::class, $d);
    }

    // =========================================================================
    // toDays() / fromDays()
    // =========================================================================

    #[DataProvider('toDaysProvider')]
    public function testToDaysMatchesGregorianToJd(int $year, int $month, int $day): void
    {
        $d = new Date(sprintf('%04d-%02d-%02d', $year, $month, $day));
        $jd = $d->toDays();

        if (function_exists('gregoriantojd')) {
            $this->assertSame(gregoriantojd($month, $day, $year), $jd);
        }
        $this->assertIsInt($jd);
        $this->assertGreaterThan(0, $jd);
    }

    public static function toDaysProvider(): array
    {
        return [
            'epoch' => [1970, 1, 1],
            'unix max 32-bit' => [2038, 1, 19],
            'y2k' => [2000, 1, 1],
            'leap day 2024' => [2024, 2, 29],
            'leap day 2000' => [2000, 2, 29],
            'non-leap 1900' => [1900, 2, 28],
            'century boundary' => [1900, 1, 1],
            'medieval' => [800, 6, 15],
            'modern' => [2026, 4, 17],
            'end of year' => [2025, 12, 31],
            'start of year' => [2026, 1, 1],
        ];
    }

    #[DataProvider('toDaysProvider')]
    public function testFromDaysRoundTrip(int $year, int $month, int $day): void
    {
        $original = new Date(sprintf('%04d-%02d-%02d', $year, $month, $day));
        $jd = $original->toDays();
        $restored = Date::fromDays($jd);

        $this->assertSame($year, (int) $restored->format('Y'));
        $this->assertSame($month, (int) $restored->format('n'));
        $this->assertSame($day, (int) $restored->format('j'));
    }

    public function testToDaysConsecutiveDays(): void
    {
        $d1 = new Date('2026-03-15');
        $d2 = new Date('2026-03-16');
        $this->assertSame($d1->toDays() + 1, $d2->toDays());
    }

    public function testToDaysLeapYearBoundary(): void
    {
        $feb28 = new Date('2024-02-28');
        $feb29 = new Date('2024-02-29');
        $mar01 = new Date('2024-03-01');

        $this->assertSame($feb28->toDays() + 1, $feb29->toDays());
        $this->assertSame($feb29->toDays() + 1, $mar01->toDays());
    }

    public function testToDaysNonLeapBoundary(): void
    {
        $feb28 = new Date('2025-02-28');
        $mar01 = new Date('2025-03-01');
        $this->assertSame($feb28->toDays() + 1, $mar01->toDays());
    }

    public function testToDaysMatchesLegacy(): void
    {
        $dates = ['2026-04-17', '2024-02-29', '2000-01-01', '1970-01-01'];
        foreach ($dates as $dateStr) {
            $modern = new Date($dateStr);
            $legacy = new Horde_Date($dateStr);
            $this->assertSame($legacy->toDays(), $modern->toDays(), "toDays mismatch for $dateStr");
        }
    }

    // =========================================================================
    // dayOfWeek()
    // =========================================================================

    #[DataProvider('dayOfWeekProvider')]
    public function testDayOfWeekMatchesDateTime(string $dateStr): void
    {
        $d = new Date($dateStr);
        $native = new DateTime($dateStr, new DateTimeZone('UTC'));

        $this->assertSame(
            (int) $native->format('w'),
            $d->dayOfWeek(),
            "dayOfWeek mismatch for $dateStr"
        );
    }

    public static function dayOfWeekProvider(): array
    {
        return [
            'sunday' => ['2026-04-12'],
            'monday' => ['2026-04-13'],
            'tuesday' => ['2026-04-14'],
            'wednesday' => ['2026-04-15'],
            'thursday' => ['2026-04-16'],
            'friday' => ['2026-04-17'],
            'saturday' => ['2026-04-18'],
            'epoch' => ['1970-01-01'],
            'y2k' => ['2000-01-01'],
            'leap day 2024' => ['2024-02-29'],
            'century boundary' => ['1900-01-01'],
            'far future' => ['2099-12-31'],
            'first day 2026' => ['2026-01-01'],
            'last day feb 2025' => ['2025-02-28'],
            'mar 1 2025' => ['2025-03-01'],
        ];
    }

    public function testDayOfWeekConstants(): void
    {
        $this->assertSame(Date::SUNDAY, (new Date('2026-04-12'))->dayOfWeek());
        $this->assertSame(Date::MONDAY, (new Date('2026-04-13'))->dayOfWeek());
        $this->assertSame(Date::TUESDAY, (new Date('2026-04-14'))->dayOfWeek());
        $this->assertSame(Date::WEDNESDAY, (new Date('2026-04-15'))->dayOfWeek());
        $this->assertSame(Date::THURSDAY, (new Date('2026-04-16'))->dayOfWeek());
        $this->assertSame(Date::FRIDAY, (new Date('2026-04-17'))->dayOfWeek());
        $this->assertSame(Date::SATURDAY, (new Date('2026-04-18'))->dayOfWeek());
    }

    public function testDayOfWeekMatchesLegacy(): void
    {
        $dates = ['2026-04-17', '2024-02-29', '2000-01-01', '1970-01-01', '1900-01-01'];
        foreach ($dates as $dateStr) {
            $modern = new Date($dateStr);
            $legacy = new Horde_Date($dateStr);
            $this->assertSame(
                $legacy->dayOfWeek(),
                $modern->dayOfWeek(),
                "dayOfWeek mismatch for $dateStr"
            );
        }
    }

    // =========================================================================
    // dayOfYear()
    // =========================================================================

    public function testDayOfYearJan1(): void
    {
        $this->assertSame(1, (new Date('2026-01-01'))->dayOfYear());
    }

    public function testDayOfYearDec31NonLeap(): void
    {
        $this->assertSame(365, (new Date('2025-12-31'))->dayOfYear());
    }

    public function testDayOfYearDec31Leap(): void
    {
        $this->assertSame(366, (new Date('2024-12-31'))->dayOfYear());
    }

    public function testDayOfYearMar1LeapYear(): void
    {
        $this->assertSame(61, (new Date('2024-03-01'))->dayOfYear());
    }

    public function testDayOfYearMar1NonLeapYear(): void
    {
        $this->assertSame(60, (new Date('2025-03-01'))->dayOfYear());
    }

    // =========================================================================
    // weekOfMonth()
    // =========================================================================

    #[DataProvider('weekOfMonthProvider')]
    public function testWeekOfMonth(int $day, int $expectedWeek): void
    {
        $d = new Date(sprintf('2026-04-%02d', $day));
        $this->assertSame($expectedWeek, $d->weekOfMonth());
    }

    public static function weekOfMonthProvider(): array
    {
        return [
            'day 1' => [1, 1],
            'day 6' => [6, 1],
            'day 7' => [7, 1],
            'day 8' => [8, 2],
            'day 14' => [14, 2],
            'day 15' => [15, 3],
            'day 21' => [21, 3],
            'day 22' => [22, 4],
            'day 28' => [28, 4],
            'day 29' => [29, 5],
            'day 30' => [30, 5],
        ];
    }

    public function testWeekOfMonthDay31(): void
    {
        $d = new Date('2026-01-31');
        $this->assertSame(5, $d->weekOfMonth());
    }

    // =========================================================================
    // weekOfYear()
    // =========================================================================

    #[DataProvider('weekOfYearProvider')]
    public function testWeekOfYear(string $dateStr, int $expectedWeek): void
    {
        $d = new Date($dateStr);
        $this->assertSame($expectedWeek, $d->weekOfYear());
    }

    public static function weekOfYearProvider(): array
    {
        return [
            '2026-01-01 is W01' => ['2026-01-01', 1],
            '2026-01-05 is W02' => ['2026-01-05', 2],
            '2025-12-29 is W01 of 2026' => ['2025-12-29', 1],
            '2025-12-28 is W52' => ['2025-12-28', 52],
            '2024-12-30 is W01 of 2025' => ['2024-12-30', 1],
        ];
    }

    // =========================================================================
    // weeksInYear()
    // =========================================================================

    #[DataProvider('weeksInYearProvider')]
    public function testWeeksInYear(int $year, int $expectedWeeks): void
    {
        $this->assertSame($expectedWeeks, Date::weeksInYear($year));
    }

    public static function weeksInYearProvider(): array
    {
        return [
            '2020 has 53 weeks' => [2020, 53],
            '2021 has 52 weeks' => [2021, 52],
            '2022 has 52 weeks' => [2022, 52],
            '2023 has 52 weeks' => [2023, 52],
            '2024 has 52 weeks' => [2024, 52],
            '2025 has 52 weeks' => [2025, 52],
            '2026 has 53 weeks' => [2026, 53],
            '2015 has 53 weeks' => [2015, 53],
            '2004 has 53 weeks' => [2004, 53],
            '2000 has 52 weeks' => [2000, 52],
            '1998 has 53 weeks' => [1998, 53],
            '1970 has 53 weeks' => [1970, 53],
        ];
    }

    // =========================================================================
    // withNthWeekday()
    // =========================================================================

    #[DataProvider('withNthWeekdayPositiveProvider')]
    public function testWithNthWeekdayPositive(
        int $year,
        int $month,
        int $weekday,
        int $nth,
        string $expectedDate,
    ): void {
        $d = new Date(sprintf('%04d-%02d-01', $year, $month));
        $result = $d->withNthWeekday($weekday, $nth);
        $this->assertSame($expectedDate, $result->format('Y-m-d'));
    }

    public static function withNthWeekdayPositiveProvider(): array
    {
        return [
            '1st Sunday Apr 2026' => [2026, 4, Date::SUNDAY, 1, '2026-04-05'],
            '1st Monday Apr 2026' => [2026, 4, Date::MONDAY, 1, '2026-04-06'],
            '1st Friday Apr 2026' => [2026, 4, Date::FRIDAY, 1, '2026-04-03'],
            '2nd Monday Apr 2026' => [2026, 4, Date::MONDAY, 2, '2026-04-13'],
            '3rd Wednesday Apr 2026' => [2026, 4, Date::WEDNESDAY, 3, '2026-04-15'],
            '4th Thursday Apr 2026' => [2026, 4, Date::THURSDAY, 4, '2026-04-23'],
            '1st Monday Jan 2026' => [2026, 1, Date::MONDAY, 1, '2026-01-05'],
            '1st Saturday Jan 2026' => [2026, 1, Date::SATURDAY, 1, '2026-01-03'],
            '2nd Saturday Jan 2026' => [2026, 1, Date::SATURDAY, 2, '2026-01-10'],
            '1st Monday Feb 2024' => [2024, 2, Date::MONDAY, 1, '2024-02-05'],
            '5th Saturday Mar 2026' => [2026, 3, Date::SATURDAY, 5, '2026-04-04'],
        ];
    }

    #[DataProvider('withNthWeekdayNegativeProvider')]
    public function testWithNthWeekdayNegative(
        int $year,
        int $month,
        int $weekday,
        int $nth,
        string $expectedDate,
    ): void {
        $d = new Date(sprintf('%04d-%02d-01', $year, $month));
        $result = $d->withNthWeekday($weekday, $nth);
        $this->assertSame($expectedDate, $result->format('Y-m-d'));
    }

    public static function withNthWeekdayNegativeProvider(): array
    {
        return [
            'last Friday Apr 2026' => [2026, 4, Date::FRIDAY, -1, '2026-04-24'],
            'last Monday Apr 2026' => [2026, 4, Date::MONDAY, -1, '2026-04-27'],
            'last Sunday Apr 2026' => [2026, 4, Date::SUNDAY, -1, '2026-04-26'],
            'last Saturday Apr 2026' => [2026, 4, Date::SATURDAY, -1, '2026-04-25'],
            'last Friday Feb 2024' => [2024, 2, Date::FRIDAY, -1, '2024-02-23'],
            'last Thursday Feb 2025' => [2025, 2, Date::THURSDAY, -1, '2025-02-27'],
            '2nd-last Monday Apr 2026' => [2026, 4, Date::MONDAY, -2, '2026-04-20'],
            'last Wednesday Dec 2025' => [2025, 12, Date::WEDNESDAY, -1, '2025-12-31'],
        ];
    }

    public function testWithNthWeekdayInvalid(): void
    {
        $d = new Date('2026-04-17');
        $this->assertSame($d, $d->withNthWeekday(7, 1));
        $this->assertSame($d, $d->withNthWeekday(-1, 1));
    }

    public function testWithNthWeekdayFeb29LeapYear(): void
    {
        $d = new Date('2024-02-01');
        $result = $d->withNthWeekday(Date::THURSDAY, 5);
        $this->assertSame('02', $result->format('m'));
        $this->assertSame('29', $result->format('d'));
    }

    public function testWithNthWeekdayIsImmutable(): void
    {
        $d = new Date('2026-04-17');
        $result = $d->withNthWeekday(Date::MONDAY, 1);
        $this->assertSame('2026-04-17', $d->format('Y-m-d'));
        $this->assertSame('2026-04-06', $result->format('Y-m-d'));
    }

    // =========================================================================
    // diffDays()
    // =========================================================================

    public function testDiffDaysSameDay(): void
    {
        $d = new Date('2026-04-17 14:00:00');
        $this->assertSame(0, $d->diffDays(new Date('2026-04-17 23:59:59')));
    }

    public function testDiffDaysAdjacent(): void
    {
        $a = new Date('2026-04-17');
        $b = new Date('2026-04-18');
        $this->assertSame(1, $a->diffDays($b));
    }

    public function testDiffDaysAcrossYear(): void
    {
        $a = new Date('2025-12-31');
        $b = new Date('2026-01-01');
        $this->assertSame(1, $a->diffDays($b));
    }

    public function testDiffDaysLeapYear(): void
    {
        $a = new Date('2024-02-28');
        $b = new Date('2024-03-01');
        $this->assertSame(2, $a->diffDays($b));
    }

    public function testDiffDaysSymmetric(): void
    {
        $a = new Date('2026-01-01');
        $b = new Date('2026-04-17');
        $this->assertSame($a->diffDays($b), $b->diffDays($a));
    }

    // =========================================================================
    // Comparison
    // =========================================================================

    public function testCompareDateEqual(): void
    {
        $a = new Date('2026-04-17 10:00:00');
        $b = new Date('2026-04-17 23:59:59');
        $this->assertSame(0, $a->compareDate($b));
    }

    public function testCompareDateBefore(): void
    {
        $a = new Date('2026-04-16');
        $b = new Date('2026-04-17');
        $this->assertLessThan(0, $a->compareDate($b));
    }

    public function testCompareDateAfter(): void
    {
        $a = new Date('2026-04-18');
        $b = new Date('2026-04-17');
        $this->assertGreaterThan(0, $a->compareDate($b));
    }

    public function testCompareTimeEqual(): void
    {
        $a = new Date('2026-04-17 14:30:00');
        $b = new Date('2025-01-01 14:30:00');
        $this->assertSame(0, $a->compareTime($b));
    }

    public function testCompareTimeBefore(): void
    {
        $a = new Date('2026-04-17 08:00:00');
        $b = new Date('2026-04-17 14:00:00');
        $this->assertLessThan(0, $a->compareTime($b));
    }

    public function testCompareDateTime(): void
    {
        $a = new Date('2026-04-17 14:30:00');
        $b = new Date('2026-04-17 14:30:00');
        $this->assertSame(0, $a->compareDateTime($b));

        $c = new Date('2026-04-17 14:30:01');
        $this->assertLessThan(0, $a->compareDateTime($c));
        $this->assertGreaterThan(0, $c->compareDateTime($a));
    }

    public function testBeforeAfterEquals(): void
    {
        $a = new Date('2026-04-16 12:00:00');
        $b = new Date('2026-04-17 12:00:00');
        $c = new Date('2026-04-17 12:00:00');

        $this->assertTrue($a->before($b));
        $this->assertFalse($a->after($b));
        $this->assertFalse($a->equals($b));

        $this->assertTrue($b->after($a));
        $this->assertTrue($b->equals($c));
    }

    public function testCompareWithNativeDatetime(): void
    {
        $d = new Date('2026-04-17 14:30:00', new DateTimeZone('UTC'));
        $native = new DateTimeImmutable('2026-04-17 14:30:00', new DateTimeZone('UTC'));
        $this->assertSame(0, $d->compareDateTime($native));
    }

    // =========================================================================
    // addParts() / subParts()
    // =========================================================================

    public function testAddPartsMonthOnly(): void
    {
        $d = new Date('2026-01-15 12:00:00');
        $result = $d->addParts(months: 1);
        $this->assertSame('2026-02-15', $result->format('Y-m-d'));
    }

    public function testAddPartsMultiple(): void
    {
        $d = new Date('2026-04-17 10:00:00');
        $result = $d->addParts(years: 1, months: 2, days: 5);
        $this->assertSame('2027-06-22', $result->format('Y-m-d'));
    }

    public function testAddPartsMonthOverflow(): void
    {
        $d = new Date('2026-01-31 12:00:00');
        $result = $d->addParts(months: 1);
        $this->assertSame('2026-03-03', $result->format('Y-m-d'));
    }

    public function testAddPartsDayOverflow(): void
    {
        $d = new Date('2026-04-28 12:00:00');
        $result = $d->addParts(days: 5);
        $this->assertSame('2026-05-03', $result->format('Y-m-d'));
    }

    public function testAddPartsYearBoundary(): void
    {
        $d = new Date('2026-11-15 12:00:00');
        $result = $d->addParts(months: 2);
        $this->assertSame('2027-01-15', $result->format('Y-m-d'));
    }

    public function testAddPartsLeapYear(): void
    {
        $d = new Date('2024-02-29 12:00:00');
        $result = $d->addParts(years: 1);
        $this->assertSame('2025-03-01', $result->format('Y-m-d'));
    }

    public function testAddPartsSeconds(): void
    {
        $d = new Date('2026-04-17 23:59:30');
        $result = $d->addParts(seconds: 60);
        $this->assertSame('2026-04-18 00:00:30', $result->format('Y-m-d H:i:s'));
    }

    public function testSubParts(): void
    {
        $d = new Date('2026-04-17 12:00:00');
        $result = $d->subParts(months: 1, days: 5);
        $this->assertSame('2026-03-12', $result->format('Y-m-d'));
    }

    public function testAddPartsIsImmutable(): void
    {
        $d = new Date('2026-04-17 12:00:00');
        $result = $d->addParts(months: 1);
        $this->assertSame('2026-04-17', $d->format('Y-m-d'));
        $this->assertSame('2026-05-17', $result->format('Y-m-d'));
    }

    #[DataProvider('cascadeProvider')]
    public function testAddPartsCascade(string $input, int $addSeconds, string $expected): void
    {
        $d = new Date($input);
        $result = $d->addParts(seconds: $addSeconds);
        $this->assertSame($expected, $result->format('Y-m-d H:i:s'));
    }

    public static function cascadeProvider(): array
    {
        return [
            'no cascade' => ['2026-04-17 10:30:00', 15, '2026-04-17 10:30:15'],
            'sec to min' => ['2026-04-17 10:30:45', 30, '2026-04-17 10:31:15'],
            'sec to hour' => ['2026-04-17 10:59:45', 30, '2026-04-17 11:00:15'],
            'sec to day' => ['2026-04-17 23:59:45', 30, '2026-04-18 00:00:15'],
            'year boundary forward' => ['2026-12-31 23:59:30', 60, '2027-01-01 00:00:30'],
            'year boundary backward' => ['2027-01-01 00:00:00', -1, '2026-12-31 23:59:59'],
            'one full day' => ['2026-04-17 12:00:00', 86400, '2026-04-18 12:00:00'],
            'negative full day' => ['2026-04-17 12:00:00', -86400, '2026-04-16 12:00:00'],
        ];
    }

    // =========================================================================
    // format() with pluggable formatters
    // =========================================================================

    public function testFormatSingleArg(): void
    {
        $d = new Date('2026-04-17 14:30:00', new DateTimeZone('UTC'));
        $this->assertSame('2026-04-17', $d->format('Y-m-d'));
    }

    public function testFormatWithFormatter(): void
    {
        $d = new Date('2026-04-17 14:30:00', new DateTimeZone('UTC'));
        $result = $d->format('Y-m-d', new DateTimeFormatter());
        $this->assertSame('2026-04-17', $result);
    }

    public function testFormatWithIcuFormatter(): void
    {
        $d = new Date('2026-04-17 14:30:00', new DateTimeZone('UTC'));
        $result = $d->format('yyyy-MM-dd', new IcuFormatter(), 'en_US');
        $this->assertSame('2026-04-17', $result);
    }

    public function testFormatWithLocale(): void
    {
        $d = new Date('2026-04-17 14:30:00', new DateTimeZone('UTC'));
        $result = $d->format('EEEE', new IcuFormatter(), 'de_DE');
        $this->assertSame('Freitag', $result);
    }

    // =========================================================================
    // Serialization
    // =========================================================================

    public function testToJson(): void
    {
        $d = new Date('2026-04-17 14:30:00');
        $this->assertSame('2026-04-17T14:30:00', $d->toJson());
    }

    public function testToiCalendarFloating(): void
    {
        $d = new Date('2026-04-17 14:30:00');
        $this->assertSame('20260417T143000', $d->toiCalendar(true));
    }

    public function testToiCalendarUtc(): void
    {
        $d = new Date('2026-04-17 14:30:00', new DateTimeZone('UTC'));
        $this->assertSame('20260417T143000Z', $d->toiCalendar());
    }

    public function testTimestamp(): void
    {
        $d = new Date('2026-04-17 14:30:00', new DateTimeZone('UTC'));
        $this->assertSame($d->getTimestamp(), $d->timestamp());
    }

    // =========================================================================
    // DateInterface compliance
    // =========================================================================

    public function testToDateTimeImmutable(): void
    {
        $d = new Date('2026-04-17 14:30:00');
        $this->assertSame($d, $d->toDateTimeImmutable());
    }

    public function testGetTimezone(): void
    {
        $d = new Date('2026-04-17 14:30:00', new DateTimeZone('America/Chicago'));
        $tz = $d->getTimezone();
        $this->assertInstanceOf(DateTimeZone::class, $tz);
        $this->assertSame('America/Chicago', $tz->getName());
    }
}
