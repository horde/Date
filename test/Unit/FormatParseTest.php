<?php

declare(strict_types=1);

/**
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Date
 */

namespace Horde\Date\Test\Unit;

use Horde\Date\DateInterface;
use Horde\Date\Format;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for Format::parse() and Format::parseDateTime()
 */
#[CoversClass(Format::class)]
class FormatParseTest extends TestCase
{
    public function testParseIcuPattern(): void
    {
        $result = Format::parse('22.05.2026', 'dd.MM.yyyy', 'de_DE');

        $this->assertInstanceOf(DateInterface::class, $result);
        $dt = $result->toDateTimeImmutable();
        $this->assertSame('2026', $dt->format('Y'));
        $this->assertSame('05', $dt->format('m'));
        $this->assertSame('22', $dt->format('d'));
    }

    public function testParseStrftimePattern(): void
    {
        $result = Format::parse('22.05.2026', '%d.%m.%Y', 'de_DE');

        $this->assertInstanceOf(DateInterface::class, $result);
        $dt = $result->toDateTimeImmutable();
        $this->assertSame('2026', $dt->format('Y'));
        $this->assertSame('05', $dt->format('m'));
        $this->assertSame('22', $dt->format('d'));
    }

    public function testParseWithTime24h(): void
    {
        $result = Format::parse('22.05.2026 14:30', 'dd.MM.yyyy HH:mm', 'de_DE');

        $dt = $result->toDateTimeImmutable();
        $this->assertSame('2026', $dt->format('Y'));
        $this->assertSame('05', $dt->format('m'));
        $this->assertSame('22', $dt->format('d'));
        $this->assertSame('14', $dt->format('H'));
        $this->assertSame('30', $dt->format('i'));
    }

    public function testParseWithTime12hAmPm(): void
    {
        $result = Format::parse('05/22/2026 2:30 PM', 'MM/dd/yyyy h:mm a', 'en_US');

        $dt = $result->toDateTimeImmutable();
        $this->assertSame('2026', $dt->format('Y'));
        $this->assertSame('05', $dt->format('m'));
        $this->assertSame('22', $dt->format('d'));
        $this->assertSame('14', $dt->format('H'));
        $this->assertSame('30', $dt->format('i'));
    }

    public function testParseDateTimeWithSeparatePatterns(): void
    {
        $result = Format::parseDateTime('22.05.2026 14:30', 'dd.MM.yyyy', 'HH:mm', 'de_DE');

        $dt = $result->toDateTimeImmutable();
        $this->assertSame('2026', $dt->format('Y'));
        $this->assertSame('05', $dt->format('m'));
        $this->assertSame('22', $dt->format('d'));
        $this->assertSame('14', $dt->format('H'));
        $this->assertSame('30', $dt->format('i'));
    }

    public function testParseDateTimeWithAmPm(): void
    {
        $result = Format::parseDateTime('05/22/2026 2:30 PM', 'MM/dd/yyyy', 'h:mm a', 'en_US');

        $dt = $result->toDateTimeImmutable();
        $this->assertSame('2026', $dt->format('Y'));
        $this->assertSame('05', $dt->format('m'));
        $this->assertSame('22', $dt->format('d'));
        $this->assertSame('14', $dt->format('H'));
        $this->assertSame('30', $dt->format('i'));
    }

    public function testParseDateTimeWithStrftimePatterns(): void
    {
        $result = Format::parseDateTime('22.05.2026 14:30', '%d.%m.%Y', '%H:%M', 'de_DE');

        $dt = $result->toDateTimeImmutable();
        $this->assertSame('2026', $dt->format('Y'));
        $this->assertSame('22', $dt->format('d'));
        $this->assertSame('14', $dt->format('H'));
        $this->assertSame('30', $dt->format('i'));
    }

    public function testRoundTripIcu(): void
    {
        $pattern = 'dd.MM.yyyy HH:mm';
        $locale = 'de_DE';
        $original = '22.05.2026 14:30';

        $parsed = Format::parse($original, $pattern, $locale);
        $formatted = Format::formatDate($parsed->toDateTimeImmutable(), $pattern, $locale);

        $this->assertSame($original, $formatted);
    }

    public function testRoundTripStrftime(): void
    {
        $pattern = '%d.%m.%Y';
        $locale = 'de_DE';
        $original = '22.05.2026';

        $parsed = Format::parse($original, $pattern, $locale);
        $formatted = Format::formatDate($parsed->toDateTimeImmutable(), $pattern, $locale);

        $this->assertSame($original, $formatted);
    }

    public function testParseInvalidStringThrowsException(): void
    {
        $this->expectException(RuntimeException::class);

        Format::parse('not-a-date', 'dd.MM.yyyy', 'de_DE');
    }

    public function testParseMismatchedPatternThrowsException(): void
    {
        $this->expectException(RuntimeException::class);

        Format::parse('hello world', 'dd.MM.yyyy', 'de_DE');
    }

    public function testParseWithTimezone(): void
    {
        $result = Format::parse('22.05.2026 14:30', 'dd.MM.yyyy HH:mm', 'de_DE', 'Europe/Berlin');

        $dt = $result->toDateTimeImmutable();
        $this->assertSame('14', $dt->format('H'));
        $this->assertSame('30', $dt->format('i'));
    }

    public function testParseIcuShortcutPattern(): void
    {
        $result = Format::parse('29.05.26', 'short', 'de_DE');

        $this->assertInstanceOf(DateInterface::class, $result);
        $dt = $result->toDateTimeImmutable();
        $this->assertSame('2026', $dt->format('Y'));
        $this->assertSame('05', $dt->format('m'));
        $this->assertSame('29', $dt->format('d'));
    }
}
