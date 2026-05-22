<?php

declare(strict_types=1);
/**
 * Copyright 2004-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Date
 */

/**
 * Horde Date wrapper/logic class, including some calculation
 * functions.
 *
 * Delegates typed work to Horde\Date\Utils while preserving the
 * untyped legacy API and returning Horde_Date where callers expect it.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2004-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Date
 */
class Horde_Date_Utils
{
    /**
     * Returns whether a year is a leap year.
     *
     * @param integer $year  The year.
     *
     * @return boolean  True if the year is a leap year.
     */
    public static function isLeapYear($year)
    {
        return Horde\Date\Utils::isLeapYear((int) $year);
    }

    /**
     * Returns the date of the year that corresponds to the first day of the
     * given week.
     *
     * @param integer $week  The week of the year to find the first day of.
     * @param integer $year  The year to calculate for.
     *
     * @return Horde_Date  The date of the first day of the given week.
     */
    public static function firstDayOfWeek($week, $year)
    {
        $modern = Horde\Date\Utils::firstDayOfWeek((int) $week, (int) $year);
        return new Horde_Date(
            $modern->format('Y-m-d H:i:s'),
            $modern->getTimezone()->getName()
        );
    }

    /**
     * Returns the number of days in the specified month.
     *
     * @param integer $month  The month
     * @param integer $year   The year.
     *
     * @return integer  The number of days in the month.
     */
    public static function daysInMonth($month, $year)
    {
        static $cache = [];
        if (!isset($cache[$year][$month])) {
            try {
                $cache[$year][$month] = Horde\Date\Utils::daysInMonth((int) $month, (int) $year);
            } catch (Horde\Date\DateException $e) {
                throw new Horde_Date_Exception($e);
            }
        }
        return $cache[$year][$month];
    }

    /**
     * Returns a relative, natural language representation of a timestamp
     *
     * @todo Wider range of values ... maybe future time as well?
     * @todo Support minimum resolution parameter.
     *
     * @param mixed $time          The time. Any format accepted by Horde_Date.
     * @param string $date_format  Format to display date if timestamp is
     *                             more then 1 day old.
     * @param string $time_format  Format to display time if timestamp is 1
     *                             day old.
     *
     * @return string  The relative time (i.e. 2 minutes ago)
     */
    public static function relativeDateTime(
        $time,
        $date_format = 'short',
        $time_format = 'medium'
    ) {
        $date = new Horde_Date($time);

        $delta = time() - $date->timestamp();
        if ($delta < 60) {
            return sprintf(Horde_Date_Translation::ngettext("%d second ago", "%d seconds ago", $delta), $delta);
        }

        $delta = round($delta / 60);
        if ($delta < 60) {
            return sprintf(Horde_Date_Translation::ngettext("%d minute ago", "%d minutes ago", $delta), $delta);
        }

        $delta = round($delta / 60);
        if ($delta < 24) {
            return sprintf(Horde_Date_Translation::ngettext("%d hour ago", "%d hours ago", $delta), $delta);
        }

        if ($delta > 24 && $delta < 48) {
            $date = new Horde_Date($time);
            return sprintf(Horde_Date_Translation::t("yesterday at %s"), Horde\Date\Format::formatDate($date->timestamp(), $time_format));
        }

        $delta = round($delta / 24);
        if ($delta < 7) {
            return sprintf(Horde_Date_Translation::t("%d days ago"), $delta);
        }

        if (round($delta / 7) < 5) {
            $delta = round($delta / 7);
            return sprintf(Horde_Date_Translation::ngettext("%d week ago", "%d weeks ago", $delta), $delta);
        }

        // Default to the user specified date format.
        return Horde\Date\Format::formatDate($date->timestamp(), $date_format);
    }

    /**
     * Tries to convert strftime() formatters to date() formatters.
     *
     * Unsupported formatters will be removed.
     *
     * @param string $format  A strftime() formatting string.
     *
     * @return string  A date() formatting string.
     */
    public static function strftime2date($format)
    {
        $provider = null;
        if (class_exists(Horde\Nls\Nls::class)) {
            $nls = new Horde\Nls\Nls();
            $provider = function (int $constant) use ($nls): string|false {
                return $nls->getLangInfo($constant);
            };
        }
        return Horde\Date\Utils::strftime2date((string) $format, $provider);
    }

    /**
     * Unify date formatters and then format the date.
     *
     * Facilitates upgrades from strftime to date_format style placeholders by accepting both.
     * Some formats are not supported and will be dropped
     * Will produce undesirable results for date_format style format strings that contain % characters
     *
     * @param string $pattern A date/time format pattern either in strftime or date_format style
     * @param string|Horde_Date|DateTimeInterface $date
     * @return void
     */
    public static function legacyDateFormatter(
        string $pattern = 'Y-m-d H:i:s',
        Horde_Date|DateTimeInterface|int|string|null $date = 'now',
        $timezone = null
    ) {
        if (is_null($date) || $date === 'now') {
            $date = new Horde_Date(time(), $timezone);
        } elseif (is_object($date) && $date instanceof DateTimeInterface) {
            $timezone ??= $date->getTimezone()->getName();
            $date = new Horde_Date($date, $timezone);
        } elseif (is_int($date) || is_string($date)) {
            $date = new Horde_Date($date, $timezone ?? 'UTC');
        }
        return $date->format(self::strftime2date($pattern));
    }
}
