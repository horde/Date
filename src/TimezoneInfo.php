<?php

declare(strict_types=1);

/**
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 */

namespace Horde\Date;

use DateTimeZone;
use Stringable;

final class TimezoneInfo implements Stringable
{
    public function __construct(
        private readonly string $ianaName,
        private readonly string $originalAlias = '',
    ) {}

    public function getIanaName(): string
    {
        return $this->ianaName;
    }

    public function getOriginalAlias(): string
    {
        return $this->originalAlias;
    }

    public function isAlias(): bool
    {
        return $this->originalAlias !== '' && $this->originalAlias !== $this->ianaName;
    }

    public function toDateTimeZone(): DateTimeZone
    {
        return new DateTimeZone($this->ianaName);
    }

    public function __toString(): string
    {
        return $this->ianaName;
    }
}
