<?php

declare(strict_types=1);

namespace Horde\Date\Test\Unit;

use DateTimeZone;
use Horde\Date\TimezoneInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimezoneInfo::class)]
class TimezoneInfoTest extends TestCase
{
    public function testConstructWithIanaOnly(): void
    {
        $info = new TimezoneInfo('America/New_York');
        $this->assertSame('America/New_York', $info->getIanaName());
        $this->assertSame('', $info->getOriginalAlias());
    }

    public function testConstructWithAlias(): void
    {
        $info = new TimezoneInfo('America/New_York', 'Eastern Standard Time');
        $this->assertSame('America/New_York', $info->getIanaName());
        $this->assertSame('Eastern Standard Time', $info->getOriginalAlias());
    }

    public function testConstructWithEmptyAlias(): void
    {
        $info = new TimezoneInfo('UTC', '');
        $this->assertSame('UTC', $info->getIanaName());
        $this->assertSame('', $info->getOriginalAlias());
    }

    public function testIsAliasReturnsTrueWhenDifferent(): void
    {
        $info = new TimezoneInfo('America/New_York', 'Eastern Standard Time');
        $this->assertTrue($info->isAlias());
    }

    public function testIsAliasReturnsFalseWhenEmpty(): void
    {
        $info = new TimezoneInfo('America/New_York');
        $this->assertFalse($info->isAlias());
    }

    public function testIsAliasReturnsFalseWhenSameAsIana(): void
    {
        $info = new TimezoneInfo('America/New_York', 'America/New_York');
        $this->assertFalse($info->isAlias());
    }

    public function testToStringReturnsIanaName(): void
    {
        $info = new TimezoneInfo('Europe/Berlin', 'W. Europe Standard Time');
        $this->assertSame('Europe/Berlin', (string) $info);
    }

    public function testToDateTimeZone(): void
    {
        $info = new TimezoneInfo('America/Chicago');
        $tz = $info->toDateTimeZone();
        $this->assertInstanceOf(DateTimeZone::class, $tz);
        $this->assertSame('America/Chicago', $tz->getName());
    }

    public function testToDateTimeZoneWithAlias(): void
    {
        $info = new TimezoneInfo('Asia/Tokyo', 'Japan');
        $tz = $info->toDateTimeZone();
        $this->assertSame('Asia/Tokyo', $tz->getName());
    }
}
