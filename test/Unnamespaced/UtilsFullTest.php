<?php

declare(strict_types=1);

namespace Horde\Date\Test;

use Horde_Date;
use Horde_Date_Utils;
use Horde\Date\Date;
use Horde\Date\Utils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use DateTime;
use DateTimeZone;

#[CoversClass(Horde_Date_Utils::class)]
class UtilsFullTest extends TestCase
{
    // =========================================================================
    // isLeapYear()
    // =========================================================================

    #[DataProvider('leapYearProvider')]
    public function testIsLeapYear(int $year, bool $expected): void
    {
        $this->assertSame(
            $expected,
            Horde_Date_Utils::isLeapYear($year),
            "isLeapYear($year) mismatch"
        );
    }

    public static function leapYearProvider(): array
    {
        return [
            '2024 is leap'         => [2024, true],
            '2025 is not leap'     => [2025, false],
            '2000 is leap (÷400)' => [2000, true],
            '1900 is not leap (÷100)' => [1900, false],
            '2100 is not leap (÷100)' => [2100, false],
            '2400 is leap (÷400)' => [2400, true],
            '1996 is leap'         => [1996, true],
            '1997 is not leap'     => [1997, false],
            '4 is leap'            => [4, true],
            '100 is not leap'      => [100, false],
            '400 is leap'          => [400, true],
        ];
    }

    // =========================================================================
    // daysInMonth()
    // =========================================================================

    #[DataProvider('daysInMonthProvider')]
    public function testDaysInMonth(int $month, int $year, int $expected): void
    {
        $this->assertSame(
            $expected,
            (int) Horde_Date_Utils::daysInMonth($month, $year),
            "daysInMonth($month, $year) mismatch"
        );
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

    // =========================================================================
    // firstDayOfWeek()
    // =========================================================================

    #[DataProvider('firstDayOfWeekProvider')]
    public function testFirstDayOfWeek(int $week, int $year, string $expectedDate): void
    {
        $date = Horde_Date_Utils::firstDayOfWeek($week, $year);
        $this->assertSame(
            $expectedDate,
            $date->format('Y-m-d'),
            "firstDayOfWeek($week, $year) mismatch"
        );
    }

    public static function firstDayOfWeekProvider(): array
    {
        return [
            'W01 2026'  => [1, 2026, '2025-12-29'],
            'W02 2026'  => [2, 2026, '2026-01-05'],
            'W52 2025'  => [52, 2025, '2025-12-22'],
            'W01 2024'  => [1, 2024, '2024-01-01'],
            'W53 2020'  => [53, 2020, '2020-12-28'],
        ];
    }

    public function testFirstDayOfWeekIsMonday(): void
    {
        $date = Horde_Date_Utils::firstDayOfWeek(15, 2026);
        $this->assertSame(
            Horde_Date::DATE_MONDAY,
            $date->dayOfWeek(),
            "firstDayOfWeek should always return a Monday"
        );
    }

    // =========================================================================
    // strftime2date() conversion
    // =========================================================================

    #[DataProvider('strftime2dateProvider')]
    public function testStrftime2date(string $strftimeFormat, string $expectedDateFormat): void
    {
        $this->assertSame(
            $expectedDateFormat,
            Horde_Date_Utils::strftime2date($strftimeFormat)
        );
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
            'composite'   => ['%Y-%m-%d %H:%M:%S', 'Y-m-d H:i:s'],
        ];
    }

    public function testStrftime2dateUnsupportedDropped(): void
    {
        $this->assertSame('', Horde_Date_Utils::strftime2date('%U'));
        $this->assertSame('', Horde_Date_Utils::strftime2date('%W'));
        $this->assertSame('', Horde_Date_Utils::strftime2date('%C'));
        $this->assertSame('', Horde_Date_Utils::strftime2date('%Z'));
    }

    public function testStrftime2dateLiteralTextPreserved(): void
    {
        $result = Horde_Date_Utils::strftime2date('Date: %Y/%m/%d');
        $this->assertSame('Date: Y/m/d', $result);
    }

    // =========================================================================
    // relativeDateTime() — basic structure tests
    // =========================================================================

    public function testRelativeDateTimeReturnsString(): void
    {
        $result = Horde_Date_Utils::relativeDateTime(time() - 30);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testRelativeDateTimeSeconds(): void
    {
        $result = Horde_Date_Utils::relativeDateTime(time() - 5);
        $this->assertMatchesRegularExpression('/\d+ seconds? ago/', $result);
    }

    public function testRelativeDateTimeMinutes(): void
    {
        $result = Horde_Date_Utils::relativeDateTime(time() - 120);
        $this->assertMatchesRegularExpression('/\d+ minutes? ago/', $result);
    }

    public function testRelativeDateTimeHours(): void
    {
        $result = Horde_Date_Utils::relativeDateTime(time() - 7200);
        $this->assertMatchesRegularExpression('/\d+ hours? ago/', $result);
    }

    // =========================================================================
    // legacyDateFormatter()
    // =========================================================================

    public function testLegacyDateFormatterWithStrftimePattern(): void
    {
        $date = new Horde_Date('2026-04-17 14:30:00', 'UTC');
        $result = Horde_Date_Utils::legacyDateFormatter('%Y-%m-%d', $date, 'UTC');
        $this->assertSame('2026-04-17', $result);
    }

    public function testLegacyDateFormatterWithTimestamp(): void
    {
        $ts = (new Horde_Date('2026-04-17 12:00:00', 'UTC'))->timestamp();
        $result = Horde_Date_Utils::legacyDateFormatter('%Y', $ts, 'UTC');
        $this->assertSame('2026', $result);
    }

    public function testLegacyDateFormatterWithDateTimeInterface(): void
    {
        $dt = new DateTime('2026-04-17 10:00:00', new DateTimeZone('UTC'));
        $result = Horde_Date_Utils::legacyDateFormatter('%Y-%m-%d', $dt);
        $this->assertSame('2026-04-17', $result);
    }

    public function testLegacyDateFormatterDefaultsToNow(): void
    {
        $result = Horde_Date_Utils::legacyDateFormatter('%Y');
        $this->assertSame(date('Y'), $result);
    }

    public function testLegacyDateFormatterNullDateDefaultsToNow(): void
    {
        $result = Horde_Date_Utils::legacyDateFormatter('%Y', null);
        $this->assertSame(date('Y'), $result);
    }

    // =========================================================================
    // Cross-validation: legacy wrapper vs modern Utils
    // =========================================================================

    #[DataProvider('leapYearProvider')]
    public function testIsLeapYearMatchesModern(int $year, bool $expected): void
    {
        $this->assertSame(
            Utils::isLeapYear($year),
            Horde_Date_Utils::isLeapYear($year),
            "isLeapYear($year): legacy and modern must agree"
        );
    }

    #[DataProvider('daysInMonthProvider')]
    public function testDaysInMonthMatchesModern(int $month, int $year, int $expected): void
    {
        $this->assertSame(
            Utils::daysInMonth($month, $year),
            (int) Horde_Date_Utils::daysInMonth($month, $year),
            "daysInMonth($month, $year): legacy and modern must agree"
        );
    }

    #[DataProvider('firstDayOfWeekProvider')]
    public function testFirstDayOfWeekMatchesModern(int $week, int $year, string $expectedDate): void
    {
        $legacy = Horde_Date_Utils::firstDayOfWeek($week, $year);
        $modern = Utils::firstDayOfWeek($week, $year);

        $this->assertSame(
            $modern->format('Y-m-d'),
            $legacy->format('Y-m-d'),
            "firstDayOfWeek($week, $year): legacy and modern must agree"
        );
    }

    public function testFirstDayOfWeekLegacyReturnsHordeDate(): void
    {
        $legacy = Horde_Date_Utils::firstDayOfWeek(1, 2026);
        $this->assertInstanceOf(Horde_Date::class, $legacy);

        $modern = Utils::firstDayOfWeek(1, 2026);
        $this->assertInstanceOf(Date::class, $modern);
    }

    #[DataProvider('strftime2dateSimpleProvider')]
    public function testStrftime2dateMatchesModernForSimpleFormats(string $strftimeFormat, string $expected): void
    {
        $this->assertSame(
            Utils::strftime2date($strftimeFormat),
            Horde_Date_Utils::strftime2date($strftimeFormat),
            "strftime2date('$strftimeFormat'): legacy and modern must agree"
        );
    }

    public static function strftime2dateSimpleProvider(): array
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
            '%F → Y-m-d'  => ['%F', 'Y-m-d'],
            '%T → H:i:s'  => ['%T', 'H:i:s'],
            '%R → H:i'    => ['%R', 'H:i'],
            '%% → %'      => ['%%', '%'],
            'composite'   => ['%Y-%m-%d %H:%M:%S', 'Y-m-d H:i:s'],
        ];
    }
}
