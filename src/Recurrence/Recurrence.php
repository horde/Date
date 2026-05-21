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
use DateTimeZone;
use Horde\Date\Date;
use Horde\Date\Translation;
use Horde\Date\Utils;
use stdClass;

class Recurrence implements RecurrenceInterface
{
    private RecurrenceType $type = RecurrenceType::None;
    private int $interval = 1;
    private DateTimeImmutable $start;
    private ?DateTimeImmutable $end = null;
    private ?int $count = null;
    private int $dayMask = 0;
    /** @var list<string> YYYYMMDD strings */
    private array $exceptions = [];
    /** @var list<string> YYYYMMDD strings */
    private array $completions = [];
    /** @var list<string> YYYYMMDD strings */
    private array $rdates = [];

    /** Create a new recurrence rule starting at the given date. */
    public function __construct(DateTimeInterface $start)
    {
        $this->start = $start instanceof DateTimeImmutable
            ? $start
            : DateTimeImmutable::createFromInterface($start);
    }

    /** @section Interface Getters */

    /** Get the recurrence type. */
    public function getType(): RecurrenceType
    {
        return $this->type;
    }

    /** Get the interval between recurrences. */
    public function getInterval(): int
    {
        return $this->interval;
    }

    /** Get the start date of the recurrence series. */
    public function getStart(): DateTimeImmutable
    {
        return $this->start;
    }

    /** Get the end date, or null if unbounded. */
    public function getEnd(): ?DateTimeImmutable
    {
        return $this->end;
    }

    /** Get the maximum occurrence count, or null if not count-limited. */
    public function getCount(): ?int
    {
        return $this->count;
    }

    /** Get the bitmask of active days (for weekly recurrences). */
    public function getDayMask(): int
    {
        return $this->dayMask;
    }

    /** @section Setters */

    /** Set the recurrence type. */
    public function setType(RecurrenceType $type): void
    {
        $this->type = $type;
    }

    /** Set the interval between recurrences (ignored if negative). */
    public function setInterval(int $interval): void
    {
        if ($interval >= 0) {
            $this->interval = $interval;
        }
    }

    /** Set the start date of the recurrence series. */
    public function setStart(DateTimeInterface $start): void
    {
        $this->start = $start instanceof DateTimeImmutable
            ? $start
            : DateTimeImmutable::createFromInterface($start);
    }

    /** Set the end date (clears count if a date is provided). */
    public function setEnd(?DateTimeInterface $end): void
    {
        if ($end !== null) {
            $this->count = null;
            $this->end = $end instanceof DateTimeImmutable
                ? $end
                : DateTimeImmutable::createFromInterface($end);
        } else {
            $this->end = null;
        }
    }

    /** Set the occurrence count limit (clears end date if count is provided). */
    public function setCount(?int $count): void
    {
        if ($count !== null && $count > 0) {
            $this->count = $count;
            $this->end = null;
        } else {
            $this->count = null;
        }
    }

    /** Set the day-of-week bitmask for weekly recurrences. */
    public function setDayMask(int $mask): void
    {
        $this->dayMask = $mask;
    }

    /** Reset all recurrence properties to their defaults. */
    public function reset(): void
    {
        $this->type = RecurrenceType::None;
        $this->interval = 1;
        $this->end = null;
        $this->count = null;
        $this->dayMask = 0;
        $this->exceptions = [];
        $this->completions = [];
        $this->rdates = [];
    }

    /** Check whether this recurrence has a meaningful end date. */
    public function hasEnd(): bool
    {
        return $this->end !== null
            && (int) $this->end->format('Y') !== 9999;
    }

    /** Check whether this recurrence is limited by an occurrence count. */
    public function hasCount(): bool
    {
        return $this->count !== null;
    }

    /** @section Exception / Completion Management */

    /** Add an exception date (excluded occurrence). */
    public function addException(DateTimeInterface $date): void
    {
        $key = $date->format('Ymd');
        if (!in_array($key, $this->exceptions, true)) {
            $this->exceptions[] = $key;
        }
    }

    /** Remove an exception date. */
    public function deleteException(DateTimeInterface $date): void
    {
        $key = $date->format('Ymd');
        $idx = array_search($key, $this->exceptions, true);
        if ($idx !== false) {
            unset($this->exceptions[$idx]);
            $this->exceptions = array_values($this->exceptions);
        }
    }

    /** Check whether a date is marked as an exception. */
    public function hasException(DateTimeInterface $date): bool
    {
        return in_array($date->format('Ymd'), $this->exceptions, true);
    }

    /** Get all exception dates as YYYYMMDD strings. */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    /** Replace the entire exceptions list. */
    public function setExceptions(array $exceptions): void
    {
        $this->exceptions = array_values($exceptions);
    }

    /** Add a completion date. */
    public function addCompletion(DateTimeInterface $date): void
    {
        $this->completions[] = $date->format('Ymd');
    }

    /** Remove a completion date. */
    public function deleteCompletion(DateTimeInterface $date): void
    {
        $key = $date->format('Ymd');
        $idx = array_search($key, $this->completions, true);
        if ($idx !== false) {
            unset($this->completions[$idx]);
            $this->completions = array_values($this->completions);
        }
    }

    /** Check whether a date is marked as completed. */
    public function hasCompletion(DateTimeInterface $date): bool
    {
        return in_array($date->format('Ymd'), $this->completions, true);
    }

    /** Get all completion dates as YYYYMMDD strings. */
    public function getCompletions(): array
    {
        return $this->completions;
    }

    /** Replace the entire completions list. */
    public function setCompletions(array $completions): void
    {
        $this->completions = array_values($completions);
    }

    /** @section RDATE Management */

    /**
     * Add an RDATE (extra recurrence date) to this recurrence rule.
     */
    public function addRdate(DateTimeInterface $date): void
    {
        $key = $date->format('Ymd');
        if (!in_array($key, $this->rdates, true)) {
            $this->rdates[] = $key;
        }
    }

    /**
     * Remove an RDATE from this recurrence rule.
     */
    public function deleteRdate(DateTimeInterface $date): void
    {
        $key = $date->format('Ymd');
        $idx = array_search($key, $this->rdates, true);
        if ($idx !== false) {
            unset($this->rdates[$idx]);
            $this->rdates = array_values($this->rdates);
        }
    }

    /**
     * Check whether a given date is an RDATE in this recurrence rule.
     */
    public function hasRdate(DateTimeInterface $date): bool
    {
        return in_array($date->format('Ymd'), $this->rdates, true);
    }

    /**
     * Get all RDATEs as YYYYMMDD strings.
     *
     * @return list<string>
     */
    public function getRdates(): array
    {
        return $this->rdates;
    }

    /**
     * Set the RDATE list directly.
     *
     * @param list<string> $dates YYYYMMDD strings
     */
    public function setRdates(array $dates): void
    {
        $this->rdates = array_values($dates);
    }

    /** @section Bounded range expansion */

    /**
     * Expand all active occurrences (RRULE + RDATE - EXDATE) within a date range.
     *
     * Returns a sorted, deduplicated list of occurrence timestamps. The
     * result is capped at $limit entries to prevent runaway expansion on
     * infinite recurrences.
     *
     * @return list<DateTimeImmutable>
     */
    public function expandRange(DateTimeInterface $from, DateTimeInterface $until, int $limit = 1000): array
    {
        $tz = $this->start->getTimezone();
        $fromImm = $from instanceof DateTimeImmutable
            ? $from->setTimezone($tz)
            : DateTimeImmutable::createFromInterface($from)->setTimezone($tz);
        $untilImm = $until instanceof DateTimeImmutable
            ? $until->setTimezone($tz)
            : DateTimeImmutable::createFromInterface($until)->setTimezone($tz);

        $results = [];
        $seen = [];

        $current = $fromImm->modify('-1 day');
        while (count($results) < $limit) {
            $next = $this->nextRecurrence($current);
            if ($next === null || $next > $untilImm) {
                break;
            }
            if (!$this->hasException($next) && !$this->hasCompletion($next)) {
                $key = $next->format('Ymd');
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $results[] = $next;
                }
            }
            $current = $next->modify('+1 day');
        }

        foreach ($this->rdates as $rdateKey) {
            if (isset($seen[$rdateKey])) {
                continue;
            }
            $rdate = new DateTimeImmutable(
                substr($rdateKey, 0, 4) . '-' . substr($rdateKey, 4, 2) . '-' . substr($rdateKey, 6, 2)
                . 'T' . $this->start->format('H:i:s'),
                $tz,
            );
            if ($rdate < $fromImm || $rdate > $untilImm) {
                continue;
            }
            if ($this->hasException($rdate) || $this->hasCompletion($rdate)) {
                continue;
            }
            $seen[$rdateKey] = true;
            $results[] = $rdate;
        }

        usort($results, fn(DateTimeImmutable $a, DateTimeImmutable $b) => $a <=> $b);

        if (count($results) > $limit) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }

    /** @section nextRecurrence / nextActiveRecurrence / hasActiveRecurrence */

    /** Find the next occurrence on or after the given date. */
    public function nextRecurrence(DateTimeInterface $after): ?DateTimeImmutable
    {
        $tz = $this->start->getTimezone();
        $after = ($after instanceof DateTimeImmutable)
            ? $after->setTimezone($tz)
            : DateTimeImmutable::createFromInterface($after)->setTimezone($tz);

        $startDate = Date::createFromInterface($this->start);
        $afterDate = Date::createFromInterface($after);

        if ($startDate->compareDateTime($afterDate) >= 0) {
            return $this->start;
        }

        if ($this->interval === 0) {
            return null;
        }

        return match ($this->type) {
            RecurrenceType::Daily => $this->nextDaily($afterDate),
            RecurrenceType::Weekly => $this->nextWeekly($afterDate),
            RecurrenceType::MonthlyDate => $this->nextMonthlyDate($afterDate),
            RecurrenceType::MonthlyWeekday,
            RecurrenceType::MonthlyLastWeekday => $this->nextMonthlyWeekday($afterDate),
            RecurrenceType::YearlyDate => $this->nextYearlyDate($afterDate),
            RecurrenceType::YearlyDay => $this->nextYearlyDay($afterDate),
            RecurrenceType::YearlyWeekday => $this->nextYearlyWeekday($afterDate),
            default => null,
        };
    }

    /** Find the next occurrence that is not excepted or completed. */
    public function nextActiveRecurrence(DateTimeInterface $after): ?DateTimeImmutable
    {
        $next = $this->nextRecurrence($after);
        while ($next !== null) {
            if (!$this->hasException($next) && !$this->hasCompletion($next)) {
                break;
            }
            $next = $this->nextRecurrence($next->modify('+1 day'));
        }

        $nextRdate = $this->nextRdateAfter($after);

        if ($next === null && $nextRdate === null) {
            return null;
        }
        if ($next === null) {
            return $nextRdate;
        }
        if ($nextRdate === null) {
            return $next;
        }

        return $nextRdate < $next ? $nextRdate : $next;
    }

    /** Check whether at least one active occurrence remains. */
    public function hasActiveRecurrence(): bool
    {
        if (!$this->hasEnd() && $this->type !== RecurrenceType::None) {
            return true;
        }

        if ($this->rdates !== []) {
            foreach ($this->rdates as $rdateKey) {
                $rdate = new DateTimeImmutable(
                    substr($rdateKey, 0, 4) . '-' . substr($rdateKey, 4, 2) . '-' . substr($rdateKey, 6, 2),
                    $this->start->getTimezone(),
                );
                if (!$this->hasException($rdate) && !$this->hasCompletion($rdate)) {
                    return true;
                }
            }
        }

        $next = $this->nextRecurrence($this->start);
        while ($next !== null) {
            if (!$this->hasException($next) && !$this->hasCompletion($next)) {
                return true;
            }
            $next = $this->nextRecurrence($next->modify('+1 day'));
        }
        return false;
    }

    /** @section Private recurrence algorithms */

    /**
     * Find the earliest RDATE strictly after the given date.
     */
    private function nextRdateAfter(DateTimeInterface $after): ?DateTimeImmutable
    {
        if ($this->rdates === []) {
            return null;
        }

        $tz = $this->start->getTimezone();
        $afterKey = $after->format('Ymd');
        $candidates = [];

        foreach ($this->rdates as $rdateKey) {
            if ($rdateKey <= $afterKey) {
                continue;
            }
            $rdate = new DateTimeImmutable(
                substr($rdateKey, 0, 4) . '-' . substr($rdateKey, 4, 2) . '-' . substr($rdateKey, 6, 2)
                . 'T' . $this->start->format('H:i:s'),
                $tz,
            );
            if (!$this->hasException($rdate) && !$this->hasCompletion($rdate)) {
                $candidates[] = $rdate;
            }
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn(DateTimeImmutable $a, DateTimeImmutable $b) => $a <=> $b);
        return $candidates[0];
    }

    /** Compute the next daily recurrence after the given date. */
    private function nextDaily(Date $after): ?DateTimeImmutable
    {
        $startDate = Date::createFromInterface($this->start);
        $diff = $startDate->diffDays($after);
        $recur = (int) ceil($diff / $this->interval);

        if ($this->count !== null && $recur >= $this->count) {
            return null;
        }

        $recur *= $this->interval;
        $next = $startDate->modify("+{$recur} days");

        if ($this->hasEnd()) {
            $endDate = Date::createFromInterface($this->end);
            if ($next->compareDateTime($endDate) > 0) {
                return null;
            }
        }

        if ($next->compareDateTime($after) >= 0) {
            return $this->toDateTimeImmutable($next);
        }

        return null;
    }

    /** Compute the next weekly recurrence after the given date. */
    private function nextWeekly(Date $after): ?DateTimeImmutable
    {
        if ($this->dayMask === 0) {
            return null;
        }

        $startDate = Date::createFromInterface($this->start);
        $tz = $this->start->getTimezone();

        $startWeek = $this->firstDayOfWeekInTz(
            (int) $startDate->format('W'),
            (int) $startDate->format('Y'),
            $tz,
            (int) $startDate->format('G'),
            (int) $startDate->format('i'),
            (int) $startDate->format('s')
        );
        $startWeek = Date::createFromInterface($startWeek);

        $week = (int) $after->format('W');
        $afterMonth = (int) $after->format('n');
        $afterYear = (int) $after->format('Y');

        if ($week === 1 && $afterMonth === 12) {
            $theYear = $afterYear + 1;
        } elseif ($week >= 52 && $afterMonth === 1) {
            $theYear = $afterYear - 1;
        } else {
            $theYear = $afterYear;
        }

        $afterWeek = $this->firstDayOfWeekInTz(
            $week,
            $theYear,
            $tz,
            (int) $startDate->format('G'),
            (int) $startDate->format('i'),
            (int) $startDate->format('s')
        );
        $afterWeek = Date::createFromInterface($afterWeek);
        $afterWeekEnd = Date::createFromInterface($afterWeek->modify('+7 days'));

        $diff = $startWeek->diffDays($afterWeek);
        $intervalDays = $this->interval * 7;
        $repeats = (int) floor($diff / $intervalDays);

        if ($diff % $intervalDays < 7) {
            $recur = $diff;
        } else {
            $recur = $intervalDays * ($repeats + 1);
        }

        if ($this->count !== null) {
            $recurrences = 0;
            $next = clone $startWeek;
            while ($next->compareDateTime($startDate) < 0) {
                if (DayMask::includes($this->dayMask, DayMask::fromDayOfWeek($next->dayOfWeek()))) {
                    $recurrences--;
                }
                $next = Date::createFromInterface($next->modify('+1 day'));
            }
            if ($repeats > 0) {
                $totalPerWeek = DayMask::count($this->dayMask);
                $recurrences += $totalPerWeek * $repeats;
            }
        }

        $next = Date::createFromInterface($startWeek->modify("+{$recur} days"));
        while ($next->compareDateTime($after) < 0
               && $next->compareDateTime($afterWeekEnd) < 0) {
            if (isset($recurrences)
                && $next->compareDateTime($after) < 0
                && DayMask::includes($this->dayMask, DayMask::fromDayOfWeek($next->dayOfWeek()))) {
                $recurrences++;
            }
            $next = Date::createFromInterface($next->modify('+1 day'));
        }

        if (isset($recurrences) && $recurrences >= $this->count) {
            return null;
        }

        $endDate = $this->hasEnd() ? Date::createFromInterface($this->end) : null;

        if ($endDate === null || $next->compareDateTime($endDate) <= 0) {
            if ($next->compareDateTime($afterWeekEnd) >= 0) {
                return $this->nextRecurrence($afterWeekEnd);
            }
            while (!DayMask::includes($this->dayMask, DayMask::fromDayOfWeek($next->dayOfWeek()))
                   && $next->compareDateTime($afterWeekEnd) < 0) {
                $next = Date::createFromInterface($next->modify('+1 day'));
            }
            if ($endDate === null || $next->compareDateTime($endDate) <= 0) {
                if ($next->compareDateTime($afterWeekEnd) >= 0) {
                    return $this->nextRecurrence($afterWeekEnd);
                }
                return $this->toDateTimeImmutable($next);
            }
        }

        return null;
    }

    /** Compute the next monthly-by-date recurrence after the given date. */
    private function nextMonthlyDate(Date $after): ?DateTimeImmutable
    {
        $startDate = Date::createFromInterface($this->start);
        $startDay = (int) $startDate->format('j');
        $startMonth = (int) $startDate->format('n');
        $startYear = (int) $startDate->format('Y');

        $afterDay = (int) $after->format('j');
        $afterMonth = (int) $after->format('n');
        $afterYear = (int) $after->format('Y');

        if ($afterDay > $startDay) {
            $afterMonth++;
            if ($afterMonth > 12) {
                $afterMonth = 1;
                $afterYear++;
            }
        }

        $offset = ($afterMonth - $startMonth) + ($afterYear - $startYear) * 12;
        $offset = (int) floor(($offset + $this->interval - 1) / $this->interval) * $this->interval;

        if ($this->count !== null && ($offset / $this->interval) >= $this->count) {
            return null;
        }

        $candidateMonthOffset = $offset;
        $countSoFar = (int) ($offset / $this->interval);

        while (true) {
            if ($this->count !== null && $countSoFar++ >= $this->count) {
                return null;
            }

            $totalMonths = ($startYear * 12 + $startMonth - 1) + $candidateMonthOffset;
            $cYear = (int) floor($totalMonths / 12);
            $cMonth = ($totalMonths % 12) + 1;

            $daysInMonth = Utils::daysInMonth($cMonth, $cYear);
            if ($startDay <= $daysInMonth) {
                $candidate = $this->buildDate($cYear, $cMonth, $startDay);
                $candidateDate = Date::createFromInterface($candidate);

                if ($this->hasEnd()) {
                    $endDate = Date::createFromInterface($this->end);
                    if ($endDate->compareDateTime($candidateDate) < 0) {
                        return null;
                    }
                }

                return $candidate;
            }

            if ($this->interval === 12 && ($cMonth !== 2 || $startDay > 29)) {
                return null;
            }

            $candidateMonthOffset += $this->interval;
        }
    }

    /** Compute the next monthly-by-weekday recurrence after the given date. */
    private function nextMonthlyWeekday(Date $after): ?DateTimeImmutable
    {
        $startDate = Date::createFromInterface($this->start);
        $startDay = (int) $startDate->format('j');
        $startMonth = (int) $startDate->format('n');
        $startYear = (int) $startDate->format('Y');

        if ($this->type === RecurrenceType::MonthlyLastWeekday) {
            $nth = -1;
        } else {
            $nth = (int) ceil($startDay / 7);
        }
        $weekday = $startDate->dayOfWeek();

        $afterMonth = (int) $after->format('n');
        $afterYear = (int) $after->format('Y');

        $offset = ($afterMonth - $startMonth) + ($afterYear - $startYear) * 12;
        $offset = (int) floor(($offset + $this->interval - 1) / $this->interval) * $this->interval;

        $baseMonths = ($startYear * 12 + $startMonth - 1);
        $candidateMonthOffset = $offset - $this->interval;
        $countSoFar = (int) ($offset / $this->interval);

        while (true) {
            if ($this->count !== null && $countSoFar++ >= $this->count) {
                return null;
            }

            $candidateMonthOffset += $this->interval;
            $totalMonths = $baseMonths + $candidateMonthOffset;
            $cYear = (int) floor($totalMonths / 12);
            $cMonth = ($totalMonths % 12) + 1;

            $firstOfMonth = new Date(sprintf('%04d-%02d-01', $cYear, $cMonth), $this->start->getTimezone());
            $firstOfMonth = $firstOfMonth->setTime(
                (int) $startDate->format('G'),
                (int) $startDate->format('i'),
                (int) $startDate->format('s')
            );
            $next = Date::createFromInterface($firstOfMonth)->withNthWeekday($weekday, $nth);

            if ((int) $next->format('n') !== $cMonth) {
                continue;
            }
            if ($next->compareDateTime($after) < 0) {
                continue;
            }
            if ($this->hasEnd()) {
                $endDate = Date::createFromInterface($this->end);
                if ($next->compareDateTime($endDate) > 0) {
                    return null;
                }
            }

            return $this->toDateTimeImmutable($next);
        }
    }

    /** Compute the next yearly-by-date recurrence after the given date. */
    private function nextYearlyDate(Date $after): ?DateTimeImmutable
    {
        $startDate = Date::createFromInterface($this->start);
        $startMonth = (int) $startDate->format('n');
        $startDay = (int) $startDate->format('j');
        $startYear = (int) $startDate->format('Y');

        $afterMonth = (int) $after->format('n');
        $afterDay = (int) $after->format('j');
        $afterYear = (int) $after->format('Y');

        if ($afterMonth > $startMonth
            || ($afterMonth === $startMonth && $afterDay > $startDay)) {
            $afterYear++;
        }

        if ($startMonth === 2 && $startDay === 29) {
            while (!Utils::isLeapYear($afterYear)) {
                $afterYear++;
            }
        }

        $offset = $afterYear - $startYear;
        if ($offset > 0) {
            $offset = (int) floor(($offset + $this->interval - 1) / $this->interval) * $this->interval;
        }

        if ($this->count !== null && $offset >= $this->count) {
            return null;
        }

        $candidateYear = $startYear + $offset;
        $candidate = $this->buildDate($candidateYear, $startMonth, $startDay);
        $candidateDate = Date::createFromInterface($candidate);

        if ($this->hasEnd()) {
            $endDate = Date::createFromInterface($this->end);
            if ($endDate->compareDateTime($candidateDate) < 0) {
                return null;
            }
        }

        return $candidate;
    }

    /** Compute the next yearly-by-day-of-year recurrence after the given date. */
    private function nextYearlyDay(Date $after): ?DateTimeImmutable
    {
        $startDate = Date::createFromInterface($this->start);
        $startYear = (int) $startDate->format('Y');
        $afterYear = (int) $after->format('Y');
        $dayOfYear = $startDate->dayOfYear();

        $count = ($afterYear - $startYear) / $this->interval + 1;
        if ($this->count !== null
            && ($count > $this->count
                || ($count == $this->count && $after->dayOfYear() > $dayOfYear))) {
            return null;
        }

        $eYear = $startYear + (int) floor($count - 1) * $this->interval;
        $estart = $this->buildDateFromDayOfYear($eYear, $dayOfYear);
        $estartDate = Date::createFromInterface($estart);

        if ($estartDate->compareDate($after) < 0) {
            $eYear += $this->interval;
            $estart = $this->buildDateFromDayOfYear($eYear, $dayOfYear);
            $estartDate = Date::createFromInterface($estart);
        }

        if ($this->hasEnd()) {
            $endDate = Date::createFromInterface($this->end);
            if ($endDate->compareDateTime($estartDate) < 0) {
                return null;
            }
        }

        return $estart;
    }

    /** Compute the next yearly-by-weekday recurrence after the given date. */
    private function nextYearlyWeekday(Date $after): ?DateTimeImmutable
    {
        $startDate = Date::createFromInterface($this->start);
        $startYear = (int) $startDate->format('Y');
        $startMonth = (int) $startDate->format('n');
        $startDay = (int) $startDate->format('j');
        $afterYear = (int) $after->format('Y');

        $nth = (int) ceil($startDay / 7);
        $weekday = $startDate->dayOfWeek();

        $offset = (int) floor(($afterYear - $startYear + $this->interval - 1) / $this->interval) * $this->interval;
        $candidateYear = $startYear + $offset - $this->interval;
        $countSoFar = (int) ($offset / $this->interval);

        while (true) {
            if ($this->count !== null && $countSoFar++ >= $this->count) {
                return null;
            }

            $candidateYear += $this->interval;

            $firstOfMonth = new Date(
                sprintf('%04d-%02d-01', $candidateYear, $startMonth),
                $this->start->getTimezone()
            );
            $firstOfMonth = $firstOfMonth->setTime(
                (int) $startDate->format('G'),
                (int) $startDate->format('i'),
                (int) $startDate->format('s')
            );
            $next = Date::createFromInterface($firstOfMonth)->withNthWeekday($weekday, $nth);

            if ($next->compareDateTime($after) < 0) {
                continue;
            }
            if ($this->hasEnd()) {
                $endDate = Date::createFromInterface($this->end);
                if ($next->compareDateTime($endDate) > 0) {
                    return null;
                }
            }

            return $this->toDateTimeImmutable($next);
        }
    }

    /** @section RRULE generation */

    /** Generate an iCalendar RRULE string in RFC 5545 (vCalendar 2.0) format. */
    public function toRRule20(): string
    {
        $startDate = Date::createFromInterface($this->start);

        switch ($this->type) {
            case RecurrenceType::None:
                return '';

            case RecurrenceType::Daily:
                $rrule = 'FREQ=DAILY;INTERVAL=' . $this->interval;
                break;

            case RecurrenceType::Weekly:
                $rrule = 'FREQ=WEEKLY;INTERVAL=' . $this->interval;
                $days = DayMask::toRfc5545Days($this->dayMask);
                if ($days !== []) {
                    $rrule .= ';BYDAY=' . implode(',', $days);
                }
                break;

            case RecurrenceType::MonthlyDate:
                $rrule = 'FREQ=MONTHLY;INTERVAL=' . $this->interval;
                break;

            case RecurrenceType::MonthlyWeekday:
            case RecurrenceType::MonthlyLastWeekday:
                if ($this->type === RecurrenceType::MonthlyLastWeekday) {
                    $nthWeekday = -1;
                } else {
                    $nthWeekday = (int) ceil((int) $startDate->format('j') / 7);
                }
                $vcaldays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
                $rrule = 'FREQ=MONTHLY;INTERVAL=' . $this->interval
                    . ';BYDAY=' . $nthWeekday . $vcaldays[$startDate->dayOfWeek()];
                break;

            case RecurrenceType::YearlyDate:
                $rrule = 'FREQ=YEARLY;INTERVAL=' . $this->interval;
                break;

            case RecurrenceType::YearlyDay:
                $rrule = 'FREQ=YEARLY;INTERVAL=' . $this->interval
                    . ';BYYEARDAY=' . $startDate->dayOfYear();
                break;

            case RecurrenceType::YearlyWeekday:
                $nthWeekday = (int) ceil((int) $startDate->format('j') / 7);
                $vcaldays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
                $rrule = 'FREQ=YEARLY;INTERVAL=' . $this->interval
                    . ';BYDAY=' . $nthWeekday . $vcaldays[$startDate->dayOfWeek()]
                    . ';BYMONTH=' . (int) $startDate->format('n');
                break;

            default:
                return '';
        }

        if ($this->hasEnd()) {
            $endDate = Date::createFromInterface($this->end);
            $rrule .= ';UNTIL=' . $endDate->toiCalendar();
        }
        if ($this->count !== null && $this->count > 0) {
            $rrule .= ';COUNT=' . $this->count;
        }

        return $rrule;
    }

    /** Generate an iCalendar RRULE string in vCalendar 1.0 format. */
    public function toRRule10(): string
    {
        $startDate = Date::createFromInterface($this->start);

        switch ($this->type) {
            case RecurrenceType::None:
                return '';

            case RecurrenceType::Daily:
                $rrule = 'D' . $this->interval;
                break;

            case RecurrenceType::Weekly:
                $rrule = 'W' . $this->interval;
                $vcaldays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
                for ($i = 0; $i <= 7; ++$i) {
                    if (DayMask::includes($this->dayMask, (int) pow(2, $i))) {
                        $rrule .= ' ' . $vcaldays[$i];
                    }
                }
                break;

            case RecurrenceType::MonthlyDate:
                $rrule = 'MD' . $this->interval . ' ' . trim($startDate->format('j'));
                break;

            case RecurrenceType::MonthlyWeekday:
            case RecurrenceType::MonthlyLastWeekday:
                if ($this->type === RecurrenceType::MonthlyLastWeekday) {
                    $nthWeekday = '1-';
                } else {
                    $day = (int) $startDate->format('j');
                    $nthWeekday = (int) ($day / 7);
                    if (($day % 7) > 0) {
                        $nthWeekday++;
                    }
                    $nthWeekday .= '+';
                }
                $vcaldays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
                $rrule = 'MP' . $this->interval . ' ' . $nthWeekday . ' ' . $vcaldays[$startDate->dayOfWeek()];
                break;

            case RecurrenceType::YearlyDate:
                $rrule = 'YM' . $this->interval . ' ' . trim($startDate->format('n'));
                break;

            case RecurrenceType::YearlyDay:
                $rrule = 'YD' . $this->interval . ' ' . $startDate->dayOfYear();
                break;

            default:
                return '';
        }

        if ($this->hasEnd()) {
            $endDate = Date::createFromInterface($this->end);
            return $rrule . ' ' . $endDate->toiCalendar();
        }

        return $rrule . ' #' . ($this->count ?? 0);
    }

    /** @section RRULE parsing */

    /** Parse an RFC 5545 (vCalendar 2.0) RRULE string into this object. */
    public function fromRRule20(string $rrule): void
    {
        $this->reset();

        $rdata = [];
        $parts = explode(';', $rrule);
        foreach ($parts as $part) {
            $kv = explode('=', $part, 2);
            if (count($kv) === 2) {
                $rdata[strtoupper($kv[0])] = $kv[1];
            }
        }

        if (!isset($rdata['FREQ'])) {
            $this->type = RecurrenceType::None;
            return;
        }

        $this->setInterval((int) ($rdata['INTERVAL'] ?? 1));

        switch (strtoupper($rdata['FREQ'])) {
            case 'DAILY':
                $this->type = RecurrenceType::Daily;
                if (!isset($rdata['BYDAY'])) {
                    break;
                }
                // Thunderbird workaround: DAILY with BYDAY → treat as WEEKLY
                // no break
            case 'WEEKLY':
                $this->type = RecurrenceType::Weekly;
                if (isset($rdata['BYDAY'])) {
                    $days = explode(',', $rdata['BYDAY']);
                    $this->dayMask = DayMask::fromRfc5545Days($days);
                } else {
                    $this->dayMask = DayMask::fromDayOfWeek(
                        Date::createFromInterface($this->start)->dayOfWeek()
                    );
                }
                break;

            case 'MONTHLY':
                if (isset($rdata['BYDAY'])) {
                    if (str_contains($rdata['BYDAY'], '-')) {
                        $this->type = RecurrenceType::MonthlyLastWeekday;
                    } else {
                        $this->type = RecurrenceType::MonthlyWeekday;
                    }
                } else {
                    $this->type = RecurrenceType::MonthlyDate;
                }
                break;

            case 'YEARLY':
                if (isset($rdata['BYYEARDAY'])) {
                    $this->type = RecurrenceType::YearlyDay;
                } elseif (isset($rdata['BYDAY'])) {
                    $this->type = RecurrenceType::YearlyWeekday;
                } else {
                    $this->type = RecurrenceType::YearlyDate;
                }
                break;
        }

        if (isset($rdata['UNTIL'])) {
            if (preg_match('/^(\d{4})-?(\d{2})-?(\d{2})T? ?(\d{2}):?(\d{2}):?(\d{2})(?:\.\d+)?(Z?)$/', $rdata['UNTIL'], $parts)) {
                $until = new DateTimeImmutable($rdata['UNTIL'], new DateTimeZone('UTC'));
                $until = $until->setTimezone($this->start->getTimezone());
            } else {
                [$year, $month, $mday] = sscanf($rdata['UNTIL'], '%04d%02d%02d');
                $until = new DateTimeImmutable(
                    sprintf('%04d-%02d-%02d', $year, $month, $mday + 1),
                    $this->start->getTimezone()
                );
            }
            $this->setEnd($until);
        }
        if (isset($rdata['COUNT'])) {
            $this->setCount((int) $rdata['COUNT']);
        }
    }

    /** Parse a vCalendar 1.0 RRULE string into this object. */
    public function fromRRule10(string $rrule): void
    {
        $this->reset();

        if ($rrule === '') {
            return;
        }

        if (!preg_match('/([A-Z]+)(\d+)?(.*)/', $rrule, $matches)) {
            $this->type = RecurrenceType::None;
            return;
        }

        $this->setInterval(!empty($matches[2]) ? (int) $matches[2] : 1);
        $remainder = trim($matches[3]);

        switch ($matches[1]) {
            case 'D':
                $this->type = RecurrenceType::Daily;
                break;

            case 'W':
                $this->type = RecurrenceType::Weekly;
                $maskdays = [
                    'SU' => DayMask::SUNDAY, 'MO' => DayMask::MONDAY,
                    'TU' => DayMask::TUESDAY, 'WE' => DayMask::WEDNESDAY,
                    'TH' => DayMask::THURSDAY, 'FR' => DayMask::FRIDAY,
                    'SA' => DayMask::SATURDAY,
                ];
                $mask = 0;
                if (!empty($remainder)) {
                    while (preg_match('/^ ?(' . implode('|', array_keys($maskdays)) . ') ?/', $remainder, $m)) {
                        $day = trim($m[0]);
                        $remainder = substr($remainder, strlen($m[0]));
                        $mask |= $maskdays[$day];
                    }
                    $this->dayMask = $mask;
                }
                if ($mask === 0) {
                    $this->dayMask = DayMask::fromDayOfWeek(
                        Date::createFromInterface($this->start)->dayOfWeek()
                    );
                }
                break;

            case 'MP':
                $this->type = RecurrenceType::MonthlyWeekday;
                // Known limitation: trim() above strips the leading space
                // before the regex, so "1-" won't be detected as last weekday.
                if (preg_match('/^ \d([+-])/', $matches[3], $m) && $m[1] === '-') {
                    $this->type = RecurrenceType::MonthlyLastWeekday;
                }
                break;

            case 'MD':
                $this->type = RecurrenceType::MonthlyDate;
                break;

            case 'YM':
                $this->type = RecurrenceType::YearlyDate;
                break;

            case 'YD':
                $this->type = RecurrenceType::YearlyDay;
                break;
        }

        while ($remainder !== '' && !preg_match('/^(#\d+|\d{8})($| |T\d{6})/', $remainder)) {
            $remainder = substr($remainder, 1);
        }

        if ($remainder !== '') {
            if (str_starts_with($remainder, '#')) {
                $this->setCount((int) substr($remainder, 1));
            } else {
                [$year, $month, $mday, $hour, $min, $sec, $tzStr]
                    = sscanf($remainder, '%04d%02d%02dT%02d%02d%02d%s');
                $tz = ($tzStr === 'Z') ? new DateTimeZone('UTC') : $this->start->getTimezone();
                $this->setEnd(new DateTimeImmutable(
                    sprintf('%04d-%02d-%02dT%02d:%02d:%02d', $year, $month, $mday, $hour ?? 0, $min ?? 0, $sec ?? 0),
                    $tz
                ));
            }
        }
    }

    /** @section Serialization */

    /** Serialize this recurrence to a compact JSON-friendly stdClass. */
    public function toJson(): stdClass
    {
        $json = new stdClass();
        $json->t = $this->type->value;
        $json->i = $this->interval;
        if ($this->hasEnd()) {
            $json->e = Date::createFromInterface($this->end)->toJson();
        }
        if ($this->count !== null && $this->count > 0) {
            $json->c = $this->count;
        }
        if ($this->dayMask !== 0) {
            $json->d = $this->dayMask;
        }
        if ($this->completions !== []) {
            $json->co = $this->completions;
        }
        if ($this->exceptions !== []) {
            $json->ex = $this->exceptions;
        }
        if ($this->rdates !== []) {
            $json->rd = $this->rdates;
        }
        return $json;
    }

    /** Serialize this recurrence to an associative array for storage. */
    public function toHash(): array
    {
        $startStr = $this->start->format('Y-m-d H:i:s') . '/' . $this->start->getTimezone()->getName();
        $endStr = $this->end !== null
            ? $this->end->format('Y-m-d H:i:s') . '/' . $this->end->getTimezone()->getName()
            : null;

        return [
            'start' => $startStr,
            'end' => $endStr,
            'count' => $this->count,
            'type' => $this->type->value,
            'interval' => $this->interval,
            'data' => $this->dayMask ?: null,
            'exceptions' => $this->exceptions,
            'completions' => $this->completions,
            'rdates' => $this->rdates,
        ];
    }

    /** Reconstruct a Recurrence instance from a hash array. */
    public static function fromHash(array $hash): static
    {
        $startParts = explode('/', $hash['start'], 2);
        $tz = isset($startParts[1]) ? new DateTimeZone($startParts[1]) : null;
        $start = new DateTimeImmutable($startParts[0], $tz);

        $recurrence = new static($start);

        if (!empty($hash['end'])) {
            $endParts = explode('/', $hash['end'], 2);
            $endTz = isset($endParts[1]) ? new DateTimeZone($endParts[1]) : null;
            $recurrence->end = new DateTimeImmutable($endParts[0], $endTz);
        }

        $recurrence->count = $hash['count'];
        $recurrence->type = RecurrenceType::from($hash['type']);
        $recurrence->interval = (int) $hash['interval'];
        $recurrence->dayMask = (int) ($hash['data'] ?? 0);
        $recurrence->exceptions = $hash['exceptions'] ?? [];
        $recurrence->completions = $hash['completions'] ?? [];
        $recurrence->rdates = $hash['rdates'] ?? [];

        return $recurrence;
    }

    /** Check whether this recurrence rule equals another (ignoring exceptions). */
    public function isEqual(self $other): bool
    {
        return $this->type === $other->type
            && $this->interval === $other->interval
            && $this->count === $other->count
            && $this->end == $other->end
            && $this->start == $other->start
            && $this->dayMask === $other->dayMask;
    }

    /** Get the translated human-readable name of this recurrence type. */
    public function getRecurName(): string
    {
        return match ($this->type) {
            RecurrenceType::None => Translation::t('No recurrence'),
            RecurrenceType::Daily => Translation::t('Daily'),
            RecurrenceType::Weekly => Translation::t('Weekly'),
            RecurrenceType::MonthlyDate,
            RecurrenceType::MonthlyWeekday,
            RecurrenceType::MonthlyLastWeekday => Translation::t('Monthly'),
            RecurrenceType::YearlyDate,
            RecurrenceType::YearlyDay,
            RecurrenceType::YearlyWeekday => Translation::t('Yearly'),
        };
    }

    /** @section Internal helpers */

    /** Get the first day (Monday) of a given ISO week in a specific timezone. */
    private function firstDayOfWeekInTz(
        int $week,
        int $year,
        DateTimeZone $tz,
        int $hour,
        int $min,
        int $sec
    ): DateTimeImmutable {
        return new DateTimeImmutable(
            sprintf('%04dW%02d %02d:%02d:%02d', $year, $week, $hour, $min, $sec),
            $tz
        );
    }

    /** Build a DateTimeImmutable for a given year/month/day using the start time. */
    private function buildDate(int $year, int $month, int $day): DateTimeImmutable
    {
        $tz = $this->start->getTimezone();
        return new DateTimeImmutable(
            sprintf(
                '%04d-%02d-%02dT%s',
                $year,
                $month,
                $day,
                $this->start->format('H:i:s')
            ),
            $tz
        );
    }

    /** Build a DateTimeImmutable from a year and day-of-year offset. */
    private function buildDateFromDayOfYear(int $year, int $dayOfYear): DateTimeImmutable
    {
        $jan1 = new DateTimeImmutable(
            sprintf('%04d-01-01T%s', $year, $this->start->format('H:i:s')),
            $this->start->getTimezone()
        );
        return $jan1->modify('+' . ($dayOfYear - 1) . ' days');
    }

    /** Convert any DateTimeInterface to a DateTimeImmutable. */
    private function toDateTimeImmutable(DateTimeInterface $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) {
            return $date;
        }
        return DateTimeImmutable::createFromInterface($date);
    }
}
