<?php

declare(strict_types=1);

namespace Horde\Date\Test\Unit\Recurrence;

use DateTimeImmutable;
use DateTimeZone;
use Horde\Date\Recurrence\Recurrence;
use Horde\Date\Recurrence\RecurrenceType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Recurrence::class)]
final class ExpandRangeTest extends TestCase
{
    private DateTimeZone $tz;

    protected function setUp(): void
    {
        $this->tz = new DateTimeZone('UTC');
    }

    #[Test]
    public function expandRangeWithDailyRule(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('2026-06-01 10:00', $this->tz));
        $recurrence->setType(RecurrenceType::Daily);
        $recurrence->setInterval(1);

        $results = $recurrence->expandRange(
            new DateTimeImmutable('2026-06-01 00:00', $this->tz),
            new DateTimeImmutable('2026-06-05 23:59', $this->tz),
        );

        $this->assertCount(5, $results);
        $this->assertSame('20260601', $results[0]->format('Ymd'));
        $this->assertSame('20260605', $results[4]->format('Ymd'));
    }

    #[Test]
    public function expandRangeWithRdatesOnly(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('2026-05-01 10:00', $this->tz));
        $recurrence->setType(RecurrenceType::None);
        $recurrence->addRdate(new DateTimeImmutable('2026-06-10', $this->tz));
        $recurrence->addRdate(new DateTimeImmutable('2026-06-20', $this->tz));
        $recurrence->addRdate(new DateTimeImmutable('2026-07-01', $this->tz));

        $results = $recurrence->expandRange(
            new DateTimeImmutable('2026-06-01 00:00', $this->tz),
            new DateTimeImmutable('2026-06-30 23:59', $this->tz),
        );

        $this->assertCount(2, $results);
        $this->assertSame('20260610', $results[0]->format('Ymd'));
        $this->assertSame('20260620', $results[1]->format('Ymd'));
    }

    #[Test]
    public function expandRangeWithRruleAndRdates(): void
    {
        // June 1, 2026 is Monday
        $recurrence = new Recurrence(new DateTimeImmutable('2026-06-01 10:00', $this->tz));
        $recurrence->setType(RecurrenceType::Weekly);
        $recurrence->setInterval(1);
        $recurrence->setDayMask(2); // Monday

        // RDATE on Thursday June 4
        $recurrence->addRdate(new DateTimeImmutable('2026-06-04', $this->tz));

        $results = $recurrence->expandRange(
            new DateTimeImmutable('2026-06-01 00:00', $this->tz),
            new DateTimeImmutable('2026-06-10 23:59', $this->tz),
        );

        $dates = array_map(fn($d) => $d->format('Ymd'), $results);
        $this->assertContains('20260601', $dates); // Monday (start, first recurrence)
        $this->assertContains('20260604', $dates); // RDATE Thursday
        $this->assertContains('20260608', $dates); // Next Monday
        $this->assertCount(3, $results);
    }

    #[Test]
    public function expandRangeWithExdates(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('2026-06-01 10:00', $this->tz));
        $recurrence->setType(RecurrenceType::Daily);
        $recurrence->setInterval(1);
        $recurrence->addException(new DateTimeImmutable('2026-06-03', $this->tz));

        $results = $recurrence->expandRange(
            new DateTimeImmutable('2026-06-01 00:00', $this->tz),
            new DateTimeImmutable('2026-06-05 23:59', $this->tz),
        );

        $dates = array_map(fn($d) => $d->format('Ymd'), $results);
        $this->assertCount(4, $results);
        $this->assertNotContains('20260603', $dates);
    }

    #[Test]
    public function expandRangeRdateExcludedByExdate(): void
    {
        // Use start date outside the query range so it doesn't interfere
        $recurrence = new Recurrence(new DateTimeImmutable('2026-05-01 10:00', $this->tz));
        $recurrence->setType(RecurrenceType::None);
        $recurrence->addRdate(new DateTimeImmutable('2026-06-10', $this->tz));
        $recurrence->addRdate(new DateTimeImmutable('2026-06-15', $this->tz));
        $recurrence->addException(new DateTimeImmutable('2026-06-10', $this->tz));

        $results = $recurrence->expandRange(
            new DateTimeImmutable('2026-06-01 00:00', $this->tz),
            new DateTimeImmutable('2026-06-30 23:59', $this->tz),
        );

        $this->assertCount(1, $results);
        $this->assertSame('20260615', $results[0]->format('Ymd'));
    }

    #[Test]
    public function expandRangeRespectsLimit(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('2026-06-01 10:00', $this->tz));
        $recurrence->setType(RecurrenceType::Daily);
        $recurrence->setInterval(1);

        $results = $recurrence->expandRange(
            new DateTimeImmutable('2026-06-01 00:00', $this->tz),
            new DateTimeImmutable('2026-12-31 23:59', $this->tz),
            3,
        );

        $this->assertCount(3, $results);
    }

    #[Test]
    public function expandRangeReturnsSortedResults(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('2026-06-01 10:00', $this->tz));
        $recurrence->setType(RecurrenceType::Daily);
        $recurrence->setInterval(3);
        // RDATEs interleave with RRULE dates
        $recurrence->addRdate(new DateTimeImmutable('2026-06-05', $this->tz));
        $recurrence->addRdate(new DateTimeImmutable('2026-06-02', $this->tz));

        $results = $recurrence->expandRange(
            new DateTimeImmutable('2026-06-01 00:00', $this->tz),
            new DateTimeImmutable('2026-06-10 23:59', $this->tz),
        );

        $dates = array_map(fn($d) => $d->format('Ymd'), $results);
        $sorted = $dates;
        sort($sorted);
        $this->assertSame($sorted, $dates);
    }

    #[Test]
    public function expandRangeDeduplicates(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('2026-06-01 10:00', $this->tz));
        $recurrence->setType(RecurrenceType::Daily);
        $recurrence->setInterval(1);
        // Add RDATE on a day that already occurs via RRULE
        $recurrence->addRdate(new DateTimeImmutable('2026-06-03', $this->tz));

        $results = $recurrence->expandRange(
            new DateTimeImmutable('2026-06-01 00:00', $this->tz),
            new DateTimeImmutable('2026-06-05 23:59', $this->tz),
        );

        $dates = array_map(fn($d) => $d->format('Ymd'), $results);
        $unique = array_unique($dates);
        $this->assertSame(array_values($unique), $dates);
        $this->assertCount(5, $results);
    }
}
