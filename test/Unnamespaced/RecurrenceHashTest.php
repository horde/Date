<?php

declare(strict_types=1);

namespace Horde\Date\Test;

use Horde_Date;
use Horde_Date_Recurrence;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Date_Recurrence::class)]
class RecurrenceHashTest extends TestCase
{
    private function makeRecurrence(
        string $start = '2026-04-17 10:00:00',
        string $timezone = 'UTC',
    ): Horde_Date_Recurrence {
        return new Horde_Date_Recurrence(new Horde_Date($start, $timezone));
    }

    // =========================================================================
    // Basic round-trip: toHash() → fromHash() preserves all fields
    // =========================================================================

    public function testRoundTripNone(): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_NONE);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame(Horde_Date_Recurrence::RECUR_NONE, $restored->getRecurType());
        $this->assertSame($r->start->format('Y-m-d H:i:s'), $restored->start->format('Y-m-d H:i:s'));
    }

    public function testRoundTripDaily(): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(3);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame(Horde_Date_Recurrence::RECUR_DAILY, $restored->getRecurType());
        $this->assertSame(3, $restored->getRecurInterval());
    }

    public function testRoundTripWeekly(): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(2);
        $r->setRecurOnDay(Horde_Date::MASK_MONDAY | Horde_Date::MASK_WEDNESDAY | Horde_Date::MASK_FRIDAY);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame(Horde_Date_Recurrence::RECUR_WEEKLY, $restored->getRecurType());
        $this->assertSame(2, $restored->getRecurInterval());
        $expectedMask = Horde_Date::MASK_MONDAY | Horde_Date::MASK_WEDNESDAY | Horde_Date::MASK_FRIDAY;
        $this->assertSame($expectedMask, $restored->getRecurOnDays());
    }

    public function testRoundTripMonthlyDate(): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_DATE);
        $r->setRecurInterval(1);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame(Horde_Date_Recurrence::RECUR_MONTHLY_DATE, $restored->getRecurType());
        $this->assertSame(1, $restored->getRecurInterval());
    }

    public function testRoundTripMonthlyWeekday(): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY);
        $r->setRecurInterval(1);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY, $restored->getRecurType());
    }

    public function testRoundTripMonthlyLastWeekday(): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_LAST_WEEKDAY);
        $r->setRecurInterval(1);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame(Horde_Date_Recurrence::RECUR_MONTHLY_LAST_WEEKDAY, $restored->getRecurType());
    }

    public function testRoundTripYearlyDate(): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE);
        $r->setRecurInterval(1);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame(Horde_Date_Recurrence::RECUR_YEARLY_DATE, $restored->getRecurType());
    }

    public function testRoundTripYearlyDay(): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DAY);
        $r->setRecurInterval(2);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame(Horde_Date_Recurrence::RECUR_YEARLY_DAY, $restored->getRecurType());
        $this->assertSame(2, $restored->getRecurInterval());
    }

    public function testRoundTripYearlyWeekday(): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY);
        $r->setRecurInterval(1);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY, $restored->getRecurType());
    }

    // =========================================================================
    // End date round-trip
    // =========================================================================

    public function testRoundTripWithEndDate(): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);
        $r->recurEnd = new Horde_Date('2026-12-31 23:59:59', 'UTC');

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertTrue($restored->hasRecurEnd());
        $this->assertSame(
            '2026-12-31 23:59:59',
            $restored->recurEnd->format('Y-m-d H:i:s')
        );
    }

    public function testRoundTripWithoutEndDate(): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);

        $hash = $r->toHash();
        $this->assertNull($hash['end']);

        $restored = Horde_Date_Recurrence::fromHash($hash);
        $this->assertFalse($restored->hasRecurEnd());
    }

    // =========================================================================
    // Count round-trip
    // =========================================================================

    public function testRoundTripWithCount(): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(1);
        $r->setRecurOnDay(Horde_Date::MASK_TUESDAY);
        $r->setRecurCount(10);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame(10, $restored->getRecurCount());
    }

    // =========================================================================
    // Exceptions round-trip
    // =========================================================================

    public function testRoundTripWithExceptions(): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);
        $r->addException(2026, 4, 20);
        $r->addException(2026, 4, 25);
        $r->addException(2026, 5, 1);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $exceptions = $restored->getExceptions();
        $this->assertCount(3, $exceptions);
        $this->assertContains('20260420', $exceptions);
        $this->assertContains('20260425', $exceptions);
        $this->assertContains('20260501', $exceptions);
    }

    public function testRoundTripEmptyExceptions(): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertEmpty($restored->getExceptions());
    }

    // =========================================================================
    // Completions round-trip
    // =========================================================================

    public function testRoundTripWithCompletions(): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);
        $r->addCompletion(2026, 4, 17);
        $r->addCompletion(2026, 4, 18);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $completions = $restored->getCompletions();
        $this->assertCount(2, $completions);
        $this->assertContains('20260417', $completions);
        $this->assertContains('20260418', $completions);
    }

    // =========================================================================
    // Start date and timezone preservation
    // =========================================================================

    public function testRoundTripPreservesStartTimezone(): void
    {
        $r = $this->makeRecurrence('2026-04-17 14:30:00', 'America/New_York');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame('America/New_York', $restored->start->timezone);
        $this->assertSame('2026-04-17 14:30:00', $restored->start->format('Y-m-d H:i:s'));
    }

    public function testRoundTripPreservesEndTimezone(): void
    {
        $r = $this->makeRecurrence('2026-01-01 09:00:00', 'Europe/Berlin');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(1);
        $r->setRecurOnDay(Horde_Date::MASK_MONDAY);
        $r->recurEnd = new Horde_Date('2026-06-30 23:59:59', 'Europe/Berlin');

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame('Europe/Berlin', $restored->recurEnd->timezone);
    }

    // =========================================================================
    // Hash structure validation
    // =========================================================================

    public function testToHashKeys(): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);

        $hash = $r->toHash();

        $this->assertArrayHasKey('start', $hash);
        $this->assertArrayHasKey('end', $hash);
        $this->assertArrayHasKey('count', $hash);
        $this->assertArrayHasKey('type', $hash);
        $this->assertArrayHasKey('interval', $hash);
        $this->assertArrayHasKey('data', $hash);
        $this->assertArrayHasKey('exceptions', $hash);
        $this->assertArrayHasKey('completions', $hash);
    }

    public function testToHashStartFormat(): void
    {
        $r = $this->makeRecurrence('2026-04-17 10:00:00', 'UTC');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);

        $hash = $r->toHash();

        $this->assertStringContainsString('2026-04-17 10:00:00', $hash['start']);
        $this->assertStringContainsString('/', $hash['start']);
    }

    // =========================================================================
    // Complex round-trip: weekly with day mask, end, exceptions, completions
    // =========================================================================

    public function testComplexRoundTrip(): void
    {
        $r = $this->makeRecurrence('2026-01-05 09:00:00', 'America/Chicago');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(2);
        $r->setRecurOnDay(
            Horde_Date::MASK_MONDAY | Horde_Date::MASK_WEDNESDAY | Horde_Date::MASK_FRIDAY
        );
        $r->recurEnd = new Horde_Date('2026-07-31 23:59:59', 'America/Chicago');
        $r->addException(2026, 1, 19);
        $r->addException(2026, 5, 25);
        $r->addCompletion(2026, 1, 5);
        $r->addCompletion(2026, 1, 7);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame(Horde_Date_Recurrence::RECUR_WEEKLY, $restored->getRecurType());
        $this->assertSame(2, $restored->getRecurInterval());
        $this->assertSame(
            Horde_Date::MASK_MONDAY | Horde_Date::MASK_WEDNESDAY | Horde_Date::MASK_FRIDAY,
            $restored->getRecurOnDays()
        );
        $this->assertTrue($restored->hasRecurEnd());
        $this->assertSame('2026-07-31 23:59:59', $restored->recurEnd->format('Y-m-d H:i:s'));
        $this->assertCount(2, $restored->getExceptions());
        $this->assertCount(2, $restored->getCompletions());
        $this->assertSame('America/Chicago', $restored->start->timezone);
    }

    // =========================================================================
    // All recurrence types via DataProvider
    // =========================================================================

    #[DataProvider('recurrenceTypeProvider')]
    public function testRoundTripPreservesType(int $type, string $label): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType($type);
        $r->setRecurInterval(1);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame(
            $type,
            $restored->getRecurType(),
            "Round-trip failed for recurrence type: $label"
        );
    }

    public static function recurrenceTypeProvider(): array
    {
        return [
            'RECUR_NONE'                => [Horde_Date_Recurrence::RECUR_NONE, 'RECUR_NONE'],
            'RECUR_DAILY'               => [Horde_Date_Recurrence::RECUR_DAILY, 'RECUR_DAILY'],
            'RECUR_WEEKLY'              => [Horde_Date_Recurrence::RECUR_WEEKLY, 'RECUR_WEEKLY'],
            'RECUR_MONTHLY_DATE'        => [Horde_Date_Recurrence::RECUR_MONTHLY_DATE, 'RECUR_MONTHLY_DATE'],
            'RECUR_MONTHLY_WEEKDAY'     => [Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY, 'RECUR_MONTHLY_WEEKDAY'],
            'RECUR_MONTHLY_LAST_WEEKDAY' => [Horde_Date_Recurrence::RECUR_MONTHLY_LAST_WEEKDAY, 'RECUR_MONTHLY_LAST_WEEKDAY'],
            'RECUR_YEARLY_DATE'         => [Horde_Date_Recurrence::RECUR_YEARLY_DATE, 'RECUR_YEARLY_DATE'],
            'RECUR_YEARLY_DAY'          => [Horde_Date_Recurrence::RECUR_YEARLY_DAY, 'RECUR_YEARLY_DAY'],
            'RECUR_YEARLY_WEEKDAY'      => [Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY, 'RECUR_YEARLY_WEEKDAY'],
        ];
    }

    // =========================================================================
    // Interval round-trip edge cases
    // =========================================================================

    #[DataProvider('intervalProvider')]
    public function testRoundTripPreservesInterval(int $interval): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval($interval);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame($interval, $restored->getRecurInterval());
    }

    public static function intervalProvider(): array
    {
        return [
            'every day'       => [1],
            'every 2 days'    => [2],
            'every 7 days'    => [7],
            'every 30 days'   => [30],
            'every 365 days'  => [365],
        ];
    }

    // =========================================================================
    // Day mask round-trip for all individual days
    // =========================================================================

    #[DataProvider('dayMaskProvider')]
    public function testRoundTripPreservesDayMask(int $mask, string $label): void
    {
        $r = $this->makeRecurrence();
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(1);
        $r->setRecurOnDay($mask);

        $hash = $r->toHash();
        $restored = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame(
            $mask,
            $restored->getRecurOnDays(),
            "Day mask mismatch for $label"
        );
    }

    public static function dayMaskProvider(): array
    {
        return [
            'Sunday'    => [Horde_Date::MASK_SUNDAY, 'Sunday'],
            'Monday'    => [Horde_Date::MASK_MONDAY, 'Monday'],
            'Tuesday'   => [Horde_Date::MASK_TUESDAY, 'Tuesday'],
            'Wednesday' => [Horde_Date::MASK_WEDNESDAY, 'Wednesday'],
            'Thursday'  => [Horde_Date::MASK_THURSDAY, 'Thursday'],
            'Friday'    => [Horde_Date::MASK_FRIDAY, 'Friday'],
            'Saturday'  => [Horde_Date::MASK_SATURDAY, 'Saturday'],
            'Weekdays'  => [Horde_Date::MASK_WEEKDAYS, 'Weekdays'],
            'Weekend'   => [Horde_Date::MASK_WEEKEND, 'Weekend'],
            'All days'  => [Horde_Date::MASK_ALLDAYS, 'All days'],
            'Mon+Wed+Fri' => [
                Horde_Date::MASK_MONDAY | Horde_Date::MASK_WEDNESDAY | Horde_Date::MASK_FRIDAY,
                'Mon+Wed+Fri',
            ],
            'Tue+Thu' => [
                Horde_Date::MASK_TUESDAY | Horde_Date::MASK_THURSDAY,
                'Tue+Thu',
            ],
        ];
    }
}
