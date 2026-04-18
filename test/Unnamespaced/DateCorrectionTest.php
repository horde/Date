<?php

declare(strict_types=1);

namespace Horde\Date\Test;

use Horde_Date;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Date::class)]
class DateCorrectionTest extends TestCase
{
    // =========================================================================
    // Second overflow → cascades to minutes
    // =========================================================================

    public function testSecondOverflowPositive(): void
    {
        $date = new Horde_Date('2026-04-17 10:30:00');
        $date->sec = 90;
        $this->assertSame(10, $date->hour);
        $this->assertSame(31, $date->min);
        $this->assertSame(30, $date->sec);
    }

    public function testSecondOverflowNegative(): void
    {
        $date = new Horde_Date('2026-04-17 10:30:30');
        $date->sec = -30;
        $this->assertSame(10, $date->hour);
        $this->assertSame(29, $date->min);
        $this->assertSame(30, $date->sec);
    }

    public function testSecondOverflowMultipleMinutes(): void
    {
        $date = new Horde_Date('2026-04-17 10:00:00');
        $date->sec = 3661;
        $this->assertSame(11, $date->hour);
        $this->assertSame(1, $date->min);
        $this->assertSame(1, $date->sec);
    }

    // =========================================================================
    // Minute overflow → cascades to hours
    // =========================================================================

    public function testMinuteOverflowPositive(): void
    {
        $date = new Horde_Date('2026-04-17 10:00:00');
        $date->min = 75;
        $this->assertSame(11, $date->hour);
        $this->assertSame(15, $date->min);
    }

    public function testMinuteOverflowNegative(): void
    {
        $date = new Horde_Date('2026-04-17 10:00:00');
        $date->min = -30;
        $this->assertSame(9, $date->hour);
        $this->assertSame(30, $date->min);
    }

    // =========================================================================
    // Hour overflow → cascades to days
    // =========================================================================

    public function testHourOverflowPositive(): void
    {
        $date = new Horde_Date('2026-04-17 00:00:00');
        $date->hour = 25;
        $this->assertSame(18, $date->mday);
        $this->assertSame(1, $date->hour);
    }

    public function testHourOverflowNegative(): void
    {
        $date = new Horde_Date('2026-04-17 02:00:00');
        $date->hour = -1;
        $this->assertSame(16, $date->mday);
        $this->assertSame(23, $date->hour);
    }

    public function testHourOverflowMultipleDays(): void
    {
        $date = new Horde_Date('2026-04-17 00:00:00');
        $date->hour = 50;
        $this->assertSame(19, $date->mday);
        $this->assertSame(2, $date->hour);
    }

    // =========================================================================
    // Day overflow → cascades to months
    // =========================================================================

    public function testDayOverflowEndOfMonth(): void
    {
        $date = new Horde_Date(['year' => 2026, 'month' => 4, 'mday' => 1]);
        $date->mday = 31;
        $this->assertSame(5, $date->month);
        $this->assertSame(1, $date->mday);
    }

    public function testDayOverflowFebruaryNonLeap(): void
    {
        $date = new Horde_Date(['year' => 2025, 'month' => 2, 'mday' => 1]);
        $date->mday = 29;
        $this->assertSame(3, $date->month);
        $this->assertSame(1, $date->mday);
    }

    public function testDayOverflowFebruaryLeap(): void
    {
        $date = new Horde_Date(['year' => 2024, 'month' => 2, 'mday' => 1]);
        $date->mday = 29;
        $this->assertSame(2, $date->month);
        $this->assertSame(29, $date->mday);
    }

    public function testDayOverflowFebruaryLeapTo30(): void
    {
        $date = new Horde_Date(['year' => 2024, 'month' => 2, 'mday' => 1]);
        $date->mday = 30;
        $this->assertSame(3, $date->month);
        $this->assertSame(1, $date->mday);
    }

    public function testDayUnderflow(): void
    {
        $date = new Horde_Date(['year' => 2026, 'month' => 4, 'mday' => 1]);
        $date->mday = 0;
        $this->assertSame(3, $date->month);
        $this->assertSame(31, $date->mday);
    }

    public function testDayUnderflowNegative(): void
    {
        $date = new Horde_Date(['year' => 2026, 'month' => 3, 'mday' => 1]);
        $date->mday = -27;
        $this->assertSame(2, $date->month);
        $this->assertSame(1, $date->mday);
    }

    // =========================================================================
    // Month overflow → cascades to years
    // =========================================================================

    public function testMonthOverflowPositive(): void
    {
        $date = new Horde_Date(['year' => 2026, 'month' => 1, 'mday' => 15]);
        $date->month = 13;
        $this->assertSame(2027, $date->year);
        $this->assertSame(1, $date->month);
    }

    public function testMonthOverflowNegative(): void
    {
        $date = new Horde_Date(['year' => 2026, 'month' => 3, 'mday' => 15]);
        $date->month = -1;
        $this->assertSame(2025, $date->year);
        $this->assertSame(11, $date->month);
    }

    public function testMonthOverflowMultipleYears(): void
    {
        $date = new Horde_Date(['year' => 2026, 'month' => 1, 'mday' => 15]);
        $date->month = 25;
        $this->assertSame(2028, $date->year);
        $this->assertSame(1, $date->month);
    }

    public function testMonthSetToZero(): void
    {
        $date = new Horde_Date(['year' => 2026, 'month' => 6, 'mday' => 15]);
        $date->month = 0;
        $this->assertSame(2025, $date->year);
        $this->assertSame(12, $date->month);
    }

    // =========================================================================
    // Full cascade: seconds → minutes → hours → days → months → years
    // =========================================================================

    public function testFullCascadeViaAdd(): void
    {
        $date = new Horde_Date('2026-04-17 23:59:59');
        $result = $date->add(1);
        $this->assertSame(2026, $result->year);
        $this->assertSame(4, $result->month);
        $this->assertSame(18, $result->mday);
        $this->assertSame(0, $result->hour);
        $this->assertSame(0, $result->min);
        $this->assertSame(0, $result->sec);
    }

    public function testFullCascadeViaAddLargeSeconds(): void
    {
        $date = new Horde_Date('2026-12-31 23:59:59');
        $result = $date->add(1);
        $this->assertSame(2027, $result->year);
        $this->assertSame(1, $result->month);
        $this->assertSame(1, $result->mday);
        $this->assertSame(0, $result->hour);
        $this->assertSame(0, $result->min);
        $this->assertSame(0, $result->sec);
    }

    public function testFullCascadeViaSubtract(): void
    {
        $date = new Horde_Date('2026-01-01 00:00:00');
        $result = $date->sub(1);
        $this->assertSame(2025, $result->year);
        $this->assertSame(12, $result->month);
        $this->assertSame(31, $result->mday);
        $this->assertSame(23, $result->hour);
        $this->assertSame(59, $result->min);
        $this->assertSame(59, $result->sec);
    }

    // =========================================================================
    // add() with array (multi-part arithmetic)
    // =========================================================================

    public function testAddMonthArray(): void
    {
        $date = new Horde_Date('2026-01-31 12:00:00');
        $result = $date->add(['month' => 1]);
        $this->assertSame(2026, $result->year);
        $this->assertSame(3, $result->month);
        $this->assertSame(3, $result->mday);
    }

    public function testAddMultipleParts(): void
    {
        $date = new Horde_Date('2026-04-17 10:00:00');
        $result = $date->add(['year' => 1, 'month' => 2, 'mday' => 5]);
        $this->assertSame(2027, $result->year);
        $this->assertSame(6, $result->month);
        $this->assertSame(22, $result->mday);
    }

    public function testSubtractMonthArray(): void
    {
        $date = new Horde_Date('2026-03-31 12:00:00');
        $result = $date->sub(['month' => 1]);
        $this->assertSame(2026, $result->year);
        $this->assertSame(2, $result->month);
        $this->assertSame(28, $result->mday);
    }

    // =========================================================================
    // Leap year boundary corrections
    // =========================================================================

    public function testAddMonthFromJan31ToFebLeapYear(): void
    {
        $date = new Horde_Date('2024-01-31 12:00:00');
        $result = $date->add(['month' => 1]);
        $this->assertSame(3, $result->month);
        $this->assertSame(2, $result->mday);
    }

    public function testAddMonthFromJan31ToFebNonLeapYear(): void
    {
        $date = new Horde_Date('2025-01-31 12:00:00');
        $result = $date->add(['month' => 1]);
        $this->assertSame(3, $result->month);
        $this->assertSame(3, $result->mday);
    }

    public function testAddYearFromFeb29(): void
    {
        $date = new Horde_Date('2024-02-29 12:00:00');
        $result = $date->add(['year' => 1]);
        $this->assertSame(2025, $result->year);
        $this->assertSame(3, $result->month);
        $this->assertSame(1, $result->mday);
    }

    // =========================================================================
    // Large day values (year-spanning)
    // =========================================================================

    public function testAddManyDays(): void
    {
        $date = new Horde_Date('2026-01-01 00:00:00');
        $result = $date->add(['mday' => 365]);
        $this->assertSame(2027, $result->year);
        $this->assertSame(1, $result->month);
        $this->assertSame(1, $result->mday);
    }

    public function testAddManyDaysLeapYear(): void
    {
        $date = new Horde_Date('2024-01-01 00:00:00');
        $result = $date->add(['mday' => 366]);
        $this->assertSame(2025, $result->year);
        $this->assertSame(1, $result->month);
        $this->assertSame(1, $result->mday);
    }

    // =========================================================================
    // Multiple cascades in one operation
    // =========================================================================

    #[DataProvider('cascadeProvider')]
    public function testCascadeScenarios(
        string $input,
        int $addSeconds,
        string $expected,
    ): void {
        $date = new Horde_Date($input);
        $result = $date->add($addSeconds);
        $this->assertSame($expected, $result->format('Y-m-d H:i:s'));
    }

    public static function cascadeProvider(): array
    {
        return [
            'no cascade' => [
                '2026-04-17 10:30:00', 15, '2026-04-17 10:30:15',
            ],
            'sec→min' => [
                '2026-04-17 10:30:45', 30, '2026-04-17 10:31:15',
            ],
            'sec→min→hour' => [
                '2026-04-17 10:59:45', 30, '2026-04-17 11:00:15',
            ],
            'sec→min→hour→day' => [
                '2026-04-17 23:59:45', 30, '2026-04-18 00:00:15',
            ],
            'year boundary forward' => [
                '2026-12-31 23:59:30', 60, '2027-01-01 00:00:30',
            ],
            'year boundary backward' => [
                '2027-01-01 00:00:00', -1, '2026-12-31 23:59:59',
            ],
            'feb boundary non-leap' => [
                '2025-02-28 23:59:30', 60, '2025-03-01 00:00:30',
            ],
            'feb boundary leap' => [
                '2024-02-28 23:59:30', 60, '2024-02-29 00:00:30',
            ],
            'one full day' => [
                '2026-04-17 12:00:00', 86400, '2026-04-18 12:00:00',
            ],
            'negative full day' => [
                '2026-04-17 12:00:00', -86400, '2026-04-16 12:00:00',
            ],
        ];
    }

    // =========================================================================
    // Correction preserves time when only date parts change
    // =========================================================================

    public function testMonthOverflowPreservesTime(): void
    {
        $date = new Horde_Date('2026-04-17 14:30:45');
        $date->month = 13;
        $this->assertSame(2027, $date->year);
        $this->assertSame(1, $date->month);
        $this->assertSame(14, $date->hour);
        $this->assertSame(30, $date->min);
        $this->assertSame(45, $date->sec);
    }

    public function testDayOverflowPreservesTime(): void
    {
        $date = new Horde_Date('2026-04-17 08:15:30');
        $date->mday = 32;
        $this->assertSame(5, $date->month);
        $this->assertSame(2, $date->mday);
        $this->assertSame(8, $date->hour);
        $this->assertSame(15, $date->min);
        $this->assertSame(30, $date->sec);
    }

    // =========================================================================
    // Edge: large negative seconds
    // =========================================================================

    public function testLargeNegativeSeconds(): void
    {
        $date = new Horde_Date('2026-04-17 12:00:00');
        $result = $date->sub(86400 * 7);
        $this->assertSame('2026-04-10', $result->format('Y-m-d'));
        $this->assertSame(12, $result->hour);
        $this->assertSame(0, $result->min);
        $this->assertSame(0, $result->sec);
    }
}
