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

    /**
     * Construct a mutable date from various input formats.
     *
     * @param mixed $date      Date input (string, timestamp, array, DateTimeInterface, or null for now).
     * @param string|null $timezone  Timezone identifier.
     * @param string|null $locale    Locale for formatting.
     */
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

    /**
     * Read a date component property from the inner DateTimeImmutable.
     *
     * Supports: year, month, mday (or day), hour, min, sec, timezone.
     */
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

    /**
     * Set a date component property, normalizing overflow via native date math.
     *
     * Supports: year, month, mday (or day), hour, min, sec, timezone.
     */
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

    /**
     * Check whether a date property exists (year, month, mday, hour, min, sec).
     */
    public function __isset($name)
    {
        if ($name === 'day') {
            $name = 'mday';
        }
        return in_array($name, ['year', 'month', 'mday', 'hour', 'min', 'sec'], true);
    }

    /**
     * Return a string representation using the default format.
     */
    public function __toString()
    {
        try {
            return $this->format($this->_defaultFormat);
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Clone handler -- no-op since DateTimeImmutable is a value type.
     */
    public function __clone()
    {
        // DateTimeImmutable is a value type, but be explicit
    }

    /** @section Conversion */

    /**
     * Convert to a mutable DateTime instance.
     */
    public function toDateTime()
    {
        return DateTime::createFromImmutable($this->inner);
    }

    /**
     * Convert to a DateTimeImmutable instance.
     */
    public function toDateTimeImmutable(): DateTimeImmutable
    {
        return $this->inner;
    }

    /**
     * Convert to the modern immutable Date value object.
     */
    public function toDate(): Date
    {
        return Date::createFromInterface($this->inner);
    }

    /**
     * Return the timezone as a DateTimeZone object.
     */
    public function getTimezone(): DateTimeZone|false
    {
        return $this->inner->getTimezone();
    }

    /** @section Calendar calculations */

    /**
     * Return the Julian Day Count for this date.
     */
    public function toDays()
    {
        return $this->toDate()->toDays();
    }

    /**
     * Create an instance from a Julian Day Count.
     *
     * @param int|float $days  Julian Day Count.
     */
    public static function fromDays($days)
    {
        $modern = Date::fromDays((int) $days);
        return new static($modern->format('Y-m-d H:i:s'));
    }

    /**
     * Return the day of the week (0=Sunday, 6=Saturday).
     */
    public function dayOfWeek()
    {
        return (int) $this->inner->format('w');
    }

    /**
     * Return the day of the year (1-366).
     */
    public function dayOfYear()
    {
        return (int) $this->inner->format('z') + 1;
    }

    /**
     * Return which week of the month this date falls in.
     */
    public function weekOfMonth()
    {
        return (int) ceil((int) $this->inner->format('j') / 7);
    }

    /**
     * Return the ISO week number of the year.
     */
    public function weekOfYear()
    {
        return (int) $this->inner->format('W');
    }

    /**
     * Return the number of ISO weeks in a given year.
     */
    public static function weeksInYear($year)
    {
        return Date::weeksInYear((int) $year);
    }

    /**
     * Set the date to the Nth occurrence of a weekday in the current month.
     *
     * @param int $weekday  Day of week (DATE_SUNDAY through DATE_SATURDAY).
     * @param int $nth      Which occurrence (negative counts from end of month).
     */
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

    /**
     * Check whether the date represents a valid calendar date.
     */
    public function isValid()
    {
        $year = (int) $this->inner->format('Y');
        return $year >= 0 && $year <= 9999;
    }

    /** @section Comparison */

    /**
     * Compare only the date portion with another date.
     *
     * @return int  Negative if before, positive if after, zero if equal.
     */
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

    /**
     * Compare only the time portion with another date.
     *
     * @return int  Negative if before, positive if after, zero if equal.
     */
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

    /**
     * Compare both date and time portions with another date.
     *
     * @return int  Negative if before, positive if after, zero if equal.
     */
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

    /**
     * Return whether this date is after another (date portion only).
     */
    public function after($other)
    {
        return $this->compareDate($other) > 0;
    }

    /**
     * Return whether this date is before another (date portion only).
     */
    public function before($other)
    {
        return $this->compareDate($other) < 0;
    }

    /**
     * Return whether this date is equal to another (date portion only).
     */
    public function equals($other)
    {
        return $this->compareDate($other) == 0;
    }

    /**
     * Return the absolute difference in days between this date and another.
     */
    public function diff($other)
    {
        if (!($other instanceof Horde_Date)) {
            $other = new Horde_Date($other);
        }
        return abs($this->toDays() - $other->toDays());
    }

    /** @section Arithmetic */

    /**
     * Add a factor to this date and return a new instance.
     *
     * @param array|object|int $factor  Field deltas or seconds to add.
     */
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

    /**
     * Subtract a factor from this date and return a new instance.
     *
     * @param array|int $factor  Field deltas or seconds to subtract.
     */
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

    /** @section Timezone */

    /**
     * Convert the date to the specified timezone (mutates inner state).
     *
     * @return $this
     */
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

    /**
     * Return the timezone offset string (e.g. +02:00 or +0200).
     *
     * @param bool $colon  Whether to include the colon separator.
     */
    public function tzOffset($colon = true)
    {
        return $this->inner->format($colon ? 'P' : 'O');
    }

    /** @section Timestamps and Serialization */

    /**
     * Return the Unix timestamp for this date.
     */
    public function timestamp()
    {
        return $this->inner->getTimestamp();
    }

    /**
     * Return a Unix timestamp for midnight on this date.
     */
    public function datestamp()
    {
        return $this->inner->setTime(0, 0, 0)->getTimestamp();
    }

    /**
     * Return a compact date string (Ymd format).
     */
    public function dateString()
    {
        return $this->inner->format('Ymd');
    }

    /**
     * Return a JSON-compatible date string.
     */
    public function toJson()
    {
        return $this->inner->format(self::DATE_JSON);
    }

    /**
     * Return an iCalendar-formatted date string.
     *
     * @param bool $floating  If true, return local time without UTC conversion.
     */
    public function toiCalendar($floating = false)
    {
        if ($floating) {
            return $this->inner->format('Ymd\THis');
        }
        return $this->inner->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
    }

    /** @section Formatting */

    /**
     * Set the default format used by __toString().
     */
    public function setDefaultFormat($format)
    {
        $this->_defaultFormat = $format;
    }

    /**
     * Format the date using a pattern and optional formatter/locale.
     *
     * @param string $pattern  Date format pattern (PHP date() or formatter-specific).
     * @param FormatterInterface|string|null $formatter  Formatter instance or class name.
     * @param string|null $locale  Locale for formatting.
     */
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

    /**
     * Format date using strftime-style format codes.
     */
    public function strftime($format)
    {
        return Format::formatDate($this->timestamp(), $format);
    }
}
