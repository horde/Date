<?php

declare(strict_types=1);

/**
 * Copyright 2007-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2007-2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 */

namespace Horde\Date\Recurrence;

use DateTimeImmutable;
use DateTimeInterface;

interface RecurrenceInterface
{
    /** Get the recurrence type (daily, weekly, monthly, etc.). */
    public function getType(): RecurrenceType;

    /** Get the interval between recurrences (e.g. every 2 weeks). */
    public function getInterval(): int;

    /** Get the start date of this recurrence series. */
    public function getStart(): DateTimeImmutable;

    /** Get the end date of the recurrence, or null if unbounded. */
    public function getEnd(): ?DateTimeImmutable;

    /** Get the maximum number of occurrences, or null if not count-limited. */
    public function getCount(): ?int;

    /** Get the bitmask of days this recurrence applies to (for weekly rules). */
    public function getDayMask(): int;

    /** Find the next occurrence on or after the given date. */
    public function nextRecurrence(DateTimeInterface $after): ?DateTimeImmutable;

    /** Find the next occurrence that is not an exception or completion. */
    public function nextActiveRecurrence(DateTimeInterface $after): ?DateTimeImmutable;

    /** Check whether at least one active (non-excepted) occurrence remains. */
    public function hasActiveRecurrence(): bool;

    /** Add an exception date (excluded occurrence). */
    public function addException(DateTimeInterface $date): void;

    /** Remove an exception date. */
    public function deleteException(DateTimeInterface $date): void;

    /** Check whether a date is marked as an exception. */
    public function hasException(DateTimeInterface $date): bool;

    /** @return list<string> YYYYMMDD strings */
    public function getExceptions(): array;

    /** Add a completion date (occurrence completed in a task recurrence). */
    public function addCompletion(DateTimeInterface $date): void;

    /** Remove a completion date. */
    public function deleteCompletion(DateTimeInterface $date): void;

    /** Check whether a date is marked as completed. */
    public function hasCompletion(DateTimeInterface $date): bool;

    /** @return list<string> YYYYMMDD strings */
    public function getCompletions(): array;

    /** Add an RDATE (additional occurrence not covered by the rule). */
    public function addRdate(DateTimeInterface $date): void;

    /** Remove an RDATE. */
    public function deleteRdate(DateTimeInterface $date): void;

    /** Check whether a date is an RDATE. */
    public function hasRdate(DateTimeInterface $date): bool;

    /** @return list<string> YYYYMMDD strings */
    public function getRdates(): array;

    /** @param list<string> $dates YYYYMMDD strings */
    public function setRdates(array $dates): void;

    /**
     * Expand all active occurrences (RRULE + RDATE - EXDATE) in a date range.
     *
     * @return list<DateTimeImmutable>
     */
    public function expandRange(DateTimeInterface $from, DateTimeInterface $until, int $limit = 1000): array;

    /** Generate an iCalendar RRULE string in RFC 5545 (vCalendar 2.0) format. */
    public function toRRule20(): string;

    /** Generate an iCalendar RRULE string in vCalendar 1.0 format. */
    public function toRRule10(): string;
}
