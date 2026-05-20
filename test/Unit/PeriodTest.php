<?php

declare(strict_types=1);

namespace Horde\Date\Test\Unit;

use DateTimeImmutable;
use DateTimeZone;
use Horde\Date\Date;
use Horde\Date\DateException;
use Horde\Date\Duration;
use Horde\Date\Period;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Period::class)]
class PeriodTest extends TestCase
{
    // =========================================================================
    // Construction — fromStartEnd
    // =========================================================================

    public function testFromStartEnd(): void
    {
        $start = new DateTimeImmutable('2026-05-20T09:00:00Z');
        $end = new DateTimeImmutable('2026-05-20T10:00:00Z');

        $p = Period::fromStartEnd($start, $end);
        $this->assertEquals($start, $p->getStart());
        $this->assertEquals($end, $p->getEnd());
    }

    public function testFromStartEndSameTime(): void
    {
        $dt = new DateTimeImmutable('2026-05-20T09:00:00Z');
        $p = Period::fromStartEnd($dt, $dt);
        $this->assertTrue($p->isZeroLength());
    }

    public function testFromStartEndInvalid(): void
    {
        $this->expectException(DateException::class);
        Period::fromStartEnd(
            new DateTimeImmutable('2026-05-20T12:00:00Z'),
            new DateTimeImmutable('2026-05-20T09:00:00Z'),
        );
    }

    // =========================================================================
    // Construction — fromStartDuration
    // =========================================================================

    public function testFromStartDuration(): void
    {
        $start = new DateTimeImmutable('2026-05-20T09:00:00Z');
        $duration = Duration::fromIcalendar('PT2H');

        $p = Period::fromStartDuration($start, $duration);
        $this->assertEquals($start, $p->getStart());
        $this->assertSame(
            '2026-05-20T11:00:00+00:00',
            $p->getEnd()->format('c'),
        );
    }

    public function testFromStartDurationNegativeThrows(): void
    {
        $this->expectException(DateException::class);
        Period::fromStartDuration(
            new DateTimeImmutable('2026-05-20T09:00:00Z'),
            Duration::fromIcalendar('-PT1H'),
        );
    }

    public function testFromStartDurationZero(): void
    {
        $start = new DateTimeImmutable('2026-05-20T09:00:00Z');
        $p = Period::fromStartDuration($start, Duration::zero());
        $this->assertTrue($p->isZeroLength());
    }

    // =========================================================================
    // Construction — fromIcalendar
    // =========================================================================

    public function testFromIcalendarStartEnd(): void
    {
        $p = Period::fromIcalendar('20260520T090000Z/20260520T100000Z');
        $this->assertSame('2026-05-20T09:00:00+00:00', $p->getStart()->format('c'));
        $this->assertSame('2026-05-20T10:00:00+00:00', $p->getEnd()->format('c'));
    }

    public function testFromIcalendarStartDuration(): void
    {
        $p = Period::fromIcalendar('20260520T090000Z/PT1H30M');
        $this->assertSame('2026-05-20T09:00:00+00:00', $p->getStart()->format('c'));
        $this->assertSame('2026-05-20T10:30:00+00:00', $p->getEnd()->format('c'));
    }

    public function testFromIcalendarLocalTime(): void
    {
        $p = Period::fromIcalendar('20260520T090000/20260520T100000');
        $this->assertSame('09:00:00', $p->getStart()->format('H:i:s'));
        $this->assertSame('10:00:00', $p->getEnd()->format('H:i:s'));
    }

    #[DataProvider('invalidIcalendarProvider')]
    public function testFromIcalendarInvalid(string $input): void
    {
        $this->expectException(DateException::class);
        Period::fromIcalendar($input);
    }

    public static function invalidIcalendarProvider(): array
    {
        return [
            'empty' => [''],
            'no slash' => ['20260520T090000Z'],
            'empty start' => ['/20260520T100000Z'],
            'empty end' => ['20260520T090000Z/'],
        ];
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function testGetStartReturnsDate(): void
    {
        $p = Period::fromIcalendar('20260520T090000Z/20260520T110000Z');
        $this->assertInstanceOf(Date::class, $p->getStart());
        $this->assertInstanceOf(Date::class, $p->getEnd());
    }

    public function testGetDuration(): void
    {
        $p = Period::fromIcalendar('20260520T090000Z/20260520T110000Z');
        $this->assertSame(7200, $p->getDuration()->toSeconds());
    }

    public function testGetWidth(): void
    {
        $p = Period::fromIcalendar('20260520T090000Z/20260520T110000Z');
        $this->assertSame(7200, $p->getWidth());
    }

    // =========================================================================
    // Containment
    // =========================================================================

    public function testContainsInside(): void
    {
        $p = Period::fromIcalendar('20260520T090000Z/20260520T110000Z');
        $inside = new DateTimeImmutable('2026-05-20T10:00:00Z');
        $this->assertTrue($p->contains($inside));
    }

    public function testContainsStart(): void
    {
        $p = Period::fromIcalendar('20260520T090000Z/20260520T110000Z');
        $start = new DateTimeImmutable('2026-05-20T09:00:00Z');
        $this->assertTrue($p->contains($start));
    }

    public function testContainsEndExclusive(): void
    {
        $p = Period::fromIcalendar('20260520T090000Z/20260520T110000Z');
        $end = new DateTimeImmutable('2026-05-20T11:00:00Z');
        $this->assertFalse($p->contains($end));
    }

    public function testContainsInclusiveEnd(): void
    {
        $p = Period::fromIcalendar('20260520T090000Z/20260520T110000Z');
        $end = new DateTimeImmutable('2026-05-20T11:00:00Z');
        $this->assertTrue($p->containsInclusive($end));
    }

    public function testContainsOutside(): void
    {
        $p = Period::fromIcalendar('20260520T090000Z/20260520T110000Z');
        $before = new DateTimeImmutable('2026-05-20T08:00:00Z');
        $after = new DateTimeImmutable('2026-05-20T12:00:00Z');
        $this->assertFalse($p->contains($before));
        $this->assertFalse($p->contains($after));
    }

    // =========================================================================
    // Overlap
    // =========================================================================

    public function testOverlapsPartial(): void
    {
        $a = Period::fromIcalendar('20260520T090000Z/20260520T110000Z');
        $b = Period::fromIcalendar('20260520T100000Z/20260520T120000Z');
        $this->assertTrue($a->overlaps($b));
        $this->assertTrue($b->overlaps($a));
    }

    public function testOverlapsEnclosed(): void
    {
        $outer = Period::fromIcalendar('20260520T080000Z/20260520T130000Z');
        $inner = Period::fromIcalendar('20260520T090000Z/20260520T110000Z');
        $this->assertTrue($outer->overlaps($inner));
        $this->assertTrue($inner->overlaps($outer));
    }

    public function testOverlapsAdjacent(): void
    {
        $a = Period::fromIcalendar('20260520T090000Z/20260520T100000Z');
        $b = Period::fromIcalendar('20260520T100000Z/20260520T110000Z');
        $this->assertFalse($a->overlaps($b));
    }

    public function testOverlapsDisjoint(): void
    {
        $a = Period::fromIcalendar('20260520T090000Z/20260520T100000Z');
        $b = Period::fromIcalendar('20260520T110000Z/20260520T120000Z');
        $this->assertFalse($a->overlaps($b));
    }

    // =========================================================================
    // Encloses
    // =========================================================================

    public function testEncloses(): void
    {
        $outer = Period::fromIcalendar('20260520T080000Z/20260520T130000Z');
        $inner = Period::fromIcalendar('20260520T090000Z/20260520T110000Z');
        $this->assertTrue($outer->encloses($inner));
        $this->assertFalse($inner->encloses($outer));
    }

    public function testEnclosesSelf(): void
    {
        $p = Period::fromIcalendar('20260520T090000Z/20260520T110000Z');
        $this->assertTrue($p->encloses($p));
    }

    // =========================================================================
    // Intersect
    // =========================================================================

    public function testIntersect(): void
    {
        $a = Period::fromIcalendar('20260520T090000Z/20260520T110000Z');
        $b = Period::fromIcalendar('20260520T100000Z/20260520T120000Z');

        $intersection = $a->intersect($b);
        $this->assertNotNull($intersection);
        $this->assertSame('2026-05-20T10:00:00+00:00', $intersection->getStart()->format('c'));
        $this->assertSame('2026-05-20T11:00:00+00:00', $intersection->getEnd()->format('c'));
    }

    public function testIntersectDisjoint(): void
    {
        $a = Period::fromIcalendar('20260520T090000Z/20260520T100000Z');
        $b = Period::fromIcalendar('20260520T110000Z/20260520T120000Z');
        $this->assertNull($a->intersect($b));
    }

    public function testIntersectAdjacent(): void
    {
        $a = Period::fromIcalendar('20260520T090000Z/20260520T100000Z');
        $b = Period::fromIcalendar('20260520T100000Z/20260520T110000Z');
        $this->assertNull($a->intersect($b));
    }

    // =========================================================================
    // Comparison
    // =========================================================================

    public function testEquals(): void
    {
        $a = Period::fromIcalendar('20260520T090000Z/20260520T110000Z');
        $b = Period::fromIcalendar('20260520T090000Z/PT2H');
        $this->assertTrue($a->equals($b));
    }

    public function testCompareTo(): void
    {
        $early = Period::fromIcalendar('20260520T080000Z/20260520T090000Z');
        $late = Period::fromIcalendar('20260520T100000Z/20260520T110000Z');
        $this->assertSame(-1, $early->compareTo($late));
        $this->assertSame(1, $late->compareTo($early));
    }

    public function testCompareToSameStartDifferentEnd(): void
    {
        $short = Period::fromIcalendar('20260520T090000Z/20260520T100000Z');
        $long = Period::fromIcalendar('20260520T090000Z/20260520T120000Z');
        $this->assertSame(-1, $short->compareTo($long));
    }

    // =========================================================================
    // Serialization
    // =========================================================================

    public function testToIcalendar(): void
    {
        $p = Period::fromIcalendar('20260520T090000Z/20260520T110000Z');
        $this->assertSame('20260520T090000Z/20260520T110000Z', $p->toIcalendar());
    }

    public function testToIcalendarWithDuration(): void
    {
        $p = Period::fromIcalendar('20260520T090000Z/20260520T110000Z');
        $this->assertSame('20260520T090000Z/PT2H', $p->toIcalendarWithDuration());
    }

    public function testStringable(): void
    {
        $p = Period::fromIcalendar('20260520T090000Z/20260520T100000Z');
        $this->assertSame('20260520T090000Z/20260520T100000Z', (string) $p);
    }

    // =========================================================================
    // Roundtrip
    // =========================================================================

    public function testRoundtripStartEnd(): void
    {
        $original = '20260520T090000Z/20260520T110000Z';
        $p = Period::fromIcalendar($original);
        $this->assertSame($original, $p->toIcalendar());
    }

    public function testRoundtripStartDurationToStartEnd(): void
    {
        $p = Period::fromIcalendar('20260520T090000Z/PT2H');
        $this->assertSame('20260520T090000Z/20260520T110000Z', $p->toIcalendar());
        $this->assertSame('20260520T090000Z/PT2H', $p->toIcalendarWithDuration());
    }

    // =========================================================================
    // Edge cases
    // =========================================================================

    public function testCrossingMidnight(): void
    {
        $p = Period::fromIcalendar('20260520T230000Z/20260521T010000Z');
        $this->assertSame(7200, $p->getWidth());
    }

    public function testLongPeriod(): void
    {
        $p = Period::fromStartDuration(
            new DateTimeImmutable('2026-01-01T00:00:00Z'),
            Duration::fromIcalendar('P365D'),
        );
        $this->assertSame('2027-01-01T00:00:00+00:00', $p->getEnd()->format('c'));
    }

    // =========================================================================
    // Cross-timezone handling
    // =========================================================================

    public function testCrossTimezoneSerializationNormalizesToUtc(): void
    {
        // NYC 09:00 (-04:00) = UTC 13:00
        $nyc = new DateTimeImmutable('2026-05-20T09:00:00', new DateTimeZone('America/New_York'));
        $utc = new DateTimeImmutable('2026-05-20T14:00:00', new DateTimeZone('UTC'));

        $p = Period::fromStartEnd($nyc, $utc);
        // Both normalized to UTC in output
        $this->assertSame('20260520T130000Z/20260520T140000Z', $p->toIcalendar());
    }

    public function testCrossTimezoneWithDurationNormalizesToUtc(): void
    {
        $nyc = new DateTimeImmutable('2026-05-20T09:00:00', new DateTimeZone('America/New_York'));
        $berlin = new DateTimeImmutable('2026-05-20T15:00:00', new DateTimeZone('Europe/Berlin'));

        $p = Period::fromStartEnd($nyc, $berlin);
        $this->assertSame('20260520T130000Z/P0D', $p->toIcalendarWithDuration());
    }

    public function testSameTimezonePreservesLocalTime(): void
    {
        $start = new DateTimeImmutable('2026-05-20T09:00:00', new DateTimeZone('America/New_York'));
        $end = new DateTimeImmutable('2026-05-20T11:00:00', new DateTimeZone('America/New_York'));

        $p = Period::fromStartEnd($start, $end);
        // Same timezone: local time preserved (no Z suffix)
        $this->assertSame('20260520T090000/20260520T110000', $p->toIcalendar());
    }

    public function testCrossTimezoneWidthIsCorrect(): void
    {
        // NYC 09:00 = UTC 13:00; UTC 14:00 is 1 hour later
        $nyc = new DateTimeImmutable('2026-05-20T09:00:00', new DateTimeZone('America/New_York'));
        $utc = new DateTimeImmutable('2026-05-20T14:00:00', new DateTimeZone('UTC'));

        $p = Period::fromStartEnd($nyc, $utc);
        $this->assertSame(3600, $p->getWidth());
    }

    public function testCrossTimezoneContains(): void
    {
        $nyc = new DateTimeImmutable('2026-05-20T09:00:00', new DateTimeZone('America/New_York'));
        $utc = new DateTimeImmutable('2026-05-20T14:00:00', new DateTimeZone('UTC'));
        $p = Period::fromStartEnd($nyc, $utc);

        // Berlin 15:00 = UTC 13:00 — within [UTC 13:00, UTC 14:00)
        $inside = new DateTimeImmutable('2026-05-20T15:00:00', new DateTimeZone('Europe/Berlin'));
        $this->assertTrue($p->contains($inside));

        // Berlin 14:00 = UTC 12:00 — before start
        $before = new DateTimeImmutable('2026-05-20T14:00:00', new DateTimeZone('Europe/Berlin'));
        $this->assertFalse($p->contains($before));
    }

    public function testCrossTimezoneEndBeforeStartThrows(): void
    {
        // NYC 09:00 = UTC 13:00; UTC 10:00 is before that
        $nyc = new DateTimeImmutable('2026-05-20T09:00:00', new DateTimeZone('America/New_York'));
        $utc = new DateTimeImmutable('2026-05-20T10:00:00', new DateTimeZone('UTC'));

        $this->expectException(DateException::class);
        Period::fromStartEnd($nyc, $utc);
    }
}
