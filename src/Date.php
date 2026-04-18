<?php

declare(strict_types=1);

/**
 * Copyright 2004-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2004-2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 */

namespace Horde\Date;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Horde\Date\Formatter\DateTimeFormatter;
use InvalidArgumentException;
use Stringable;

class Date extends DateTimeImmutable implements DateInterface
{
    public const SUNDAY = 0;
    public const MONDAY = 1;
    public const TUESDAY = 2;
    public const WEDNESDAY = 3;
    public const THURSDAY = 4;
    public const FRIDAY = 5;
    public const SATURDAY = 6;

    // =========================================================================
    // DateInterface
    // =========================================================================

    public function format(
        Stringable|string $pattern,
        string|FormatterInterface|null $formatter = null,
        Stringable|string|null $locale = null,
    ) {
        $pattern = (string) $pattern;

        if ($formatter === null && $locale === null) {
            return parent::format($pattern);
        }

        if ($formatter === null) {
            $formatter = new DateTimeFormatter();
        } elseif (is_string($formatter)) {
            if (!class_exists($formatter)) {
                throw new InvalidArgumentException("Formatter class not found: $formatter");
            }
            $formatter = new $formatter();
        }

        if (!$formatter instanceof FormatterInterface) {
            throw new InvalidArgumentException('Formatter must implement FormatterInterface');
        }

        $locale = $locale !== null ? (string) $locale : 'en_US';
        $timezone = $this->getTimezone()->getName();

        return $formatter->format($this, $pattern, $locale, $timezone);
    }

    public function timestamp(): int
    {
        return $this->getTimestamp();
    }

    public function toDateTimeImmutable(): DateTimeImmutable
    {
        return $this;
    }

    // =========================================================================
    // Calendar calculations
    // =========================================================================

    public function toDays(): int
    {
        $month = (int) parent::format('n');
        $day = (int) parent::format('j');
        $year = (int) parent::format('Y');

        if (function_exists('GregorianToJD')) {
            return gregoriantojd($month, $day, $year);
        }

        if ($month > 2) {
            $month -= 3;
        } else {
            $month += 9;
            --$year;
        }

        $negativeyear = $year < 0;
        $century = intval($year / 100);
        $year = $year % 100;

        if ($negativeyear) {
            return intval((14609700 * $century + ($year == 0 ? 1 : 0)) / 400) +
                intval((1461 * $year + 1) / 4) +
                intval((153 * $month + 2) / 5) +
                $day + 1721118;
        }

        return intval(146097 * $century / 4) +
            intval(1461 * $year / 4) +
            intval((153 * $month + 2) / 5) +
            $day + 1721119;
    }

    public static function fromDays(int $days): static
    {
        if (function_exists('jdtogregorian')) {
            [$month, $day, $year] = explode('/', jdtogregorian($days));
        } else {
            $days -= 1721119;
            $century = floor((4 * $days - 1) / 146097);
            $days = floor(4 * $days - 1 - 146097 * $century);
            $day = floor($days / 4);

            $year = floor((4 * $day + 3) / 1461);
            $day = floor(4 * $day + 3 - 1461 * $year);
            $day = floor(($day + 4) / 4);

            $month = floor((5 * $day - 3) / 153);
            $day = floor(5 * $day - 3 - 153 * $month);
            $day = floor(($day + 5) / 5);

            $year = $century * 100 + $year;
            if ($month < 10) {
                $month += 3;
            } else {
                $month -= 9;
                ++$year;
            }
        }

        return new static(sprintf('%04d-%02d-%02d', (int) $year, (int) $month, (int) $day));
    }

    public function dayOfWeek(): int
    {
        $month = (int) parent::format('n');
        $day = (int) parent::format('j');
        $year = (int) parent::format('Y');

        if ($month > 2) {
            $month -= 2;
        } else {
            $month += 10;
            --$year;
        }

        $result = floor((13 * $month - 1) / 5) +
            $day + ($year % 100) +
            floor(($year % 100) / 4) +
            floor(($year / 100) / 4) - 2 *
            floor($year / 100) + 77;

        return (int) ($result - 7 * floor($result / 7));
    }

    public function dayOfYear(): int
    {
        return (int) parent::format('z') + 1;
    }

    public function weekOfMonth(): int
    {
        return (int) ceil((int) parent::format('j') / 7);
    }

    public function weekOfYear(): int
    {
        return (int) parent::format('W');
    }

    public static function weeksInYear(int $year): int
    {
        $date = new static($year . '-12-31');
        while ($date->dayOfWeek() !== self::THURSDAY) {
            $date = $date->modify('-1 day');
        }
        return $date->weekOfYear();
    }

    public function withNthWeekday(int $weekday, int $nth = 1): static
    {
        if ($weekday < 0 || $weekday > 6) {
            return $this;
        }

        $year = (int) parent::format('Y');
        $month = (int) parent::format('n');

        if ($nth >= 0) {
            $firstOfMonth = $this->setDate($year, $month, 1);
            $firstDow = $firstOfMonth->dayOfWeek();

            $day = $weekday - $firstDow + 1;
            if ($weekday < $firstDow) {
                $day += 7;
            }
            $day += 7 * $nth - 7;

            return $this->setDate($year, $month, $day);
        }

        $daysInMonth = (int) parent::format('t');
        $lastOfMonth = $this->setDate($year, $month, $daysInMonth);
        $lastDow = $lastOfMonth->dayOfWeek();

        $day = $daysInMonth - ($lastDow - $weekday);
        if ($lastDow < $weekday) {
            $day -= 7;
        }
        $day -= (-7 * $nth - 7);

        return $this->setDate($year, $month, $day);
    }

    public function diffDays(DateTimeInterface $other): int
    {
        $otherDate = $other instanceof self
            ? $other
            : static::createFromInterface($other);

        return abs($this->toDays() - $otherDate->toDays());
    }

    // =========================================================================
    // Comparison
    // =========================================================================

    public function compareDate(DateTimeInterface $other): int
    {
        $thisY = (int) parent::format('Y');
        $thisM = (int) parent::format('n');
        $thisD = (int) parent::format('j');

        $otherY = (int) $other->format('Y');
        $otherM = (int) $other->format('n');
        $otherD = (int) $other->format('j');

        if ($thisY !== $otherY) {
            return $thisY - $otherY;
        }
        if ($thisM !== $otherM) {
            return $thisM - $otherM;
        }
        return $thisD - $otherD;
    }

    public function compareTime(DateTimeInterface $other): int
    {
        $thisH = (int) parent::format('G');
        $thisI = (int) parent::format('i');
        $thisS = (int) parent::format('s');

        $otherH = (int) $other->format('G');
        $otherI = (int) $other->format('i');
        $otherS = (int) $other->format('s');

        if ($thisH !== $otherH) {
            return $thisH - $otherH;
        }
        if ($thisI !== $otherI) {
            return $thisI - $otherI;
        }
        return $thisS - $otherS;
    }

    public function compareDateTime(DateTimeInterface $other): int
    {
        $cmp = $this->compareDate($other);
        if ($cmp !== 0) {
            return $cmp;
        }
        return $this->compareTime($other);
    }

    public function before(DateTimeInterface $other): bool
    {
        return $this->compareDateTime($other) < 0;
    }

    public function after(DateTimeInterface $other): bool
    {
        return $this->compareDateTime($other) > 0;
    }

    public function equals(DateTimeInterface $other): bool
    {
        return $this->compareDateTime($other) === 0;
    }

    // =========================================================================
    // Arithmetic
    // =========================================================================

    public function addParts(
        int $years = 0,
        int $months = 0,
        int $days = 0,
        int $hours = 0,
        int $minutes = 0,
        int $seconds = 0,
    ): static {
        $result = $this;

        $totalMonths = $years * 12 + $months;
        if ($totalMonths > 0) {
            $result = $result->modify("+{$totalMonths} months");
        } elseif ($totalMonths < 0) {
            $abs = abs($totalMonths);
            $result = $result->modify("-{$abs} months");
        }

        if ($days > 0) {
            $result = $result->modify("+{$days} days");
        } elseif ($days < 0) {
            $abs = abs($days);
            $result = $result->modify("-{$abs} days");
        }

        $totalSeconds = $hours * 3600 + $minutes * 60 + $seconds;
        if ($totalSeconds > 0) {
            $result = $result->modify("+{$totalSeconds} seconds");
        } elseif ($totalSeconds < 0) {
            $abs = abs($totalSeconds);
            $result = $result->modify("-{$abs} seconds");
        }

        return $result;
    }

    public function subParts(
        int $years = 0,
        int $months = 0,
        int $days = 0,
        int $hours = 0,
        int $minutes = 0,
        int $seconds = 0,
    ): static {
        return $this->addParts(-$years, -$months, -$days, -$hours, -$minutes, -$seconds);
    }

    // =========================================================================
    // Serialization
    // =========================================================================

    public function toJson(): string
    {
        return parent::format('Y-m-d\TH:i:s');
    }

    public function toiCalendar(bool $floating = false): string
    {
        if ($floating) {
            return parent::format('Ymd\THis');
        }
        return $this->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
    }
}
