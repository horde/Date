<?php

declare(strict_types=1);

namespace Horde\Date\Test\Repeater;

use Horde_Date;
use Horde_Date_Repeater_Exception;
use Horde_Date_Repeater_Fortnight;
use Horde_Date_Repeater_Minute;
use Horde_Date_Repeater_Second;
use Horde_Date_Span;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Horde_Date_Repeater::class)]
#[CoversClass(Horde_Date_Repeater_Minute::class)]
#[CoversClass(Horde_Date_Repeater_Second::class)]
#[CoversClass(Horde_Date_Repeater_Fortnight::class)]
class EdgeCaseTest extends TestCase
{
    // =========================================================================
    // Base repeater error paths
    // =========================================================================

    public function testNextWithoutNowThrowsException(): void
    {
        $this->expectException(Horde_Date_Repeater_Exception::class);
        $minutes = new Horde_Date_Repeater_Minute();
        $minutes->next('future');
    }

    public function testThisWithoutNowThrowsException(): void
    {
        $this->expectException(Horde_Date_Repeater_Exception::class);
        $minutes = new Horde_Date_Repeater_Minute();
        $minutes->this('future');
    }

    public function testNextWithInvalidPointerThrowsException(): void
    {
        $this->expectException(Horde_Date_Repeater_Exception::class);
        $minutes = new Horde_Date_Repeater_Minute();
        $minutes->now = new Horde_Date('2026-04-17 14:00:00');
        $minutes->next('sideways');
    }

    public function testThisWithInvalidPointerThrowsException(): void
    {
        $this->expectException(Horde_Date_Repeater_Exception::class);
        $minutes = new Horde_Date_Repeater_Minute();
        $minutes->now = new Horde_Date('2026-04-17 14:00:00');
        $minutes->this('sideways');
    }

    // =========================================================================
    // Minute repeater
    // =========================================================================

    public function testMinuteWidth(): void
    {
        $minutes = new Horde_Date_Repeater_Minute();
        $this->assertSame(60, $minutes->width());
    }

    public function testMinuteNextFuture(): void
    {
        $minutes = new Horde_Date_Repeater_Minute();
        $minutes->now = new Horde_Date('2026-04-17 14:30:00');

        $span = $minutes->next('future');
        $this->assertSame('2026-04-17 14:31:00', (string)$span->begin);
        $this->assertSame('2026-04-17 14:32:00', (string)$span->end);

        $span2 = $minutes->next('future');
        $this->assertSame('2026-04-17 14:32:00', (string)$span2->begin);
        $this->assertSame('2026-04-17 14:33:00', (string)$span2->end);
    }

    public function testMinuteNextPast(): void
    {
        $minutes = new Horde_Date_Repeater_Minute();
        $minutes->now = new Horde_Date('2026-04-17 14:30:00');

        $span = $minutes->next('past');
        $this->assertSame('2026-04-17 14:29:00', (string)$span->begin);
        $this->assertSame('2026-04-17 14:30:00', (string)$span->end);
    }

    public function testMinuteNextFutureAcrossHourBoundary(): void
    {
        $minutes = new Horde_Date_Repeater_Minute();
        $minutes->now = new Horde_Date('2026-04-17 14:59:00');

        $span = $minutes->next('future');
        $this->assertSame('2026-04-17 15:00:00', (string)$span->begin);
        $this->assertSame('2026-04-17 15:01:00', (string)$span->end);
    }

    public function testMinuteThisFuture(): void
    {
        $minutes = new Horde_Date_Repeater_Minute();
        $minutes->now = new Horde_Date('2026-04-17 14:30:30');

        $span = $minutes->this('future');
        $this->assertSame('2026-04-17 14:30:30', (string)$span->begin);
        $this->assertSame('2026-04-17 14:30:00', (string)$span->end);
    }

    public function testMinuteThisPast(): void
    {
        $minutes = new Horde_Date_Repeater_Minute();
        $minutes->now = new Horde_Date('2026-04-17 14:30:30');

        $span = $minutes->this('past');
        $this->assertSame('2026-04-17 14:30:00', (string)$span->begin);
        $this->assertSame('2026-04-17 14:30:30', (string)$span->end);
    }

    public function testMinuteThisNone(): void
    {
        $minutes = new Horde_Date_Repeater_Minute();
        $minutes->now = new Horde_Date('2026-04-17 14:30:00');

        $span = $minutes->this('none');
        $this->assertSame('2026-04-17 14:30:00', (string)$span->begin);
        $this->assertSame('2026-04-17 14:31:00', (string)$span->end);
    }

    public function testMinuteOffset(): void
    {
        $minutes = new Horde_Date_Repeater_Minute();
        $span = new Horde_Date_Span(
            new Horde_Date('2026-04-17 14:00:00'),
            new Horde_Date('2026-04-17 14:00:01')
        );

        $offsetSpan = $minutes->offset($span, 5, 'future');
        $this->assertSame('2026-04-17 14:05:00', (string)$offsetSpan->begin);

        $offsetSpan = $minutes->offset($span, 90, 'past');
        $this->assertSame('2026-04-17 12:30:00', (string)$offsetSpan->begin);
    }

    public function testMinuteToString(): void
    {
        $minutes = new Horde_Date_Repeater_Minute();
        $this->assertSame('repeater-minute', (string)$minutes);
    }

    // =========================================================================
    // Second repeater
    // =========================================================================

    public function testSecondWidth(): void
    {
        $seconds = new Horde_Date_Repeater_Second();
        $this->assertSame(1, $seconds->width());
    }

    public function testSecondNextFuture(): void
    {
        $seconds = new Horde_Date_Repeater_Second();
        $seconds->now = new Horde_Date('2026-04-17 14:30:00');

        $span = $seconds->next('future');
        $this->assertSame('2026-04-17 14:30:01', (string)$span->begin);
        $this->assertSame('2026-04-17 14:30:02', (string)$span->end);
    }

    public function testSecondNextPast(): void
    {
        $seconds = new Horde_Date_Repeater_Second();
        $seconds->now = new Horde_Date('2026-04-17 14:30:30');

        $span = $seconds->next('past');
        $this->assertSame('2026-04-17 14:30:29', (string)$span->begin);
        $this->assertSame('2026-04-17 14:30:30', (string)$span->end);
    }

    public function testSecondThis(): void
    {
        $seconds = new Horde_Date_Repeater_Second();
        $seconds->now = new Horde_Date('2026-04-17 14:30:00');

        $span = $seconds->this('future');
        $this->assertSame('2026-04-17 14:30:00', (string)$span->begin);
        $this->assertSame('2026-04-17 14:30:01', (string)$span->end);
    }

    public function testSecondOffset(): void
    {
        $seconds = new Horde_Date_Repeater_Second();
        $span = new Horde_Date_Span(
            new Horde_Date('2026-04-17 14:00:00'),
            new Horde_Date('2026-04-17 14:00:01')
        );

        $offsetSpan = $seconds->offset($span, 30, 'future');
        $this->assertSame('2026-04-17 14:00:30', (string)$offsetSpan->begin);
        $this->assertSame('2026-04-17 14:00:31', (string)$offsetSpan->end);
    }

    public function testSecondToString(): void
    {
        $seconds = new Horde_Date_Repeater_Second();
        $this->assertSame('repeater-second', (string)$seconds);
    }

    // =========================================================================
    // Fortnight repeater
    // =========================================================================

    public function testFortnightWidth(): void
    {
        $fortnight = new Horde_Date_Repeater_Fortnight();
        $this->assertSame(1209600, $fortnight->width());
    }

    public function testFortnightNextFuture(): void
    {
        $fortnight = new Horde_Date_Repeater_Fortnight();
        $fortnight->now = new Horde_Date('2026-04-17 14:00:00');

        $span = $fortnight->next('future');
        $this->assertSame(1209600, $span->width());
        $this->assertSame(Horde_Date::DATE_SUNDAY, $span->begin->dayOfWeek());
    }

    public function testFortnightNextPast(): void
    {
        $fortnight = new Horde_Date_Repeater_Fortnight();
        $fortnight->now = new Horde_Date('2026-04-17 14:00:00');

        $span = $fortnight->next('past');
        $this->assertSame(1209600, $span->width());
        $this->assertSame(Horde_Date::DATE_SUNDAY, $span->begin->dayOfWeek());
        $this->assertTrue($span->end->before($fortnight->now) || $span->end->equals($fortnight->now));
    }

    public function testFortnightOffset(): void
    {
        $fortnight = new Horde_Date_Repeater_Fortnight();
        $span = new Horde_Date_Span(
            new Horde_Date('2026-04-17 14:00:00'),
            new Horde_Date('2026-04-17 14:00:01')
        );

        $offsetSpan = $fortnight->offset($span, 1, 'future');
        $this->assertSame('2026-05-01 14:00:00', (string)$offsetSpan->begin);
    }

    public function testFortnightToString(): void
    {
        $fortnight = new Horde_Date_Repeater_Fortnight();
        $this->assertSame('repeater-fortnight', (string)$fortnight);
    }

    // =========================================================================
    // Consecutive next() calls produce non-overlapping spans
    // =========================================================================

    public function testMinuteConsecutiveNextProducesIncreasingSpans(): void
    {
        $minutes = new Horde_Date_Repeater_Minute();
        $minutes->now = new Horde_Date('2026-04-17 14:00:00');

        $span1 = $minutes->next('future');
        $begin1 = (string)$span1->begin;
        $end1 = (string)$span1->end;

        $span2 = $minutes->next('future');
        $begin2 = (string)$span2->begin;
        $end2 = (string)$span2->end;

        $this->assertSame('2026-04-17 14:01:00', $begin1);
        $this->assertSame('2026-04-17 14:02:00', $end1);
        $this->assertSame('2026-04-17 14:02:00', $begin2);
        $this->assertSame('2026-04-17 14:03:00', $end2);
    }

    public function testMinuteSpansStableAfterSubsequentCalls(): void
    {
        $minutes = new Horde_Date_Repeater_Minute();
        $minutes->now = new Horde_Date('2026-04-17 14:00:00');

        $span1 = $minutes->next('future');
        $this->assertSame('2026-04-17 14:01:00', (string)$span1->begin);

        $minutes->next('future');
        $this->assertSame('2026-04-17 14:01:00', (string)$span1->begin);
    }

    public function testSecondConsecutiveNextNonOverlapping(): void
    {
        $seconds = new Horde_Date_Repeater_Second();
        $seconds->now = new Horde_Date('2026-04-17 14:00:00');

        $span1 = $seconds->next('future');
        $span1End = (string)$span1->end;
        $span2 = $seconds->next('future');

        $this->assertSame($span1End, (string)$span2->begin);
    }

    // =========================================================================
    // Boundary: midnight crossings
    // =========================================================================

    public function testMinuteNextAcrossMidnight(): void
    {
        $minutes = new Horde_Date_Repeater_Minute();
        $minutes->now = new Horde_Date('2026-04-17 23:59:00');

        $span = $minutes->next('future');
        $this->assertSame('2026-04-18 00:00:00', (string)$span->begin);
    }

    public function testSecondNextAcrossMidnight(): void
    {
        $seconds = new Horde_Date_Repeater_Second();
        $seconds->now = new Horde_Date('2026-04-17 23:59:59');

        $span = $seconds->next('future');
        $this->assertSame('2026-04-18 00:00:00', (string)$span->begin);
    }

    // =========================================================================
    // Boundary: year crossing
    // =========================================================================

    public function testMinuteNextAcrossYearBoundary(): void
    {
        $minutes = new Horde_Date_Repeater_Minute();
        $minutes->now = new Horde_Date('2026-12-31 23:59:00');

        $span = $minutes->next('future');
        $this->assertSame('2027-01-01 00:00:00', (string)$span->begin);
    }
}
