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
    /**
     * Create a new TimezoneInfo instance.
     */
    public function __construct(
        private readonly string $ianaName,
        private readonly string $originalAlias = '',
    ) {}

    /**
     * Return the canonical IANA timezone name.
     */
    public function getIanaName(): string
    {
        return $this->ianaName;
    }

    /**
     * Return the original alias name used to resolve this timezone.
     */
    public function getOriginalAlias(): string
    {
        return $this->originalAlias;
    }

    /**
     * Whether this timezone was resolved from a non-canonical alias.
     */
    public function isAlias(): bool
    {
        return $this->originalAlias !== '' && $this->originalAlias !== $this->ianaName;
    }

    /**
     * Convert to a native DateTimeZone instance.
     */
    public function toDateTimeZone(): DateTimeZone
    {
        return new DateTimeZone($this->ianaName);
    }

    /**
     * Return the IANA timezone name as a string.
     */
    public function __toString(): string
    {
        return $this->ianaName;
    }
}
