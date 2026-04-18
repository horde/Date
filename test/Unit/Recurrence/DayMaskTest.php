<?php

declare(strict_types=1);

namespace Horde\Date\Test\Unit\Recurrence;

use Horde\Date\Recurrence\DayMask;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DayMask::class)]
class DayMaskTest extends TestCase
{
    // =========================================================================
    // Constants
    // =========================================================================

    public function testConstantValues(): void
    {
        $this->assertSame(1, DayMask::SUNDAY);
        $this->assertSame(2, DayMask::MONDAY);
        $this->assertSame(4, DayMask::TUESDAY);
        $this->assertSame(8, DayMask::WEDNESDAY);
        $this->assertSame(16, DayMask::THURSDAY);
        $this->assertSame(32, DayMask::FRIDAY);
        $this->assertSame(64, DayMask::SATURDAY);
    }

    public function testCompositeConstants(): void
    {
        $this->assertSame(62, DayMask::WEEKDAYS);
        $this->assertSame(65, DayMask::WEEKEND);
        $this->assertSame(127, DayMask::ALL_DAYS);
    }

    public function testWeekdaysCombination(): void
    {
        $computed = DayMask::MONDAY | DayMask::TUESDAY | DayMask::WEDNESDAY
            | DayMask::THURSDAY | DayMask::FRIDAY;
        $this->assertSame(DayMask::WEEKDAYS, $computed);
    }

    public function testWeekendCombination(): void
    {
        $computed = DayMask::SUNDAY | DayMask::SATURDAY;
        $this->assertSame(DayMask::WEEKEND, $computed);
    }

    public function testAllDaysCombination(): void
    {
        $computed = DayMask::WEEKDAYS | DayMask::WEEKEND;
        $this->assertSame(DayMask::ALL_DAYS, $computed);
    }

    // =========================================================================
    // includes()
    // =========================================================================

    public function testIncludesTrue(): void
    {
        $this->assertTrue(DayMask::includes(DayMask::WEEKDAYS, DayMask::MONDAY));
        $this->assertTrue(DayMask::includes(DayMask::ALL_DAYS, DayMask::SUNDAY));
    }

    public function testIncludesFalse(): void
    {
        $this->assertFalse(DayMask::includes(DayMask::WEEKDAYS, DayMask::SUNDAY));
        $this->assertFalse(DayMask::includes(DayMask::WEEKEND, DayMask::MONDAY));
    }

    public function testIncludesZeroMask(): void
    {
        $this->assertFalse(DayMask::includes(0, DayMask::MONDAY));
    }

    // =========================================================================
    // fromDays()
    // =========================================================================

    public function testFromDaysSingle(): void
    {
        $this->assertSame(DayMask::MONDAY, DayMask::fromDays(DayMask::MONDAY));
    }

    public function testFromDaysMultiple(): void
    {
        $mask = DayMask::fromDays(DayMask::MONDAY, DayMask::FRIDAY);
        $this->assertSame(DayMask::MONDAY | DayMask::FRIDAY, $mask);
    }

    public function testFromDaysEmpty(): void
    {
        $this->assertSame(0, DayMask::fromDays());
    }

    // =========================================================================
    // fromDayOfWeek()
    // =========================================================================

    #[DataProvider('dayOfWeekProvider')]
    public function testFromDayOfWeek(int $dayOfWeek, int $expectedBit): void
    {
        $this->assertSame($expectedBit, DayMask::fromDayOfWeek($dayOfWeek));
    }

    public static function dayOfWeekProvider(): array
    {
        return [
            'Sunday (0)' => [0, DayMask::SUNDAY],
            'Monday (1)' => [1, DayMask::MONDAY],
            'Tuesday (2)' => [2, DayMask::TUESDAY],
            'Wednesday (3)' => [3, DayMask::WEDNESDAY],
            'Thursday (4)' => [4, DayMask::THURSDAY],
            'Friday (5)' => [5, DayMask::FRIDAY],
            'Saturday (6)' => [6, DayMask::SATURDAY],
        ];
    }

    public function testFromDayOfWeekInvalidThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DayMask::fromDayOfWeek(7);
    }

    public function testFromDayOfWeekNegativeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DayMask::fromDayOfWeek(-1);
    }

    // =========================================================================
    // toDays()
    // =========================================================================

    public function testToDaysSingle(): void
    {
        $this->assertSame([DayMask::WEDNESDAY], DayMask::toDays(DayMask::WEDNESDAY));
    }

    public function testToDaysMultiple(): void
    {
        $days = DayMask::toDays(DayMask::WEEKEND);
        $this->assertSame([DayMask::SUNDAY, DayMask::SATURDAY], $days);
    }

    public function testToDaysEmpty(): void
    {
        $this->assertSame([], DayMask::toDays(0));
    }

    public function testToDaysAllDays(): void
    {
        $days = DayMask::toDays(DayMask::ALL_DAYS);
        $this->assertCount(7, $days);
    }

    // =========================================================================
    // count()
    // =========================================================================

    public function testCountSingle(): void
    {
        $this->assertSame(1, DayMask::count(DayMask::MONDAY));
    }

    public function testCountWeekdays(): void
    {
        $this->assertSame(5, DayMask::count(DayMask::WEEKDAYS));
    }

    public function testCountAllDays(): void
    {
        $this->assertSame(7, DayMask::count(DayMask::ALL_DAYS));
    }

    public function testCountZero(): void
    {
        $this->assertSame(0, DayMask::count(0));
    }

    public function testCountWeekend(): void
    {
        $this->assertSame(2, DayMask::count(DayMask::WEEKEND));
    }

    // =========================================================================
    // fromRfc5545Days()
    // =========================================================================

    public function testFromRfc5545DaysSingle(): void
    {
        $this->assertSame(DayMask::MONDAY, DayMask::fromRfc5545Days(['MO']));
    }

    public function testFromRfc5545DaysMultiple(): void
    {
        $mask = DayMask::fromRfc5545Days(['MO', 'WE', 'FR']);
        $this->assertSame(
            DayMask::MONDAY | DayMask::WEDNESDAY | DayMask::FRIDAY,
            $mask
        );
    }

    public function testFromRfc5545DaysCaseInsensitive(): void
    {
        $this->assertSame(DayMask::TUESDAY, DayMask::fromRfc5545Days(['tu']));
    }

    public function testFromRfc5545DaysInvalidThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DayMask::fromRfc5545Days(['XX']);
    }

    public function testFromRfc5545DaysEmpty(): void
    {
        $this->assertSame(0, DayMask::fromRfc5545Days([]));
    }

    // =========================================================================
    // toRfc5545Days()
    // =========================================================================

    public function testToRfc5545DaysSingle(): void
    {
        $this->assertSame(['MO'], DayMask::toRfc5545Days(DayMask::MONDAY));
    }

    public function testToRfc5545DaysMultiple(): void
    {
        $days = DayMask::toRfc5545Days(DayMask::WEEKEND);
        $this->assertSame(['SU', 'SA'], $days);
    }

    public function testToRfc5545DaysWeekdays(): void
    {
        $days = DayMask::toRfc5545Days(DayMask::WEEKDAYS);
        $this->assertSame(['MO', 'TU', 'WE', 'TH', 'FR'], $days);
    }

    public function testToRfc5545DaysEmpty(): void
    {
        $this->assertSame([], DayMask::toRfc5545Days(0));
    }

    // =========================================================================
    // Round-trip: fromRfc5545Days ↔ toRfc5545Days
    // =========================================================================

    public function testRfc5545RoundTrip(): void
    {
        $input = ['MO', 'WE', 'FR'];
        $mask = DayMask::fromRfc5545Days($input);
        $output = DayMask::toRfc5545Days($mask);
        $this->assertSame($input, $output);
    }

    public function testRfc5545RoundTripAllDays(): void
    {
        $input = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
        $mask = DayMask::fromRfc5545Days($input);
        $output = DayMask::toRfc5545Days($mask);
        $this->assertSame($input, $output);
    }
}
