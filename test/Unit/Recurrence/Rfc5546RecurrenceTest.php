<?php

declare(strict_types=1);

namespace Horde\Date\Test\Unit\Recurrence;

use DateTimeImmutable;
use DateTimeZone;
use Horde\Date\Recurrence\DayMask;
use Horde\Date\Recurrence\Recurrence;
use Horde\Date\Recurrence\RecurrenceType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests recurrence expansion using data from RFC 5546 §4.4 canonical examples.
 */
#[CoversClass(Recurrence::class)]
final class Rfc5546RecurrenceTest extends TestCase
{
    private DateTimeZone $tz;

    protected function setUp(): void
    {
        $this->tz = new DateTimeZone('America/Los_Angeles');
    }

    /**
     * RFC 5546 §4.4.1: Weekly recurring event (FREQ=WEEKLY;COUNT=20;BYDAY=TU)
     * starting 1997-07-01 (Tuesday) with one RDATE and two EXDATEs.
     *
     * RRULE produces 20 Tuesdays from 1997-07-01 through 1997-11-11.
     * RDATE adds 1997-09-10 (Wednesday).
     * EXDATE removes 1997-09-09 (Tuesday) and 1997-10-28 (Tuesday).
     *
     * Expected: 20 - 2 + 1 = 19 occurrences total.
     */
    #[Test]
    public function weeklyRecurrenceWithRdateAndExdate(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('1997-07-01 14:00', $this->tz));
        $recurrence->setType(RecurrenceType::Weekly);
        $recurrence->setInterval(1);
        $recurrence->setDayMask(DayMask::TUESDAY);
        $recurrence->setCount(20);

        $recurrence->addRdate(new DateTimeImmutable('1997-09-10 14:00', $this->tz));
        $recurrence->addException(new DateTimeImmutable('1997-09-09', $this->tz));
        $recurrence->addException(new DateTimeImmutable('1997-10-28', $this->tz));

        $results = $recurrence->expandRange(
            new DateTimeImmutable('1997-07-01 00:00', $this->tz),
            new DateTimeImmutable('1997-12-31 23:59', $this->tz),
        );

        $this->assertCount(19, $results);
    }

    /**
     * Verify expandRange for a sub-window: July 1997 should have 5 Tuesdays
     * (Jul 1, 8, 15, 22, 29).
     */
    #[Test]
    public function expandRangeReturnsCorrectSubset(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('1997-07-01 14:00', $this->tz));
        $recurrence->setType(RecurrenceType::Weekly);
        $recurrence->setInterval(1);
        $recurrence->setDayMask(DayMask::TUESDAY);
        $recurrence->setCount(20);

        $results = $recurrence->expandRange(
            new DateTimeImmutable('1997-07-01 00:00', $this->tz),
            new DateTimeImmutable('1997-07-31 23:59', $this->tz),
        );

        $this->assertCount(5, $results);
        $dates = array_map(fn($d) => $d->format('Y-m-d'), $results);
        $this->assertSame('1997-07-01', $dates[0]);
        $this->assertSame('1997-07-08', $dates[1]);
        $this->assertSame('1997-07-15', $dates[2]);
        $this->assertSame('1997-07-22', $dates[3]);
        $this->assertSame('1997-07-29', $dates[4]);
    }

    /**
     * The RDATE 1997-09-10 (a Wednesday) should appear in the expansion
     * even though the RRULE only produces Tuesdays.
     */
    #[Test]
    public function rdateAppearsInExpansion(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('1997-07-01 14:00', $this->tz));
        $recurrence->setType(RecurrenceType::Weekly);
        $recurrence->setInterval(1);
        $recurrence->setDayMask(DayMask::TUESDAY);
        $recurrence->setCount(20);

        $recurrence->addRdate(new DateTimeImmutable('1997-09-10 14:00', $this->tz));

        $results = $recurrence->expandRange(
            new DateTimeImmutable('1997-09-08 00:00', $this->tz),
            new DateTimeImmutable('1997-09-12 23:59', $this->tz),
        );

        $dates = array_map(fn($d) => $d->format('Y-m-d'), $results);
        $this->assertContains('1997-09-09', $dates);
        $this->assertContains('1997-09-10', $dates);
    }

    /**
     * The EXDATEs 1997-09-09 and 1997-10-28 should NOT appear in expansion.
     */
    #[Test]
    public function exdateExcludesFromExpansion(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('1997-07-01 14:00', $this->tz));
        $recurrence->setType(RecurrenceType::Weekly);
        $recurrence->setInterval(1);
        $recurrence->setDayMask(DayMask::TUESDAY);
        $recurrence->setCount(20);

        $recurrence->addException(new DateTimeImmutable('1997-09-09', $this->tz));
        $recurrence->addException(new DateTimeImmutable('1997-10-28', $this->tz));

        $results = $recurrence->expandRange(
            new DateTimeImmutable('1997-07-01 00:00', $this->tz),
            new DateTimeImmutable('1997-12-31 23:59', $this->tz),
        );

        $dates = array_map(fn($d) => $d->format('Y-m-d'), $results);
        $this->assertNotContains('1997-09-09', $dates);
        $this->assertNotContains('1997-10-28', $dates);
        $this->assertCount(18, $results);
    }

    /**
     * RFC 5546 §4.4.2: Monthly recurrence (FREQ=MONTHLY;BYMONTHDAY=1;UNTIL=19980901T210000Z)
     * starting 1997-06-01.
     *
     * This should produce occurrences on the 1st of each month from June 1997 through
     * September 1998 (UNTIL is inclusive).
     */
    #[Test]
    public function monthlyRecurrenceFromRfc(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('1997-06-01 21:00', new DateTimeZone('UTC')));
        $recurrence->fromRRule20('FREQ=MONTHLY;BYMONTHDAY=1;UNTIL=19980901T210000Z');

        $results = $recurrence->expandRange(
            new DateTimeImmutable('1997-06-01 00:00', new DateTimeZone('UTC')),
            new DateTimeImmutable('1998-12-31 23:59', new DateTimeZone('UTC')),
        );

        $dates = array_map(fn($d) => $d->format('Y-m-d'), $results);
        $this->assertContains('1997-06-01', $dates);
        $this->assertContains('1997-07-01', $dates);
        $this->assertContains('1998-01-01', $dates);
        $this->assertContains('1998-09-01', $dates);
        $this->assertNotContains('1998-10-01', $dates);

        $this->assertCount(16, $results);
    }
}
