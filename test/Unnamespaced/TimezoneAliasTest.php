<?php

declare(strict_types=1);

namespace Horde\Date\Test;

use Horde_Date;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Date::class)]
class TimezoneAliasTest extends TestCase
{
    // =========================================================================
    // Windows timezone names (from Exchange/ActiveSync)
    // =========================================================================

    #[DataProvider('windowsTimezoneProvider')]
    public function testWindowsTimezoneAliases(string $windowsName, string $expectedIana): void
    {
        $this->assertSame(
            $expectedIana,
            Horde_Date::getTimezoneAlias($windowsName),
            "Windows timezone '$windowsName' should resolve to '$expectedIana'"
        );
    }

    public static function windowsTimezoneProvider(): array
    {
        return [
            'Eastern Standard Time'      => ['Eastern Standard Time', 'America/New_York'],
            'Pacific Standard Time'      => ['Pacific Standard Time', 'America/Los_Angeles'],
            'Central Standard Time'      => ['Central Standard Time', 'America/Chicago'],
            'Mountain Standard Time'     => ['Mountain Standard Time', 'America/Denver'],
            'GMT Standard Time'          => ['GMT Standard Time', 'Europe/London'],
            'W. Europe Standard Time'    => ['W. Europe Standard Time', 'Europe/Berlin'],
            'Romance Standard Time'      => ['Romance Standard Time', 'Europe/Paris'],
            'Tokyo Standard Time'        => ['Tokyo Standard Time', 'Asia/Tokyo'],
            'China Standard Time'        => ['China Standard Time', 'Asia/Shanghai'],
            'India Standard Time'        => ['India Standard Time', 'Asia/Calcutta'],
            'Russian Standard Time'      => ['Russian Standard Time', 'Europe/Moscow'],
            'AUS Eastern Standard Time'  => ['AUS Eastern Standard Time', 'Australia/Sydney'],
            'New Zealand Standard Time'  => ['New Zealand Standard Time', 'Pacific/Auckland'],
            'Hawaiian Standard Time'     => ['Hawaiian Standard Time', 'Pacific/Honolulu'],
            'Central European Standard Time' => ['Central European Standard Time', 'Europe/Warsaw'],
            'FLE Standard Time'          => ['FLE Standard Time', 'Europe/Kyiv'],
            'Israel Standard Time'       => ['Israel Standard Time', 'Asia/Jerusalem'],
            'Arabic Standard Time'       => ['Arabic Standard Time', 'Asia/Baghdad'],
            'Iran Standard Time'         => ['Iran Standard Time', 'Asia/Tehran'],
            'Singapore Standard Time'    => ['Singapore Standard Time', 'Asia/Singapore'],
        ];
    }

    // =========================================================================
    // POSIX-style abbreviations
    // =========================================================================

    #[DataProvider('posixTimezoneProvider')]
    public function testPosixTimezoneAliases(string $posixName, string $expectedIana): void
    {
        $this->assertSame(
            $expectedIana,
            Horde_Date::getTimezoneAlias($posixName),
            "POSIX timezone '$posixName' should resolve to '$expectedIana'"
        );
    }

    public static function posixTimezoneProvider(): array
    {
        return [
            'CET'      => ['CET', 'Europe/Berlin'],
            'CST6CDT'  => ['CST6CDT', 'America/Chicago'],
            'EET'      => ['EET', 'Europe/Athens'],
            'EST'      => ['EST', 'America/Panama'],
            'EST5EDT'  => ['EST5EDT', 'America/New_York'],
            'MET'      => ['MET', 'Europe/Berlin'],
            'MST'      => ['MST', 'America/Phoenix'],
            'MST7MDT'  => ['MST7MDT', 'America/Denver'],
            'PST8PDT'  => ['PST8PDT', 'America/Los_Angeles'],
            'WET'      => ['WET', 'Europe/Lisbon'],
        ];
    }

    // =========================================================================
    // Old Olson / backward compatibility names
    // =========================================================================

    #[DataProvider('oldOlsonTimezoneProvider')]
    public function testOldOlsonTimezoneAliases(string $oldName, string $expectedIana): void
    {
        $this->assertSame(
            $expectedIana,
            Horde_Date::getTimezoneAlias($oldName),
            "Old Olson name '$oldName' should resolve to '$expectedIana'"
        );
    }

    public static function oldOlsonTimezoneProvider(): array
    {
        return [
            'Asia/Calcutta → Kolkata'       => ['Asia/Calcutta', 'Asia/Kolkata'],
            'Asia/Katmandu → Kathmandu'     => ['Asia/Katmandu', 'Asia/Kathmandu'],
            'Asia/Saigon → Ho_Chi_Minh'     => ['Asia/Saigon', 'Asia/Ho_Chi_Minh'],
            'Asia/Rangoon → Yangon'         => ['Asia/Rangoon', 'Asia/Yangon'],
            'Europe/Kiev → Kyiv'            => ['Europe/Kiev', 'Europe/Kyiv'],
            'America/Godthab → Nuuk'        => ['America/Godthab', 'America/Nuuk'],
            'America/Buenos_Aires'          => ['America/Buenos_Aires', 'America/Argentina/Buenos_Aires'],
            'America/Indianapolis'          => ['America/Indianapolis', 'America/Indiana/Indianapolis'],
            'America/Montreal → Toronto'    => ['America/Montreal', 'America/Toronto'],
            'Pacific/Ponape → Pohnpei'      => ['Pacific/Ponape', 'Pacific/Pohnpei'],
            'Pacific/Truk → Chuuk'          => ['Pacific/Truk', 'Pacific/Chuuk'],
            'Australia/ACT → Sydney'        => ['Australia/ACT', 'Australia/Sydney'],
            'Canada/Eastern → Toronto'      => ['Canada/Eastern', 'America/Toronto'],
            'US/Eastern → New_York'         => ['US/Eastern', 'America/New_York'],
            'US/Pacific → Los_Angeles'      => ['US/Pacific', 'America/Los_Angeles'],
        ];
    }

    // =========================================================================
    // Lotus Notes timezone names
    // =========================================================================

    public function testLotusTimezoneAliases(): void
    {
        $this->assertSame(
            'Europe/Berlin',
            Horde_Date::getTimezoneAlias('W. Europe')
        );
        $this->assertSame(
            'Asia/Nicosia',
            Horde_Date::getTimezoneAlias('E. Europe')
        );
    }

    // =========================================================================
    // Country/region shorthand names
    // =========================================================================

    #[DataProvider('countryShorthandProvider')]
    public function testCountryShorthandAliases(string $shorthand, string $expectedIana): void
    {
        $this->assertSame(
            $expectedIana,
            Horde_Date::getTimezoneAlias($shorthand),
            "Country shorthand '$shorthand' should resolve to '$expectedIana'"
        );
    }

    public static function countryShorthandProvider(): array
    {
        return [
            'Cuba'     => ['Cuba', 'America/Havana'],
            'Egypt'    => ['Egypt', 'Africa/Cairo'],
            'Eire'     => ['Eire', 'Europe/Dublin'],
            'GB'       => ['GB', 'Europe/London'],
            'Hongkong' => ['Hongkong', 'Asia/Hong_Kong'],
            'Iceland'  => ['Iceland', 'Atlantic/Reykjavik'],
            'Iran'     => ['Iran', 'Asia/Tehran'],
            'Israel'   => ['Israel', 'Asia/Jerusalem'],
            'Jamaica'  => ['Jamaica', 'America/Jamaica'],
            'Japan'    => ['Japan', 'Asia/Tokyo'],
            'Poland'   => ['Poland', 'Europe/Warsaw'],
            'Turkey'   => ['Turkey', 'Europe/Istanbul'],
            'NZ'       => ['NZ', 'Pacific/Auckland'],
            'PRC'      => ['PRC', 'Asia/Shanghai'],
            'ROK'      => ['ROK', 'Asia/Seoul'],
            'Singapore' => ['Singapore', 'Asia/Singapore'],
            'Zulu'     => ['Zulu', 'Etc/Universal'],
            'Universal' => ['Universal', 'Etc/Universal'],
        ];
    }

    // =========================================================================
    // Already-valid IANA names pass through unchanged
    // =========================================================================

    #[DataProvider('validIanaProvider')]
    public function testValidIanaPassthrough(string $ianaName): void
    {
        $this->assertSame(
            $ianaName,
            Horde_Date::getTimezoneAlias($ianaName),
            "Valid IANA name '$ianaName' should pass through unchanged"
        );
    }

    public static function validIanaProvider(): array
    {
        return [
            'America/New_York'    => ['America/New_York'],
            'Europe/Berlin'       => ['Europe/Berlin'],
            'Asia/Tokyo'          => ['Asia/Tokyo'],
            'UTC'                 => ['UTC'],
            'Pacific/Auckland'    => ['Pacific/Auckland'],
            'Africa/Cairo'        => ['Africa/Cairo'],
            'Australia/Sydney'    => ['Australia/Sydney'],
        ];
    }

    // =========================================================================
    // Timezone alias used in date construction
    // =========================================================================

    public function testTimezoneAliasInConstructor(): void
    {
        $date = new Horde_Date('2026-04-17 12:00:00', 'Eastern Standard Time');
        $this->assertSame('America/New_York', $date->timezone);
    }

    public function testSetTimezoneWithAlias(): void
    {
        $date = new Horde_Date('2026-04-17 12:00:00', 'UTC');
        $date->setTimezone('W. Europe Standard Time');
        $this->assertSame('Europe/Berlin', $date->timezone);
    }

    // =========================================================================
    // Abbreviation lookup via DateTimeZone::listAbbreviations()
    // =========================================================================

    public function testTimezoneAbbreviationLookup(): void
    {
        // 'cet' should resolve via DateTimeZone::listAbbreviations()
        // if not found in the static alias array (but CET is in the array)
        $result = Horde_Date::getTimezoneAlias('CET');
        $this->assertNotEmpty($result);
        // It should be a valid timezone
        $tz = new \DateTimeZone($result);
        $this->assertInstanceOf(\DateTimeZone::class, $tz);
    }
}
