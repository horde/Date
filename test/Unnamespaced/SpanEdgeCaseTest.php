<?php

declare(strict_types=1);

namespace Horde\Date\Test;

use Horde_Date;
use Horde_Date_Span;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Date_Span::class)]
class SpanEdgeCaseTest extends TestCase
{
    // =========================================================================
    // width()
    // =========================================================================

    public function testWidthOneHour(): void
    {
        $span = new Horde_Date_Span(
            '2026-04-17 10:00:00',
            '2026-04-17 11:00:00'
        );
        $this->assertSame(3600, $span->width());
    }

    public function testWidthOneDay(): void
    {
        $span = new Horde_Date_Span(
            '2026-04-17 00:00:00',
            '2026-04-18 00:00:00'
        );
        $this->assertSame(86400, $span->width());
    }

    public function testWidthZero(): void
    {
        $span = new Horde_Date_Span(
            '2026-04-17 12:00:00',
            '2026-04-17 12:00:00'
        );
        $this->assertSame(0, $span->width());
    }

    public function testWidthIsAbsoluteWhenReversed(): void
    {
        $span = new Horde_Date_Span(
            '2026-04-18 00:00:00',
            '2026-04-17 00:00:00'
        );
        $this->assertSame(86400, $span->width());
    }

    public function testWidthLargeSpan(): void
    {
        $span = new Horde_Date_Span(
            '2026-01-01 00:00:00',
            '2026-12-31 23:59:59'
        );
        $expected = (365 * 86400) - 1;
        $this->assertSame($expected, $span->width());
    }

    public function testWidthAcrossLeapDay(): void
    {
        $span = new Horde_Date_Span(
            '2024-02-28 00:00:00',
            '2024-03-01 00:00:00'
        );
        $this->assertSame(2 * 86400, $span->width());
    }

    public function testWidthAcrossNonLeapFeb(): void
    {
        $span = new Horde_Date_Span(
            '2025-02-28 00:00:00',
            '2025-03-01 00:00:00'
        );
        $this->assertSame(86400, $span->width());
    }

    // =========================================================================
    // includes()
    // =========================================================================

    public function testIncludesBeginBoundary(): void
    {
        $span = new Horde_Date_Span(
            '2026-04-17 10:00:00',
            '2026-04-17 12:00:00'
        );
        $this->assertTrue($span->includes(new Horde_Date('2026-04-17 10:00:00')));
    }

    public function testIncludesEndBoundary(): void
    {
        $span = new Horde_Date_Span(
            '2026-04-17 10:00:00',
            '2026-04-17 12:00:00'
        );
        $this->assertTrue($span->includes(new Horde_Date('2026-04-17 12:00:00')));
    }

    public function testIncludesMidpoint(): void
    {
        $span = new Horde_Date_Span(
            '2026-04-17 10:00:00',
            '2026-04-17 12:00:00'
        );
        $this->assertTrue($span->includes(new Horde_Date('2026-04-17 11:00:00')));
    }

    public function testExcludesBeforeBegin(): void
    {
        $span = new Horde_Date_Span(
            '2026-04-17 10:00:00',
            '2026-04-17 12:00:00'
        );
        $this->assertFalse($span->includes(new Horde_Date('2026-04-17 09:59:59')));
    }

    public function testExcludesAfterEnd(): void
    {
        $span = new Horde_Date_Span(
            '2026-04-17 10:00:00',
            '2026-04-17 12:00:00'
        );
        $this->assertFalse($span->includes(new Horde_Date('2026-04-17 12:00:01')));
    }

    public function testZeroWidthSpanIncludesExactTime(): void
    {
        $time = '2026-04-17 12:00:00';
        $span = new Horde_Date_Span($time, $time);
        $this->assertTrue($span->includes(new Horde_Date($time)));
    }

    public function testZeroWidthSpanExcludesOtherTimes(): void
    {
        $time = '2026-04-17 12:00:00';
        $span = new Horde_Date_Span($time, $time);
        $this->assertFalse($span->includes(new Horde_Date('2026-04-17 12:00:01')));
    }

    // =========================================================================
    // add() / sub()
    // =========================================================================

    public function testAddShiftsEntireSpan(): void
    {
        $span = new Horde_Date_Span(
            '2026-04-17 10:00:00',
            '2026-04-17 12:00:00'
        );
        $shifted = $span->add(3600);

        $this->assertSame('2026-04-17 11:00:00', $shifted->begin->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-17 13:00:00', $shifted->end->format('Y-m-d H:i:s'));
        $this->assertSame($span->width(), $shifted->width());
    }

    public function testSubShiftsEntireSpan(): void
    {
        $span = new Horde_Date_Span(
            '2026-04-17 10:00:00',
            '2026-04-17 12:00:00'
        );
        $shifted = $span->sub(3600);

        $this->assertSame('2026-04-17 09:00:00', $shifted->begin->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-17 11:00:00', $shifted->end->format('Y-m-d H:i:s'));
    }

    public function testAddPreservesWidth(): void
    {
        $span = new Horde_Date_Span(
            '2026-04-17 10:00:00',
            '2026-04-17 14:30:00'
        );
        $shifted = $span->add(86400);
        $this->assertSame($span->width(), $shifted->width());
    }

    public function testAddDoesNotMutateOriginal(): void
    {
        $span = new Horde_Date_Span(
            '2026-04-17 10:00:00',
            '2026-04-17 12:00:00'
        );
        $originalBegin = $span->begin->format('Y-m-d H:i:s');
        $span->add(3600);
        $this->assertSame($originalBegin, $span->begin->format('Y-m-d H:i:s'));
    }

    public function testAddAcrossMonthBoundary(): void
    {
        $span = new Horde_Date_Span(
            '2026-04-30 22:00:00',
            '2026-04-30 23:59:59'
        );
        $shifted = $span->add(3 * 3600);

        $this->assertSame('2026-05-01 01:00:00', $shifted->begin->format('Y-m-d H:i:s'));
    }

    public function testAddWithArrayFactor(): void
    {
        $span = new Horde_Date_Span(
            '2026-04-17 10:00:00',
            '2026-04-17 12:00:00'
        );
        $shifted = $span->add(['mday' => 7]);

        $this->assertSame('2026-04-24 10:00:00', $shifted->begin->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-24 12:00:00', $shifted->end->format('Y-m-d H:i:s'));
    }

    // =========================================================================
    // __toString()
    // =========================================================================

    public function testToStringFormat(): void
    {
        $span = new Horde_Date_Span(
            '2026-04-17 10:00:00',
            '2026-04-17 12:00:00'
        );
        $str = (string)$span;
        $this->assertStringStartsWith('(', $str);
        $this->assertStringEndsWith(')', $str);
        $this->assertStringContainsString('..', $str);
    }

    // =========================================================================
    // Constructor accepts various input types
    // =========================================================================

    public function testConstructorAcceptsStrings(): void
    {
        $span = new Horde_Date_Span('2026-04-17', '2026-04-18');
        $this->assertInstanceOf(Horde_Date_Span::class, $span);
        $this->assertSame(86400, $span->width());
    }

    public function testConstructorAcceptsHordeDateObjects(): void
    {
        $begin = new Horde_Date('2026-04-17 10:00:00');
        $end = new Horde_Date('2026-04-17 12:00:00');
        $span = new Horde_Date_Span($begin, $end);
        $this->assertSame(7200, $span->width());
    }

    // =========================================================================
    // Large spans (multi-year)
    // =========================================================================

    public function testMultiYearSpan(): void
    {
        $span = new Horde_Date_Span(
            '2020-01-01 00:00:00',
            '2026-01-01 00:00:00'
        );
        $width = $span->width();
        $daysApprox = $width / 86400;
        $this->assertGreaterThan(2190, $daysApprox);
        $this->assertLessThan(2200, $daysApprox);
    }
}
