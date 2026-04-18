<?php

declare(strict_types=1);

use Horde\Date\Date;
use Horde\Date\DateInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Date::class)]
class DateEjectionTest extends TestCase
{
    protected string $oldTimezone;

    protected function setUp(): void
    {
        $this->oldTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->oldTimezone);
    }

    public function testToDateTimeImmutable(): void
    {
        $legacy = new Horde_Date('2026-04-17 14:30:00', 'UTC');
        $immutable = $legacy->toDateTimeImmutable();

        $this->assertInstanceOf(DateTimeImmutable::class, $immutable);
        $this->assertSame('2026', $immutable->format('Y'));
        $this->assertSame('04', $immutable->format('m'));
        $this->assertSame('17', $immutable->format('d'));
        $this->assertSame('14', $immutable->format('H'));
        $this->assertSame('30', $immutable->format('i'));
        $this->assertSame('00', $immutable->format('s'));
    }

    public function testToDate(): void
    {
        $legacy = new Horde_Date('2026-04-17 14:30:00', 'UTC');
        $modern = $legacy->toDate();

        $this->assertInstanceOf(Date::class, $modern);
        $this->assertInstanceOf(DateInterface::class, $modern);
        $this->assertSame('2026', $modern->format('Y'));
        $this->assertSame('04', $modern->format('m'));
        $this->assertSame('17', $modern->format('d'));
        $this->assertSame('14', $modern->format('H'));
        $this->assertSame('30', $modern->format('i'));
        $this->assertSame('00', $modern->format('s'));
    }

    public function testToDatePreservesTimezone(): void
    {
        $legacy = new Horde_Date('2026-04-17 14:30:00', 'America/New_York');
        $modern = $legacy->toDate();

        $this->assertSame('America/New_York', $modern->getTimezone()->getName());
        $this->assertSame('14', $modern->format('H'));
    }

    public function testToDatePreservesDateTime(): void
    {
        $legacy = new Horde_Date('2026-12-31 23:59:59', 'Asia/Tokyo');
        $modern = $legacy->toDate();

        $this->assertSame('2026-12-31 23:59:59', $modern->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Tokyo', $modern->getTimezone()->getName());
    }

    public function testToDateRoundTrip(): void
    {
        $legacy = new Horde_Date('2026-04-17 14:30:45', 'Europe/Berlin');
        $modern = $legacy->toDate();

        $this->assertSame(
            $legacy->format('Y-m-d H:i:s'),
            $modern->format('Y-m-d H:i:s')
        );
    }

    public function testGetTimezone(): void
    {
        $legacy = new Horde_Date('2026-04-17 14:30:00', 'Europe/Berlin');
        $tz = $legacy->getTimezone();

        $this->assertInstanceOf(DateTimeZone::class, $tz);
        $this->assertSame('Europe/Berlin', $tz->getName());
    }

    public function testGetTimezoneUtc(): void
    {
        $legacy = new Horde_Date('2026-04-17 14:30:00', 'UTC');
        $tz = $legacy->getTimezone();

        $this->assertSame('UTC', $tz->getName());
    }

    public function testToDateTimeImmutablePreservesTimezone(): void
    {
        $legacy = new Horde_Date('2026-04-17 14:30:00', 'Asia/Tokyo');
        $immutable = $legacy->toDateTimeImmutable();

        $this->assertSame('Asia/Tokyo', $immutable->getTimezone()->getName());
        $this->assertSame('14', $immutable->format('H'));
    }
}
