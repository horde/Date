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
final class RdateTest extends TestCase
{
    private DateTimeZone $tz;

    protected function setUp(): void
    {
        $this->tz = new DateTimeZone('UTC');
    }

    #[Test]
    public function addAndHasRdate(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('2026-06-01 10:00', $this->tz));
        $rdate = new DateTimeImmutable('2026-06-15', $this->tz);

        $this->assertFalse($recurrence->hasRdate($rdate));

        $recurrence->addRdate($rdate);
        $this->assertTrue($recurrence->hasRdate($rdate));
        $this->assertSame(['20260615'], $recurrence->getRdates());
    }

    #[Test]
    public function addRdateDeduplicates(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('2026-06-01 10:00', $this->tz));
        $rdate = new DateTimeImmutable('2026-06-15', $this->tz);

        $recurrence->addRdate($rdate);
        $recurrence->addRdate($rdate);

        $this->assertCount(1, $recurrence->getRdates());
    }

    #[Test]
    public function deleteRdate(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('2026-06-01 10:00', $this->tz));
        $rdate = new DateTimeImmutable('2026-06-15', $this->tz);

        $recurrence->addRdate($rdate);
        $recurrence->deleteRdate($rdate);

        $this->assertFalse($recurrence->hasRdate($rdate));
        $this->assertSame([], $recurrence->getRdates());
    }

    #[Test]
    public function setRdates(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('2026-06-01 10:00', $this->tz));
        $recurrence->setRdates(['20260610', '20260620', '20260630']);

        $this->assertSame(['20260610', '20260620', '20260630'], $recurrence->getRdates());
    }

    #[Test]
    public function rdateAppearsInNextActiveRecurrence(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('2026-06-01 10:00', $this->tz));
        $recurrence->setType(RecurrenceType::None);
        $recurrence->addRdate(new DateTimeImmutable('2026-06-15', $this->tz));
        $recurrence->addRdate(new DateTimeImmutable('2026-06-20', $this->tz));

        // Query after the start date — only RDATEs should be returned
        $next = $recurrence->nextActiveRecurrence(new DateTimeImmutable('2026-06-02 10:00', $this->tz));

        $this->assertNotNull($next);
        $this->assertSame('20260615', $next->format('Ymd'));
    }

    #[Test]
    public function rdateExcludedByExdate(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('2026-06-01 10:00', $this->tz));
        $recurrence->setType(RecurrenceType::None);

        $rdate = new DateTimeImmutable('2026-06-15', $this->tz);
        $recurrence->addRdate($rdate);
        $recurrence->addException($rdate);

        // Query after the start date — the only RDATE is excluded
        $next = $recurrence->nextActiveRecurrence(new DateTimeImmutable('2026-06-02 10:00', $this->tz));
        $this->assertNull($next);
    }

    #[Test]
    public function rdateMergesWithRrule(): void
    {
        // June 1, 2026 is Monday
        $recurrence = new Recurrence(new DateTimeImmutable('2026-06-01 10:00', $this->tz));
        $recurrence->setType(RecurrenceType::Weekly);
        $recurrence->setInterval(1);
        $recurrence->setDayMask(2); // Monday

        // Add an RDATE on Thursday June 4 (not a regular recurrence day)
        $recurrence->addRdate(new DateTimeImmutable('2026-06-04', $this->tz));

        // Next after June 2 should be the RDATE on June 4 (Thursday)
        // since next Monday is June 8
        $next = $recurrence->nextActiveRecurrence(new DateTimeImmutable('2026-06-02 10:00', $this->tz));
        $this->assertNotNull($next);
        $this->assertSame('20260604', $next->format('Ymd'));
    }

    #[Test]
    public function hasActiveRecurrenceWithOnlyRdates(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('2026-06-01 10:00', $this->tz));
        $recurrence->setType(RecurrenceType::None);
        $recurrence->addRdate(new DateTimeImmutable('2026-06-15', $this->tz));

        $this->assertTrue($recurrence->hasActiveRecurrence());
    }

    #[Test]
    public function resetClearsRdates(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('2026-06-01 10:00', $this->tz));
        $recurrence->addRdate(new DateTimeImmutable('2026-06-15', $this->tz));
        $recurrence->reset();

        $this->assertSame([], $recurrence->getRdates());
    }

    #[Test]
    public function hashRoundTrip(): void
    {
        $recurrence = new Recurrence(new DateTimeImmutable('2026-06-01 10:00', $this->tz));
        $recurrence->setType(RecurrenceType::Daily);
        $recurrence->addRdate(new DateTimeImmutable('2026-07-04', $this->tz));

        $hash = $recurrence->toHash();
        $restored = Recurrence::fromHash($hash);

        $this->assertSame(['20260704'], $restored->getRdates());
    }
}
