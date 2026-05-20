<?php

declare(strict_types=1);

/**
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 */

namespace Horde\Date;

use DateTimeImmutable;
use DateTimeInterface;
use Stringable;
use DateTimeZone;

/**
 * Immutable iCalendar PERIOD value type (RFC 5545 §3.3.9).
 *
 * A period is a precise range of time defined by a start and either an
 * explicit end or a duration. Internally always stored as start + end.
 *
 * Format: date-time "/" date-time
 *     OR: date-time "/" duration
 */
final class Period implements Stringable
{
    private function __construct(
        private readonly Date $start,
        private readonly Date $end,
    ) {
        if ($end < $start) {
            throw new DateException('Period end must not be before start');
        }
    }

    // =========================================================================
    // Factory methods
    // =========================================================================

    public static function fromStartEnd(DateTimeInterface $start, DateTimeInterface $end): self
    {
        return new self(
            Date::createFromInterface($start),
            Date::createFromInterface($end),
        );
    }

    public static function fromStartDuration(DateTimeInterface $start, Duration $duration): self
    {
        if ($duration->isNegative()) {
            throw new DateException('Period duration must not be negative');
        }

        $immutableStart = Date::createFromInterface($start);

        return new self(
            $immutableStart,
            Date::createFromInterface($duration->addTo($immutableStart)),
        );
    }

    /**
     * Parse an iCalendar PERIOD string (RFC 5545 §3.3.9).
     *
     * Accepts both forms:
     *   20260520T090000Z/20260520T100000Z (start/end)
     *   20260520T090000Z/PT1H             (start/duration)
     */
    public static function fromIcalendar(string $value): self
    {
        $value = trim($value);
        $slashPos = strpos($value, '/');

        if ($slashPos === false) {
            throw new DateException("Invalid period: no '/' separator in \"$value\"");
        }

        $startStr = substr($value, 0, $slashPos);
        $endStr = substr($value, $slashPos + 1);

        if ($startStr === '' || $endStr === '') {
            throw new DateException("Invalid period: empty component in \"$value\"");
        }

        $start = self::parseDateTime($startStr);

        // Second part is either a duration (starts with P or sign+P) or a date-time
        if (preg_match('/^[+-]?P/', $endStr)) {
            $duration = Duration::fromIcalendar($endStr);
            return self::fromStartDuration($start, $duration);
        }

        $end = self::parseDateTime($endStr);
        return new self($start, $end);
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function getStart(): Date
    {
        return $this->start;
    }

    public function getEnd(): Date
    {
        return $this->end;
    }

    /**
     * Compute the duration of this period.
     */
    public function getDuration(): Duration
    {
        return Duration::fromDateDiff($this->start, $this->end);
    }

    /**
     * Width in seconds.
     */
    public function getWidth(): int
    {
        return $this->end->getTimestamp() - $this->start->getTimestamp();
    }

    /**
     * Whether the period has zero length (start equals end).
     */
    public function isZeroLength(): bool
    {
        return $this->start == $this->end;
    }

    // =========================================================================
    // Containment and overlap
    // =========================================================================

    /**
     * Whether the given point in time falls within this period (inclusive of start, exclusive of end).
     */
    public function contains(DateTimeInterface $dateTime): bool
    {
        return $dateTime >= $this->start && $dateTime < $this->end;
    }

    /**
     * Whether the given point in time falls within this period (inclusive of both bounds).
     */
    public function containsInclusive(DateTimeInterface $dateTime): bool
    {
        return $dateTime >= $this->start && $dateTime <= $this->end;
    }

    /**
     * Whether this period overlaps with another.
     *
     * Two periods overlap if they share any point in time (half-open intervals: [start, end)).
     */
    public function overlaps(self $other): bool
    {
        return $this->start < $other->end && $other->start < $this->end;
    }

    /**
     * Whether this period fully encloses another.
     */
    public function encloses(self $other): bool
    {
        return $this->start <= $other->start && $this->end >= $other->end;
    }

    /**
     * Compute the intersection of two periods, or null if they don't overlap.
     */
    public function intersect(self $other): ?self
    {
        $start = $this->start >= $other->start ? $this->start : $other->start;
        $end = $this->end <= $other->end ? $this->end : $other->end;

        if ($start >= $end) {
            return null;
        }

        return new self($start, $end);
    }

    // =========================================================================
    // Comparison
    // =========================================================================

    public function equals(self $other): bool
    {
        return $this->start == $other->start && $this->end == $other->end;
    }

    /**
     * Compare by start time, then by end time.
     */
    public function compareTo(self $other): int
    {
        $cmp = $this->start <=> $other->start;
        if ($cmp !== 0) {
            return $cmp;
        }

        return $this->end <=> $other->end;
    }

    // =========================================================================
    // Serialization
    // =========================================================================

    /**
     * Serialize to iCalendar PERIOD format using explicit start/end form.
     *
     * When start and end have different timezones, both are normalized to
     * UTC to produce a valid RFC 5545 representation.
     */
    public function toIcalendar(): string
    {
        [$start, $end] = $this->normalizedEndpoints();
        return self::formatDateTime($start) . '/' . self::formatDateTime($end);
    }

    /**
     * Serialize to iCalendar PERIOD format using start/duration form.
     *
     * When start has a non-UTC timezone, it is normalized to UTC.
     */
    public function toIcalendarWithDuration(): string
    {
        [$start, ] = $this->normalizedEndpoints();
        return self::formatDateTime($start) . '/' . $this->getDuration()->toIcalendar();
    }

    public function __toString(): string
    {
        return $this->toIcalendar();
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    private static function parseDateTime(string $value): Date
    {
        // iCalendar date-time: YYYYMMDDTHHMMSS or YYYYMMDDTHHMMSSZ
        if (preg_match('/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})(Z)?$/', $value, $m)) {
            $iso = sprintf('%s-%s-%sT%s:%s:%s', $m[1], $m[2], $m[3], $m[4], $m[5], $m[6]);
            if (isset($m[7]) && $m[7] === 'Z') {
                $iso .= '+00:00';
            }
            $result = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:sP', $iso);
            if ($result === false) {
                $result = new DateTimeImmutable($iso);
            }
            return Date::createFromInterface($result);
        }

        // Fall back to PHP's native parsing (ISO 8601, etc.)
        return new Date($value);
    }

    /**
     * Ensure both endpoints share the same timezone for valid serialization.
     *
     * If they differ, both are converted to UTC. If both are already in the
     * same timezone, they are returned as-is (preserving local-time output).
     *
     * @return array{Date, Date}
     */
    private function normalizedEndpoints(): array
    {
        $startTz = $this->start->getTimezone()->getName();
        $endTz = $this->end->getTimezone()->getName();

        if ($startTz === $endTz) {
            return [$this->start, $this->end];
        }

        $utc = new DateTimeZone('UTC');
        return [
            Date::createFromInterface($this->start->setTimezone($utc)),
            Date::createFromInterface($this->end->setTimezone($utc)),
        ];
    }

    private static function formatDateTime(Date $dt): string
    {
        if ($dt->getTimezone()->getName() === 'UTC' || $dt->getOffset() === 0) {
            return $dt->format('Ymd\THis\Z');
        }

        return $dt->format('Ymd\THis');
    }
}
