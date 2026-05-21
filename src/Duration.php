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

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Stringable;

/**
 * Immutable iCalendar DURATION value type (RFC 5545 §3.3.6).
 *
 * Format: [+|-]P[weeks W | [days D][T[hours H][minutes M][seconds S]]]
 *
 * Weeks form and date/time form are mutually exclusive per the RFC.
 */
final class Duration implements Stringable
{
    private function __construct(
        private readonly bool $negative,
        private readonly int $weeks,
        private readonly int $days,
        private readonly int $hours,
        private readonly int $minutes,
        private readonly int $seconds,
    ) {}

    /** @section Factory methods */

    /**
     * Parse an iCalendar duration string (RFC 5545 §3.3.6).
     *
     * Examples: P1W, P1DT2H30M, PT15M, -P1D, +PT1H30M, P0D
     */
    public static function fromIcalendar(string $value): self
    {
        $value = trim($value);
        if ($value === '') {
            throw new DateException('Empty duration string');
        }

        $negative = false;
        $pos = 0;

        if ($value[$pos] === '-') {
            $negative = true;
            $pos++;
        } elseif ($value[$pos] === '+') {
            $pos++;
        }

        if (!isset($value[$pos]) || $value[$pos] !== 'P') {
            throw new DateException("Invalid duration: expected 'P' at position $pos in \"$value\"");
        }
        $pos++;

        $remaining = substr($value, $pos);

        if ($remaining === '' || $remaining === false) {
            throw new DateException("Invalid duration: no designators after 'P' in \"$value\"");
        }

        // Weeks form: PnW (mutually exclusive with date/time form per RFC)
        if (str_contains($remaining, 'W')) {
            if (!preg_match('/^(\d+)W$/', $remaining, $m)) {
                throw new DateException("Invalid duration: malformed weeks designator in \"$value\"");
            }
            return new self($negative, (int) $m[1], 0, 0, 0, 0);
        }

        // Date/time form: [nD][T[nH][nM][nS]]
        $days = 0;
        $hours = 0;
        $minutes = 0;
        $seconds = 0;

        if (!preg_match('/^(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/', $remaining, $m)) {
            throw new DateException("Invalid duration: cannot parse \"$value\"");
        }

        if ($remaining !== '' && $m[0] === '') {
            throw new DateException("Invalid duration: cannot parse \"$value\"");
        }

        $days = isset($m[1]) && $m[1] !== '' ? (int) $m[1] : 0;
        $hours = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0;
        $minutes = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : 0;
        $seconds = isset($m[4]) && $m[4] !== '' ? (int) $m[4] : 0;

        return new self($negative, 0, $days, $hours, $minutes, $seconds);
    }

    /**
     * Create from total seconds.
     *
     * Decomposes into days/hours/minutes/seconds (no weeks form).
     */
    public static function fromSeconds(int $seconds): self
    {
        $negative = $seconds < 0;
        $remaining = abs($seconds);

        $days = intdiv($remaining, 86400);
        $remaining -= $days * 86400;

        $hours = intdiv($remaining, 3600);
        $remaining -= $hours * 3600;

        $minutes = intdiv($remaining, 60);
        $remaining -= $minutes * 60;

        return new self($negative, 0, $days, $hours, $minutes, $remaining);
    }

    /**
     * Create from explicit components.
     *
     * Weeks and date/time parts are mutually exclusive per RFC 5545.
     * If weeks > 0, days/hours/minutes/seconds must be zero.
     */
    public static function fromParts(
        int $weeks = 0,
        int $days = 0,
        int $hours = 0,
        int $minutes = 0,
        int $seconds = 0,
        bool $negative = false,
    ): self {
        if ($weeks > 0 && ($days > 0 || $hours > 0 || $minutes > 0 || $seconds > 0)) {
            throw new DateException('Weeks form and date/time form are mutually exclusive in RFC 5545 DURATION');
        }

        return new self($negative, $weeks, $days, $hours, $minutes, $seconds);
    }

    /**
     * Create from the difference between two points in time.
     *
     * Result is always non-negative; order of arguments determines sign.
     * If $end is before $start, the duration is negative.
     */
    public static function fromDateDiff(DateTimeInterface $start, DateTimeInterface $end): self
    {
        $startTs = $start->getTimestamp();
        $endTs = $end->getTimestamp();

        return self::fromSeconds($endTs - $startTs);
    }

    /**
     * Zero-length duration.
     */
    public static function zero(): self
    {
        return new self(false, 0, 0, 0, 0, 0);
    }

    /** @section Accessors */

    /** Whether this duration is negative. */
    public function isNegative(): bool
    {
        return $this->negative;
    }

    /** Whether all components are zero. */
    public function isZero(): bool
    {
        return $this->weeks === 0
            && $this->days === 0
            && $this->hours === 0
            && $this->minutes === 0
            && $this->seconds === 0;
    }

    /**
     * Whether this duration uses the weeks form (mutually exclusive with date/time).
     */
    public function isWeeksForm(): bool
    {
        return $this->weeks > 0;
    }

    /** Get the weeks component. */
    public function getWeeks(): int
    {
        return $this->weeks;
    }

    /** Get the days component. */
    public function getDays(): int
    {
        return $this->days;
    }

    /** Get the hours component. */
    public function getHours(): int
    {
        return $this->hours;
    }

    /** Get the minutes component. */
    public function getMinutes(): int
    {
        return $this->minutes;
    }

    /** Get the seconds component. */
    public function getSeconds(): int
    {
        return $this->seconds;
    }

    /**
     * Total duration in seconds (sign-aware).
     */
    public function toSeconds(): int
    {
        $total = ($this->weeks * 7 * 86400)
            + ($this->days * 86400)
            + ($this->hours * 3600)
            + ($this->minutes * 60)
            + $this->seconds;

        return $this->negative ? -$total : $total;
    }

    /**
     * Convert to a native DateInterval (always non-inverted).
     *
     * The returned interval represents the absolute magnitude.
     * Use isNegative() separately to determine direction.
     */
    public function toDateInterval(): DateInterval
    {
        $interval = new DateInterval(sprintf(
            'P%s%s',
            $this->weeks > 0 ? $this->weeks . 'W' : ($this->days > 0 ? $this->days . 'D' : '0D'),
            ($this->hours > 0 || $this->minutes > 0 || $this->seconds > 0)
                ? sprintf(
                    'T%s%s%s',
                    $this->hours > 0 ? $this->hours . 'H' : '',
                    $this->minutes > 0 ? $this->minutes . 'M' : '',
                    $this->seconds > 0 ? $this->seconds . 'S' : '',
                )
                : '',
        ));

        return $interval;
    }

    /** @section Arithmetic */

    /**
     * Add this duration to a point in time.
     *
     * Uses DateInterval internally for calendar-aware arithmetic:
     * adding P1D across a DST boundary yields the same wall-clock time.
     */
    public function addTo(DateTimeImmutable $dateTime): DateTimeImmutable
    {
        $interval = $this->toDateInterval();

        return $this->negative
            ? $dateTime->sub($interval)
            : $dateTime->add($interval);
    }

    /**
     * Subtract this duration from a point in time.
     *
     * Uses DateInterval internally for calendar-aware arithmetic.
     */
    public function subtractFrom(DateTimeImmutable $dateTime): DateTimeImmutable
    {
        $interval = $this->toDateInterval();

        return $this->negative
            ? $dateTime->add($interval)
            : $dateTime->sub($interval);
    }

    /**
     * Return the negated duration.
     */
    public function negate(): self
    {
        if ($this->isZero()) {
            return $this;
        }

        return new self(!$this->negative, $this->weeks, $this->days, $this->hours, $this->minutes, $this->seconds);
    }

    /**
     * Return the absolute (non-negative) duration.
     */
    public function abs(): self
    {
        if (!$this->negative) {
            return $this;
        }

        return new self(false, $this->weeks, $this->days, $this->hours, $this->minutes, $this->seconds);
    }

    /** @section Comparison */

    /** Whether two durations represent the same total time. */
    public function equals(self $other): bool
    {
        return $this->toSeconds() === $other->toSeconds();
    }

    /** Compare two durations by total seconds (-1, 0, 1). */
    public function compareTo(self $other): int
    {
        return $this->toSeconds() <=> $other->toSeconds();
    }

    /** @section Serialization */

    /**
     * Serialize to RFC 5545 DURATION format.
     */
    public function toIcalendar(): string
    {
        $result = $this->negative ? '-P' : 'P';

        if ($this->weeks > 0) {
            return $result . $this->weeks . 'W';
        }

        if ($this->isZero()) {
            return $result . '0D';
        }

        if ($this->days > 0) {
            $result .= $this->days . 'D';
        }

        if ($this->hours > 0 || $this->minutes > 0 || $this->seconds > 0) {
            $result .= 'T';
            if ($this->hours > 0) {
                $result .= $this->hours . 'H';
            }
            if ($this->minutes > 0) {
                $result .= $this->minutes . 'M';
            }
            if ($this->seconds > 0) {
                $result .= $this->seconds . 'S';
            }
        }

        return $result;
    }

    /** Stringable implementation; returns RFC 5545 DURATION format. */
    public function __toString(): string
    {
        return $this->toIcalendar();
    }
}
