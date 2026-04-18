<?php

declare(strict_types=1);

namespace Horde\Date\Test;

use Horde_Date;
use Horde_Date_Recurrence;
use Horde_Icalendar;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests pinning down the exact public API contract of Horde_Date_Recurrence
 * before converting it to a thin wrapper over Horde\Date\Recurrence\Recurrence.
 *
 * Focus: property access, return types, toString(), edge cases in
 * mutator/accessor pairs, and cross-method interactions.
 */
#[CoversClass(Horde_Date_Recurrence::class)]
class RecurrenceWrapperReadyTest extends TestCase
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

    // =========================================================================
    // Section 1: Direct property read — types and values
    // =========================================================================

    public function testStartPropertyIsHordeDate(): void
    {
        $r = new Horde_Date_Recurrence('2026-06-15 14:30:00');
        $this->assertInstanceOf(Horde_Date::class, $r->start);
        $this->assertSame('2026-06-15', $r->start->format('Y-m-d'));
        $this->assertSame('14:30:00', $r->start->format('H:i:s'));
    }

    public function testStartPropertyClonedOnConstruction(): void
    {
        $orig = new Horde_Date('2026-01-01');
        $r = new Horde_Date_Recurrence($orig);
        $orig->year = 2099;
        $this->assertSame('2026-01-01', $r->start->format('Y-m-d'));
    }

    public function testRecurTypeDefaultNone(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $this->assertSame(Horde_Date_Recurrence::RECUR_NONE, $r->recurType);
    }

    public function testRecurIntervalDefault1(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $this->assertSame(1, $r->recurInterval);
    }

    public function testRecurEndDefaultNull(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $this->assertNull($r->recurEnd);
    }

    public function testRecurCountDefaultNull(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $this->assertNull($r->recurCount);
    }

    public function testRecurDataDefaultNull(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $this->assertNull($r->recurData);
    }

    public function testExceptionsDefaultEmpty(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $this->assertSame([], $r->exceptions);
    }

    public function testCompletionsDefaultEmpty(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $this->assertSame([], $r->completions);
    }

    // =========================================================================
    // Section 2: Direct property write → read round-trip
    // =========================================================================

    public function testWriteRecurType(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->recurType = Horde_Date_Recurrence::RECUR_WEEKLY;
        $this->assertSame(Horde_Date_Recurrence::RECUR_WEEKLY, $r->recurType);
        $this->assertSame(Horde_Date_Recurrence::RECUR_WEEKLY, $r->getRecurType());
    }

    public function testWriteRecurInterval(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->recurInterval = 5;
        $this->assertSame(5, $r->recurInterval);
        $this->assertSame(5, $r->getRecurInterval());
    }

    public function testWriteRecurEnd(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->recurEnd = new Horde_Date('2026-12-31');
        $this->assertInstanceOf(Horde_Date::class, $r->recurEnd);
        $this->assertSame('2026-12-31', $r->recurEnd->format('Y-m-d'));
    }

    public function testWriteRecurCount(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->recurCount = 7;
        $this->assertSame(7, $r->recurCount);
    }

    public function testWriteRecurData(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->recurData = Horde_Date::MASK_MONDAY | Horde_Date::MASK_FRIDAY;
        $this->assertSame(Horde_Date::MASK_MONDAY | Horde_Date::MASK_FRIDAY, $r->recurData);
    }

    public function testWriteExceptions(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->exceptions = ['20260115', '20260220'];
        $this->assertSame(['20260115', '20260220'], $r->exceptions);
    }

    public function testWriteCompletions(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->completions = ['20260115'];
        $this->assertSame(['20260115'], $r->completions);
    }

    // =========================================================================
    // Section 3: Setter/getter consistency with property access
    // =========================================================================

    public function testSetRecurTypeReflectedInProperty(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $this->assertSame(Horde_Date_Recurrence::RECUR_DAILY, $r->recurType);
    }

    public function testSetRecurIntervalReflectedInProperty(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->setRecurInterval(3);
        $this->assertSame(3, $r->recurInterval);
    }

    public function testSetRecurEndReflectedInProperty(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->setRecurEnd(new Horde_Date('2026-06-30 23:59:59'));
        $this->assertInstanceOf(Horde_Date::class, $r->recurEnd);
        $this->assertSame('2026-06-30', $r->recurEnd->format('Y-m-d'));
    }

    public function testSetRecurCountReflectedInProperty(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->setRecurCount(10);
        $this->assertSame(10, $r->recurCount);
    }

    public function testSetRecurOnDayReflectedInRecurData(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $mask = Horde_Date::MASK_MONDAY | Horde_Date::MASK_WEDNESDAY;
        $r->setRecurOnDay($mask);
        $this->assertSame($mask, $r->recurData);
    }

    public function testSetRecurStartReflectedInStartProperty(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->setRecurStart(new Horde_Date('2026-07-04 12:00:00'));
        $this->assertSame('2026-07-04', $r->start->format('Y-m-d'));
    }

    // =========================================================================
    // Section 4: Property write → method behavior
    //   (set via property, then call a method that depends on it)
    // =========================================================================

    public function testPropertyWriteThenNextRecurrence(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->recurType = Horde_Date_Recurrence::RECUR_DAILY;
        $r->recurInterval = 2;
        $next = $r->nextRecurrence(new Horde_Date('2026-01-02'));
        $this->assertInstanceOf(Horde_Date::class, $next);
        $this->assertSame('2026-01-03', $next->format('Y-m-d'));
    }

    public function testPropertyWriteRecurDataThenWeeklyRecurrence(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r->recurType = Horde_Date_Recurrence::RECUR_WEEKLY;
        $r->recurInterval = 1;
        $r->recurData = Horde_Date::MASK_MONDAY | Horde_Date::MASK_FRIDAY;
        $next = $r->nextRecurrence(new Horde_Date('2026-01-06'));
        $this->assertInstanceOf(Horde_Date::class, $next);
        $this->assertSame('2026-01-09', $next->format('Y-m-d'));
    }

    public function testPropertyWriteRecurCountThenDailyLimited(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->recurType = Horde_Date_Recurrence::RECUR_DAILY;
        $r->recurInterval = 1;
        $r->recurCount = 3;
        $dates = [];
        $after = new Horde_Date('2026-01-01');
        while ($next = $r->nextRecurrence($after)) {
            if (count($dates) >= 10) {
                break;
            }
            $dates[] = $next->format('Y-m-d');
            $after = clone $next;
            $after->mday++;
        }
        $this->assertSame(['2026-01-01', '2026-01-02', '2026-01-03'], $dates);
    }

    public function testPropertyWriteRecurEndThenDailyLimited(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->recurType = Horde_Date_Recurrence::RECUR_DAILY;
        $r->recurInterval = 1;
        $r->recurEnd = new Horde_Date('2026-01-03 23:59:59');
        $dates = [];
        $after = new Horde_Date('2026-01-01');
        while ($next = $r->nextRecurrence($after)) {
            if (count($dates) >= 10) {
                break;
            }
            $dates[] = $next->format('Y-m-d');
            $after = clone $next;
            $after->mday++;
        }
        $this->assertSame(['2026-01-01', '2026-01-02', '2026-01-03'], $dates);
    }

    // =========================================================================
    // Section 5: nextRecurrence return types
    // =========================================================================

    public function testNextRecurrenceReturnsFalseWhenNoMatch(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurEnd(new Horde_Date('2026-01-01 23:59:59'));
        $result = $r->nextRecurrence(new Horde_Date('2026-01-02'));
        $this->assertFalse($result);
    }

    public function testNextRecurrenceReturnsHordeDate(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $next = $r->nextRecurrence(new Horde_Date('2026-01-01'));
        $this->assertInstanceOf(Horde_Date::class, $next);
    }

    public function testNextRecurrenceNoneReturnsFalse(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $result = $r->nextRecurrence(new Horde_Date('2026-01-02'));
        $this->assertFalse($result);
    }

    public function testNextActiveRecurrenceReturnsFalseWhenAllExcepted(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurEnd(new Horde_Date('2026-01-03 23:59:59'));
        $r->addException(2026, 1, 1);
        $r->addException(2026, 1, 2);
        $r->addException(2026, 1, 3);
        $result = $r->nextActiveRecurrence(new Horde_Date('2026-01-01'));
        $this->assertFalse($result);
    }

    public function testNextActiveRecurrenceReturnsHordeDate(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->addException(2026, 1, 1);
        $next = $r->nextActiveRecurrence(new Horde_Date('2026-01-01'));
        $this->assertInstanceOf(Horde_Date::class, $next);
        $this->assertSame('2026-01-02', $next->format('Y-m-d'));
    }

    // =========================================================================
    // Section 6: toString()
    // =========================================================================

    public function testToStringDaily(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(2);
        $str = $r->toString('%Y-%m-%d');
        $this->assertStringContainsString('Daily', $str);
        $this->assertStringContainsString('2', $str);
        $this->assertStringContainsString('day(s)', $str);
    }

    public function testToStringWeeklyWithDays(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(1);
        $r->setRecurOnDay(Horde_Date::MASK_MONDAY | Horde_Date::MASK_FRIDAY);
        $str = $r->toString('%Y-%m-%d');
        $this->assertStringContainsString('Weekly', $str);
        $this->assertStringContainsString('Monday', $str);
        $this->assertStringContainsString('Friday', $str);
    }

    public function testToStringMonthlyDate(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-15 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_DATE);
        $r->setRecurInterval(1);
        $str = $r->toString('%Y-%m-%d');
        $this->assertStringContainsString('Monthly', $str);
        $this->assertStringContainsString('same date', $str);
    }

    public function testToStringMonthlyWeekday(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-13 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY);
        $r->setRecurInterval(1);
        $str = $r->toString('%Y-%m-%d');
        $this->assertStringContainsString('Monthly', $str);
        $this->assertStringContainsString('same weekday', $str);
    }

    public function testToStringMonthlyLastWeekday(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-30 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_LAST_WEEKDAY);
        $r->setRecurInterval(1);
        $str = $r->toString('%Y-%m-%d');
        $this->assertStringContainsString('Monthly', $str);
        $this->assertStringContainsString('last weekday', $str);
    }

    public function testToStringYearlyDate(): void
    {
        $r = new Horde_Date_Recurrence('2026-03-15 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE);
        $r->setRecurInterval(1);
        $str = $r->toString('%Y-%m-%d');
        $this->assertStringContainsString('Yearly', $str);
        $this->assertStringContainsString('same date', $str);
    }

    public function testToStringYearlyDay(): void
    {
        $r = new Horde_Date_Recurrence('2026-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DAY);
        $r->setRecurInterval(1);
        $str = $r->toString('%Y-%m-%d');
        $this->assertStringContainsString('Yearly', $str);
        $this->assertStringContainsString('same day of the year', $str);
    }

    public function testToStringYearlyWeekday(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-13 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY);
        $r->setRecurInterval(1);
        $str = $r->toString('%Y-%m-%d');
        $this->assertStringContainsString('Yearly', $str);
        $this->assertStringContainsString('same weekday and month', $str);
    }

    public function testToStringWithEndDate(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurEnd(new Horde_Date('2026-12-31 23:59:59'));
        $str = $r->toString('%Y-%m-%d');
        $this->assertStringContainsString('2026-12-31', $str);
        $this->assertStringNotContainsString('No end date', $str);
    }

    public function testToStringWithCount(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurCount(10);
        $str = $r->toString('%Y-%m-%d');
        $this->assertStringContainsString('10 times', $str);
    }

    public function testToStringNoEnd(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $str = $r->toString('%Y-%m-%d');
        $this->assertStringContainsString('No end date', $str);
    }

    public function testToStringWithExceptions(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->addException(2026, 1, 5);
        $r->addException(2026, 1, 10);
        $str = $r->toString('%Y-%m-%d');
        $this->assertStringContainsString('Exceptions', $str);
        $this->assertStringContainsString('2026-01-05', $str);
        $this->assertStringContainsString('2026-01-10', $str);
    }

    public function testToStringReturnsString(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_NONE);
        $str = $r->toString('%Y-%m-%d');
        $this->assertIsString($str);
    }

    public function testToStringEndDateWithTime(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurEnd(new Horde_Date('2026-12-31 14:30:00'));
        $str = $r->toString('%Y-%m-%d', '%H:%M');
        $this->assertStringContainsString('2026-12-31', $str);
        $this->assertStringContainsString('14:30', $str);
    }

    public function testToStringEndDateMidnightOmitsTime(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurEnd(new Horde_Date('2026-12-31 23:59:00'));
        $str = $r->toString('%Y-%m-%d', '%H:%M');
        $this->assertStringContainsString('2026-12-31', $str);
        $this->assertStringNotContainsString('23:59', $str);
    }

    // =========================================================================
    // Section 7: toRRule20 / toRRule10 with Horde_Icalendar
    // =========================================================================

    public function testToRRule20WithCalendarParameter(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);
        $r->setRecurEnd(new Horde_Date('2026-12-31 23:59:59'));
        $cal = new Horde_Icalendar();
        $rrule = $r->toRRule20($cal);
        $this->assertStringContainsString('FREQ=DAILY', $rrule);
        $this->assertStringContainsString('UNTIL=', $rrule);
    }

    public function testToRRule10WithCalendarParameter(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(2);
        $cal = new Horde_Icalendar();
        $rrule = $r->toRRule10($cal);
        $this->assertStringStartsWith('D2', $rrule);
    }

    public function testToRRule20NoneReturnsEmpty(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $cal = new Horde_Icalendar();
        $this->assertSame('', $r->toRRule20($cal));
    }

    public function testToRRule10NoneReturnsEmpty(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $cal = new Horde_Icalendar();
        $this->assertSame('', $r->toRRule10($cal));
    }

    public function testToRRule20WeeklyByday(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(1);
        $r->setRecurOnDay(Horde_Date::MASK_MONDAY | Horde_Date::MASK_FRIDAY);
        $cal = new Horde_Icalendar();
        $rrule = $r->toRRule20($cal);
        $this->assertStringContainsString('BYDAY=', $rrule);
        $this->assertStringContainsString('MO', $rrule);
        $this->assertStringContainsString('FR', $rrule);
    }

    public function testToRRule20MonthlyWeekday(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-13 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY);
        $r->setRecurInterval(1);
        $cal = new Horde_Icalendar();
        $rrule = $r->toRRule20($cal);
        $this->assertStringContainsString('FREQ=MONTHLY', $rrule);
        $this->assertStringContainsString('BYDAY=2TU', $rrule);
    }

    public function testToRRule20MonthlyLastWeekday(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-30 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_LAST_WEEKDAY);
        $r->setRecurInterval(1);
        $cal = new Horde_Icalendar();
        $rrule = $r->toRRule20($cal);
        $this->assertStringContainsString('BYDAY=-1FR', $rrule);
    }

    public function testToRRule20YearlyWeekday(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-13 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY);
        $r->setRecurInterval(1);
        $cal = new Horde_Icalendar();
        $rrule = $r->toRRule20($cal);
        $this->assertStringContainsString('FREQ=YEARLY', $rrule);
        $this->assertStringContainsString('BYDAY=2TU', $rrule);
        $this->assertStringContainsString('BYMONTH=1', $rrule);
    }

    public function testToRRule20WithCount(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);
        $r->setRecurCount(5);
        $cal = new Horde_Icalendar();
        $rrule = $r->toRRule20($cal);
        $this->assertStringContainsString('COUNT=5', $rrule);
    }

    public function testToRRule10WeeklyDays(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(1);
        $r->setRecurOnDay(Horde_Date::MASK_MONDAY | Horde_Date::MASK_FRIDAY);
        $cal = new Horde_Icalendar();
        $rrule = $r->toRRule10($cal);
        $this->assertStringStartsWith('W1', $rrule);
        $this->assertStringContainsString('MO', $rrule);
        $this->assertStringContainsString('FR', $rrule);
    }

    public function testToRRule10YearlyDay(): void
    {
        $r = new Horde_Date_Recurrence('2026-03-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DAY);
        $r->setRecurInterval(1);
        $cal = new Horde_Icalendar();
        $rrule = $r->toRRule10($cal);
        $this->assertStringStartsWith('YD1', $rrule);
    }

    // =========================================================================
    // Section 8: fromRRule20 / fromRRule10 → property access
    //   (parse RRULE then verify properties match)
    // =========================================================================

    public function testFromRRule20SetsProperties(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->fromRRule20('FREQ=DAILY;INTERVAL=3;COUNT=10');
        $this->assertSame(Horde_Date_Recurrence::RECUR_DAILY, $r->recurType);
        $this->assertEquals(3, $r->recurInterval);
        $this->assertSame(10, $r->recurCount);
    }

    public function testFromRRule20WeeklySetsRecurData(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r->fromRRule20('FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,WE,FR');
        $this->assertSame(Horde_Date_Recurrence::RECUR_WEEKLY, $r->recurType);
        $expected = Horde_Date::MASK_MONDAY | Horde_Date::MASK_WEDNESDAY | Horde_Date::MASK_FRIDAY;
        $this->assertSame($expected, $r->recurData);
    }

    public function testFromRRule20WithUntilSetsRecurEnd(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->fromRRule20('FREQ=DAILY;INTERVAL=1;UNTIL=20261231T235959Z');
        $this->assertInstanceOf(Horde_Date::class, $r->recurEnd);
        $this->assertSame('2026-12-31', $r->recurEnd->format('Y-m-d'));
    }

    public function testFromRRule10SetsProperties(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->fromRRule10('D3 #5');
        $this->assertSame(Horde_Date_Recurrence::RECUR_DAILY, $r->recurType);
        $this->assertEquals(3, $r->recurInterval);
        $this->assertSame(5, $r->recurCount);
    }

    // =========================================================================
    // Section 9: Constants
    // =========================================================================

    #[DataProvider('constantProvider')]
    public function testConstants(string $name, int $value): void
    {
        $this->assertSame($value, constant('Horde_Date_Recurrence::' . $name));
    }

    public static function constantProvider(): array
    {
        return [
            'RECUR_NONE' => ['RECUR_NONE', 0],
            'RECUR_DAILY' => ['RECUR_DAILY', 1],
            'RECUR_WEEKLY' => ['RECUR_WEEKLY', 2],
            'RECUR_MONTHLY_DATE' => ['RECUR_MONTHLY_DATE', 3],
            'RECUR_MONTHLY_WEEKDAY' => ['RECUR_MONTHLY_WEEKDAY', 4],
            'RECUR_YEARLY_DATE' => ['RECUR_YEARLY_DATE', 5],
            'RECUR_YEARLY_DAY' => ['RECUR_YEARLY_DAY', 6],
            'RECUR_YEARLY_WEEKDAY' => ['RECUR_YEARLY_WEEKDAY', 7],
            'RECUR_MONTHLY_LAST_WEEKDAY' => ['RECUR_MONTHLY_LAST_WEEKDAY', 8],
        ];
    }

    // =========================================================================
    // Section 10: Kolab round-trip (already tested elsewhere, pin specific details)
    // =========================================================================

    public function testKolabRoundTripDaily(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(2);
        $r->setRecurCount(5);

        $kolab = $r->toKolab();
        $this->assertIsArray($kolab);
        $this->assertSame('daily', $kolab['cycle']);
        $this->assertSame(2, $kolab['interval']);

        $r2 = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r2->fromKolab($kolab);
        $this->assertSame(Horde_Date_Recurrence::RECUR_DAILY, $r2->getRecurType());
        $this->assertSame(2, $r2->getRecurInterval());
        $this->assertSame(5, $r2->getRecurCount());
    }

    public function testKolabRoundTripWeekly(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(1);
        $r->setRecurOnDay(Horde_Date::MASK_MONDAY | Horde_Date::MASK_FRIDAY);

        $kolab = $r->toKolab();
        $this->assertSame('weekly', $kolab['cycle']);

        $r2 = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r2->fromKolab($kolab);
        $this->assertSame(Horde_Date_Recurrence::RECUR_WEEKLY, $r2->getRecurType());
        $this->assertTrue((bool) $r2->recurOnDay(Horde_Date::MASK_MONDAY));
        $this->assertTrue((bool) $r2->recurOnDay(Horde_Date::MASK_FRIDAY));
    }

    public function testKolabRoundTripMonthlyDate(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-15 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_DATE);
        $r->setRecurInterval(1);

        $kolab = $r->toKolab();
        $this->assertSame('monthly', $kolab['cycle']);
        $this->assertSame('daynumber', $kolab['type']);

        $r2 = new Horde_Date_Recurrence('2026-01-15 10:00:00');
        $r2->fromKolab($kolab);
        $this->assertSame(Horde_Date_Recurrence::RECUR_MONTHLY_DATE, $r2->getRecurType());
    }

    public function testKolabRoundTripMonthlyWeekday(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-13 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY);
        $r->setRecurInterval(1);

        $kolab = $r->toKolab();
        $this->assertSame('monthly', $kolab['cycle']);
        $this->assertSame('weekday', $kolab['type']);

        $r2 = new Horde_Date_Recurrence('2026-01-13 10:00:00');
        $r2->fromKolab($kolab);
        $this->assertSame(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY, $r2->getRecurType());
    }

    public function testKolabRoundTripYearlyDate(): void
    {
        $r = new Horde_Date_Recurrence('2026-03-15 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE);
        $r->setRecurInterval(1);

        $kolab = $r->toKolab();
        $this->assertSame('yearly', $kolab['cycle']);
        $this->assertSame('monthday', $kolab['type']);

        $r2 = new Horde_Date_Recurrence('2026-03-15 10:00:00');
        $r2->fromKolab($kolab);
        $this->assertSame(Horde_Date_Recurrence::RECUR_YEARLY_DATE, $r2->getRecurType());
    }

    public function testKolabRoundTripYearlyWeekday(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-13 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY);
        $r->setRecurInterval(1);

        $kolab = $r->toKolab();
        $this->assertSame('yearly', $kolab['cycle']);
        $this->assertSame('weekday', $kolab['type']);

        $r2 = new Horde_Date_Recurrence('2026-01-13 10:00:00');
        $r2->fromKolab($kolab);
        $this->assertSame(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY, $r2->getRecurType());
    }

    public function testKolabWithEndDate(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);
        $r->setRecurEnd(new Horde_Date('2026-12-31 23:59:59'));

        $kolab = $r->toKolab();
        $this->assertSame('date', $kolab['range-type']);

        $r2 = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r2->fromKolab($kolab);
        $this->assertTrue($r2->hasRecurEnd());
    }

    // =========================================================================
    // Section 11: Exception/completion with (year, month, day) signature
    // =========================================================================

    public function testAddExceptionThreeArgs(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->addException(2026, 3, 15);
        $this->assertTrue($r->hasException(2026, 3, 15));
        $this->assertFalse($r->hasException(2026, 3, 16));
        $this->assertSame(['20260315'], $r->getExceptions());
    }

    public function testDeleteExceptionThreeArgs(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->addException(2026, 3, 15);
        $r->deleteException(2026, 3, 15);
        $this->assertFalse($r->hasException(2026, 3, 15));
        $this->assertSame([], $r->getExceptions());
    }

    public function testAddCompletionThreeArgs(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->addCompletion(2026, 3, 15);
        $this->assertTrue($r->hasCompletion(2026, 3, 15));
        $this->assertSame(['20260315'], $r->getCompletions());
    }

    public function testDeleteCompletionThreeArgs(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->addCompletion(2026, 3, 15);
        $r->deleteCompletion(2026, 3, 15);
        $this->assertFalse($r->hasCompletion(2026, 3, 15));
    }

    public function testExceptionReflectedInExceptionsProperty(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->addException(2026, 1, 5);
        $r->addException(2026, 2, 10);
        $this->assertSame(['20260105', '20260210'], $r->exceptions);
    }

    public function testCompletionReflectedInCompletionsProperty(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->addCompletion(2026, 1, 5);
        $this->assertSame(['20260105'], $r->completions);
    }

    // =========================================================================
    // Section 12: Cross-method interactions
    // =========================================================================

    public function testResetClearsEverything(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(3);
        $r->setRecurCount(10);
        $r->setRecurOnDay(Horde_Date::MASK_MONDAY);
        $r->addException(2026, 1, 5);
        $r->addCompletion(2026, 1, 6);
        $r->reset();

        $this->assertSame(Horde_Date_Recurrence::RECUR_NONE, $r->recurType);
        $this->assertSame(1, $r->recurInterval);
        $this->assertNull($r->recurCount);
        $this->assertNull($r->recurEnd);
        $this->assertNull($r->recurData);
        $this->assertSame([], $r->exceptions);
        $this->assertSame([], $r->completions);
    }

    public function testSetEndClearsCount(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->setRecurCount(10);
        $r->setRecurEnd(new Horde_Date('2026-12-31'));
        $this->assertNull($r->recurCount);
        $this->assertNotNull($r->recurEnd);
    }

    public function testSetCountClearsEnd(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01');
        $r->setRecurEnd(new Horde_Date('2026-12-31'));
        $r->setRecurCount(10);
        $this->assertNull($r->recurEnd);
        $this->assertSame(10, $r->recurCount);
    }

    public function testFromRRule20ThenPropertyAccess(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r->fromRRule20('FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,WE,FR;COUNT=8');
        $this->assertSame(Horde_Date_Recurrence::RECUR_WEEKLY, $r->recurType);
        $this->assertEquals(2, $r->recurInterval);
        $expected = Horde_Date::MASK_MONDAY | Horde_Date::MASK_WEDNESDAY | Horde_Date::MASK_FRIDAY;
        $this->assertSame($expected, $r->recurData);
        $this->assertSame(8, $r->recurCount);
        $this->assertNull($r->recurEnd);
    }

    public function testFromRRule20ThenNextRecurrence(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->fromRRule20('FREQ=DAILY;INTERVAL=1;COUNT=3');
        $next = $r->nextRecurrence(new Horde_Date('2026-01-01'));
        $this->assertInstanceOf(Horde_Date::class, $next);
        $this->assertSame('2026-01-01', $next->format('Y-m-d'));
    }

    public function testToHashFromHashThenPropertyAccess(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-05 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
        $r->setRecurInterval(2);
        $r->setRecurOnDay(Horde_Date::MASK_MONDAY);
        $r->setRecurCount(10);
        $r->addException(2026, 1, 12);

        $hash = $r->toHash();
        $r2 = Horde_Date_Recurrence::fromHash($hash);

        $this->assertSame(Horde_Date_Recurrence::RECUR_WEEKLY, $r2->recurType);
        $this->assertSame(2, $r2->recurInterval);
        $this->assertSame(Horde_Date::MASK_MONDAY, $r2->recurData);
        $this->assertSame(10, $r2->recurCount);
        $this->assertNull($r2->recurEnd);
        $this->assertSame(['20260112'], $r2->exceptions);
    }

    public function testFromHashThenNextRecurrence(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-01 10:00:00');
        $r->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $r->setRecurInterval(1);

        $hash = $r->toHash();
        $r2 = Horde_Date_Recurrence::fromHash($hash);

        $next = $r2->nextRecurrence(new Horde_Date('2026-01-02'));
        $this->assertInstanceOf(Horde_Date::class, $next);
        $this->assertSame('2026-01-02', $next->format('Y-m-d'));
    }

    // =========================================================================
    // Section 13: Specific recurrence algorithms via property setup
    //   (tests that set up via properties instead of setters, ensuring
    //    wrapper __set delegates properly)
    // =========================================================================

    public function testMonthlyDateViaProperties(): void
    {
        $r = new Horde_Date_Recurrence('2026-01-15 10:00:00');
        $r->recurType = Horde_Date_Recurrence::RECUR_MONTHLY_DATE;
        $r->recurInterval = 1;
        $dates = [];
        $after = new Horde_Date('2026-01-01');
        while ($next = $r->nextRecurrence($after)) {
            if (count($dates) >= 4) {
                break;
            }
            $dates[] = $next->format('Y-m-d');
            $after = clone $next;
            $after->mday++;
        }
        $this->assertSame([
            '2026-01-15', '2026-02-15', '2026-03-15', '2026-04-15',
        ], $dates);
    }

    public function testYearlyWeekdayViaProperties(): void
    {
        // 2026-01-13 is the 2nd Tuesday of January
        $r = new Horde_Date_Recurrence('2026-01-13 10:00:00');
        $r->recurType = Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY;
        $r->recurInterval = 1;
        $dates = [];
        $after = new Horde_Date('2026-01-01');
        while ($next = $r->nextRecurrence($after)) {
            if (count($dates) >= 3) {
                break;
            }
            $dates[] = $next->format('Y-m-d');
            $after = clone $next;
            $after->mday++;
        }
        $this->assertSame([
            '2026-01-13', '2027-01-12', '2028-01-11',
        ], $dates);
    }
}
