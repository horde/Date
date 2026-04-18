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

use DateTimeInterface;
use Horde_Date;
use RuntimeException;
use Stringable;

/**
 * Date formatter interface
 *
 * Formatters convert timestamps to formatted strings using specific pattern
 * syntaxes (PHP date(), ICU, strftime, etc.) and parse formatted strings
 * back to date objects.
 *
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 */
interface FormatterInterface
{
    /**
     * Format a timestamp
     *
     * @param int|DateTimeInterface|DateInterface|Horde_Date $datetime  Unix timestamp, DateTime/DateTimeImmutable, DateInterface, or Horde_Date
     * @param string $pattern  Format pattern in formatter's syntax
     * @param string|Stringable $locale  Locale for formatting (default: 'en_US')
     * @param string|null $timezone  Timezone identifier (null = UTC)
     *
     * @return string  Formatted date string (no return type for BC)
     */
    public function format(
        int|DateTimeInterface|DateInterface|Horde_Date $datetime,
        string $pattern,
        string|Stringable $locale = 'en_US',
        ?string $timezone = null
    );

    /**
     * Parse a formatted date string back to date object
     *
     * IMPORTANT: Always converts through DateTime object for proper timezone handling
     *
     * @param string $formattedString  Formatted date string
     * @param string $pattern  Format pattern in formatter's syntax
     * @param string|Stringable $locale  Locale for parsing (default: 'en_US')
     * @param string|null $timezone  Timezone identifier (null = UTC)
     *
     * @return DateInterface  Date object
     *
     * @throws RuntimeException if parsing fails
     */
    public function parse(
        string $formattedString,
        string $pattern,
        string|Stringable $locale = 'en_US',
        ?string $timezone = null
    ): DateInterface;
}
