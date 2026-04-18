<?php

declare(strict_types=1);

namespace Horde\Date\Test;

use Horde_Date;
use Horde_Date_Recurrence;
use Horde_Icalendar;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Horde_Date_Recurrence::class)]
class RecurrenceFullTest extends TestCase
{
    private string $oldTimezone;

    protected function setUp(): void
    {
        $this->oldTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->oldTimezone);
    }

    private function collectRecurrences(Horde_Date_Recurrence $r, string $afterDate, int $limit = 30): array
    {
        $dates = [];
        $next = new Horde_Date($afterDate);
        while ($next = $r->nextRecurrence($next)) {
            if (count($dates) >= $limit) {
                break;
            }
            $dates[] = $next->format('Y-m-d');
            $next->mday++;
        }
        return $dates;
    }

    // =========================================================================
    // Section 1: Property Getters / Setters
    // =========================================================================

    public function testReset(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(3);
        $r->setRecurCount(10);
        $r->setRecurOnDay(Horde_Date::MASK_MONDAY);
        $r->addException(2026, 1, 5);
        $r->addCompletion(2026, 1, 6);

        $r->reset();

        $this->assertSame(Horde_Date_Recurrence::RECUR_NONE, $r->getRecurType());
        $this->assertSame(1, $r->getRecurInterval());
        $this->assertNull($r->getRecurCount());
        $this->assertNull($r->getRecurEnd());
        $this->assertNull($r->recurData);
        $this->assertSame([], $r->getExceptions());
        $this->assertSame([], $r->getCompletions());
    }

    public function testHasRecurType(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);

        $this->assertTrue($r->hasRecurType(Horde_Date_Recurrence::RECUR_WEEKLY));
        $this->assertFalse($r->hasRecurType(Horde_Date_Recurrence::RECUR_DAILY));
        $this->assertFalse($r->hasRecurType(Horde_Date_Recurrence::RECUR_NONE));
    }

    #[DataProvider('recurTypeProvider')]
    public function testSetGetRecurType(int $type): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType($type);
        $this->assertSame($type, $r->getRecurType());
    }

    public static function recurTypeProvider(): array
    {
        return [
            'NONE'                  => [Horde_Date_Recurrence::RECUR_NONE],
            'DAILY'                 => [Horde_Date_Recurrence::RECUR_DAILY],
            'WEEKLY'                => [Horde_Date_Recurrence::RECUR_WEEKLY],
            'MONTHLY_DATE'          => [Horde_Date_Recurrence::RECUR_MONTHLY_DATE],
            'MONTHLY_WEEKDAY'       => [Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY],
            'YEARLY_DATE'           => [Horde_Date_Recurrence::RECUR_YEARLY_DATE],
            'YEARLY_DAY'            => [Horde_Date_Recurrence::RECUR_YEARLY_DAY],
            'YEARLY_WEEKDAY'        => [Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY],
            'MONTHLY_LAST_WEEKDAY'  => [Horde_Date_Recurrence::RECUR_MONTHLY_LAST_WEEKDAY],
        ];
    }

    #[DataProvider('intervalProvider')]
    public function testSetGetRecurInterval(int $interval): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurInterval($interval);
        $this->assertSame($interval, $r->getRecurInterval());
    }

    public static function intervalProvider(): array
    {
        return [
            '1'  => [1],
            '2'  => [2],
            '7'  => [7],
            '30' => [30],
        ];
    }

    public function testSetRecurIntervalZeroIgnored(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurInterval(5);
        $r->setRecurInterval(0);
        $this->assertSame(5, $r->getRecurInterval());
    }

    public function testSetRecurIntervalNegativeIgnored(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurInterval(3);
        $r->setRecurInterval(-1);
        $this->assertSame(3, $r->getRecurInterval());
    }

    public function testSetGetRecurCount(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurCount(10);
        $this->assertSame(10, $r->getRecurCount());
        $this->assertTrue($r->hasRecurCount());
    }

    public function testSetRecurCountNegativeSetsNull(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurCount(5);
        $r->setRecurCount(-1);
        $this->assertNull($r->getRecurCount());
        $this->assertFalse($r->hasRecurCount());
    }

    public function testSetRecurCountZeroSetsNull(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurCount(5);
        $r->setRecurCount(0);
        $this->assertNull($r->getRecurCount());
    }

    public function testCountAndEndMutuallyExclusive(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');

        $r->setRecurCount(10);
        $this->assertSame(10, $r->getRecurCount());
        $this->assertNull($r->getRecurEnd());

        $r->setRecurEnd(new Horde_Date('2026-12-31'));
        $this->assertNull($r->getRecurCount());
        $this->assertNotNull($r->getRecurEnd());

        $r->setRecurCount(5);
        $this->assertSame(5, $r->getRecurCount());
        $this->assertNull($r->getRecurEnd());
    }

    public function testRecurOnDay(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurOnDay(Horde_Date::MASK_MONDAY | Horde_Date::MASK_WEDNESDAY);

        $this->assertTrue((bool) $r->recurOnDay(Horde_Date::MASK_MONDAY));
        $this->assertTrue((bool) $r->recurOnDay(Horde_Date::MASK_WEDNESDAY));
        $this->assertFalse((bool) $r->recurOnDay(Horde_Date::MASK_TUESDAY));
        $this->assertFalse((bool) $r->recurOnDay(Horde_Date::MASK_FRIDAY));
    }

    public function testSetGetRecurOnDays(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $mask = Horde_Date::MASK_MONDAY | Horde_Date::MASK_FRIDAY;
        $r->setRecurOnDay($mask);
        $this->assertSame($mask, $r->getRecurOnDays());
    }

    public function testHasRecurEndSentinelYear9999(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurEnd(new Horde_Date('9999-12-31 23:59:59'));
        $this->assertFalse($r->hasRecurEnd());
    }

    public function testHasRecurEndNullReturnsFalse(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $this->assertFalse($r->hasRecurEnd());
    }

    public function testHasRecurEndValid(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurEnd(new Horde_Date('2026-12-31'));
        $this->assertTrue($r->hasRecurEnd());
    }

    public function testSetRecurStartClones(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $original = new Horde_Date('2026-06-15 12:00:00');
        $r->setRecurStart($original);
        $original->year = 2099;
        $this->assertSame('2026', $r->getRecurStart()->format('Y'));
    }

    public function testSetRecurEndClones(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $end = new Horde_Date('2026-12-31 23:59:59');
        $r->setRecurEnd($end);
        $end->year = 2099;
        $this->assertSame('2026', $r->getRecurEnd()->format('Y'));
    }

    // =========================================================================
    // Section 2: getRecurName()
    // =========================================================================

    #[DataProvider('recurNameProvider')]
    public function testGetRecurName(int $type, string $expectedSubstring): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType($type);
        $name = $r->getRecurName();
        $this->assertNotEmpty($name);
        $this->assertStringContainsString($expectedSubstring, $name);
    }

    public static function recurNameProvider(): array
    {
        return [
            'NONE'                => [Horde_Date_Recurrence::RECUR_NONE, 'No recurrence'],
            'DAILY'               => [Horde_Date_Recurrence::RECUR_DAILY, 'Daily'],
            'WEEKLY'              => [Horde_Date_Recurrence::RECUR_WEEKLY, 'Weekly'],
            'MONTHLY_DATE'        => [Horde_Date_Recurrence::RECUR_MONTHLY_DATE, 'Monthly'],
            'MONTHLY_WEEKDAY'     => [Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY, 'Monthly'],
            'MONTHLY_LAST_WEEKDAY'=> [Horde_Date_Recurrence::RECUR_MONTHLY_LAST_WEEKDAY, 'Monthly'],
            'YEARLY_DATE'         => [Horde_Date_Recurrence::RECUR_YEARLY_DATE, 'Yearly'],
            'YEARLY_DAY'          => [Horde_Date_Recurrence::RECUR_YEARLY_DAY, 'Yearly'],
            'YEARLY_WEEKDAY'      => [Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY, 'Yearly'],
        ];
    }

    // =========================================================================
    // Section 3: Exception / Completion Management
    // =========================================================================

    public function testAddExceptionIdempotent(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->addException(2026, 3, 15);
        $r->addException(2026, 3, 15);
        $this->assertCount(1, $r->getExceptions());
    }

    public function testDeleteNonexistentExceptionNoOp(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->addException(2026, 3, 15);
        $r->deleteException(2026, 6, 20);
        $this->assertCount(1, $r->getExceptions());
    }

    public function testExceptionsFormat(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->addException(2026, 1, 5);
        $r->addException(2026, 12, 25);
        $this->assertSame(['20260105', '20261225'], $r->getExceptions());
    }

    public function testHasException(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->addException(2026, 3, 15);
        $this->assertTrue($r->hasException(2026, 3, 15));
        $this->assertFalse($r->hasException(2026, 3, 16));
    }

    public function testDeleteException(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->addException(2026, 3, 15);
        $r->addException(2026, 3, 16);
        $r->deleteException(2026, 3, 15);
        $this->assertFalse($r->hasException(2026, 3, 15));
        $this->assertTrue($r->hasException(2026, 3, 16));
    }

    public function testCompletionAllowsDuplicates(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->addCompletion(2026, 3, 15);
        $r->addCompletion(2026, 3, 15);
        $this->assertCount(2, $r->getCompletions());
    }

    public function testHasExceptionFalseForCompletion(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->addCompletion(2026, 3, 15);
        $this->assertFalse($r->hasException(2026, 3, 15));
        $this->assertTrue($r->hasCompletion(2026, 3, 15));
    }

    public function testNextActiveRecurrenceSkipsExceptions(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);
        $r->setRecurEnd(new Horde_Date('2026-01-15 23:59:59'));

        $r->addException(2026, 1, 5);
        $r->addException(2026, 1, 6);

        $next = $r->nextActiveRecurrence(new Horde_Date('2026-01-05'));
        $this->assertInstanceOf(Horde_Date::class, $next);
        $this->assertSame('2026-01-07', $next->format('Y-m-d'));
    }

    public function testNextActiveRecurrenceSkipsCompletions(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);
        $r->setRecurEnd(new Horde_Date('2026-01-15 23:59:59'));

        $r->addCompletion(2026, 1, 5);

        $next = $r->nextActiveRecurrence(new Horde_Date('2026-01-05'));
        $this->assertInstanceOf(Horde_Date::class, $next);
        $this->assertSame('2026-01-06', $next->format('Y-m-d'));
    }

    public function testHasActiveRecurrenceWithAllExcepted(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);
        $r->setRecurEnd(new Horde_Date('2026-01-07 23:59:59'));

        $r->addException(2026, 1, 5);
        $r->addException(2026, 1, 6);
        $r->addException(2026, 1, 7);

        $this->assertFalse($r->hasActiveRecurrence());
    }

    public function testHasActiveRecurrenceWithSomeActive(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);
        $r->setRecurEnd(new Horde_Date('2026-01-07 23:59:59'));

        $r->addException(2026, 1, 5);

        $this->assertTrue($r->hasActiveRecurrence());
    }

    // =========================================================================
    // Section 4: nextRecurrence() Edge Cases
    // =========================================================================

    public function testNextRecurrenceIntervalZero(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->recurInterval = 0;

        $result = $r->nextRecurrence(new Horde_Date('2026-01-02'));
        $this->assertFalse($result);
    }

    public function testNextRecurrenceUnknownType(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->recurType = 99;
        $r->recurInterval = 1;

        $result = $r->nextRecurrence(new Horde_Date('2026-01-02'));
        $this->assertFalse($result);
    }

    public function testNextRecurrenceReturnsStartWhenAfterIsBeforeStart(): void
    {
        $r = new Horde_Date_Recurrence('2026-06-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);

        $result = $r->nextRecurrence(new Horde_Date('2026-01-01'));
        $this->assertInstanceOf(Horde_Date::class, $result);
        $this->assertSame('2026-06-01', $result->format('Y-m-d'));
    }

    public function testNextRecurrenceWeeklyNoDataReturnsFalse(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(1);

        $result = $r->nextRecurrence(new Horde_Date('2026-01-06'));
        $this->assertFalse($result);
    }

    public function testNextRecurrenceDailyPreservesTime(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 14:30:45');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);

        $next = $r->nextRecurrence(new Horde_Date('2026-01-02'));
        $this->assertSame('14', $next->format('H'));
        $this->assertSame('30', $next->format('i'));
        $this->assertSame('45', $next->format('s'));
    }

    public function testNextRecurrenceReturnsClone(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);

        $next1 = $r->nextRecurrence(new Horde_Date('2026-01-02'));
        $next1->year = 2099;

        $next2 = $r->nextRecurrence(new Horde_Date('2026-01-02'));
        $this->assertSame('2026', $next2->format('Y'));
    }

    public function testNextRecurrenceAtExactEnd(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);
        $r->setRecurEnd(new Horde_Date('2026-01-03 10:00:00'));

        $next = $r->nextRecurrence(new Horde_Date('2026-01-03'));
        $this->assertInstanceOf(Horde_Date::class, $next);
        $this->assertSame('2026-01-03', $next->format('Y-m-d'));

        $after = $r->nextRecurrence(new Horde_Date('2026-01-04'));
        $this->assertFalse($after);
    }

    public function testNextRecurrenceTimezoneNormalization(): void
    {
        date_default_timezone_set('America/New_York');
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);

        $after = new Horde_Date('2026-01-02 00:00:00');
        $after->setTimezone('Europe/Berlin');

        $next = $r->nextRecurrence($after);
        $this->assertInstanceOf(Horde_Date::class, $next);
    }

    // =========================================================================
    // Section 5: Monthly Last Weekday Extended
    // =========================================================================

    public function testMonthlyLastWeekdayCount(): void
    {
        // Last Thursday of month, starting 2026-01-29 (last Thu of Jan 2026)
        $r = new Horde_Date_Recurrence('2026-01-29 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_LAST_WEEKDAY);
        $r->setRecurInterval(1);
        $r->setRecurCount(6);

        $dates = $this->collectRecurrences($r, '2026-01-01');
        $this->assertCount(6, $dates);
        $this->assertSame('2026-01-29', $dates[0]);
        $this->assertSame('2026-02-26', $dates[1]);
        $this->assertSame('2026-03-26', $dates[2]);
    }

    public function testMonthlyLastWeekdayEnd(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-29 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_LAST_WEEKDAY);
        $r->setRecurInterval(1);
        $r->setRecurEnd(new Horde_Date('2026-04-30 23:59:59'));

        $dates = $this->collectRecurrences($r, '2026-01-01');
        $this->assertGreaterThanOrEqual(4, count($dates));
        foreach ($dates as $date) {
            $hd = new Horde_Date($date);
            $this->assertLessThanOrEqual(4, (int) $hd->format('m'));
        }
    }

    public function testMonthlyLastWeekdayInterval2(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-29 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_LAST_WEEKDAY);
        $r->setRecurInterval(2);
        $r->setRecurCount(4);

        $dates = $this->collectRecurrences($r, '2026-01-01');
        $this->assertCount(4, $dates);

        $months = array_map(fn($d) => (int) (new Horde_Date($d))->format('m'), $dates);
        $this->assertSame(1, $months[0]);
        $this->assertSame(3, $months[1]);
        $this->assertSame(5, $months[2]);
        $this->assertSame(7, $months[3]);
    }

    public function testMonthlyLastWeekdayFriday(): void
    {
        // Last Friday of Jan 2026 = Jan 30
        $r = new Horde_Date_Recurrence('2026-01-30 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_LAST_WEEKDAY);
        $r->setRecurInterval(1);
        $r->setRecurCount(3);

        $dates = $this->collectRecurrences($r, '2026-01-01');
        $this->assertCount(3, $dates);

        foreach ($dates as $date) {
            $hd = new Horde_Date($date);
            $this->assertSame(
                Horde_Date::DATE_FRIDAY,
                $hd->dayOfWeek(),
                "$date should be a Friday"
            );
        }
    }

    // =========================================================================
    // Section 6: Yearly Types Extended
    // =========================================================================

    public function testYearlyDateFeb29LeapSkip(): void
    {
        // Count check uses year offset, not occurrence count.
        // With count=10 and interval=1, offsets 0,4,8 all pass < 10.
        $r = new Horde_Date_Recurrence('2024-02-29 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE);
        $r->setRecurInterval(1);
        $r->setRecurCount(10);

        $dates = $this->collectRecurrences($r, '2024-01-01');
        $this->assertSame('2024-02-29', $dates[0]);
        $this->assertSame('2028-02-29', $dates[1]);
        $this->assertSame('2032-02-29', $dates[2]);
    }

    public function testYearlyDateInterval2(): void
    {
        // Count check uses offset/interval. With interval=2 and count=4,
        // offsets 0, 2 pass (< 4), but offset 4 fails (>= 4).
        $r = new Horde_Date_Recurrence('2026-06-15 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE);
        $r->setRecurInterval(2);
        $r->setRecurCount(6);

        $dates = $this->collectRecurrences($r, '2026-01-01');
        $this->assertSame('2026-06-15', $dates[0]);
        $this->assertSame('2028-06-15', $dates[1]);
        $this->assertSame('2030-06-15', $dates[2]);
    }

    public function testYearlyDayInterval3(): void
    {
        // Day 100 of 2026 = April 10
        $r = new Horde_Date_Recurrence('2026-04-10 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DAY);
        $r->setRecurInterval(3);
        $r->setRecurCount(3);

        $dates = $this->collectRecurrences($r, '2026-01-01');
        $this->assertCount(3, $dates);
        $this->assertSame('2026-04-10', $dates[0]);

        $year2 = (int) (new Horde_Date($dates[1]))->format('Y');
        $year3 = (int) (new Horde_Date($dates[2]))->format('Y');
        $this->assertSame(2029, $year2);
        $this->assertSame(2032, $year3);
    }

    public function testYearlyWeekdayCount6(): void
    {
        // 1st Thursday of March 2026 = March 5
        $r = new Horde_Date_Recurrence('2026-03-05 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY);
        $r->setRecurInterval(1);
        $r->setRecurCount(6);

        $dates = $this->collectRecurrences($r, '2026-01-01');
        $this->assertCount(6, $dates);

        foreach ($dates as $date) {
            $hd = new Horde_Date($date);
            $this->assertSame(
                Horde_Date::DATE_THURSDAY,
                $hd->dayOfWeek(),
                "$date should be Thursday"
            );
            $this->assertSame('03', $hd->format('m'), "$date should be in March");
        }
    }

    public function testYearlyWeekdayInterval2(): void
    {
        // 1st Thursday of March 2026 = March 5
        $r = new Horde_Date_Recurrence('2026-03-05 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY);
        $r->setRecurInterval(2);
        $r->setRecurCount(3);

        $dates = $this->collectRecurrences($r, '2026-01-01');
        $this->assertCount(3, $dates);

        $years = array_map(fn($d) => (int) (new Horde_Date($d))->format('Y'), $dates);
        $this->assertSame(2026, $years[0]);
        $this->assertSame(2028, $years[1]);
        $this->assertSame(2030, $years[2]);
    }

    public function testYearlyDateLeapYearWithCount(): void
    {
        // With count=10 and interval=1, offsets 0 and 4 pass, giving 2 results
        $r = new Horde_Date_Recurrence('2024-02-29 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE);
        $r->setRecurInterval(1);
        $r->setRecurCount(5);

        $dates = $this->collectRecurrences($r, '2024-01-01');
        $this->assertCount(2, $dates);
        $this->assertSame('2024-02-29', $dates[0]);
        $this->assertSame('2028-02-29', $dates[1]);
    }

    // =========================================================================
    // Section 7: toJson()
    // =========================================================================

    public function testToJsonStructure(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(2);

        $json = $r->toJson();
        $this->assertInstanceOf(stdClass::class, $json);
        $this->assertObjectHasProperty('t', $json);
        $this->assertObjectHasProperty('i', $json);
        $this->assertSame(Horde_Date_Recurrence::RECUR_DAILY, $json->t);
        $this->assertSame(2, $json->i);
    }

    public function testToJsonWithEnd(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);
        $r->setRecurEnd(new Horde_Date('2026-06-30'));

        $json = $r->toJson();
        $this->assertObjectHasProperty('e', $json);
    }

    public function testToJsonWithCount(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);
        $r->setRecurCount(5);

        $json = $r->toJson();
        $this->assertObjectHasProperty('c', $json);
        $this->assertSame(5, $json->c);
    }

    public function testToJsonWeeklyWithExtras(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(1);
        $r->setRecurOnDay(Horde_Date::MASK_MONDAY | Horde_Date::MASK_FRIDAY);
        $r->addException(2026, 1, 12);
        $r->addCompletion(2026, 1, 9);

        $json = $r->toJson();
        $this->assertObjectHasProperty('d', $json);
        $this->assertObjectHasProperty('ex', $json);
        $this->assertObjectHasProperty('co', $json);
        $this->assertSame(Horde_Date::MASK_MONDAY | Horde_Date::MASK_FRIDAY, $json->d);
    }

    // =========================================================================
    // Section 8: isEqual()
    // =========================================================================

    public function testIsEqualSame(): void
    {
        $r1 = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r1->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r1->setRecurInterval(2);
        $r1->setRecurCount(10);

        $r2 = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r2->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r2->setRecurInterval(2);
        $r2->setRecurCount(10);

        $this->assertTrue($r1->isEqual($r2));
    }

    public function testIsEqualDifferentType(): void
    {
        $r1 = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r1->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);

        $r2 = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r2->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);

        $this->assertFalse($r1->isEqual($r2));
    }

    public function testIsEqualDifferentInterval(): void
    {
        $r1 = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r1->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r1->setRecurInterval(1);

        $r2 = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r2->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r2->setRecurInterval(3);

        $this->assertFalse($r1->isEqual($r2));
    }

    public function testIsEqualIgnoresExceptions(): void
    {
        $r1 = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r1->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r1->setRecurInterval(1);

        $r2 = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r2->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r2->setRecurInterval(1);
        $r2->addException(2026, 3, 15);

        $this->assertTrue($r1->isEqual($r2));
    }

    public function testIsEqualDifferentDayMask(): void
    {
        $r1 = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r1->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r1->setRecurOnDay(Horde_Date::MASK_MONDAY);

        $r2 = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r2->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r2->setRecurOnDay(Horde_Date::MASK_FRIDAY);

        $this->assertFalse($r1->isEqual($r2));
    }

    // =========================================================================
    // Section 9: RRULE Round-Trip Consistency
    // =========================================================================

    public function testRRule20RoundTripDaily(): void
    {
        $ical = new Horde_Icalendar();

        $r = new Horde_Date_Recurrence('2026-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(3);
        $r->setRecurCount(10);

        $rrule = $r->toRRule20($ical);
        $this->assertStringContainsString('FREQ=DAILY', $rrule);
        $this->assertStringContainsString('INTERVAL=3', $rrule);
        $this->assertStringContainsString('COUNT=10', $rrule);

        $r2 = new Horde_Date_Recurrence('2026-03-01 10:00:00');
        $r2->fromRRule20($rrule);

        $this->assertSame($r->getRecurType(), $r2->getRecurType());
        $this->assertEquals($r->getRecurInterval(), $r2->getRecurInterval());
        $this->assertEquals($r->getRecurCount(), $r2->getRecurCount());
    }

    public function testRRule20RoundTripWeekly(): void
    {
        $ical = new Horde_Icalendar();

        $r = new Horde_Date_Recurrence('2026-03-02 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(2);
        $r->setRecurOnDay(Horde_Date::MASK_MONDAY | Horde_Date::MASK_WEDNESDAY | Horde_Date::MASK_FRIDAY);
        $r->setRecurCount(12);

        $rrule = $r->toRRule20($ical);
        $this->assertStringContainsString('FREQ=WEEKLY', $rrule);
        $this->assertStringContainsString('BYDAY=', $rrule);

        $r2 = new Horde_Date_Recurrence('2026-03-02 10:00:00');
        $r2->fromRRule20($rrule);

        $this->assertSame($r->getRecurType(), $r2->getRecurType());
        $this->assertEquals($r->getRecurInterval(), $r2->getRecurInterval());
        $this->assertSame($r->getRecurOnDays(), $r2->getRecurOnDays());
    }

    public function testRRule20RoundTripMonthlyDate(): void
    {
        $ical = new Horde_Icalendar();

        $r = new Horde_Date_Recurrence('2026-03-15 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_DATE);
        $r->setRecurInterval(1);
        $r->setRecurCount(6);

        $rrule = $r->toRRule20($ical);

        $r2 = new Horde_Date_Recurrence('2026-03-15 10:00:00');
        $r2->fromRRule20($rrule);

        $this->assertSame(Horde_Date_Recurrence::RECUR_MONTHLY_DATE, $r2->getRecurType());
        $this->assertEquals(1, $r2->getRecurInterval());
    }

    public function testRRule20RoundTripMonthlyLastWeekday(): void
    {
        $ical = new Horde_Icalendar();

        // Last Thursday of month, starting Jan 29 2026
        $r = new Horde_Date_Recurrence('2026-01-29 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_LAST_WEEKDAY);
        $r->setRecurInterval(1);
        $r->setRecurCount(5);

        $rrule = $r->toRRule20($ical);
        $this->assertStringContainsString('BYDAY=-1', $rrule);

        $r2 = new Horde_Date_Recurrence('2026-01-29 10:00:00');
        $r2->fromRRule20($rrule);

        $this->assertSame(Horde_Date_Recurrence::RECUR_MONTHLY_LAST_WEEKDAY, $r2->getRecurType());
    }

    public function testRRule20RoundTripYearlyDate(): void
    {
        $ical = new Horde_Icalendar();

        $r = new Horde_Date_Recurrence('2026-06-15 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE);
        $r->setRecurInterval(1);
        $r->setRecurCount(5);

        $rrule = $r->toRRule20($ical);

        $r2 = new Horde_Date_Recurrence('2026-06-15 10:00:00');
        $r2->fromRRule20($rrule);

        $this->assertSame(Horde_Date_Recurrence::RECUR_YEARLY_DATE, $r2->getRecurType());
        $this->assertEquals(1, $r2->getRecurInterval());
    }

    public function testRRule20RoundTripYearlyDay(): void
    {
        $ical = new Horde_Icalendar();

        $r = new Horde_Date_Recurrence('2026-04-10 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DAY);
        $r->setRecurInterval(1);
        $r->setRecurCount(3);

        $rrule = $r->toRRule20($ical);
        $this->assertStringContainsString('BYYEARDAY=', $rrule);

        $r2 = new Horde_Date_Recurrence('2026-04-10 10:00:00');
        $r2->fromRRule20($rrule);

        $this->assertSame(Horde_Date_Recurrence::RECUR_YEARLY_DAY, $r2->getRecurType());
    }

    public function testRRule20RoundTripYearlyWeekday(): void
    {
        $ical = new Horde_Icalendar();

        // 1st Thursday of March 2026 = March 5
        $r = new Horde_Date_Recurrence('2026-03-05 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY);
        $r->setRecurInterval(1);
        $r->setRecurCount(4);

        $rrule = $r->toRRule20($ical);
        $this->assertStringContainsString('FREQ=YEARLY', $rrule);
        $this->assertStringContainsString('BYDAY=', $rrule);
        $this->assertStringContainsString('BYMONTH=', $rrule);

        $r2 = new Horde_Date_Recurrence('2026-03-05 10:00:00');
        $r2->fromRRule20($rrule);

        $this->assertSame(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY, $r2->getRecurType());
    }

    public function testRRule10RoundTripDaily(): void
    {
        $ical = new Horde_Icalendar();

        $r = new Horde_Date_Recurrence('2026-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(2);
        $r->setRecurCount(5);

        $rrule10 = $r->toRRule10($ical);
        $this->assertStringStartsWith('D2', $rrule10);

        $r2 = new Horde_Date_Recurrence('2026-03-01 10:00:00');
        $r2->fromRRule10($rrule10);

        $this->assertSame(Horde_Date_Recurrence::RECUR_DAILY, $r2->getRecurType());
        $this->assertEquals(2, $r2->getRecurInterval());
    }

    public function testRRule10RoundTripWeekly(): void
    {
        $ical = new Horde_Icalendar();

        $r = new Horde_Date_Recurrence('2026-03-02 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(1);
        $r->setRecurOnDay(Horde_Date::MASK_MONDAY | Horde_Date::MASK_FRIDAY);
        $r->setRecurCount(8);

        $rrule10 = $r->toRRule10($ical);
        $this->assertStringStartsWith('W1', $rrule10);
        $this->assertStringContainsString('MO', $rrule10);
        $this->assertStringContainsString('FR', $rrule10);

        $r2 = new Horde_Date_Recurrence('2026-03-02 10:00:00');
        $r2->fromRRule10($rrule10);

        $this->assertSame(Horde_Date_Recurrence::RECUR_WEEKLY, $r2->getRecurType());
        $this->assertTrue((bool) $r2->recurOnDay(Horde_Date::MASK_MONDAY));
        $this->assertTrue((bool) $r2->recurOnDay(Horde_Date::MASK_FRIDAY));
    }

    public function testRRule10MonthlyLastWeekday(): void
    {
        $ical = new Horde_Icalendar();

        $r = new Horde_Date_Recurrence('2026-01-29 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_LAST_WEEKDAY);
        $r->setRecurInterval(1);
        $r->setRecurCount(3);

        $rrule10 = $r->toRRule10($ical);
        $this->assertStringContainsString('MP', $rrule10);
        $this->assertStringContainsString('1-', $rrule10);

        // Known limitation: fromRRule10 trims the remainder before the regex
        // that detects the minus sign, so LAST_WEEKDAY round-trips as WEEKDAY.
        $r2 = new Horde_Date_Recurrence('2026-01-29 10:00:00');
        $r2->fromRRule10($rrule10);

        $this->assertSame(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY, $r2->getRecurType());
    }

    public function testFromRRule20DailyWithByday(): void
    {
        $r = new Horde_Date_Recurrence('2026-03-02 10:00:00');
        $r->fromRRule20('FREQ=DAILY;BYDAY=MO,TU,WE,TH,FR');

        $this->assertSame(
            Horde_Date_Recurrence::RECUR_WEEKLY,
            $r->getRecurType(),
            'DAILY with BYDAY should be converted to WEEKLY (Thunderbird workaround)'
        );
        $this->assertTrue((bool) $r->recurOnDay(Horde_Date::MASK_MONDAY));
        $this->assertTrue((bool) $r->recurOnDay(Horde_Date::MASK_FRIDAY));
        $this->assertFalse((bool) $r->recurOnDay(Horde_Date::MASK_SATURDAY));
    }

    public function testRRule20NoneReturnsEmpty(): void
    {
        $ical = new Horde_Icalendar();
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_NONE);
        $this->assertSame('', $r->toRRule20($ical));
        $this->assertSame('', $r->toRRule10($ical));
    }

    public function testFromRRule20NoFreqSetsNone(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->fromRRule20('INVALID=RULE');
        $this->assertSame(Horde_Date_Recurrence::RECUR_NONE, $r->getRecurType());
    }

    public function testFromRRule10EmptyNoOp(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->fromRRule10('');
        $this->assertSame(Horde_Date_Recurrence::RECUR_NONE, $r->getRecurType());
    }

    // =========================================================================
    // Section 10: toHash() / fromHash() (supplement existing tests)
    // =========================================================================

    public function testToHashContainsAllKeys(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(2);
        $r->setRecurOnDay(Horde_Date::MASK_MONDAY);
        $r->setRecurCount(10);
        $r->addException(2026, 1, 12);
        $r->addCompletion(2026, 1, 19);

        $hash = $r->toHash();

        $this->assertArrayHasKey('start', $hash);
        $this->assertArrayHasKey('end', $hash);
        $this->assertArrayHasKey('count', $hash);
        $this->assertArrayHasKey('type', $hash);
        $this->assertArrayHasKey('interval', $hash);
        $this->assertArrayHasKey('data', $hash);
        $this->assertArrayHasKey('exceptions', $hash);
        $this->assertArrayHasKey('completions', $hash);

        $this->assertSame(Horde_Date_Recurrence::RECUR_WEEKLY, $hash['type']);
        $this->assertSame(2, $hash['interval']);
        $this->assertSame(10, $hash['count']);
    }

    public function testFromHashRoundTrip(): void
    {
        $r = new Horde_Date_Recurrence('2026-03-15 14:30:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(3);
        $r->setRecurCount(7);
        $r->addException(2026, 3, 18);

        $hash = $r->toHash();
        $r2 = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame($r->getRecurType(), $r2->getRecurType());
        $this->assertSame($r->getRecurInterval(), $r2->getRecurInterval());
        $this->assertSame($r->getRecurCount(), $r2->getRecurCount());
        $this->assertSame($r->getExceptions(), $r2->getExceptions());
    }
}
