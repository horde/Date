<?php

declare(strict_types=1);

namespace Horde\Date\Test\Unit;

use DateTimeImmutable;
use DateTimeZone;
use Horde\Date\DateException;
use Horde\Date\Duration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Duration::class)]
class DurationTest extends TestCase
{
    // =========================================================================
    // fromIcalendar — valid inputs
    // =========================================================================

    #[DataProvider('validIcalendarProvider')]
    public function testFromIcalendarValid(string $input, int $expectedSeconds): void
    {
        $d = Duration::fromIcalendar($input);
        $this->assertSame($expectedSeconds, $d->toSeconds());
    }

    public static function validIcalendarProvider(): array
    {
        return [
            'one week' => ['P1W', 604800],
            'two weeks' => ['P2W', 1209600],
            'one day' => ['P1D', 86400],
            'fifteen minutes' => ['PT15M', 900],
            'one hour' => ['PT1H', 3600],
            'one hour thirty minutes' => ['PT1H30M', 5400],
            'one day two hours thirty minutes' => ['P1DT2H30M', 95400],
            'complex' => ['P15DT5H20M30S', 1315230],
            'seconds only' => ['PT45S', 45],
            'negative one day' => ['-P1D', -86400],
            'positive prefix' => ['+PT1H', 3600],
            'negative fifteen minutes' => ['-PT15M', -900],
            'zero days' => ['P0D', 0],
            'day and time' => ['P1DT1H', 90000],
            'hours and seconds' => ['PT2H30S', 7230],
        ];
    }

    // =========================================================================
    // fromIcalendar — invalid inputs
    // =========================================================================

    #[DataProvider('invalidIcalendarProvider')]
    public function testFromIcalendarInvalid(string $input): void
    {
        $this->expectException(DateException::class);
        Duration::fromIcalendar($input);
    }

    public static function invalidIcalendarProvider(): array
    {
        return [
            'empty string' => [''],
            'no P' => ['1D'],
            'P only' => ['P'],
            'invalid designator' => ['P1X'],
            'weeks mixed with days' => ['P1W2D'],
            'missing T before time' => ['P1H'],
            'random text' => ['hello'],
            'double T' => ['PTT1H'],
        ];
    }

    // =========================================================================
    // fromSeconds
    // =========================================================================

    public function testFromSecondsPositive(): void
    {
        $d = Duration::fromSeconds(90061);
        $this->assertSame(1, $d->getDays());
        $this->assertSame(1, $d->getHours());
        $this->assertSame(1, $d->getMinutes());
        $this->assertSame(1, $d->getSeconds());
        $this->assertFalse($d->isNegative());
        $this->assertSame(90061, $d->toSeconds());
    }

    public function testFromSecondsNegative(): void
    {
        $d = Duration::fromSeconds(-3600);
        $this->assertTrue($d->isNegative());
        $this->assertSame(1, $d->getHours());
        $this->assertSame(-3600, $d->toSeconds());
    }

    public function testFromSecondsZero(): void
    {
        $d = Duration::fromSeconds(0);
        $this->assertTrue($d->isZero());
        $this->assertFalse($d->isNegative());
    }

    // =========================================================================
    // fromParts
    // =========================================================================

    public function testFromPartsWeeks(): void
    {
        $d = Duration::fromParts(weeks: 3);
        $this->assertTrue($d->isWeeksForm());
        $this->assertSame(3, $d->getWeeks());
        $this->assertSame(3 * 7 * 86400, $d->toSeconds());
    }

    public function testFromPartsDaysAndTime(): void
    {
        $d = Duration::fromParts(days: 2, hours: 3, minutes: 15);
        $this->assertFalse($d->isWeeksForm());
        $this->assertSame(2, $d->getDays());
        $this->assertSame(3, $d->getHours());
        $this->assertSame(15, $d->getMinutes());
    }

    public function testFromPartsNegative(): void
    {
        $d = Duration::fromParts(hours: 1, negative: true);
        $this->assertTrue($d->isNegative());
        $this->assertSame(-3600, $d->toSeconds());
    }

    public function testFromPartsMutuallyExclusive(): void
    {
        $this->expectException(DateException::class);
        Duration::fromParts(weeks: 1, days: 2);
    }

    // =========================================================================
    // fromDateDiff
    // =========================================================================

    public function testFromDateDiff(): void
    {
        $start = new DateTimeImmutable('2026-05-20T09:00:00Z');
        $end = new DateTimeImmutable('2026-05-20T11:30:00Z');

        $d = Duration::fromDateDiff($start, $end);
        $this->assertSame(9000, $d->toSeconds());
        $this->assertFalse($d->isNegative());
    }

    public function testFromDateDiffNegative(): void
    {
        $start = new DateTimeImmutable('2026-05-20T12:00:00Z');
        $end = new DateTimeImmutable('2026-05-20T10:00:00Z');

        $d = Duration::fromDateDiff($start, $end);
        $this->assertTrue($d->isNegative());
        $this->assertSame(-7200, $d->toSeconds());
    }

    // =========================================================================
    // zero
    // =========================================================================

    public function testZero(): void
    {
        $d = Duration::zero();
        $this->assertTrue($d->isZero());
        $this->assertSame(0, $d->toSeconds());
        $this->assertSame('P0D', $d->toIcalendar());
    }

    // =========================================================================
    // Arithmetic
    // =========================================================================

    public function testAddTo(): void
    {
        $dt = new DateTimeImmutable('2026-05-20T09:00:00Z');
        $d = Duration::fromIcalendar('PT2H30M');

        $result = $d->addTo($dt);
        $this->assertSame('2026-05-20T11:30:00+00:00', $result->format('c'));
    }

    public function testSubtractFrom(): void
    {
        $dt = new DateTimeImmutable('2026-05-20T12:00:00Z');
        $d = Duration::fromIcalendar('PT1H');

        $result = $d->subtractFrom($dt);
        $this->assertSame('2026-05-20T11:00:00+00:00', $result->format('c'));
    }

    public function testAddToDstTransitionPreservesWallClock(): void
    {
        // US spring-forward: 2026-03-08 02:00 EST → 03:00 EDT
        // Adding P1D should give same wall-clock time, not +86400 seconds
        $tz = new DateTimeZone('America/New_York');
        $before = new DateTimeImmutable('2026-03-07T10:00:00', $tz);
        $d = Duration::fromIcalendar('P1D');

        $result = $d->addTo($before);
        $this->assertSame('10:00:00', $result->format('H:i:s'));
        $this->assertSame('2026-03-08', $result->format('Y-m-d'));
    }

    public function testSubtractFromDstTransitionPreservesWallClock(): void
    {
        // US fall-back: 2026-11-01 02:00 EDT → 01:00 EST
        $tz = new DateTimeZone('America/New_York');
        $after = new DateTimeImmutable('2026-11-02T10:00:00', $tz);
        $d = Duration::fromIcalendar('P1D');

        $result = $d->subtractFrom($after);
        $this->assertSame('10:00:00', $result->format('H:i:s'));
        $this->assertSame('2026-11-01', $result->format('Y-m-d'));
    }

    public function testAddToNegativeDuration(): void
    {
        $dt = new DateTimeImmutable('2026-05-20T12:00:00Z');
        $d = Duration::fromIcalendar('-PT1H');

        $result = $d->addTo($dt);
        $this->assertSame('2026-05-20T11:00:00+00:00', $result->format('c'));
    }

    public function testNegate(): void
    {
        $d = Duration::fromIcalendar('PT1H');
        $neg = $d->negate();

        $this->assertFalse($d->isNegative());
        $this->assertTrue($neg->isNegative());
        $this->assertSame(-3600, $neg->toSeconds());
    }

    public function testNegateZero(): void
    {
        $d = Duration::zero();
        $this->assertSame($d, $d->negate());
    }

    public function testAbs(): void
    {
        $d = Duration::fromSeconds(-3600);
        $abs = $d->abs();

        $this->assertFalse($abs->isNegative());
        $this->assertSame(3600, $abs->toSeconds());
    }

    public function testAbsAlreadyPositive(): void
    {
        $d = Duration::fromSeconds(3600);
        $this->assertSame($d, $d->abs());
    }

    // =========================================================================
    // Comparison
    // =========================================================================

    public function testEquals(): void
    {
        $a = Duration::fromIcalendar('PT90M');
        $b = Duration::fromIcalendar('PT1H30M');
        $this->assertTrue($a->equals($b));
    }

    public function testCompareTo(): void
    {
        $short = Duration::fromIcalendar('PT30M');
        $long = Duration::fromIcalendar('PT2H');

        $this->assertSame(-1, $short->compareTo($long));
        $this->assertSame(1, $long->compareTo($short));
        $this->assertSame(0, $short->compareTo($short));
    }

    // =========================================================================
    // toIcalendar serialization
    // =========================================================================

    #[DataProvider('serializationProvider')]
    public function testToIcalendar(string $input, string $expected): void
    {
        $d = Duration::fromIcalendar($input);
        $this->assertSame($expected, $d->toIcalendar());
    }

    public static function serializationProvider(): array
    {
        return [
            'weeks' => ['P1W', 'P1W'],
            'days only' => ['P3D', 'P3D'],
            'time only' => ['PT1H30M', 'PT1H30M'],
            'day and time' => ['P1DT2H', 'P1DT2H'],
            'full' => ['P2DT3H15M45S', 'P2DT3H15M45S'],
            'negative' => ['-P1D', '-P1D'],
            'zero' => ['P0D', 'P0D'],
            'positive prefix normalized' => ['+PT1H', 'PT1H'],
        ];
    }

    // =========================================================================
    // Roundtrip: fromSeconds → toIcalendar → fromIcalendar → toSeconds
    // =========================================================================

    #[DataProvider('roundtripProvider')]
    public function testRoundtripFromSeconds(int $seconds): void
    {
        $d = Duration::fromSeconds($seconds);
        $ical = $d->toIcalendar();
        $reparsed = Duration::fromIcalendar($ical);
        $this->assertSame($seconds, $reparsed->toSeconds());
    }

    public static function roundtripProvider(): array
    {
        return [
            'zero' => [0],
            'one minute' => [60],
            'one hour' => [3600],
            'one day' => [86400],
            'complex' => [90061],
            'negative' => [-5400],
        ];
    }

    // =========================================================================
    // toDateInterval
    // =========================================================================

    public function testToDateInterval(): void
    {
        $d = Duration::fromIcalendar('P1DT2H30M');
        $interval = $d->toDateInterval();

        $this->assertSame(1, $interval->d);
        $this->assertSame(2, $interval->h);
        $this->assertSame(30, $interval->i);
        $this->assertSame(0, $interval->invert);
    }

    public function testToDateIntervalNegative(): void
    {
        $d = Duration::fromIcalendar('-PT1H');
        $interval = $d->toDateInterval();
        $this->assertSame(1, $interval->h);
        $this->assertSame(0, $interval->invert);
    }

    // =========================================================================
    // Stringable
    // =========================================================================

    public function testStringable(): void
    {
        $d = Duration::fromIcalendar('P1DT2H');
        $this->assertSame('P1DT2H', (string) $d);
    }
}
