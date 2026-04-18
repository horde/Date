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
    public function getType(): RecurrenceType;

    public function getInterval(): int;

    public function getStart(): DateTimeImmutable;

    public function getEnd(): ?DateTimeImmutable;

    public function getCount(): ?int;

    public function getDayMask(): int;

    public function nextRecurrence(DateTimeInterface $after): ?DateTimeImmutable;

    public function nextActiveRecurrence(DateTimeInterface $after): ?DateTimeImmutable;

    public function hasActiveRecurrence(): bool;

    public function addException(DateTimeInterface $date): void;

    public function deleteException(DateTimeInterface $date): void;

    public function hasException(DateTimeInterface $date): bool;

    /** @return list<string> YYYYMMDD strings */
    public function getExceptions(): array;

    public function addCompletion(DateTimeInterface $date): void;

    public function deleteCompletion(DateTimeInterface $date): void;

    public function hasCompletion(DateTimeInterface $date): bool;

    /** @return list<string> YYYYMMDD strings */
    public function getCompletions(): array;

    public function toRRule20(): string;

    public function toRRule10(): string;
}
