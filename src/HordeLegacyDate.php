<?php

declare(strict_types=1);

/**
 * Copyright 2004-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2004-2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 */

namespace Horde\Date;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Horde_Date;
use Horde_Date_Exception;
use InvalidArgumentException;
use Exception;
use Horde\Date\Format;

/**
 * Mutable date class backed by DateTimeImmutable.
 *
 * Extends Horde_Date for full BC compatibility but stores all state in a
 * private DateTimeImmutable. The parent's protected $_* fields are dead
 * storage after construction — only $inner is authoritative.
 *
 * Overflow normalization uses PHP's native date math (setDate/setTime)
 * instead of the legacy _correct() engine.
 */
class HordeLegacyDate extends Horde_Date
{
    private DateTimeImmutable $inner;

    public function __construct($date = null, $timezone = null, $locale = null)
    {
        // Parent uses func_num_args() to detect variadic (year,month,day...)
        // calling. We must match the caller's actual arg count to avoid
        // triggering that branch when $timezone/$locale are null.
        if ($locale !== null) {
            parent::__construct($date, $timezone, $locale);
        } elseif ($timezone !== null) {
            parent::__construct($date, $timezone);
        } elseif ($date !== null) {
            parent::__construct($date);
        } else {
            parent::__construct();
        }

        $this->inner = new DateTimeImmutable(
            sprintf(
                '%04d-%02d-%02d %02d:%02d:%02d',
                (int) $this->_year,
                (int) $this->_month,
                (int) $this->_mday,
                (int) $this->_hour,
                (int) $this->_min,
                (int) $this->_sec,
            ),
            new DateTimeZone($this->_timezone),
        );
    }

    public function __get($name)
    {
        if ($name === 'day') {
            $name = 'mday';
        }
        return match ($name) {
            'year' => (int) $this->inner->format('Y'),
            'month' => (int) $this->inner->format('n'),
            'mday' => (int) $this->inner->format('j'),
            'hour' => (int) $this->inner->format('G'),
            'min' => (int) $this->inner->format('i'),
            'sec' => (int) $this->inner->format('s'),
            'timezone' => $this->inner->getTimezone()->getName(),
            default => null,
        };
    }

    public function __set($name, $value)
    {
        if ($name === 'day') {
            $name = 'mday';
        }

        if ($name === 'timezone') {
            $tz = new DateTimeZone(Horde_Date::getTimezoneAlias((string) $value));
            $this->inner = new DateTimeImmutable(
                $this->inner->format('Y-m-d H:i:s'),
                $tz,
            );
            return;
        }

        $valid = ['year', 'month', 'mday', 'hour', 'min', 'sec'];
        if (!in_array($name, $valid, true)) {
            throw new InvalidArgumentException('Undefined property ' . $name);
        }

        $value = (int) $value;
        $y = (int) $this->inner->format('Y');
        $m = (int) $this->inner->format('n');
        $d = (int) $this->inner->format('j');
        $h = (int) $this->inner->format('G');
        $i = (int) $this->inner->format('i');
        $s = (int) $this->inner->format('s');

        match ($name) {
            'year' => $y = $value,
            'month' => $m = $value,
            'mday' => $d = $value,
            'hour' => $h = $value,
            'min' => $i = $value,
            'sec' => $s = $value,
        };

        $this->inner = $this->inner->setDate($y, $m, $d)->setTime($h, $i, $s);
    }

    public function __isset($name)
    {
        if ($name === 'day') {
            $name = 'mday';
        }
        return in_array($name, ['year', 'month', 'mday', 'hour', 'min', 'sec'], true);
    }

    public function __toString()
    {
        try {
            return $this->format($this->_defaultFormat);
        } catch (Exception $e) {
            return '';
        }
    }

    public function __clone()
    {
        // DateTimeImmutable is a value type, but be explicit
    }

    // =========================================================================
    // Conversion
    // =========================================================================

    public function toDateTime()
    {
        return DateTime::createFromImmutable($this->inner);
    }

    public function toDateTimeImmutable(): DateTimeImmutable
    {
        return $this->inner;
    }

    public function toDate(): Date
    {
        return Date::createFromInterface($this->inner);
    }

    public function getTimezone(): DateTimeZone|false
    {
        return $this->inner->getTimezone();
    }

    // =========================================================================
    // Calendar calculations
    // =========================================================================

    public function toDays()
    {
        return $this->toDate()->toDays();
    }

    public static function fromDays($days)
    {
        $modern = Date::fromDays((int) $days);
        return new static($modern->format('Y-m-d H:i:s'));
    }

    public function dayOfWeek()
    {
        return (int) $this->inner->format('w');
    }

    public function dayOfYear()
    {
        return (int) $this->inner->format('z') + 1;
    }

    public function weekOfMonth()
    {
        return (int) ceil((int) $this->inner->format('j') / 7);
    }

    public function weekOfYear()
    {
        return (int) $this->inner->format('W');
    }

    public static function weeksInYear($year)
    {
        return Date::weeksInYear((int) $year);
    }

    public function setNthWeekday($weekday, $nth = 1)
    {
        if ($weekday < self::DATE_SUNDAY || $weekday > self::DATE_SATURDAY) {
            return;
        }

        $date = $this->toDate();
        $result = $date->withNthWeekday($weekday, $nth);
        $this->inner = $this->inner
            ->setDate(
                (int) $result->format('Y'),
                (int) $result->format('n'),
                (int) $result->format('j'),
            );
    }

    public function isValid()
    {
        $year = (int) $this->inner->format('Y');
        return $year >= 0 && $year <= 9999;
    }

    // =========================================================================
    // Comparison
    // =========================================================================

    public function compareDate($other)
    {
        if (!($other instanceof Horde_Date)) {
            $other = new Horde_Date($other);
        }

        $thisY = (int) $this->inner->format('Y');
        $thisM = (int) $this->inner->format('n');
        $thisD = (int) $this->inner->format('j');

        if ($thisY != $other->year) {
            return $thisY - $other->year;
        }
        if ($thisM != $other->month) {
            return $thisM - $other->month;
        }
        return $thisD - $other->mday;
    }

    public function compareTime($other)
    {
        if (!($other instanceof Horde_Date)) {
            $other = new Horde_Date($other);
        }

        $thisH = (int) $this->inner->format('G');
        $thisI = (int) $this->inner->format('i');
        $thisS = (int) $this->inner->format('s');

        if ($thisH != $other->hour) {
            return $thisH - $other->hour;
        }
        if ($thisI != $other->min) {
            return $thisI - $other->min;
        }
        return $thisS - $other->sec;
    }

    public function compareDateTime($other)
    {
        if (!($other instanceof Horde_Date)) {
            $other = new Horde_Date($other);
        }

        if ($diff = $this->compareDate($other)) {
            return $diff;
        }
        return $this->compareTime($other);
    }

    public function after($other)
    {
        return $this->compareDate($other) > 0;
    }

    public function before($other)
    {
        return $this->compareDate($other) < 0;
    }

    public function equals($other)
    {
        return $this->compareDate($other) == 0;
    }

    public function diff($other)
    {
        if (!($other instanceof Horde_Date)) {
            $other = new Horde_Date($other);
        }
        return abs($this->toDays() - $other->toDays());
    }

    // =========================================================================
    // Arithmetic
    // =========================================================================

    public function add($factor)
    {
        $d = clone $this;
        if (is_array($factor) || is_object($factor)) {
            foreach ($factor as $property => $value) {
                $d->$property += $value;
            }
        } else {
            $d->inner = $d->inner->modify(sprintf('%+d seconds', (int) $factor));
        }
        return $d;
    }

    public function sub($factor)
    {
        if (is_array($factor)) {
            foreach ($factor as &$value) {
                $value *= -1;
            }
        } else {
            $factor *= -1;
        }
        return $this->add($factor);
    }

    // =========================================================================
    // Timezone
    // =========================================================================

    public function setTimezone($timezone)
    {
        $timezone = self::getTimezoneAlias($timezone);
        try {
            $this->inner = $this->inner->setTimezone(new DateTimeZone($timezone));
        } catch (Exception $e) {
            throw new Horde_Date_Exception($e->getMessage());
        }
        return $this;
    }

    public function tzOffset($colon = true)
    {
        return $this->inner->format($colon ? 'P' : 'O');
    }

    // =========================================================================
    // Timestamps & Serialization
    // =========================================================================

    public function timestamp()
    {
        return $this->inner->getTimestamp();
    }

    public function datestamp()
    {
        return $this->inner->setTime(0, 0, 0)->getTimestamp();
    }

    public function dateString()
    {
        return $this->inner->format('Ymd');
    }

    public function toJson()
    {
        return $this->inner->format(self::DATE_JSON);
    }

    public function toiCalendar($floating = false)
    {
        if ($floating) {
            return $this->inner->format('Ymd\THis');
        }
        return $this->inner->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
    }

    // =========================================================================
    // Formatting
    // =========================================================================

    public function setDefaultFormat($format)
    {
        $this->_defaultFormat = $format;
    }

    public function format($pattern, $formatter = null, $locale = null)
    {
        if ($formatter === null && $locale === null && func_num_args() === 1) {
            return $this->inner->format((string) $pattern);
        }

        $pattern = (string) $pattern;

        if ($formatter === null) {
            $formatter = new Formatter\DateTimeFormatter();
        } elseif (is_string($formatter)) {
            if (!class_exists($formatter)) {
                throw new InvalidArgumentException("Formatter class not found: $formatter");
            }
            $formatter = new $formatter();
        }

        if (!$formatter instanceof FormatterInterface) {
            throw new InvalidArgumentException("Formatter must implement FormatterInterface");
        }

        if ($locale !== null) {
            $locale = (string) $locale;
        }

        $timezone = $this->inner->getTimezone()->getName();
        $locale = $locale ?? $this->_locale ?? setlocale(LC_ALL, '0') ?: 'en_US';

        return $formatter->format($this, $pattern, $locale, $timezone);
    }

    public function strftime($format)
    {
        return Format::formatDate($this->timestamp(), $format);
    }
}
