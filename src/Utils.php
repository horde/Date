<?php

declare(strict_types=1);

/**
 * Copyright 2004-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2004-2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 */

namespace Horde\Date;

use DateTime;
use Exception;

/**
 * Date utility methods
 *
 * Typed, dependency-free replacements for the static helpers formerly
 * on Horde_Date_Utils.
 *
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 * @since     3.1.0
 */
class Utils
{
    /**
     * @param int $year The year to check
     */
    public static function isLeapYear(int $year): bool
    {
        return ($year % 4 === 0 && $year % 100 !== 0) || $year % 400 === 0;
    }

    /**
     * Return the Monday of the given ISO week
     *
     * @param int $week ISO week number (1–53)
     * @param int $year The year
     */
    public static function firstDayOfWeek(int $week, int $year): Date
    {
        return new Date(sprintf('%04dW%02d', $year, $week));
    }

    /**
     * @param int $month Month number (1–12)
     * @param int $year  The year
     *
     * @throws DateException on invalid month/year
     */
    public static function daysInMonth(int $month, int $year): int
    {
        try {
            $date = new DateTime(
                sprintf($year < 0 ? '%05d-%02d-01' : '%04d-%02d-01', $year, $month)
            );
        } catch (Exception $e) {
            throw new DateException($e->getMessage(), (int) $e->getCode(), $e);
        }

        return (int) $date->format('t');
    }

    /**
     * Convert strftime() format string to PHP date() format string
     *
     * Unsupported formatters are removed.
     *
     * @param string        $format              A strftime() formatting string
     * @param callable|null $localeInfoProvider   Optional callback accepting an
     *                                           int constant (T_FMT or D_FMT)
     *                                           and returning a date() format
     *                                           string. When null, English
     *                                           defaults are used for %x/%X.
     */
    public static function strftime2date(
        string $format,
        ?callable $localeInfoProvider = null,
    ): string {
        $replace = [
            '/%a/'  => 'D',
            '/%A/'  => 'l',
            '/%d/'  => 'd',
            '/%e/'  => 'j',
            '/%j/'  => 'z',
            '/%u/'  => 'N',
            '/%w/'  => 'w',
            '/%U/'  => '',
            '/%V/'  => 'W',
            '/%W/'  => '',
            '/%b/'  => 'M',
            '/%B/'  => 'F',
            '/%h/'  => 'M',
            '/%m/'  => 'm',
            '/%C/'  => '',
            '/%g/'  => 'y',
            '/%G/'  => 'o',
            '/%y/'  => 'y',
            '/%Y/'  => 'Y',
            '/%H/'  => 'H',
            '/%I/'  => 'h',
            '/%i/'  => 'g',
            '/%M/'  => 'i',
            '/%p/'  => 'A',
            '/%P/'  => 'a',
            '/%r/'  => 'h:i:s A',
            '/%R/'  => 'H:i',
            '/%S/'  => 's',
            '/%T/'  => 'H:i:s',
            '/%z/'  => 'O',
            '/%Z/'  => '',
            '/%c/'  => '',
            '/%D/'  => 'm/d/y',
            '/%F/'  => 'Y-m-d',
            '/%s/'  => 'U',
            '/%n/'  => "\n",
            '/%t/'  => "\t",
            '/%%/'  => '%',
        ];

        $callbackPatterns = [
            '/%X/' => function () use ($localeInfoProvider): string {
                if ($localeInfoProvider !== null) {
                    $result = $localeInfoProvider(T_FMT);
                    if ($result !== false) {
                        return $result;
                    }
                }
                return 'H:i:s';
            },
            '/%x/' => function () use ($localeInfoProvider): string {
                if ($localeInfoProvider !== null) {
                    $result = $localeInfoProvider(D_FMT);
                    if ($result !== false) {
                        return $result;
                    }
                }
                return 'm/d/Y';
            },
        ];

        $pass1 = preg_replace_callback_array($callbackPatterns, $format);
        return preg_replace(array_keys($replace), array_values($replace), $pass1);
    }
}
