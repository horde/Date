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

use InvalidArgumentException;

final class DayMask
{
    public const SUNDAY = 1;
    public const MONDAY = 2;
    public const TUESDAY = 4;
    public const WEDNESDAY = 8;
    public const THURSDAY = 16;
    public const FRIDAY = 32;
    public const SATURDAY = 64;

    public const WEEKDAYS = self::MONDAY | self::TUESDAY | self::WEDNESDAY | self::THURSDAY | self::FRIDAY;
    public const WEEKEND = self::SUNDAY | self::SATURDAY;
    public const ALL_DAYS = self::WEEKDAYS | self::WEEKEND;

    private const DAY_OF_WEEK_MAP = [
        0 => self::SUNDAY,
        1 => self::MONDAY,
        2 => self::TUESDAY,
        3 => self::WEDNESDAY,
        4 => self::THURSDAY,
        5 => self::FRIDAY,
        6 => self::SATURDAY,
    ];

    private const RFC5545_MAP = [
        'SU' => self::SUNDAY,
        'MO' => self::MONDAY,
        'TU' => self::TUESDAY,
        'WE' => self::WEDNESDAY,
        'TH' => self::THURSDAY,
        'FR' => self::FRIDAY,
        'SA' => self::SATURDAY,
    ];

    private const REVERSE_RFC5545_MAP = [
        self::SUNDAY => 'SU',
        self::MONDAY => 'MO',
        self::TUESDAY => 'TU',
        self::WEDNESDAY => 'WE',
        self::THURSDAY => 'TH',
        self::FRIDAY => 'FR',
        self::SATURDAY => 'SA',
    ];

    public static function includes(int $mask, int $day): bool
    {
        return ($mask & $day) !== 0;
    }

    public static function fromDays(int ...$days): int
    {
        $mask = 0;
        foreach ($days as $day) {
            $mask |= $day;
        }
        return $mask;
    }

    /**
     * @param int $dayOfWeek 0 (Sunday) through 6 (Saturday)
     */
    public static function fromDayOfWeek(int $dayOfWeek): int
    {
        if (!isset(self::DAY_OF_WEEK_MAP[$dayOfWeek])) {
            throw new InvalidArgumentException(
                "Invalid day of week: $dayOfWeek (expected 0-6)"
            );
        }
        return self::DAY_OF_WEEK_MAP[$dayOfWeek];
    }

    /**
     * @return list<int> Individual day constants present in the mask
     */
    public static function toDays(int $mask): array
    {
        $days = [];
        foreach (self::DAY_OF_WEEK_MAP as $bit) {
            if (($mask & $bit) !== 0) {
                $days[] = $bit;
            }
        }
        return $days;
    }

    public static function count(int $mask): int
    {
        $count = 0;
        foreach (self::DAY_OF_WEEK_MAP as $bit) {
            if (($mask & $bit) !== 0) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @param list<string> $days RFC 5545 day abbreviations (e.g. ['MO', 'WE', 'FR'])
     */
    public static function fromRfc5545Days(array $days): int
    {
        $mask = 0;
        foreach ($days as $day) {
            $upper = strtoupper($day);
            if (!isset(self::RFC5545_MAP[$upper])) {
                throw new InvalidArgumentException(
                    "Invalid RFC 5545 day abbreviation: $day"
                );
            }
            $mask |= self::RFC5545_MAP[$upper];
        }
        return $mask;
    }

    /**
     * @return list<string> RFC 5545 day abbreviations in SU-SA order
     */
    public static function toRfc5545Days(int $mask): array
    {
        $days = [];
        foreach (self::REVERSE_RFC5545_MAP as $bit => $abbr) {
            if (($mask & $bit) !== 0) {
                $days[] = $abbr;
            }
        }
        return $days;
    }
}
