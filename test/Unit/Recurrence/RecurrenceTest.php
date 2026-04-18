<?php

declare(strict_types=1);

namespace Horde\Date\Test\Unit\Recurrence;

use DateTimeImmutable;
use DateTimeZone;
use Horde\Date\Recurrence\DayMask;
use Horde\Date\Recurrence\Recurrence;
use Horde\Date\Recurrence\RecurrenceInterface;
use Horde\Date\Recurrence\RecurrenceType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Recurrence::class)]
class RecurrenceTest extends TestCase
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

    private function date(string $date, string $tz = 'UTC'): DateTimeImmutable
    {
        return new DateTimeImmutable($date, new DateTimeZone($tz));
    }

    private function collectRecurrences(Recurrence $r, string $afterDate, int $limit = 30): array
    {
        $dates = [];
        $after = $this->date($afterDate);
        while ($next = $r->nextRecurrence($after)) {
            if (count($dates) >= $limit) {
                break;
            }
            $dates[] = $next->format('Y-m-d');
            $after = $next->modify('+1 day');
        }
        return $dates;
    }

    // =========================================================================
    // Constructor & Interface
    // =========================================================================

    public function testImplementsInterface(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $this->assertInstanceOf(RecurrenceInterface::class, $r);
    }

    public function testConstructorStoresStart(): void
    {
        $start = $this->date('2026-04-18 10:00:00');
        $r = new Recurrence($start);
        $this->assertSame('2026-04-18 10:00:00', $r->getStart()->format('Y-m-d H:i:s'));
    }

    public function testConstructorAcceptsMutableDateTime(): void
    {
        $dt = new \DateTime('2026-04-18 10:00:00', new DateTimeZone('UTC'));
        $r = new Recurrence($dt);
        $this->assertInstanceOf(DateTimeImmutable::class, $r->getStart());
        $this->assertSame('2026-04-18', $r->getStart()->format('Y-m-d'));
    }

    public function testDefaultState(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $this->assertSame(RecurrenceType::None, $r->getType());
        $this->assertSame(1, $r->getInterval());
        $this->assertNull($r->getEnd());
        $this->assertNull($r->getCount());
        $this->assertSame(0, $r->getDayMask());
    }

    // =========================================================================
    // Setters & Reset
    // =========================================================================

    public function testSetType(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Weekly);
        $this->assertSame(RecurrenceType::Weekly, $r->getType());
    }

    public function testSetInterval(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setInterval(3);
        $this->assertSame(3, $r->getInterval());
    }

    public function testSetIntervalAcceptsZero(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setInterval(5);
        $r->setInterval(0);
        $this->assertSame(0, $r->getInterval());
    }

    public function testSetIntervalIgnoresNegative(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setInterval(5);
        $r->setInterval(-1);
        $this->assertSame(5, $r->getInterval());
    }

    public function testSetEndClearsCount(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setCount(10);
        $r->setEnd($this->date('2026-12-31'));
        $this->assertNull($r->getCount());
        $this->assertSame('2026-12-31', $r->getEnd()->format('Y-m-d'));
    }

    public function testSetCountClearsEnd(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setEnd($this->date('2026-12-31'));
        $r->setCount(10);
        $this->assertNull($r->getEnd());
        $this->assertSame(10, $r->getCount());
    }

    public function testSetCountNullClears(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setCount(10);
        $r->setCount(null);
        $this->assertNull($r->getCount());
    }

    public function testSetCountZeroClears(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setCount(10);
        $r->setCount(0);
        $this->assertNull($r->getCount());
    }

    public function testSetStart(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setStart($this->date('2026-06-15 08:00:00'));
        $this->assertSame('2026-06-15 08:00:00', $r->getStart()->format('Y-m-d H:i:s'));
    }

    public function testSetDayMask(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setDayMask(DayMask::MONDAY | DayMask::FRIDAY);
        $this->assertSame(DayMask::MONDAY | DayMask::FRIDAY, $r->getDayMask());
    }

    public function testReset(): void
    {
        $r = new Recurrence($this->date('2026-01-01 10:00:00'));
        $r->setType(RecurrenceType::Daily);
        $r->setInterval(3);
        $r->setCount(10);
        $r->setDayMask(DayMask::MONDAY);
        $r->addException($this->date('2026-01-05'));
        $r->addCompletion($this->date('2026-01-06'));

        $r->reset();

        $this->assertSame(RecurrenceType::None, $r->getType());
        $this->assertSame(1, $r->getInterval());
        $this->assertNull($r->getCount());
        $this->assertNull($r->getEnd());
        $this->assertSame(0, $r->getDayMask());
        $this->assertSame([], $r->getExceptions());
        $this->assertSame([], $r->getCompletions());
    }

    public function testHasEnd(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $this->assertFalse($r->hasEnd());

        $r->setEnd($this->date('2026-12-31'));
        $this->assertTrue($r->hasEnd());

        $r->setEnd($this->date('9999-12-31'));
        $this->assertFalse($r->hasEnd());
    }

    public function testHasCount(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $this->assertFalse($r->hasCount());

        $r->setCount(5);
        $this->assertTrue($r->hasCount());
    }

    // =========================================================================
    // Exception / Completion management
    // =========================================================================

    public function testAddAndHasException(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->addException($this->date('2026-01-15'));
        $this->assertTrue($r->hasException($this->date('2026-01-15')));
        $this->assertFalse($r->hasException($this->date('2026-01-16')));
    }

    public function testExceptionDeduplication(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->addException($this->date('2026-01-15'));
        $r->addException($this->date('2026-01-15'));
        $this->assertCount(1, $r->getExceptions());
    }

    public function testDeleteException(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->addException($this->date('2026-01-15'));
        $r->deleteException($this->date('2026-01-15'));
        $this->assertFalse($r->hasException($this->date('2026-01-15')));
        $this->assertSame([], $r->getExceptions());
    }

    public function testGetExceptionsReturnsYyyymmddStrings(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->addException($this->date('2026-01-15'));
        $r->addException($this->date('2026-02-20'));
        $this->assertSame(['20260115', '20260220'], $r->getExceptions());
    }

    public function testAddAndHasCompletion(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->addCompletion($this->date('2026-01-15'));
        $this->assertTrue($r->hasCompletion($this->date('2026-01-15')));
    }

    public function testCompletionAllowsDuplicates(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->addCompletion($this->date('2026-01-15'));
        $r->addCompletion($this->date('2026-01-15'));
        $this->assertCount(2, $r->getCompletions());
    }

    public function testDeleteCompletion(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->addCompletion($this->date('2026-01-15'));
        $r->deleteCompletion($this->date('2026-01-15'));
        $this->assertSame([], $r->getCompletions());
    }

    public function testSetExceptions(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setExceptions(['20260101', '20260201']);
        $this->assertSame(['20260101', '20260201'], $r->getExceptions());
    }

    public function testSetCompletions(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setCompletions(['20260101']);
        $this->assertSame(['20260101'], $r->getCompletions());
    }

    // =========================================================================
    // getRecurName()
    // =========================================================================

    #[DataProvider('recurNameProvider')]
    public function testGetRecurName(RecurrenceType $type, string $expected): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType($type);
        $this->assertSame($expected, $r->getRecurName());
    }

    public static function recurNameProvider(): array
    {
        return [
            'None'               => [RecurrenceType::None, 'No recurrence'],
            'Daily'              => [RecurrenceType::Daily, 'Daily'],
            'Weekly'             => [RecurrenceType::Weekly, 'Weekly'],
            'MonthlyDate'        => [RecurrenceType::MonthlyDate, 'Monthly'],
            'MonthlyWeekday'     => [RecurrenceType::MonthlyWeekday, 'Monthly'],
            'MonthlyLastWeekday' => [RecurrenceType::MonthlyLastWeekday, 'Monthly'],
            'YearlyDate'         => [RecurrenceType::YearlyDate, 'Yearly'],
            'YearlyDay'          => [RecurrenceType::YearlyDay, 'Yearly'],
            'YearlyWeekday'      => [RecurrenceType::YearlyWeekday, 'Yearly'],
        ];
    }

    // =========================================================================
    // nextRecurrence — None / Edge Cases
    // =========================================================================

    public function testNextRecurrenceNoneReturnsNull(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::None);
        $this->assertNull($r->nextRecurrence($this->date('2026-01-02')));
    }

    public function testNextRecurrenceReturnsStartWhenAfterBeforeStart(): void
    {
        $r = new Recurrence($this->date('2026-06-01 10:00:00'));
        $r->setType(RecurrenceType::Daily);
        $r->setInterval(1);
        $next = $r->nextRecurrence($this->date('2026-01-01'));
        $this->assertNotNull($next);
        $this->assertSame('2026-06-01', $next->format('Y-m-d'));
    }

    public function testNextRecurrenceIntervalZeroReturnsNull(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Daily);
        $r->setInterval(5);
        // Force interval to 0 via reflection since setInterval rejects 0
        $ref = new \ReflectionProperty($r, 'interval');
        $ref->setValue($r, 0);
        $this->assertNull($r->nextRecurrence($this->date('2026-01-02')));
    }

    public function testNextRecurrenceReturnsDateTimeImmutable(): void
    {
        $r = new Recurrence($this->date('2026-01-01 09:00:00'));
        $r->setType(RecurrenceType::Daily);
        $next = $r->nextRecurrence($this->date('2026-01-02'));
        $this->assertInstanceOf(DateTimeImmutable::class, $next);
    }

    // =========================================================================
    // Daily recurrence
    // =========================================================================

    public function testDailyBasic(): void
    {
        $r = new Recurrence($this->date('2026-01-01 10:00:00'));
        $r->setType(RecurrenceType::Daily);
        $r->setInterval(1);
        $dates = $this->collectRecurrences($r, '2026-01-01', 5);
        $this->assertSame([
            '2026-01-01', '2026-01-02', '2026-01-03', '2026-01-04', '2026-01-05',
        ], $dates);
    }

    public function testDailyInterval3(): void
    {
        $r = new Recurrence($this->date('2026-01-01 10:00:00'));
        $r->setType(RecurrenceType::Daily);
        $r->setInterval(3);
        $dates = $this->collectRecurrences($r, '2026-01-01', 4);
        $this->assertSame([
            '2026-01-01', '2026-01-04', '2026-01-07', '2026-01-10',
        ], $dates);
    }

    public function testDailyWithEndDate(): void
    {
        $r = new Recurrence($this->date('2026-01-01 10:00:00'));
        $r->setType(RecurrenceType::Daily);
        $r->setInterval(1);
        $r->setEnd($this->date('2026-01-05 23:59:59'));
        $dates = $this->collectRecurrences($r, '2026-01-01');
        $this->assertSame([
            '2026-01-01', '2026-01-02', '2026-01-03', '2026-01-04', '2026-01-05',
        ], $dates);
    }

    public function testDailyWithCount(): void
    {
        $r = new Recurrence($this->date('2026-01-01 10:00:00'));
        $r->setType(RecurrenceType::Daily);
        $r->setInterval(1);
        $r->setCount(3);
        $dates = $this->collectRecurrences($r, '2026-01-01');
        $this->assertSame([
            '2026-01-01', '2026-01-02', '2026-01-03',
        ], $dates);
    }

    public function testDailyPreservesTime(): void
    {
        $r = new Recurrence($this->date('2026-01-01 14:30:00'));
        $r->setType(RecurrenceType::Daily);
        $next = $r->nextRecurrence($this->date('2026-01-02'));
        $this->assertSame('14:30:00', $next->format('H:i:s'));
    }

    // =========================================================================
    // Weekly recurrence
    // =========================================================================

    public function testWeeklyMondayWednesdayFriday(): void
    {
        $r = new Recurrence($this->date('2026-01-05 10:00:00'));
        $r->setType(RecurrenceType::Weekly);
        $r->setInterval(1);
        $r->setDayMask(DayMask::MONDAY | DayMask::WEDNESDAY | DayMask::FRIDAY);
        $dates = $this->collectRecurrences($r, '2026-01-05', 6);
        $this->assertSame([
            '2026-01-05', '2026-01-07', '2026-01-09',
            '2026-01-12', '2026-01-14', '2026-01-16',
        ], $dates);
    }

    public function testWeeklyInterval2(): void
    {
        $r = new Recurrence($this->date('2026-01-05 10:00:00'));
        $r->setType(RecurrenceType::Weekly);
        $r->setInterval(2);
        $r->setDayMask(DayMask::MONDAY);
        $dates = $this->collectRecurrences($r, '2026-01-05', 4);
        $this->assertSame([
            '2026-01-05', '2026-01-19', '2026-02-02', '2026-02-16',
        ], $dates);
    }

    public function testWeeklyNoDayMaskReturnsNull(): void
    {
        $r = new Recurrence($this->date('2026-01-05 10:00:00'));
        $r->setType(RecurrenceType::Weekly);
        $this->assertNull($r->nextRecurrence($this->date('2026-01-06')));
    }

    public function testWeeklyWithEndDate(): void
    {
        $r = new Recurrence($this->date('2026-01-05 10:00:00'));
        $r->setType(RecurrenceType::Weekly);
        $r->setInterval(1);
        $r->setDayMask(DayMask::MONDAY);
        $r->setEnd($this->date('2026-01-20 23:59:59'));
        $dates = $this->collectRecurrences($r, '2026-01-05');
        $this->assertSame(['2026-01-05', '2026-01-12', '2026-01-19'], $dates);
    }

    public function testWeeklyWithCount(): void
    {
        $r = new Recurrence($this->date('2026-01-05 10:00:00'));
        $r->setType(RecurrenceType::Weekly);
        $r->setInterval(1);
        $r->setDayMask(DayMask::MONDAY);
        $r->setCount(3);
        $dates = $this->collectRecurrences($r, '2026-01-05');
        $this->assertSame(['2026-01-05', '2026-01-12', '2026-01-19'], $dates);
    }

    // =========================================================================
    // Monthly Date recurrence
    // =========================================================================

    public function testMonthlyDateBasic(): void
    {
        $r = new Recurrence($this->date('2026-01-15 10:00:00'));
        $r->setType(RecurrenceType::MonthlyDate);
        $r->setInterval(1);
        $dates = $this->collectRecurrences($r, '2026-01-01', 4);
        $this->assertSame([
            '2026-01-15', '2026-02-15', '2026-03-15', '2026-04-15',
        ], $dates);
    }

    public function testMonthlyDateInterval2(): void
    {
        $r = new Recurrence($this->date('2026-01-15 10:00:00'));
        $r->setType(RecurrenceType::MonthlyDate);
        $r->setInterval(2);
        $dates = $this->collectRecurrences($r, '2026-01-01', 4);
        $this->assertSame([
            '2026-01-15', '2026-03-15', '2026-05-15', '2026-07-15',
        ], $dates);
    }

    public function testMonthlyDateDay31SkipsShortMonths(): void
    {
        $r = new Recurrence($this->date('2026-01-31 10:00:00'));
        $r->setType(RecurrenceType::MonthlyDate);
        $r->setInterval(1);
        $dates = $this->collectRecurrences($r, '2026-01-01', 4);
        $this->assertSame([
            '2026-01-31', '2026-03-31', '2026-05-31', '2026-07-31',
        ], $dates);
    }

    public function testMonthlyDateWithEndDate(): void
    {
        $r = new Recurrence($this->date('2026-01-15 10:00:00'));
        $r->setType(RecurrenceType::MonthlyDate);
        $r->setInterval(1);
        $r->setEnd($this->date('2026-03-31 23:59:59'));
        $dates = $this->collectRecurrences($r, '2026-01-01');
        $this->assertSame(['2026-01-15', '2026-02-15', '2026-03-15'], $dates);
    }

    public function testMonthlyDateWithCount(): void
    {
        $r = new Recurrence($this->date('2026-01-15 10:00:00'));
        $r->setType(RecurrenceType::MonthlyDate);
        $r->setInterval(1);
        $r->setCount(3);
        $dates = $this->collectRecurrences($r, '2026-01-01');
        $this->assertSame(['2026-01-15', '2026-02-15', '2026-03-15'], $dates);
    }

    // =========================================================================
    // Monthly Weekday recurrence
    // =========================================================================

    public function testMonthlyWeekday2ndTuesday(): void
    {
        // 2026-01-13 is the 2nd Tuesday of January
        $r = new Recurrence($this->date('2026-01-13 10:00:00'));
        $r->setType(RecurrenceType::MonthlyWeekday);
        $r->setInterval(1);
        $dates = $this->collectRecurrences($r, '2026-01-01', 4);
        $this->assertSame([
            '2026-01-13', '2026-02-10', '2026-03-10', '2026-04-14',
        ], $dates);
    }

    public function testMonthlyLastWeekday(): void
    {
        // 2026-01-30 is the last Friday of January
        $r = new Recurrence($this->date('2026-01-30 10:00:00'));
        $r->setType(RecurrenceType::MonthlyLastWeekday);
        $r->setInterval(1);
        $dates = $this->collectRecurrences($r, '2026-01-01', 4);
        $this->assertSame([
            '2026-01-30', '2026-02-27', '2026-03-27', '2026-04-24',
        ], $dates);
    }

    public function testMonthlyWeekdayWithCount(): void
    {
        $r = new Recurrence($this->date('2026-01-13 10:00:00'));
        $r->setType(RecurrenceType::MonthlyWeekday);
        $r->setInterval(1);
        $r->setCount(3);
        $dates = $this->collectRecurrences($r, '2026-01-01');
        $this->assertSame([
            '2026-01-13', '2026-02-10', '2026-03-10',
        ], $dates);
    }

    // =========================================================================
    // Yearly Date recurrence
    // =========================================================================

    public function testYearlyDateBasic(): void
    {
        $r = new Recurrence($this->date('2026-03-15 10:00:00'));
        $r->setType(RecurrenceType::YearlyDate);
        $r->setInterval(1);
        $dates = $this->collectRecurrences($r, '2026-01-01', 4);
        $this->assertSame([
            '2026-03-15', '2027-03-15', '2028-03-15', '2029-03-15',
        ], $dates);
    }

    public function testYearlyDateInterval2(): void
    {
        $r = new Recurrence($this->date('2026-06-15 10:00:00'));
        $r->setType(RecurrenceType::YearlyDate);
        $r->setInterval(2);
        $dates = $this->collectRecurrences($r, '2026-01-01', 3);
        $this->assertSame([
            '2026-06-15', '2028-06-15', '2030-06-15',
        ], $dates);
    }

    public function testYearlyDateFeb29LeapSkip(): void
    {
        $r = new Recurrence($this->date('2024-02-29 10:00:00'));
        $r->setType(RecurrenceType::YearlyDate);
        $r->setInterval(1);
        $r->setCount(10);
        $dates = $this->collectRecurrences($r, '2024-01-01');
        $this->assertSame([
            '2024-02-29', '2028-02-29', '2032-02-29',
        ], $dates);
    }

    public function testYearlyDateFeb29Interval2WithCount(): void
    {
        $r = new Recurrence($this->date('2024-02-29 10:00:00'));
        $r->setType(RecurrenceType::YearlyDate);
        $r->setInterval(2);
        $r->setCount(6);
        $dates = $this->collectRecurrences($r, '2024-01-01');
        $this->assertSame(['2024-02-29', '2028-02-29'], $dates);
    }

    public function testYearlyDateWithEndDate(): void
    {
        $r = new Recurrence($this->date('2026-03-15 10:00:00'));
        $r->setType(RecurrenceType::YearlyDate);
        $r->setInterval(1);
        $r->setEnd($this->date('2028-12-31'));
        $dates = $this->collectRecurrences($r, '2026-01-01');
        $this->assertSame([
            '2026-03-15', '2027-03-15', '2028-03-15',
        ], $dates);
    }

    public function testYearlyDateFeb29LeapWithCount(): void
    {
        $r = new Recurrence($this->date('2024-02-29 10:00:00'));
        $r->setType(RecurrenceType::YearlyDate);
        $r->setInterval(1);
        $r->setCount(5);
        $dates = $this->collectRecurrences($r, '2024-01-01');
        $this->assertSame([
            '2024-02-29', '2028-02-29',
        ], $dates);
    }

    // =========================================================================
    // Yearly Day recurrence
    // =========================================================================

    public function testYearlyDayBasic(): void
    {
        // Day 60 of 2026 = 2026-03-01; in leap year 2028 day 60 = Feb 29
        $r = new Recurrence($this->date('2026-03-01 10:00:00'));
        $r->setType(RecurrenceType::YearlyDay);
        $r->setInterval(1);
        $dates = $this->collectRecurrences($r, '2026-01-01', 3);
        $this->assertSame([
            '2026-03-01', '2027-03-01', '2028-02-29',
        ], $dates);
    }

    public function testYearlyDayWithEndDate(): void
    {
        $r = new Recurrence($this->date('2026-03-01 10:00:00'));
        $r->setType(RecurrenceType::YearlyDay);
        $r->setInterval(1);
        $r->setEnd($this->date('2027-12-31'));
        $dates = $this->collectRecurrences($r, '2026-01-01');
        $this->assertSame(['2026-03-01', '2027-03-01'], $dates);
    }

    // =========================================================================
    // Yearly Weekday recurrence
    // =========================================================================

    public function testYearlyWeekday2ndTuesdayOfJanuary(): void
    {
        // 2026-01-13 is the 2nd Tuesday of January
        $r = new Recurrence($this->date('2026-01-13 10:00:00'));
        $r->setType(RecurrenceType::YearlyWeekday);
        $r->setInterval(1);
        $dates = $this->collectRecurrences($r, '2026-01-01', 3);
        $this->assertSame([
            '2026-01-13', '2027-01-12', '2028-01-11',
        ], $dates);
    }

    // =========================================================================
    // nextActiveRecurrence & hasActiveRecurrence
    // =========================================================================

    public function testNextActiveRecurrenceSkipsExceptions(): void
    {
        $r = new Recurrence($this->date('2026-01-01 10:00:00'));
        $r->setType(RecurrenceType::Daily);
        $r->addException($this->date('2026-01-01'));
        $r->addException($this->date('2026-01-02'));
        $next = $r->nextActiveRecurrence($this->date('2026-01-01'));
        $this->assertNotNull($next);
        $this->assertSame('2026-01-03', $next->format('Y-m-d'));
    }

    public function testNextActiveRecurrenceSkipsCompletions(): void
    {
        $r = new Recurrence($this->date('2026-01-01 10:00:00'));
        $r->setType(RecurrenceType::Daily);
        $r->addCompletion($this->date('2026-01-01'));
        $next = $r->nextActiveRecurrence($this->date('2026-01-01'));
        $this->assertNotNull($next);
        $this->assertSame('2026-01-02', $next->format('Y-m-d'));
    }

    public function testHasActiveRecurrenceNoEnd(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Daily);
        $this->assertTrue($r->hasActiveRecurrence());
    }

    public function testHasActiveRecurrenceAllExcepted(): void
    {
        $r = new Recurrence($this->date('2026-01-01 10:00:00'));
        $r->setType(RecurrenceType::Daily);
        $r->setEnd($this->date('2026-01-03 23:59:59'));
        $r->addException($this->date('2026-01-01'));
        $r->addException($this->date('2026-01-02'));
        $r->addException($this->date('2026-01-03'));
        $this->assertFalse($r->hasActiveRecurrence());
    }

    // =========================================================================
    // toRRule20 generation
    // =========================================================================

    public function testToRRule20Daily(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Daily);
        $r->setInterval(2);
        $this->assertSame('FREQ=DAILY;INTERVAL=2', $r->toRRule20());
    }

    public function testToRRule20Weekly(): void
    {
        $r = new Recurrence($this->date('2026-01-05'));
        $r->setType(RecurrenceType::Weekly);
        $r->setInterval(1);
        $r->setDayMask(DayMask::MONDAY | DayMask::WEDNESDAY | DayMask::FRIDAY);
        $this->assertSame('FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,WE,FR', $r->toRRule20());
    }

    public function testToRRule20MonthlyDate(): void
    {
        $r = new Recurrence($this->date('2026-01-15'));
        $r->setType(RecurrenceType::MonthlyDate);
        $r->setInterval(1);
        $this->assertSame('FREQ=MONTHLY;INTERVAL=1', $r->toRRule20());
    }

    public function testToRRule20MonthlyWeekday(): void
    {
        // 2026-01-13 is the 2nd Tuesday
        $r = new Recurrence($this->date('2026-01-13'));
        $r->setType(RecurrenceType::MonthlyWeekday);
        $r->setInterval(1);
        $this->assertSame('FREQ=MONTHLY;INTERVAL=1;BYDAY=2TU', $r->toRRule20());
    }

    public function testToRRule20MonthlyLastWeekday(): void
    {
        // 2026-01-30 is the last Friday
        $r = new Recurrence($this->date('2026-01-30'));
        $r->setType(RecurrenceType::MonthlyLastWeekday);
        $r->setInterval(1);
        $this->assertSame('FREQ=MONTHLY;INTERVAL=1;BYDAY=-1FR', $r->toRRule20());
    }

    public function testToRRule20YearlyDate(): void
    {
        $r = new Recurrence($this->date('2026-03-15'));
        $r->setType(RecurrenceType::YearlyDate);
        $r->setInterval(1);
        $this->assertSame('FREQ=YEARLY;INTERVAL=1', $r->toRRule20());
    }

    public function testToRRule20YearlyDay(): void
    {
        // 2026-03-01 is day 60
        $r = new Recurrence($this->date('2026-03-01'));
        $r->setType(RecurrenceType::YearlyDay);
        $r->setInterval(1);
        $this->assertStringContainsString('BYYEARDAY=60', $r->toRRule20());
    }

    public function testToRRule20YearlyWeekday(): void
    {
        // 2026-01-13 is 2nd Tuesday of January
        $r = new Recurrence($this->date('2026-01-13'));
        $r->setType(RecurrenceType::YearlyWeekday);
        $r->setInterval(1);
        $rrule = $r->toRRule20();
        $this->assertStringContainsString('FREQ=YEARLY', $rrule);
        $this->assertStringContainsString('BYDAY=2TU', $rrule);
        $this->assertStringContainsString('BYMONTH=1', $rrule);
    }

    public function testToRRule20WithEndDate(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Daily);
        $r->setInterval(1);
        $r->setEnd($this->date('2026-12-31 23:59:59'));
        $this->assertStringContainsString('UNTIL=', $r->toRRule20());
    }

    public function testToRRule20WithCount(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Daily);
        $r->setInterval(1);
        $r->setCount(10);
        $this->assertStringContainsString('COUNT=10', $r->toRRule20());
    }

    public function testToRRule20NoneReturnsEmpty(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $this->assertSame('', $r->toRRule20());
    }

    // =========================================================================
    // fromRRule20 parsing
    // =========================================================================

    public function testFromRRule20Daily(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->fromRRule20('FREQ=DAILY;INTERVAL=3');
        $this->assertSame(RecurrenceType::Daily, $r->getType());
        $this->assertSame(3, $r->getInterval());
    }

    public function testFromRRule20Weekly(): void
    {
        $r = new Recurrence($this->date('2026-01-05'));
        $r->fromRRule20('FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,WE,FR');
        $this->assertSame(RecurrenceType::Weekly, $r->getType());
        $this->assertSame(
            DayMask::MONDAY | DayMask::WEDNESDAY | DayMask::FRIDAY,
            $r->getDayMask()
        );
    }

    public function testFromRRule20WeeklyNoByday(): void
    {
        // Monday start → should default to Monday's bit
        $r = new Recurrence($this->date('2026-01-05'));
        $r->fromRRule20('FREQ=WEEKLY;INTERVAL=1');
        $this->assertSame(RecurrenceType::Weekly, $r->getType());
        $this->assertSame(DayMask::MONDAY, $r->getDayMask());
    }

    public function testFromRRule20MonthlyDate(): void
    {
        $r = new Recurrence($this->date('2026-01-15'));
        $r->fromRRule20('FREQ=MONTHLY;INTERVAL=1');
        $this->assertSame(RecurrenceType::MonthlyDate, $r->getType());
    }

    public function testFromRRule20MonthlyWeekday(): void
    {
        $r = new Recurrence($this->date('2026-01-13'));
        $r->fromRRule20('FREQ=MONTHLY;INTERVAL=1;BYDAY=2TU');
        $this->assertSame(RecurrenceType::MonthlyWeekday, $r->getType());
    }

    public function testFromRRule20MonthlyLastWeekday(): void
    {
        $r = new Recurrence($this->date('2026-01-30'));
        $r->fromRRule20('FREQ=MONTHLY;INTERVAL=1;BYDAY=-1FR');
        $this->assertSame(RecurrenceType::MonthlyLastWeekday, $r->getType());
    }

    public function testFromRRule20YearlyDate(): void
    {
        $r = new Recurrence($this->date('2026-03-15'));
        $r->fromRRule20('FREQ=YEARLY;INTERVAL=1');
        $this->assertSame(RecurrenceType::YearlyDate, $r->getType());
    }

    public function testFromRRule20YearlyDay(): void
    {
        $r = new Recurrence($this->date('2026-03-01'));
        $r->fromRRule20('FREQ=YEARLY;INTERVAL=1;BYYEARDAY=60');
        $this->assertSame(RecurrenceType::YearlyDay, $r->getType());
    }

    public function testFromRRule20YearlyWeekday(): void
    {
        $r = new Recurrence($this->date('2026-01-13'));
        $r->fromRRule20('FREQ=YEARLY;INTERVAL=1;BYDAY=2TU;BYMONTH=1');
        $this->assertSame(RecurrenceType::YearlyWeekday, $r->getType());
    }

    public function testFromRRule20WithCount(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->fromRRule20('FREQ=DAILY;INTERVAL=1;COUNT=10');
        $this->assertSame(10, $r->getCount());
    }

    public function testFromRRule20WithUntil(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->fromRRule20('FREQ=DAILY;INTERVAL=1;UNTIL=20261231T235959Z');
        $this->assertNotNull($r->getEnd());
        $this->assertSame('2026-12-31', $r->getEnd()->format('Y-m-d'));
    }

    public function testFromRRule20EmptyStringResetsToNone(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Daily);
        $r->fromRRule20('');
        $this->assertSame(RecurrenceType::None, $r->getType());
    }

    public function testFromRRule20ThunderbirdDailyWithByday(): void
    {
        $r = new Recurrence($this->date('2026-01-05'));
        $r->fromRRule20('FREQ=DAILY;BYDAY=MO,WE,FR');
        $this->assertSame(RecurrenceType::Weekly, $r->getType());
    }

    // =========================================================================
    // toRRule10 generation
    // =========================================================================

    public function testToRRule10Daily(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Daily);
        $r->setInterval(2);
        $this->assertSame('D2 #0', $r->toRRule10());
    }

    public function testToRRule10Weekly(): void
    {
        $r = new Recurrence($this->date('2026-01-05'));
        $r->setType(RecurrenceType::Weekly);
        $r->setInterval(1);
        $r->setDayMask(DayMask::MONDAY | DayMask::FRIDAY);
        $rrule = $r->toRRule10();
        $this->assertStringStartsWith('W1', $rrule);
        $this->assertStringContainsString('MO', $rrule);
        $this->assertStringContainsString('FR', $rrule);
    }

    public function testToRRule10MonthlyDate(): void
    {
        $r = new Recurrence($this->date('2026-01-15'));
        $r->setType(RecurrenceType::MonthlyDate);
        $r->setInterval(1);
        $this->assertStringStartsWith('MD1', $r->toRRule10());
    }

    public function testToRRule10YearlyDate(): void
    {
        $r = new Recurrence($this->date('2026-03-15'));
        $r->setType(RecurrenceType::YearlyDate);
        $r->setInterval(1);
        $rrule = $r->toRRule10();
        $this->assertStringStartsWith('YM1', $rrule);
        $this->assertStringContainsString('3', $rrule);
    }

    public function testToRRule10WithCount(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Daily);
        $r->setInterval(1);
        $r->setCount(5);
        $this->assertSame('D1 #5', $r->toRRule10());
    }

    public function testToRRule10NoneReturnsEmpty(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $this->assertSame('', $r->toRRule10());
    }

    // =========================================================================
    // fromRRule10 parsing
    // =========================================================================

    public function testFromRRule10Daily(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->fromRRule10('D3 #0');
        $this->assertSame(RecurrenceType::Daily, $r->getType());
        $this->assertSame(3, $r->getInterval());
    }

    public function testFromRRule10Weekly(): void
    {
        $r = new Recurrence($this->date('2026-01-05'));
        $r->fromRRule10('W1 MO FR #0');
        $this->assertSame(RecurrenceType::Weekly, $r->getType());
        $this->assertSame(DayMask::MONDAY | DayMask::FRIDAY, $r->getDayMask());
    }

    public function testFromRRule10MonthlyDate(): void
    {
        $r = new Recurrence($this->date('2026-01-15'));
        $r->fromRRule10('MD1 15 #0');
        $this->assertSame(RecurrenceType::MonthlyDate, $r->getType());
    }

    public function testFromRRule10MonthlyWeekday(): void
    {
        $r = new Recurrence($this->date('2026-01-13'));
        $r->fromRRule10('MP1 2+ TU #0');
        $this->assertSame(RecurrenceType::MonthlyWeekday, $r->getType());
    }

    public function testFromRRule10YearlyDate(): void
    {
        $r = new Recurrence($this->date('2026-03-15'));
        $r->fromRRule10('YM1 3 #0');
        $this->assertSame(RecurrenceType::YearlyDate, $r->getType());
    }

    public function testFromRRule10YearlyDay(): void
    {
        $r = new Recurrence($this->date('2026-03-01'));
        $r->fromRRule10('YD1 60 #0');
        $this->assertSame(RecurrenceType::YearlyDay, $r->getType());
    }

    public function testFromRRule10WithCount(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->fromRRule10('D1 #5');
        $this->assertSame(5, $r->getCount());
    }

    public function testFromRRule10WithEndDate(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->fromRRule10('D1 20261231T235959Z');
        $this->assertNotNull($r->getEnd());
        $this->assertSame('2026-12-31', $r->getEnd()->format('Y-m-d'));
    }

    public function testFromRRule10EmptyString(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Daily);
        $r->fromRRule10('');
        $this->assertSame(RecurrenceType::None, $r->getType());
    }

    public function testFromRRule10MonthlyLastWeekday(): void
    {
        $r = new Recurrence($this->date('2026-01-30'));
        $r->fromRRule10('MP1 1- FR #0');
        // Modern code correctly detects the minus sign (fixed from legacy)
        $this->assertSame(RecurrenceType::MonthlyLastWeekday, $r->getType());
    }

    // =========================================================================
    // RRULE20 round-trips
    // =========================================================================

    #[DataProvider('rrule20RoundTripProvider')]
    public function testRRule20RoundTrip(RecurrenceType $type, int $interval, int $dayMask): void
    {
        $r = new Recurrence($this->date('2026-01-05 10:00:00'));
        $r->setType($type);
        $r->setInterval($interval);
        if ($dayMask !== 0) {
            $r->setDayMask($dayMask);
        }

        $rrule = $r->toRRule20();
        $r2 = new Recurrence($this->date('2026-01-05 10:00:00'));
        $r2->fromRRule20($rrule);

        $this->assertSame($r->getType(), $r2->getType());
        $this->assertEquals($r->getInterval(), $r2->getInterval());
    }

    public static function rrule20RoundTripProvider(): array
    {
        return [
            'Daily' => [RecurrenceType::Daily, 2, 0],
            'Weekly MWF' => [RecurrenceType::Weekly, 1, DayMask::MONDAY | DayMask::WEDNESDAY | DayMask::FRIDAY],
            'MonthlyDate' => [RecurrenceType::MonthlyDate, 1, 0],
            'YearlyDate' => [RecurrenceType::YearlyDate, 1, 0],
        ];
    }

    public function testRRule20RoundTripWithCount(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Daily);
        $r->setInterval(1);
        $r->setCount(10);

        $rrule = $r->toRRule20();
        $r2 = new Recurrence($this->date('2026-01-01'));
        $r2->fromRRule20($rrule);

        $this->assertSame(10, $r2->getCount());
    }

    public function testRRule20RoundTripWithEndDate(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Daily);
        $r->setInterval(1);
        $r->setEnd($this->date('2026-12-31 23:59:59'));

        $rrule = $r->toRRule20();
        $r2 = new Recurrence($this->date('2026-01-01'));
        $r2->fromRRule20($rrule);

        $this->assertNotNull($r2->getEnd());
        $this->assertSame('2026-12-31', $r2->getEnd()->format('Y-m-d'));
    }

    // =========================================================================
    // toJson
    // =========================================================================

    public function testToJsonBasic(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Daily);
        $r->setInterval(2);
        $json = $r->toJson();
        $this->assertSame(RecurrenceType::Daily->value, $json->t);
        $this->assertSame(2, $json->i);
    }

    public function testToJsonWithEnd(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Daily);
        $r->setEnd($this->date('2026-12-31'));
        $json = $r->toJson();
        $this->assertTrue(isset($json->e));
    }

    public function testToJsonWithCount(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Daily);
        $r->setCount(5);
        $json = $r->toJson();
        $this->assertSame(5, $json->c);
    }

    public function testToJsonWithDayMask(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Weekly);
        $r->setDayMask(DayMask::MONDAY | DayMask::FRIDAY);
        $json = $r->toJson();
        $this->assertSame(DayMask::MONDAY | DayMask::FRIDAY, $json->d);
    }

    public function testToJsonOmitsEmptyOptionalFields(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Daily);
        $json = $r->toJson();
        $this->assertFalse(isset($json->e));
        $this->assertFalse(isset($json->c));
        $this->assertFalse(isset($json->d));
        $this->assertFalse(isset($json->co));
        $this->assertFalse(isset($json->ex));
    }

    public function testToJsonWithExceptionsAndCompletions(): void
    {
        $r = new Recurrence($this->date('2026-01-01'));
        $r->setType(RecurrenceType::Daily);
        $r->addException($this->date('2026-01-05'));
        $r->addCompletion($this->date('2026-01-06'));
        $json = $r->toJson();
        $this->assertSame(['20260105'], $json->ex);
        $this->assertSame(['20260106'], $json->co);
    }

    // =========================================================================
    // toHash / fromHash
    // =========================================================================

    public function testHashRoundTrip(): void
    {
        $r = new Recurrence($this->date('2026-01-05 10:00:00'));
        $r->setType(RecurrenceType::Weekly);
        $r->setInterval(2);
        $r->setDayMask(DayMask::MONDAY | DayMask::FRIDAY);
        $r->setCount(10);
        $r->addException($this->date('2026-01-12'));

        $hash = $r->toHash();
        $r2 = Recurrence::fromHash($hash);

        $this->assertSame($r->getType(), $r2->getType());
        $this->assertSame($r->getInterval(), $r2->getInterval());
        $this->assertSame($r->getDayMask(), $r2->getDayMask());
        $this->assertSame($r->getCount(), $r2->getCount());
        $this->assertSame($r->getExceptions(), $r2->getExceptions());
    }

    public function testHashRoundTripWithEndDate(): void
    {
        $r = new Recurrence($this->date('2026-01-01 10:00:00'));
        $r->setType(RecurrenceType::Daily);
        $r->setEnd($this->date('2026-12-31 23:59:59'));

        $hash = $r->toHash();
        $r2 = Recurrence::fromHash($hash);

        $this->assertNotNull($r2->getEnd());
        $this->assertSame('2026-12-31', $r2->getEnd()->format('Y-m-d'));
    }

    public function testToHashStructure(): void
    {
        $r = new Recurrence($this->date('2026-01-01 10:00:00'));
        $r->setType(RecurrenceType::Daily);
        $r->setInterval(2);
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

    // =========================================================================
    // isEqual
    // =========================================================================

    public function testIsEqualSameConfig(): void
    {
        $r1 = new Recurrence($this->date('2026-01-01'));
        $r1->setType(RecurrenceType::Daily);
        $r1->setInterval(2);

        $r2 = new Recurrence($this->date('2026-01-01'));
        $r2->setType(RecurrenceType::Daily);
        $r2->setInterval(2);

        $this->assertTrue($r1->isEqual($r2));
    }

    public function testIsEqualDifferentType(): void
    {
        $r1 = new Recurrence($this->date('2026-01-01'));
        $r1->setType(RecurrenceType::Daily);

        $r2 = new Recurrence($this->date('2026-01-01'));
        $r2->setType(RecurrenceType::Weekly);

        $this->assertFalse($r1->isEqual($r2));
    }

    public function testIsEqualDifferentInterval(): void
    {
        $r1 = new Recurrence($this->date('2026-01-01'));
        $r1->setType(RecurrenceType::Daily);
        $r1->setInterval(1);

        $r2 = new Recurrence($this->date('2026-01-01'));
        $r2->setType(RecurrenceType::Daily);
        $r2->setInterval(2);

        $this->assertFalse($r1->isEqual($r2));
    }

    public function testIsEqualIgnoresExceptions(): void
    {
        $r1 = new Recurrence($this->date('2026-01-01'));
        $r1->setType(RecurrenceType::Daily);

        $r2 = new Recurrence($this->date('2026-01-01'));
        $r2->setType(RecurrenceType::Daily);
        $r2->addException($this->date('2026-01-05'));

        $this->assertTrue($r1->isEqual($r2));
    }

    public function testIsEqualDifferentCount(): void
    {
        $r1 = new Recurrence($this->date('2026-01-01'));
        $r1->setType(RecurrenceType::Daily);
        $r1->setCount(5);

        $r2 = new Recurrence($this->date('2026-01-01'));
        $r2->setType(RecurrenceType::Daily);
        $r2->setCount(10);

        $this->assertFalse($r1->isEqual($r2));
    }

    public function testIsEqualDifferentDayMask(): void
    {
        $r1 = new Recurrence($this->date('2026-01-05'));
        $r1->setType(RecurrenceType::Weekly);
        $r1->setDayMask(DayMask::MONDAY);

        $r2 = new Recurrence($this->date('2026-01-05'));
        $r2->setType(RecurrenceType::Weekly);
        $r2->setDayMask(DayMask::FRIDAY);

        $this->assertFalse($r1->isEqual($r2));
    }
}
