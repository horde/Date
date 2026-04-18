<?php

declare(strict_types=1);

namespace Horde\Date\Test\Unit;

use DateTimeImmutable;
use Horde\Date\Date;
use Horde\Date\DateInterface;
use Horde\Date\Formatter\DateTimeFormatter;
use Horde\Date\Formatter\IcuFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateTimeFormatter::class)]
#[CoversClass(IcuFormatter::class)]
class FormatterParseReturnTest extends TestCase
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

    public function testDateTimeFormatterParseReturnsDate(): void
    {
        $formatter = new DateTimeFormatter();
        $result = $formatter->parse('2026-03-18', 'Y-m-d', 'en_US');

        $this->assertInstanceOf(Date::class, $result);
    }

    public function testIcuFormatterParseReturnsDate(): void
    {
        $formatter = new IcuFormatter();
        $result = $formatter->parse('2026-03-18', 'yyyy-MM-dd', 'en_US');

        $this->assertInstanceOf(Date::class, $result);
    }

    public function testParsedDateImplementsDateInterface(): void
    {
        $dtFormatter = new DateTimeFormatter();
        $dtResult = $dtFormatter->parse('2026-03-18', 'Y-m-d', 'en_US');

        $icuFormatter = new IcuFormatter();
        $icuResult = $icuFormatter->parse('2026-03-18', 'yyyy-MM-dd', 'en_US');

        $this->assertInstanceOf(DateInterface::class, $dtResult);
        $this->assertInstanceOf(DateInterface::class, $icuResult);
    }

    public function testParsedDateIsDateTimeImmutable(): void
    {
        $dtFormatter = new DateTimeFormatter();
        $dtResult = $dtFormatter->parse('2026-03-18', 'Y-m-d', 'en_US');

        $icuFormatter = new IcuFormatter();
        $icuResult = $icuFormatter->parse('2026-03-18', 'yyyy-MM-dd', 'en_US');

        $this->assertInstanceOf(DateTimeImmutable::class, $dtResult);
        $this->assertInstanceOf(DateTimeImmutable::class, $icuResult);
    }

    public function testParsedDatePreservesValues(): void
    {
        $dtFormatter = new DateTimeFormatter();
        $dtResult = $dtFormatter->parse('2026-03-18 14:30:45', 'Y-m-d H:i:s', 'en_US');

        $this->assertSame('2026', $dtResult->format('Y'));
        $this->assertSame('03', $dtResult->format('m'));
        $this->assertSame('18', $dtResult->format('d'));
        $this->assertSame('14', $dtResult->format('H'));
        $this->assertSame('30', $dtResult->format('i'));
        $this->assertSame('45', $dtResult->format('s'));

        $icuFormatter = new IcuFormatter();
        $icuResult = $icuFormatter->parse('2026-03-18 14:30:45', 'yyyy-MM-dd HH:mm:ss', 'en_US');

        $this->assertSame('2026', $icuResult->format('Y'));
        $this->assertSame('03', $icuResult->format('m'));
        $this->assertSame('18', $icuResult->format('d'));
        $this->assertSame('14', $icuResult->format('H'));
        $this->assertSame('30', $icuResult->format('i'));
        $this->assertSame('45', $icuResult->format('s'));
    }
}
