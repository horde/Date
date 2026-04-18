<?php

declare(strict_types=1);

namespace Horde\Date\Test\Unit;

use Horde\Date\Date;
use Horde\Date\DateException;
use Horde\Date\Utils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Utils::class)]
class UtilsTest extends TestCase
{
    // =========================================================================
    // isLeapYear()
    // =========================================================================

    #[DataProvider('leapYearProvider')]
    public function testIsLeapYear(int $year, bool $expected): void
    {
        $this->assertSame($expected, Utils::isLeapYear($year), "isLeapYear($year)");
    }

    public static function leapYearProvider(): array
    {
        return [
            '4 is leap'            => [4, true],
            '100 is not leap'      => [100, false],
            '400 is leap'          => [400, true],
            '1900 is not leap'     => [1900, false],
            '1996 is leap'         => [1996, true],
            '1997 is not leap'     => [1997, false],
            '2000 is leap (÷400)' => [2000, true],
            '2024 is leap'         => [2024, true],
            '2025 is not leap'     => [2025, false],
            '2100 is not leap'     => [2100, false],
            '2400 is leap (÷400)' => [2400, true],
        ];
    }

    // =========================================================================
    // daysInMonth()
    // =========================================================================

    #[DataProvider('daysInMonthProvider')]
    public function testDaysInMonth(int $month, int $year, int $expected): void
    {
        $this->assertSame($expected, Utils::daysInMonth($month, $year), "daysInMonth($month, $year)");
    }

    public static function daysInMonthProvider(): array
    {
        return [
            'Jan'            => [1, 2026, 31],
            'Feb non-leap'   => [2, 2025, 28],
            'Feb leap'       => [2, 2024, 29],
            'Feb 1900'       => [2, 1900, 28],
            'Feb 2000'       => [2, 2000, 29],
            'Mar'            => [3, 2026, 31],
            'Apr'            => [4, 2026, 30],
            'May'            => [5, 2026, 31],
            'Jun'            => [6, 2026, 30],
            'Jul'            => [7, 2026, 31],
            'Aug'            => [8, 2026, 31],
            'Sep'            => [9, 2026, 30],
            'Oct'            => [10, 2026, 31],
            'Nov'            => [11, 2026, 30],
            'Dec'            => [12, 2026, 31],
        ];
    }

    public function testDaysInMonthReturnsInt(): void
    {
        $result = Utils::daysInMonth(1, 2026);
        $this->assertIsInt($result);
    }

    public function testDaysInMonthInvalidThrows(): void
    {
        $this->expectException(DateException::class);
        Utils::daysInMonth(13, 2026);
    }

    // =========================================================================
    // firstDayOfWeek()
    // =========================================================================

    #[DataProvider('firstDayOfWeekProvider')]
    public function testFirstDayOfWeek(int $week, int $year, string $expectedDate): void
    {
        $date = Utils::firstDayOfWeek($week, $year);
        $this->assertSame($expectedDate, $date->format('Y-m-d'), "firstDayOfWeek($week, $year)");
    }

    public static function firstDayOfWeekProvider(): array
    {
        return [
            'W01 2006' => [1, 2006, '2006-01-02'],
            'W01 2007' => [1, 2007, '2007-01-01'],
            'W01 2008' => [1, 2008, '2007-12-31'],
            'W01 2010' => [1, 2010, '2010-01-04'],
            'W01 2024' => [1, 2024, '2024-01-01'],
            'W01 2026' => [1, 2026, '2025-12-29'],
            'W53 2020' => [53, 2020, '2020-12-28'],
        ];
    }

    public function testFirstDayOfWeekReturnsDate(): void
    {
        $this->assertInstanceOf(Date::class, Utils::firstDayOfWeek(1, 2026));
    }

    public function testFirstDayOfWeekIsAlwaysMonday(): void
    {
        foreach ([1, 10, 26, 40, 52] as $week) {
            $date = Utils::firstDayOfWeek($week, 2026);
            $this->assertSame(
                Date::MONDAY,
                $date->dayOfWeek(),
                "Week $week of 2026 should start on Monday"
            );
        }
    }

    // =========================================================================
    // strftime2date()
    // =========================================================================

    #[DataProvider('strftime2dateProvider')]
    public function testStrftime2date(string $strftimeFormat, string $expectedDateFormat): void
    {
        $this->assertSame($expectedDateFormat, Utils::strftime2date($strftimeFormat));
    }

    public static function strftime2dateProvider(): array
    {
        return [
            '%Y → Y'      => ['%Y', 'Y'],
            '%m → m'      => ['%m', 'm'],
            '%d → d'      => ['%d', 'd'],
            '%H → H'      => ['%H', 'H'],
            '%M → i'      => ['%M', 'i'],
            '%S → s'      => ['%S', 's'],
            '%A → l'      => ['%A', 'l'],
            '%a → D'      => ['%a', 'D'],
            '%B → F'      => ['%B', 'F'],
            '%b → M'      => ['%b', 'M'],
            '%h → M'      => ['%h', 'M'],
            '%e → j'      => ['%e', 'j'],
            '%I → h'      => ['%I', 'h'],
            '%p → A'      => ['%p', 'A'],
            '%P → a'      => ['%P', 'a'],
            '%F → Y-m-d'  => ['%F', 'Y-m-d'],
            '%T → H:i:s'  => ['%T', 'H:i:s'],
            '%R → H:i'    => ['%R', 'H:i'],
            '%V → W'      => ['%V', 'W'],
            '%n → newline' => ['%n', "\n"],
            '%t → tab'    => ['%t', "\t"],
            '%% → %'      => ['%%', '%'],
            '%s → U'      => ['%s', 'U'],
            '%D → m/d/y'  => ['%D', 'm/d/y'],
            '%r → time12' => ['%r', 'h:i:s A'],
            '%z → O'      => ['%z', 'O'],
        ];
    }

    public function testStrftime2dateUnsupportedDropped(): void
    {
        $this->assertSame('', Utils::strftime2date('%U'));
        $this->assertSame('', Utils::strftime2date('%W'));
        $this->assertSame('', Utils::strftime2date('%C'));
        $this->assertSame('', Utils::strftime2date('%Z'));
    }

    public function testStrftime2dateLiteralPreserved(): void
    {
        $this->assertSame('Date: Y/m/d', Utils::strftime2date('Date: %Y/%m/%d'));
    }

    public function testStrftime2dateWithLocaleProvider(): void
    {
        $provider = function (int $constant): string {
            return match ($constant) {
                D_FMT => 'd/m/Y',
                T_FMT => 'H:i',
                default => '',
            };
        };

        $this->assertSame('d/m/Y', Utils::strftime2date('%x', $provider));
        $this->assertSame('H:i', Utils::strftime2date('%X', $provider));
    }

    public function testStrftime2dateDefaultLocale(): void
    {
        $this->assertSame('m/d/Y', Utils::strftime2date('%x'));
        $this->assertSame('H:i:s', Utils::strftime2date('%X'));
    }

    public function testStrftime2dateComposite(): void
    {
        $this->assertSame('Y-m-d H:i:s', Utils::strftime2date('%Y-%m-%d %H:%M:%S'));
    }
}
