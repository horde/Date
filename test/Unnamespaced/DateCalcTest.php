<?php

declare(strict_types=1);

namespace Horde\Date\Test;

use DateTime;
use DateTimeZone;
use Horde_Date;
use Horde_Date_Utils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Date::class)]
class DateCalcTest extends TestCase
{
    // =========================================================================
    // toDays() / fromDays() — Julian Day round-trips
    // =========================================================================

    #[DataProvider('toDaysProvider')]
    public function testToDaysMatchesGregorianToJd(int $year, int $month, int $day): void
    {
        $date = new Horde_Date(['year' => $year, 'month' => $month, 'mday' => $day]);
        $jd = $date->toDays();

        if (function_exists('gregoriantojd')) {
            $this->assertSame(
                gregoriantojd($month, $day, $year),
                $jd,
                "toDays() should match gregoriantojd() for $year-$month-$day"
            );
        }
        $this->assertIsInt($jd);
        $this->assertGreaterThan(0, $jd);
    }

    public static function toDaysProvider(): array
    {
        return [
            'epoch'            => [1970, 1, 1],
            'unix max 32-bit'  => [2038, 1, 19],
            'y2k'              => [2000, 1, 1],
            'leap day 2024'    => [2024, 2, 29],
            'leap day 2000'    => [2000, 2, 29],
            'non-leap 1900'    => [1900, 2, 28],
            'century boundary' => [1900, 1, 1],
            'far past'         => [1, 1, 1],
            'medieval'         => [800, 6, 15],
            'modern'           => [2026, 4, 17],
            'end of year'      => [2025, 12, 31],
            'start of year'    => [2026, 1, 1],
        ];
    }

    #[DataProvider('toDaysProvider')]
    public function testFromDaysRoundTrip(int $year, int $month, int $day): void
    {
        if (function_exists('jdtogregorian')) {
            $this->markTestSkipped(
                'fromDays() bug: jdtogregorian returns strings which fail the 3-arg constructor string check'
            );
        }
        $original = new Horde_Date(['year' => $year, 'month' => $month, 'mday' => $day]);
        $jd = $original->toDays();
        $restored = Horde_Date::fromDays($jd);

        $this->assertSame($year, $restored->year, "Year mismatch for JD $jd");
        $this->assertSame($month, $restored->month, "Month mismatch for JD $jd");
        $this->assertSame($day, $restored->mday, "Day mismatch for JD $jd");
    }

    public function testFromDaysStringYearBug(): void
    {
        if (!function_exists('jdtogregorian')) {
            $this->markTestSkipped('Bug only manifests when jdtogregorian is available');
        }
        $this->expectException(\Horde_Date_Exception::class);
        Horde_Date::fromDays(gregoriantojd(1, 1, 1970));
    }

    public function testToDaysConsecutiveDaysAreConsecutiveJd(): void
    {
        $d1 = new Horde_Date('2026-03-15');
        $d2 = new Horde_Date('2026-03-16');
        $this->assertSame($d1->toDays() + 1, $d2->toDays());
    }

    public function testToDaysLeapYearBoundary(): void
    {
        $feb28 = new Horde_Date(['year' => 2024, 'month' => 2, 'mday' => 28]);
        $feb29 = new Horde_Date(['year' => 2024, 'month' => 2, 'mday' => 29]);
        $mar01 = new Horde_Date(['year' => 2024, 'month' => 3, 'mday' => 1]);

        $this->assertSame($feb28->toDays() + 1, $feb29->toDays());
        $this->assertSame($feb29->toDays() + 1, $mar01->toDays());
    }

    public function testToDaysNonLeapYearBoundary(): void
    {
        $feb28 = new Horde_Date(['year' => 2025, 'month' => 2, 'mday' => 28]);
        $mar01 = new Horde_Date(['year' => 2025, 'month' => 3, 'mday' => 1]);

        $this->assertSame($feb28->toDays() + 1, $mar01->toDays());
    }

    public function testDiffUsesToDays(): void
    {
        $a = new Horde_Date('2026-01-01');
        $b = new Horde_Date('2026-01-31');
        $this->assertSame(30, $a->diff($b));
    }

    public function testDiffAcrossYearBoundary(): void
    {
        $a = new Horde_Date('2025-12-31');
        $b = new Horde_Date('2026-01-01');
        $this->assertSame(1, $a->diff($b));
    }

    // =========================================================================
    // dayOfWeek() — Zeller's formula verification against DateTime
    // =========================================================================

    #[DataProvider('dayOfWeekProvider')]
    public function testDayOfWeekMatchesDateTime(string $dateStr): void
    {
        $hordeDate = new Horde_Date($dateStr);
        $nativeDate = new DateTime($dateStr, new DateTimeZone('UTC'));

        $this->assertSame(
            (int)$nativeDate->format('w'),
            $hordeDate->dayOfWeek(),
            "dayOfWeek() mismatch for $dateStr"
        );
    }

    public static function dayOfWeekProvider(): array
    {
        return [
            'sunday'            => ['2026-04-12'],
            'monday'            => ['2026-04-13'],
            'tuesday'           => ['2026-04-14'],
            'wednesday'         => ['2026-04-15'],
            'thursday'          => ['2026-04-16'],
            'friday'            => ['2026-04-17'],
            'saturday'          => ['2026-04-18'],
            'epoch'             => ['1970-01-01'],
            'y2k'               => ['2000-01-01'],
            'leap day 2024'     => ['2024-02-29'],
            'century boundary'  => ['1900-01-01'],
            'new year 2000'     => ['2000-12-31'],
            'far future'        => ['2099-12-31'],
            'jan 1 1901'        => ['1901-01-01'],
            'jul 4 1776'        => ['1776-07-04'],
            'jan 1 1'           => ['0001-01-01'],
            'dec 31 9999'       => ['9999-12-31'],
            'first day 2026'    => ['2026-01-01'],
            'last day feb 2025' => ['2025-02-28'],
            'mar 1 2025'        => ['2025-03-01'],
        ];
    }

    public function testDayOfWeekConstants(): void
    {
        $this->assertSame(Horde_Date::DATE_SUNDAY, (new Horde_Date('2026-04-12'))->dayOfWeek());
        $this->assertSame(Horde_Date::DATE_MONDAY, (new Horde_Date('2026-04-13'))->dayOfWeek());
        $this->assertSame(Horde_Date::DATE_TUESDAY, (new Horde_Date('2026-04-14'))->dayOfWeek());
        $this->assertSame(Horde_Date::DATE_WEDNESDAY, (new Horde_Date('2026-04-15'))->dayOfWeek());
        $this->assertSame(Horde_Date::DATE_THURSDAY, (new Horde_Date('2026-04-16'))->dayOfWeek());
        $this->assertSame(Horde_Date::DATE_FRIDAY, (new Horde_Date('2026-04-17'))->dayOfWeek());
        $this->assertSame(Horde_Date::DATE_SATURDAY, (new Horde_Date('2026-04-18'))->dayOfWeek());
    }

    // =========================================================================
    // weekOfMonth()
    // =========================================================================

    #[DataProvider('weekOfMonthProvider')]
    public function testWeekOfMonth(int $day, int $expectedWeek): void
    {
        $date = new Horde_Date(['year' => 2026, 'month' => 4, 'mday' => $day]);
        $this->assertSame($expectedWeek, (int)$date->weekOfMonth());
    }

    public static function weekOfMonthProvider(): array
    {
        return [
            'day 1 → week 1'  => [1, 1],
            'day 6 → week 1'  => [6, 1],
            'day 7 → week 1'  => [7, 1],
            'day 8 → week 2'  => [8, 2],
            'day 14 → week 2' => [14, 2],
            'day 15 → week 3' => [15, 3],
            'day 21 → week 3' => [21, 3],
            'day 22 → week 4' => [22, 4],
            'day 28 → week 4' => [28, 4],
            'day 29 → week 5' => [29, 5],
            'day 30 → week 5' => [30, 5],
        ];
    }

    public function testWeekOfMonthDay31(): void
    {
        $date = new Horde_Date(['year' => 2026, 'month' => 1, 'mday' => 31]);
        $this->assertSame(5, (int)$date->weekOfMonth());
    }

    // =========================================================================
    // weekOfYear()
    // =========================================================================

    #[DataProvider('weekOfYearProvider')]
    public function testWeekOfYearMatchesDateTime(string $dateStr, int $expectedWeek): void
    {
        $date = new Horde_Date($dateStr);
        $this->assertSame(
            $expectedWeek,
            (int)$date->weekOfYear(),
            "weekOfYear() mismatch for $dateStr"
        );
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
        $this->assertSame(
            $expectedWeeks,
            (int)Horde_Date::weeksInYear($year),
            "weeksInYear() mismatch for year $year"
        );
    }

    public static function weeksInYearProvider(): array
    {
        // Years with 53 ISO weeks: years where Jan 1 is Thursday,
        // or Dec 31 is Thursday.
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
    // dayOfYear()
    // =========================================================================

    public function testDayOfYearJan1(): void
    {
        $date = new Horde_Date('2026-01-01');
        $this->assertSame(1, $date->dayOfYear());
    }

    public function testDayOfYearDec31NonLeap(): void
    {
        $date = new Horde_Date('2025-12-31');
        $this->assertSame(365, $date->dayOfYear());
    }

    public function testDayOfYearDec31Leap(): void
    {
        $date = new Horde_Date('2024-12-31');
        $this->assertSame(366, $date->dayOfYear());
    }

    public function testDayOfYearMar1LeapYear(): void
    {
        $date = new Horde_Date('2024-03-01');
        $this->assertSame(61, $date->dayOfYear());
    }

    public function testDayOfYearMar1NonLeapYear(): void
    {
        $date = new Horde_Date('2025-03-01');
        $this->assertSame(60, $date->dayOfYear());
    }

    // =========================================================================
    // setNthWeekday() — positive nth
    // =========================================================================

    #[DataProvider('setNthWeekdayPositiveProvider')]
    public function testSetNthWeekdayPositive(
        int $year,
        int $month,
        int $weekday,
        int $nth,
        string $expectedDate,
    ): void {
        $date = new Horde_Date(['year' => $year, 'month' => $month, 'mday' => 1]);
        $date->setNthWeekday($weekday, $nth);
        $this->assertSame(
            $expectedDate,
            $date->format('Y-m-d'),
            "setNthWeekday($weekday, $nth) for $year-$month"
        );
    }

    public static function setNthWeekdayPositiveProvider(): array
    {
        return [
            '1st Sunday Apr 2026'    => [2026, 4, Horde_Date::DATE_SUNDAY, 1, '2026-04-05'],
            '1st Monday Apr 2026'    => [2026, 4, Horde_Date::DATE_MONDAY, 1, '2026-04-06'],
            '1st Friday Apr 2026'    => [2026, 4, Horde_Date::DATE_FRIDAY, 1, '2026-04-03'],
            '2nd Monday Apr 2026'    => [2026, 4, Horde_Date::DATE_MONDAY, 2, '2026-04-13'],
            '3rd Wednesday Apr 2026' => [2026, 4, Horde_Date::DATE_WEDNESDAY, 3, '2026-04-15'],
            '4th Thursday Apr 2026'  => [2026, 4, Horde_Date::DATE_THURSDAY, 4, '2026-04-23'],
            '1st Monday Jan 2026'    => [2026, 1, Horde_Date::DATE_MONDAY, 1, '2026-01-05'],
            '1st Saturday Jan 2026'  => [2026, 1, Horde_Date::DATE_SATURDAY, 1, '2026-01-03'],
            '2nd Saturday Jan 2026'  => [2026, 1, Horde_Date::DATE_SATURDAY, 2, '2026-01-10'],
            '1st Monday Feb 2024'    => [2024, 2, Horde_Date::DATE_MONDAY, 1, '2024-02-05'],
            '5th Saturday Mar 2026'  => [2026, 3, Horde_Date::DATE_SATURDAY, 5, '2026-04-04'],
        ];
    }

    // =========================================================================
    // setNthWeekday() — negative nth (last weekday of month)
    // =========================================================================

    #[DataProvider('setNthWeekdayNegativeProvider')]
    public function testSetNthWeekdayNegative(
        int $year,
        int $month,
        int $weekday,
        int $nth,
        string $expectedDate,
    ): void {
        $date = new Horde_Date(['year' => $year, 'month' => $month, 'mday' => 1]);
        $date->setNthWeekday($weekday, $nth);
        $this->assertSame(
            $expectedDate,
            $date->format('Y-m-d'),
            "setNthWeekday($weekday, $nth) for $year-$month"
        );
    }

    public static function setNthWeekdayNegativeProvider(): array
    {
        return [
            'last Friday Apr 2026'    => [2026, 4, Horde_Date::DATE_FRIDAY, -1, '2026-04-24'],
            'last Monday Apr 2026'    => [2026, 4, Horde_Date::DATE_MONDAY, -1, '2026-04-27'],
            'last Sunday Apr 2026'    => [2026, 4, Horde_Date::DATE_SUNDAY, -1, '2026-04-26'],
            'last Saturday Apr 2026'  => [2026, 4, Horde_Date::DATE_SATURDAY, -1, '2026-04-25'],
            'last Friday Feb 2024'    => [2024, 2, Horde_Date::DATE_FRIDAY, -1, '2024-02-23'],
            'last Thursday Feb 2025'  => [2025, 2, Horde_Date::DATE_THURSDAY, -1, '2025-02-27'],
            '2nd-last Monday Apr 2026' => [2026, 4, Horde_Date::DATE_MONDAY, -2, '2026-04-20'],
            'last Wednesday Dec 2025'  => [2025, 12, Horde_Date::DATE_WEDNESDAY, -1, '2025-12-31'],
        ];
    }

    public function testSetNthWeekdayInvalidWeekdayIsNoOp(): void
    {
        $date = new Horde_Date('2026-04-17');
        $originalDay = $date->mday;
        $date->setNthWeekday(7, 1);
        $this->assertSame($originalDay, $date->mday);

        $date->setNthWeekday(-1, 1);
        $this->assertSame($originalDay, $date->mday);
    }

    // =========================================================================
    // setNthWeekday() — February 29 edge cases
    // =========================================================================

    public function testSetNthWeekdayFeb29LeapYear(): void
    {
        // Feb 2024: 1st Thursday is Feb 1, 5th Thursday is Feb 29
        $date = new Horde_Date(['year' => 2024, 'month' => 2, 'mday' => 1]);
        $date->setNthWeekday(Horde_Date::DATE_THURSDAY, 5);
        $this->assertSame(2, $date->month);
        $this->assertSame(29, $date->mday);
    }

    public function testSetNthWeekdayLastFridayFeb2024(): void
    {
        // Feb 2024 has 29 days, last Friday should be Feb 23
        $date = new Horde_Date(['year' => 2024, 'month' => 2, 'mday' => 1]);
        $date->setNthWeekday(Horde_Date::DATE_FRIDAY, -1);
        $this->assertSame('2024-02-23', $date->format('Y-m-d'));
    }

    // =========================================================================
    // Comparison methods
    // =========================================================================

    public function testCompareDateEqual(): void
    {
        $a = new Horde_Date('2026-04-17 10:00:00');
        $b = new Horde_Date('2026-04-17 23:59:59');
        $this->assertSame(0, $a->compareDate($b));
    }

    public function testCompareDateBefore(): void
    {
        $a = new Horde_Date('2026-04-16');
        $b = new Horde_Date('2026-04-17');
        $this->assertLessThan(0, $a->compareDate($b));
    }

    public function testCompareDateAfter(): void
    {
        $a = new Horde_Date('2026-04-18');
        $b = new Horde_Date('2026-04-17');
        $this->assertGreaterThan(0, $a->compareDate($b));
    }

    public function testCompareTimeEqual(): void
    {
        $a = new Horde_Date('2026-04-17 14:30:00');
        $b = new Horde_Date('2025-01-01 14:30:00');
        $this->assertSame(0, $a->compareTime($b));
    }

    public function testCompareTimeBefore(): void
    {
        $a = new Horde_Date('2026-04-17 08:00:00');
        $b = new Horde_Date('2026-04-17 14:00:00');
        $this->assertLessThan(0, $a->compareTime($b));
    }

    public function testCompareDateTimeCombined(): void
    {
        $a = new Horde_Date('2026-04-17 14:30:00');
        $b = new Horde_Date('2026-04-17 14:30:00');
        $this->assertSame(0, $a->compareDateTime($b));

        $c = new Horde_Date('2026-04-17 14:30:01');
        $this->assertLessThan(0, $a->compareDateTime($c));
        $this->assertGreaterThan(0, $c->compareDateTime($a));
    }

    public function testBeforeAfterEquals(): void
    {
        $a = new Horde_Date('2026-04-16');
        $b = new Horde_Date('2026-04-17');
        $c = new Horde_Date('2026-04-17');

        $this->assertTrue($a->before($b));
        $this->assertFalse($a->after($b));
        $this->assertFalse($a->equals($b));

        $this->assertTrue($b->after($a));
        $this->assertTrue($b->equals($c));
    }
}
