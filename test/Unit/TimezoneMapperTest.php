<?php

declare(strict_types=1);

namespace Horde\Date\Test\Unit;

use Horde\Date\TimezoneInfo;
use Horde\Date\TimezoneMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimezoneMapper::class)]
class TimezoneMapperTest extends TestCase
{
    protected function tearDown(): void
    {
        TimezoneMapper::resetRuntimeAliases();
    }

    // =========================================================================
    // IANA passthrough
    // =========================================================================

    public function testIanaPassthrough(): void
    {
        $info = TimezoneMapper::resolve('America/New_York');
        $this->assertSame('America/New_York', $info->getIanaName());
        $this->assertFalse($info->isAlias());
    }

    public function testIanaPassthroughUtc(): void
    {
        $info = TimezoneMapper::resolve('UTC');
        $this->assertSame('UTC', $info->getIanaName());
        $this->assertFalse($info->isAlias());
    }

    public function testIanaPassthroughEtcGmt(): void
    {
        $info = TimezoneMapper::resolve('Etc/GMT');
        $this->assertSame('Etc/GMT', $info->getIanaName());
        $this->assertFalse($info->isAlias());
    }

    // =========================================================================
    // Windows timezone aliases
    // =========================================================================

    #[DataProvider('windowsAliasProvider')]
    public function testWindowsAlias(string $windows, string $expectedIana): void
    {
        $info = TimezoneMapper::resolve($windows);
        $this->assertSame($expectedIana, $info->getIanaName());
        $this->assertTrue($info->isAlias());
        $this->assertSame($windows, $info->getOriginalAlias());
    }

    public static function windowsAliasProvider(): array
    {
        return [
            'Eastern' => ['Eastern Standard Time', 'America/New_York'],
            'Pacific' => ['Pacific Standard Time', 'America/Los_Angeles'],
            'Central' => ['Central Standard Time', 'America/Chicago'],
            'Mountain' => ['Mountain Standard Time', 'America/Denver'],
            'GMT' => ['GMT Standard Time', 'Europe/London'],
            'Romance' => ['Romance Standard Time', 'Europe/Paris'],
            'W. Europe' => ['W. Europe Standard Time', 'Europe/Berlin'],
            'Tokyo' => ['Tokyo Standard Time', 'Asia/Tokyo'],
            'China' => ['China Standard Time', 'Asia/Shanghai'],
            'India' => ['India Standard Time', 'Asia/Calcutta'],
            'Korea' => ['Korea Standard Time', 'Asia/Seoul'],
            'AUS Eastern' => ['AUS Eastern Standard Time', 'Australia/Sydney'],
            'New Zealand' => ['New Zealand Standard Time', 'Pacific/Auckland'],
            'Russian' => ['Russian Standard Time', 'Europe/Moscow'],
            'SA Pacific' => ['SA Pacific Standard Time', 'America/Bogota'],
            'Hawaiian' => ['Hawaiian Standard Time', 'Pacific/Honolulu'],
            'Alaskan' => ['Alaskan Standard Time', 'America/Anchorage'],
            'Iran' => ['Iran Standard Time', 'Asia/Tehran'],
            'Israel' => ['Israel Standard Time', 'Asia/Jerusalem'],
            'Turkey' => ['Turkey Standard Time', 'Europe/Istanbul'],
        ];
    }

    // =========================================================================
    // Old Olson names
    // =========================================================================

    #[DataProvider('oldOlsonProvider')]
    public function testOldOlsonAlias(string $old, string $expectedIana): void
    {
        $info = TimezoneMapper::resolve($old);
        $this->assertSame($expectedIana, $info->getIanaName());
    }

    public static function oldOlsonProvider(): array
    {
        return [
            'Asia/Calcutta' => ['Asia/Calcutta', 'Asia/Kolkata'],
            'Asia/Saigon' => ['Asia/Saigon', 'Asia/Ho_Chi_Minh'],
            'Asia/Katmandu' => ['Asia/Katmandu', 'Asia/Kathmandu'],
            'Asia/Rangoon' => ['Asia/Rangoon', 'Asia/Yangon'],
            'Europe/Kiev' => ['Europe/Kiev', 'Europe/Kyiv'],
            'America/Godthab' => ['America/Godthab', 'America/Nuuk'],
            'America/Montreal' => ['America/Montreal', 'America/Toronto'],
            'America/Indianapolis' => ['America/Indianapolis', 'America/Indiana/Indianapolis'],
            'Pacific/Ponape' => ['Pacific/Ponape', 'Pacific/Pohnpei'],
            'Pacific/Truk' => ['Pacific/Truk', 'Pacific/Chuuk'],
            'Asia/Ulan_Bator' => ['Asia/Ulan_Bator', 'Asia/Ulaanbaatar'],
            'Atlantic/Faeroe' => ['Atlantic/Faeroe', 'Atlantic/Faroe'],
            'Africa/Asmera' => ['Africa/Asmera', 'Africa/Nairobi'],
            'America/Buenos_Aires' => ['America/Buenos_Aires', 'America/Argentina/Buenos_Aires'],
            'Asia/Dacca' => ['Asia/Dacca', 'Asia/Dhaka'],
        ];
    }

    // =========================================================================
    // Country shorthand aliases
    // =========================================================================

    #[DataProvider('countryShorthandProvider')]
    public function testCountryShorthand(string $shorthand, string $expectedIana): void
    {
        $info = TimezoneMapper::resolve($shorthand);
        $this->assertSame($expectedIana, $info->getIanaName());
    }

    public static function countryShorthandProvider(): array
    {
        return [
            'Cuba' => ['Cuba', 'America/Havana'],
            'Egypt' => ['Egypt', 'Africa/Cairo'],
            'Eire' => ['Eire', 'Europe/Dublin'],
            'GB' => ['GB', 'Europe/London'],
            'GB-Eire' => ['GB-Eire', 'Europe/London'],
            'Hongkong' => ['Hongkong', 'Asia/Hong_Kong'],
            'Iceland' => ['Iceland', 'Atlantic/Reykjavik'],
            'Iran' => ['Iran', 'Asia/Tehran'],
            'Israel' => ['Israel', 'Asia/Jerusalem'],
            'Jamaica' => ['Jamaica', 'America/Jamaica'],
            'Japan' => ['Japan', 'Asia/Tokyo'],
            'Kwajalein' => ['Kwajalein', 'Pacific/Kwajalein'],
            'Libya' => ['Libya', 'Africa/Tripoli'],
            'NZ' => ['NZ', 'Pacific/Auckland'],
            'Poland' => ['Poland', 'Europe/Warsaw'],
            'Portugal' => ['Portugal', 'Europe/Lisbon'],
            'Singapore' => ['Singapore', 'Asia/Singapore'],
            'Turkey' => ['Turkey', 'Europe/Istanbul'],
        ];
    }

    // =========================================================================
    // Lotus Notes aliases
    // =========================================================================

    public function testLotusNotesWEurope(): void
    {
        $info = TimezoneMapper::resolve('W. Europe');
        $this->assertSame('Europe/Berlin', $info->getIanaName());
    }

    public function testLotusNotesEEurope(): void
    {
        $info = TimezoneMapper::resolve('E. Europe');
        $this->assertSame('Asia/Nicosia', $info->getIanaName());
    }

    // =========================================================================
    // POSIX abbreviation aliases
    // =========================================================================

    #[DataProvider('posixAbbreviationProvider')]
    public function testPosixAbbreviation(string $abbr, string $expectedIana): void
    {
        $info = TimezoneMapper::resolve($abbr);
        $this->assertSame($expectedIana, $info->getIanaName());
    }

    public static function posixAbbreviationProvider(): array
    {
        return [
            'CET' => ['CET', 'Europe/Berlin'],
            'CST6CDT' => ['CST6CDT', 'America/Chicago'],
            'EET' => ['EET', 'Europe/Athens'],
            'EST' => ['EST', 'America/Panama'],
            'EST5EDT' => ['EST5EDT', 'America/New_York'],
            'MET' => ['MET', 'Europe/Berlin'],
            'MST' => ['MST', 'America/Phoenix'],
            'MST7MDT' => ['MST7MDT', 'America/Denver'],
            'PST8PDT' => ['PST8PDT', 'America/Los_Angeles'],
            'WET' => ['WET', 'Europe/Lisbon'],
        ];
    }

    // =========================================================================
    // Special UTC-like aliases
    // =========================================================================

    public function testZuluResolvesToUtc(): void
    {
        $this->assertSame('UTC', TimezoneMapper::toIana('Zulu'));
    }

    public function testUniversalResolvesToUtc(): void
    {
        $this->assertSame('UTC', TimezoneMapper::toIana('Universal'));
    }

    public function testGmt0ResolvesToEtcGmt(): void
    {
        $this->assertSame('Etc/GMT', TimezoneMapper::toIana('GMT0'));
    }

    public function testGreenwich(): void
    {
        $this->assertSame('Etc/GMT', TimezoneMapper::toIana('Greenwich'));
    }

    // =========================================================================
    // Abbreviation fallback via DateTimeZone::listAbbreviations()
    // =========================================================================

    public function testAbbreviationFallbackCet(): void
    {
        $info = TimezoneMapper::resolve('cet');
        $this->assertInstanceOf(TimezoneInfo::class, $info);
        $this->assertNotEmpty($info->getIanaName());
    }

    // =========================================================================
    // Convenience methods
    // =========================================================================

    public function testToIanaReturnsString(): void
    {
        $this->assertSame('America/New_York', TimezoneMapper::toIana('Eastern Standard Time'));
    }

    public function testIsAliasTrue(): void
    {
        $this->assertTrue(TimezoneMapper::isAlias('Eastern Standard Time'));
    }

    public function testIsAliasFalseForIana(): void
    {
        $this->assertFalse(TimezoneMapper::isAlias('America/New_York'));
    }

    // =========================================================================
    // Runtime aliases
    // =========================================================================

    public function testAddAliases(): void
    {
        TimezoneMapper::addAliases(['MyCustomTZ' => 'Europe/Berlin']);
        $this->assertSame('Europe/Berlin', TimezoneMapper::toIana('MyCustomTZ'));
    }

    public function testRuntimeAliasOverridesBuiltIn(): void
    {
        TimezoneMapper::addAliases(['Zulu' => 'Europe/Paris']);
        $this->assertSame('Europe/Paris', TimezoneMapper::toIana('Zulu'));
    }

    public function testGetAliasesIncludesRuntime(): void
    {
        TimezoneMapper::addAliases(['TestZone' => 'Asia/Tokyo']);
        $all = TimezoneMapper::getAliases();
        $this->assertArrayHasKey('TestZone', $all);
        $this->assertSame('Asia/Tokyo', $all['TestZone']);
    }

    public function testGetAliasesIncludesBuiltIn(): void
    {
        $all = TimezoneMapper::getAliases();
        $this->assertArrayHasKey('Eastern Standard Time', $all);
    }

    public function testResetRuntimeAliases(): void
    {
        TimezoneMapper::addAliases(['TestZone' => 'Asia/Tokyo']);
        TimezoneMapper::resetRuntimeAliases();
        $all = TimezoneMapper::getAliases();
        $this->assertArrayNotHasKey('TestZone', $all);
    }

    // =========================================================================
    // Unknown timezone passthrough
    // =========================================================================

    public function testUnknownTimezonePassthrough(): void
    {
        $info = TimezoneMapper::resolve('Completely/Unknown/Zone');
        $this->assertSame('Completely/Unknown/Zone', $info->getIanaName());
        $this->assertFalse($info->isAlias());
    }

    // =========================================================================
    // resolve() returns TimezoneInfo
    // =========================================================================

    public function testResolveReturnsTimezoneInfo(): void
    {
        $info = TimezoneMapper::resolve('Pacific Standard Time');
        $this->assertInstanceOf(TimezoneInfo::class, $info);
    }
}
