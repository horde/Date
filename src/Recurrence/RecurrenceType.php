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

enum RecurrenceType: int
{
    case None = 0;
    case Daily = 1;
    case Weekly = 2;
    case MonthlyDate = 3;
    case MonthlyWeekday = 4;
    case YearlyDate = 5;
    case YearlyDay = 6;
    case YearlyWeekday = 7;
    case MonthlyLastWeekday = 8;

    public function label(): string
    {
        return match ($this) {
            self::None => 'None',
            self::Daily => 'Daily',
            self::Weekly => 'Weekly',
            self::MonthlyDate => 'Monthly (date)',
            self::MonthlyWeekday => 'Monthly (weekday)',
            self::YearlyDate => 'Yearly (date)',
            self::YearlyDay => 'Yearly (day)',
            self::YearlyWeekday => 'Yearly (weekday)',
            self::MonthlyLastWeekday => 'Monthly (last weekday)',
        };
    }

    public static function fromLegacy(int $type): self
    {
        return self::from($type);
    }
}
