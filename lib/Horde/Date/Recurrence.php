<?php

declare(strict_types=1);
/**
 * Copyright 2007-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Date
 */

use Horde\Date\Recurrence\DayMask;
use Horde\Date\Recurrence\Recurrence;
use Horde\Date\Recurrence\RecurrenceType;

/**
 * Thin wrapper around Horde\Date\Recurrence\Recurrence that preserves the
 * legacy public API (property access, Horde_Date return types, Horde_Icalendar
 * parameters) while delegating recurrence logic to the modern implementation.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2007-2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Date
 *
 * @property Horde_Date $start
 * @property Horde_Date|null $recurEnd
 * @property int|null $recurCount
 * @property int $recurType
 * @property int $recurInterval
 * @property int|null $recurData
 * @property array $exceptions
 * @property array $completions
 */
class Horde_Date_Recurrence
{
    /** No Recurrence */
    public const RECUR_NONE = 0;

    /** Recurs daily. */
    public const RECUR_DAILY = 1;

    /** Recurs weekly. */
    public const RECUR_WEEKLY = 2;

    /** Recurs monthly on the same date. */
    public const RECUR_MONTHLY_DATE = 3;

    /** Recurs monthly on the same week day. */
    public const RECUR_MONTHLY_WEEKDAY = 4;

    /**
     * Recurs monthly on the same last week day.
     *
     * @since Horde_Date 2.1.0
     */
    public const RECUR_MONTHLY_LAST_WEEKDAY = 8;

    /** Recurs yearly on the same date. */
    public const RECUR_YEARLY_DATE = 5;

    /** Recurs yearly on the same day of the year. */
    public const RECUR_YEARLY_DAY = 6;

    /** Recurs yearly on the same week day. */
    public const RECUR_YEARLY_WEEKDAY = 7;

    private Recurrence $modern;

    public function __construct($start)
    {
        $hdate = new Horde_Date($start);
        $this->modern = new Recurrence($hdate->toDateTime());
    }

    public function __get($name)
    {
        return match ($name) {
            'start' => $this->toLegacy($this->modern->getStart()),
            'recurEnd' => $this->modern->getEnd() !== null
                ? $this->toLegacy($this->modern->getEnd())
                : null,
            'recurCount' => $this->modern->getCount(),
            'recurType' => $this->modern->getType()->value,
            'recurInterval' => $this->modern->getInterval(),
            'recurData' => $this->modern->getDayMask() !== 0
                ? $this->modern->getDayMask()
                : null,
            'exceptions' => $this->modern->getExceptions(),
            'completions' => $this->modern->getCompletions(),
            default => null,
        };
    }

    public function __set($name, $value)
    {
        match ($name) {
            'start' => $this->modern->setStart(
                (new Horde_Date($value))->toDateTime()
            ),
            'recurEnd' => $this->modern->setEnd(
                $value !== null
                    ? (new Horde_Date($value))->toDateTime()
                    : null
            ),
            'recurCount' => $this->modern->setCount(
                $value !== null ? (int) $value : null
            ),
            'recurType' => (function () use ($value) {
                try {
                    $this->modern->setType(RecurrenceType::from((int) $value));
                } catch (ValueError) {
                    $this->modern->setType(RecurrenceType::None);
                }
            })(),
            'recurInterval' => $this->modern->setInterval((int) $value),
            'recurData' => $this->modern->setDayMask((int) ($value ?? 0)),
            'exceptions' => $this->modern->setExceptions((array) $value),
            'completions' => $this->modern->setCompletions((array) $value),
            default => null,
        };
    }

    public function __isset($name)
    {
        return match ($name) {
            'start' => true,
            'recurEnd' => $this->modern->getEnd() !== null,
            'recurCount' => $this->modern->getCount() !== null,
            'recurType' => true,
            'recurInterval' => true,
            'recurData' => $this->modern->getDayMask() !== 0,
            'exceptions' => true,
            'completions' => true,
            default => false,
        };
    }

    /**
     * Creates an instance of this class from a hash.
     *
     * @since Horde_Date 2.4.0
     * @see toHash()
     */
    public static function fromHash($hash)
    {
        $start = explode('/', $hash['start'], 2);
        if (!isset($start[1])) {
            $start[1] = null;
        }
        $recurrence = new self(new Horde_Date($start[0], $start[1]));
        if (!empty($hash['end'])) {
            $end = explode('/', $hash['end'], 2);
            if (!isset($end[1])) {
                $end[1] = null;
            }
            $recurrence->recurEnd = new Horde_Date($end[0], $end[1]);
        }
        $recurrence->recurCount = $hash['count'];
        $recurrence->recurType = $hash['type'];
        $recurrence->recurInterval = $hash['interval'];
        $recurrence->recurData = $hash['data'];
        $recurrence->exceptions = $hash['exceptions'];
        $recurrence->completions = $hash['completions'];
        return $recurrence;
    }

    public function reset()
    {
        $this->modern->reset();
    }

    public function recurOnDay($dayMask)
    {
        return ($this->modern->getDayMask() & (int) $dayMask);
    }

    public function setRecurOnDay($dayMask)
    {
        $this->modern->setDayMask((int) $dayMask);
    }

    public function getRecurOnDays()
    {
        $mask = $this->modern->getDayMask();
        return $mask !== 0 ? $mask : null;
    }

    public function hasRecurType($recurrence)
    {
        return ($recurrence == $this->modern->getType()->value);
    }

    public function setRecurType($recurrence)
    {
        try {
            $this->modern->setType(RecurrenceType::from((int) $recurrence));
        } catch (ValueError) {
            $this->modern->setType(RecurrenceType::None);
        }
    }

    public function getRecurType()
    {
        return $this->modern->getType()->value;
    }

    public function getRecurName()
    {
        switch ($this->getRecurType()) {
            case self::RECUR_NONE:
                return Horde_Date_Translation::t("No recurrence");
            case self::RECUR_DAILY:
                return Horde_Date_Translation::t("Daily");
            case self::RECUR_WEEKLY:
                return Horde_Date_Translation::t("Weekly");
            case self::RECUR_MONTHLY_DATE:
            case self::RECUR_MONTHLY_WEEKDAY:
            case self::RECUR_MONTHLY_LAST_WEEKDAY:
                return Horde_Date_Translation::t("Monthly");
            case self::RECUR_YEARLY_DATE:
            case self::RECUR_YEARLY_DAY:
            case self::RECUR_YEARLY_WEEKDAY:
                return Horde_Date_Translation::t("Yearly");
        }
    }

    public function setRecurInterval($interval)
    {
        if ($interval > 0) {
            $this->modern->setInterval((int) $interval);
        }
    }

    public function getRecurInterval()
    {
        return $this->modern->getInterval();
    }

    public function setRecurCount($count)
    {
        if ($count > 0) {
            $this->modern->setCount((int) $count);
        } else {
            $this->modern->setCount(null);
        }
    }

    public function getRecurCount()
    {
        return $this->modern->getCount();
    }

    public function hasRecurCount()
    {
        return $this->modern->getCount() !== null;
    }

    public function setRecurStart($start)
    {
        $hdate = new Horde_Date($start);
        $this->modern->setStart($hdate->toDateTime());
    }

    public function getRecurStart()
    {
        return $this->toLegacy($this->modern->getStart());
    }

    public function setRecurEnd($end)
    {
        if (!empty($end)) {
            $hdate = new Horde_Date($end);
            $this->modern->setEnd($hdate->toDateTime());
        } else {
            $this->modern->setEnd(null);
        }
    }

    public function getRecurEnd()
    {
        $end = $this->modern->getEnd();
        return $end !== null ? $this->toLegacy($end) : null;
    }

    public function hasRecurEnd()
    {
        $end = $this->modern->getEnd();
        if ($end === null) {
            return false;
        }
        $hdate = $this->toLegacy($end);
        return isset($hdate->year) && $hdate->year != 9999;
    }

    public function nextRecurrence($after)
    {
        if (!($after instanceof Horde_Date)) {
            $after = new Horde_Date($after);
        }
        $result = $this->modern->nextRecurrence($after->toDateTime());
        if ($result === null) {
            return false;
        }
        return $this->toLegacy($result);
    }

    public function nextActiveRecurrence($afterDate)
    {
        $next = $this->nextRecurrence($afterDate);
        while (is_object($next)) {
            if (!$this->hasException($next->year, $next->month, $next->mday)
                && !$this->hasCompletion($next->year, $next->month, $next->mday)) {
                return $next;
            }
            $next->mday++;
            $next = $this->nextRecurrence($next);
        }

        return false;
    }

    public function hasActiveRecurrence()
    {
        return $this->modern->hasActiveRecurrence();
    }

    public function addException($year, $month, $mday)
    {
        $this->modern->addException(
            new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $mday))
        );
    }

    public function deleteException($year, $month, $mday)
    {
        $this->modern->deleteException(
            new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $mday))
        );
    }

    public function hasException($year, $month, $mday)
    {
        return in_array(
            sprintf('%04d%02d%02d', $year, $month, $mday),
            $this->modern->getExceptions(),
            true
        );
    }

    public function getExceptions()
    {
        return $this->modern->getExceptions();
    }

    public function addCompletion($year, $month, $mday)
    {
        $this->modern->addCompletion(
            new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $mday))
        );
    }

    public function deleteCompletion($year, $month, $mday)
    {
        $this->modern->deleteCompletion(
            new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $mday))
        );
    }

    public function hasCompletion($year, $month, $mday)
    {
        return in_array(
            sprintf('%04d%02d%02d', $year, $month, $mday),
            $this->modern->getCompletions(),
            true
        );
    }

    public function getCompletions()
    {
        return $this->modern->getCompletions();
    }

    public function fromRRule10($rrule)
    {
        $this->modern->fromRRule10((string) $rrule);
    }

    public function fromRRule20($rrule)
    {
        $this->modern->fromRRule20((string) $rrule);
    }

    /**
     * Creates a vCalendar 1.0 recurrence rule.
     *
     * @param Horde_Icalendar $calendar  A Horde_Icalendar object instance.
     * @return string  A vCalendar 1.0 conform RRULE value.
     */
    public function toRRule10($calendar)
    {
        $start = $this->toLegacy($this->modern->getStart());

        switch ($this->modern->getType()->value) {
            case self::RECUR_NONE:
                return '';

            case self::RECUR_DAILY:
                $rrule = 'D' . $this->modern->getInterval();
                break;

            case self::RECUR_WEEKLY:
                $rrule = 'W' . $this->modern->getInterval();
                $vcaldays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
                for ($i = 0; $i <= 7; ++$i) {
                    if ($this->recurOnDay(pow(2, $i))) {
                        $rrule .= ' ' . $vcaldays[$i];
                    }
                }
                break;

            case self::RECUR_MONTHLY_DATE:
                $rrule = 'MD' . $this->modern->getInterval() . ' ' . trim((string) $start->mday);
                break;

            case self::RECUR_MONTHLY_WEEKDAY:
            case self::RECUR_MONTHLY_LAST_WEEKDAY:
                if ($this->modern->getType()->value == self::RECUR_MONTHLY_LAST_WEEKDAY) {
                    $nth_weekday = '1-';
                } else {
                    $nth_weekday = (int) ($start->mday / 7);
                    if (($start->mday % 7) > 0) {
                        $nth_weekday++;
                    }
                    $nth_weekday .= '+';
                }
                $vcaldays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
                $rrule = 'MP' . $this->modern->getInterval() . ' ' . $nth_weekday . ' ' . $vcaldays[$start->dayOfWeek()];
                break;

            case self::RECUR_YEARLY_DATE:
                $rrule = 'YM' . $this->modern->getInterval() . ' ' . trim((string) $start->month);
                break;

            case self::RECUR_YEARLY_DAY:
                $rrule = 'YD' . $this->modern->getInterval() . ' ' . $start->dayOfYear();
                break;

            default:
                return '';
        }

        if ($this->hasRecurEnd()) {
            $recurEnd = clone $this->getRecurEnd();
            return $rrule . ' ' . $calendar->_exportDateTime($recurEnd);
        }

        return $rrule . ' #' . (int) $this->getRecurCount();
    }

    /**
     * Creates an iCalendar 2.0 recurrence rule.
     *
     * @param Horde_Icalendar $calendar  A Horde_Icalendar object instance.
     * @return string  An iCalendar 2.0 conform RRULE value.
     */
    public function toRRule20($calendar)
    {
        $start = $this->toLegacy($this->modern->getStart());

        switch ($this->modern->getType()->value) {
            case self::RECUR_NONE:
                return '';

            case self::RECUR_DAILY:
                $rrule = 'FREQ=DAILY;INTERVAL=' . $this->modern->getInterval();
                break;

            case self::RECUR_WEEKLY:
                $rrule = 'FREQ=WEEKLY;INTERVAL=' . $this->modern->getInterval();
                $vcaldays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
                for ($i = $flag = 0; $i <= 7; ++$i) {
                    if ($this->recurOnDay(pow(2, $i))) {
                        if ($flag == 0) {
                            $rrule .= ';BYDAY=';
                            $flag = 1;
                        } else {
                            $rrule .= ',';
                        }
                        $rrule .= $vcaldays[$i];
                    }
                }
                break;

            case self::RECUR_MONTHLY_DATE:
                $rrule = 'FREQ=MONTHLY;INTERVAL=' . $this->modern->getInterval();
                break;

            case self::RECUR_MONTHLY_WEEKDAY:
            case self::RECUR_MONTHLY_LAST_WEEKDAY:
                if ($this->modern->getType()->value == self::RECUR_MONTHLY_LAST_WEEKDAY) {
                    $nth_weekday = -1;
                } else {
                    $nth_weekday = (int) ($start->mday / 7);
                    if (($start->mday % 7) > 0) {
                        $nth_weekday++;
                    }
                }
                $vcaldays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
                $rrule = 'FREQ=MONTHLY;INTERVAL=' . $this->modern->getInterval()
                    . ';BYDAY=' . $nth_weekday . $vcaldays[$start->dayOfWeek()];
                break;

            case self::RECUR_YEARLY_DATE:
                $rrule = 'FREQ=YEARLY;INTERVAL=' . $this->modern->getInterval();
                break;

            case self::RECUR_YEARLY_DAY:
                $rrule = 'FREQ=YEARLY;INTERVAL=' . $this->modern->getInterval()
                    . ';BYYEARDAY=' . $start->dayOfYear();
                break;

            case self::RECUR_YEARLY_WEEKDAY:
                $nth_weekday = (int) ($start->mday / 7);
                if (($start->mday % 7) > 0) {
                    $nth_weekday++;
                }
                $vcaldays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
                $rrule = 'FREQ=YEARLY;INTERVAL=' . $this->modern->getInterval()
                    . ';BYDAY='
                    . $nth_weekday
                    . $vcaldays[$start->dayOfWeek()]
                    . ';BYMONTH=' . $start->month;
                break;
        }

        if ($this->hasRecurEnd()) {
            $recurEnd = clone $this->getRecurEnd();
            $rrule .= ';UNTIL=' . $calendar->_exportDateTime($recurEnd);
        }
        if ($count = $this->getRecurCount()) {
            $rrule .= ';COUNT=' . $count;
        }
        return $rrule;
    }

    /**
     * Parses the recurrence data from a Kolab hash.
     */
    public function fromKolab($hash)
    {
        $this->reset();

        if (!isset($hash['interval']) || !isset($hash['cycle'])) {
            $this->setRecurType(self::RECUR_NONE);
            return false;
        }

        $this->setRecurInterval((int) $hash['interval']);

        $parse_day = false;
        $set_daymask = false;
        $update_month = false;
        $update_daynumber = false;
        $update_weekday = false;
        $nth_weekday = -1;

        switch ($hash['cycle']) {
            case 'daily':
                $this->setRecurType(self::RECUR_DAILY);
                break;

            case 'weekly':
                $this->setRecurType(self::RECUR_WEEKLY);
                $parse_day = true;
                $set_daymask = true;
                break;

            case 'monthly':
                if (!isset($hash['daynumber'])) {
                    $this->setRecurType(self::RECUR_NONE);
                    return false;
                }

                switch ($hash['type']) {
                    case 'daynumber':
                        $this->setRecurType(self::RECUR_MONTHLY_DATE);
                        $update_daynumber = true;
                        break;

                    case 'weekday':
                        $this->setRecurType(self::RECUR_MONTHLY_WEEKDAY);
                        $nth_weekday = (int) $hash['daynumber'];
                        if ($nth_weekday < 0) {
                            $this->setRecurType(self::RECUR_MONTHLY_LAST_WEEKDAY);
                        }
                        $hash['daynumber'] = 1;
                        $parse_day = true;
                        $update_daynumber = true;
                        $update_weekday = true;
                        break;
                }
                break;

            case 'yearly':
                if (!isset($hash['type'])) {
                    $this->setRecurType(self::RECUR_NONE);
                    return false;
                }

                switch ($hash['type']) {
                    case 'monthday':
                        $this->setRecurType(self::RECUR_YEARLY_DATE);
                        $update_month = true;
                        $update_daynumber = true;
                        break;

                    case 'yearday':
                        if (!isset($hash['daynumber'])) {
                            $this->setRecurType(self::RECUR_NONE);
                            return false;
                        }

                        $this->setRecurType(self::RECUR_YEARLY_DAY);
                        $hash['month'] = 'january';
                        $update_month = true;
                        $update_daynumber = true;
                        break;

                    case 'weekday':
                        if (!isset($hash['daynumber'])) {
                            $this->setRecurType(self::RECUR_NONE);
                            return false;
                        }

                        $this->setRecurType(self::RECUR_YEARLY_WEEKDAY);
                        $nth_weekday = (int) $hash['daynumber'];
                        $hash['daynumber'] = 1;
                        $parse_day = true;
                        $update_month = true;
                        $update_daynumber = true;
                        $update_weekday = true;
                        break;
                }
        }

        if (isset($hash['range-type']) && isset($hash['range'])) {
            switch ($hash['range-type']) {
                case 'number':
                    $this->setRecurCount((int) $hash['range']);
                    break;

                case 'date':
                    $recur_end = new Horde_Date($hash['range']);
                    $recur_end->hour = 23;
                    $recur_end->min = 59;
                    $recur_end->sec = 59;
                    $this->setRecurEnd($recur_end);
                    break;
            }
        }

        $last_found_day = -1;
        if ($parse_day) {
            if (!isset($hash['day'])) {
                $this->setRecurType(self::RECUR_NONE);
                return false;
            }

            $mask = 0;
            $bits = [
                'monday' => Horde_Date::MASK_MONDAY,
                'tuesday' => Horde_Date::MASK_TUESDAY,
                'wednesday' => Horde_Date::MASK_WEDNESDAY,
                'thursday' => Horde_Date::MASK_THURSDAY,
                'friday' => Horde_Date::MASK_FRIDAY,
                'saturday' => Horde_Date::MASK_SATURDAY,
                'sunday' => Horde_Date::MASK_SUNDAY,
            ];
            $days = [
                'monday' => Horde_Date::DATE_MONDAY,
                'tuesday' => Horde_Date::DATE_TUESDAY,
                'wednesday' => Horde_Date::DATE_WEDNESDAY,
                'thursday' => Horde_Date::DATE_THURSDAY,
                'friday' => Horde_Date::DATE_FRIDAY,
                'saturday' => Horde_Date::DATE_SATURDAY,
                'sunday' => Horde_Date::DATE_SUNDAY,
            ];

            foreach ($hash['day'] as $day) {
                if (empty($day) || !isset($bits[$day])) {
                    continue;
                }

                $mask |= $bits[$day];
                $last_found_day = $days[$day];
            }

            if ($set_daymask) {
                $this->setRecurOnDay($mask);
            }
        }

        if ($update_month || $update_daynumber || $update_weekday) {
            $start = $this->getRecurStart();

            if ($update_month) {
                $month2number = [
                    'january'   => 1,
                    'february'  => 2,
                    'march'     => 3,
                    'april'     => 4,
                    'may'       => 5,
                    'june'      => 6,
                    'july'      => 7,
                    'august'    => 8,
                    'september' => 9,
                    'october'   => 10,
                    'november'  => 11,
                    'december'  => 12,
                ];

                if (isset($month2number[$hash['month']])) {
                    $start->month = $month2number[$hash['month']];
                }
            }

            if ($update_daynumber) {
                if (!isset($hash['daynumber'])) {
                    $this->setRecurType(self::RECUR_NONE);
                    return false;
                }

                $start->mday = $hash['daynumber'];
            }

            if ($update_weekday) {
                $start->setNthWeekday($last_found_day, $nth_weekday);
            }

            $this->setRecurStart($start);
        }

        if (isset($hash['exclusion'])) {
            foreach ($hash['exclusion'] as $exception) {
                if ($exception instanceof DateTime) {
                    $this->modern->addException(
                        DateTimeImmutable::createFromMutable($exception)
                    );
                }
            }
        }

        if (isset($hash['complete'])) {
            foreach ($hash['complete'] as $completion) {
                if ($exception instanceof DateTime) {
                    $this->modern->addCompletion(
                        DateTimeImmutable::createFromMutable($completion)
                    );
                }
            }
        }

        return true;
    }

    /**
     * Export this object into a Kolab hash.
     */
    public function toKolab()
    {
        if ($this->getRecurType() == self::RECUR_NONE) {
            return [];
        }

        $day2number = [
            0 => 'sunday',
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
        ];
        $month2number = [
            1 => 'january',
            2 => 'february',
            3 => 'march',
            4 => 'april',
            5 => 'may',
            6 => 'june',
            7 => 'july',
            8 => 'august',
            9 => 'september',
            10 => 'october',
            11 => 'november',
            12 => 'december',
        ];

        $hash = ['interval' => $this->getRecurInterval()];
        $start = $this->getRecurStart();

        switch ($this->getRecurType()) {
            case self::RECUR_DAILY:
                $hash['cycle'] = 'daily';
                break;

            case self::RECUR_WEEKLY:
                $hash['cycle'] = 'weekly';
                $bits = [
                    'monday' => Horde_Date::MASK_MONDAY,
                    'tuesday' => Horde_Date::MASK_TUESDAY,
                    'wednesday' => Horde_Date::MASK_WEDNESDAY,
                    'thursday' => Horde_Date::MASK_THURSDAY,
                    'friday' => Horde_Date::MASK_FRIDAY,
                    'saturday' => Horde_Date::MASK_SATURDAY,
                    'sunday' => Horde_Date::MASK_SUNDAY,
                ];
                $days = [];
                foreach ($bits as $name => $bit) {
                    if ($this->recurOnDay($bit)) {
                        $days[] = $name;
                    }
                }
                $hash['day'] = $days;
                break;

            case self::RECUR_MONTHLY_DATE:
                $hash['cycle'] = 'monthly';
                $hash['type'] = 'daynumber';
                $hash['daynumber'] = $start->mday;
                break;

            case self::RECUR_MONTHLY_WEEKDAY:
            case self::RECUR_MONTHLY_LAST_WEEKDAY:
                $hash['cycle'] = 'monthly';
                $hash['type'] = 'weekday';
                if ($this->getRecurType() == self::RECUR_MONTHLY_LAST_WEEKDAY) {
                    $hash['daynumber'] = '-1';
                } else {
                    $hash['daynumber'] = $start->weekOfMonth();
                }
                $hash['day'] = [$day2number[$start->dayOfWeek()]];
                break;

            case self::RECUR_YEARLY_DATE:
                $hash['cycle'] = 'yearly';
                $hash['type'] = 'monthday';
                $hash['daynumber'] = $start->mday;
                $hash['month'] = $month2number[$start->month];
                break;

            case self::RECUR_YEARLY_DAY:
                $hash['cycle'] = 'yearly';
                $hash['type'] = 'yearday';
                $hash['daynumber'] = $start->dayOfYear();
                break;

            case self::RECUR_YEARLY_WEEKDAY:
                $hash['cycle'] = 'yearly';
                $hash['type'] = 'weekday';
                $hash['daynumber'] = $start->weekOfMonth();
                $hash['day'] =  [$day2number[$start->dayOfWeek()]];
                $hash['month'] = $month2number[$start->month];
        }

        if ($this->hasRecurCount()) {
            $hash['range-type'] = 'number';
            $hash['range'] = $this->getRecurCount();
        } elseif ($this->hasRecurEnd()) {
            $date = $this->getRecurEnd();
            $hash['range-type'] = 'date';
            $hash['range'] = $date->toDateTime();
        } else {
            $hash['range-type'] = 'none';
            $hash['range'] = '';
        }

        $hash['exclusion'] = $hash['complete'] = [];
        foreach ($this->modern->getExceptions() as $exception) {
            $hash['exclusion'][] = new DateTime($exception);
        }
        foreach ($this->modern->getCompletions() as $completionexception) {
            $hash['complete'][] = new DateTime($completionexception);
        }

        return $hash;
    }

    public function toHash()
    {
        $start = $this->getRecurStart();
        $recurEnd = $this->getRecurEnd();
        return [
            'start' => $start->format(Horde_Date::DATE_DEFAULT . '/e'),
            'end' => $recurEnd
                ? $recurEnd->format(Horde_Date::DATE_DEFAULT . '/e')
                : null,
            'count' => $this->modern->getCount(),
            'type' => $this->modern->getType()->value,
            'interval' => $this->modern->getInterval(),
            'data' => $this->modern->getDayMask() !== 0
                ? $this->modern->getDayMask()
                : null,
            'exceptions' => $this->modern->getExceptions(),
            'completions' => $this->modern->getCompletions(),
        ];
    }

    public function toJson()
    {
        $json = new stdClass();
        $json->t = $this->modern->getType()->value;
        $json->i = $this->modern->getInterval();
        if ($this->hasRecurEnd()) {
            $recurEnd = $this->getRecurEnd();
            $json->e = $recurEnd->toJson();
        }
        if ($this->modern->getCount()) {
            $json->c = $this->modern->getCount();
        }
        if ($this->modern->getDayMask()) {
            $json->d = $this->modern->getDayMask();
        }
        if ($this->modern->getCompletions()) {
            $json->co = $this->modern->getCompletions();
        }
        if ($this->modern->getExceptions()) {
            $json->ex = $this->modern->getExceptions();
        }
        return $json;
    }

    /**
     * @since 2.1.0
     */
    public function toString($date_format, $time_format = 'medium')
    {
        $string = '';
        if ($this->hasRecurType(self::RECUR_DAILY)) {
            $string = Horde_Date_Translation::t("Daily: Recurs every") . ' ' . $this->getRecurInterval() . ' ' . Horde_Date_Translation::t("day(s)");
        } elseif ($this->hasRecurType(self::RECUR_WEEKLY)) {
            $weekdays = [];
            if ($this->recurOnDay(Horde_Date::MASK_MONDAY)) {
                $weekdays[] = Horde_Date_Translation::t("Monday");
            }
            if ($this->recurOnDay(Horde_Date::MASK_TUESDAY)) {
                $weekdays[] = Horde_Date_Translation::t("Tuesday");
            }
            if ($this->recurOnDay(Horde_Date::MASK_WEDNESDAY)) {
                $weekdays[] = Horde_Date_Translation::t("Wednesday");
            }
            if ($this->recurOnDay(Horde_Date::MASK_THURSDAY)) {
                $weekdays[] = Horde_Date_Translation::t("Thursday");
            }
            if ($this->recurOnDay(Horde_Date::MASK_FRIDAY)) {
                $weekdays[] = Horde_Date_Translation::t("Friday");
            }
            if ($this->recurOnDay(Horde_Date::MASK_SATURDAY)) {
                $weekdays[] = Horde_Date_Translation::t("Saturday");
            }
            if ($this->recurOnDay(Horde_Date::MASK_SUNDAY)) {
                $weekdays[] = Horde_Date_Translation::t("Sunday");
            }
            $string = Horde_Date_Translation::t("Weekly: Recurs every") . ' ' . $this->getRecurInterval() . ' ' . Horde_Date_Translation::t("week(s) on:") . ' ' . implode(', ', $weekdays);
        } elseif ($this->hasRecurType(self::RECUR_MONTHLY_DATE)) {
            $string = Horde_Date_Translation::t("Monthly: Recurs every") . ' ' . $this->getRecurInterval() . ' ' . Horde_Date_Translation::t("month(s)") . ' ' . Horde_Date_Translation::t("on the same date");
        } elseif ($this->hasRecurType(self::RECUR_MONTHLY_WEEKDAY)) {
            $string = Horde_Date_Translation::t("Monthly: Recurs every") . ' ' . $this->getRecurInterval() . ' ' . Horde_Date_Translation::t("month(s)") . ' ' . Horde_Date_Translation::t("on the same weekday");
        } elseif ($this->hasRecurType(self::RECUR_MONTHLY_LAST_WEEKDAY)) {
            $string = Horde_Date_Translation::t("Monthly: Recurs every") . ' ' . $this->getRecurInterval() . ' ' . Horde_Date_Translation::t("month(s)") . ' ' . Horde_Date_Translation::t("on the same last weekday");
        } elseif ($this->hasRecurType(self::RECUR_YEARLY_DATE)) {
            $string =  Horde_Date_Translation::t("Yearly: Recurs every") . ' ' . $this->getRecurInterval() . ' ' . Horde_Date_Translation::t("year(s) on the same date");
        } elseif ($this->hasRecurType(self::RECUR_YEARLY_DAY)) {
            $string = Horde_Date_Translation::t("Yearly: Recurs every") . ' ' . $this->getRecurInterval() . ' ' . Horde_Date_Translation::t("year(s) on the same day of the year");
        } elseif ($this->hasRecurType(self::RECUR_YEARLY_WEEKDAY)) {
            $string = Horde_Date_Translation::t("Yearly: Recurs every") . ' ' . $this->getRecurInterval() . ' ' . Horde_Date_Translation::t("year(s) on the same weekday and month of the year");
        }

        $recurEnd = $this->getRecurEnd();
        $string .= "\n" . Horde_Date_Translation::t("Ends after") . ': '
            . ($this->hasRecurEnd()
               ? Horde\Date\Format::formatDate($recurEnd->timestamp(), $date_format)
                   . ($recurEnd->hour == 23 && $recurEnd->min == 59
                      ? ''
                      : ' ' . Horde\Date\Format::formatDate($recurEnd->timestamp(), $time_format))
               : ($this->getRecurCount()
                  ? sprintf(Horde_Date_Translation::t("%d times"), $this->getRecurCount())
                  : Horde_Date_Translation::t("No end date")));
        if ($this->getExceptions()) {
            $string .= "\n" . Horde_Date_Translation::t("Exceptions on") . ': ';
            foreach ($this->getExceptions() as $exception_date) {
                $string .= $this->_formatExceptionDate($exception_date, $date_format) . ' ';
            }
        }

        return $string;
    }

    public function isEqual(Horde_Date_Recurrence $recurrence)
    {
        return ($this->getRecurType() == $recurrence->getRecurType()
            && $this->getRecurInterval() == $recurrence->getRecurInterval()
            && $this->getRecurCount() == $recurrence->getRecurCount()
            && $this->getRecurEnd() == $recurrence->getRecurEnd()
            && $this->getRecurStart() == $recurrence->getRecurStart()
            && $this->getRecurOnDays() == $recurrence->getRecurOnDays()
        );
    }

    /**
     * @since 2.1.0
     */
    protected function _formatExceptionDate($date, $format)
    {
        if (!preg_match('/(\d{4})(\d{2})(\d{2})/', $date, $match)) {
            return '';
        }
        $horde_date = new Horde_Date(['year' => $match[1],
            'month' => $match[2],
            'mday' => $match[3]]);
        return Horde\Date\Format::formatDate($horde_date->timestamp(), $format);
    }

    /**
     * Provides access to the modern Recurrence instance for advanced use.
     */
    public function getModern(): Recurrence
    {
        return $this->modern;
    }

    private function toLegacy(DateTimeImmutable $dt): Horde_Date
    {
        return new Horde_Date($dt->format('Y-m-d H:i:s'), $dt->getTimezone()->getName());
    }
}
