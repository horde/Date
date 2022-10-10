<?php
/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */
namespace Horde\Date\Test\Repeater;
use \PHPUnit\Framework\TestCase;
use \Horde_Date;
use \Horde_Date_Span;
use \Horde_Date_Repeater_Month;

/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */
class MonthTest extends TestCase
{
    public function setUp(): void
    {
        $this->now = new Horde_Date('2006-08-16 14:00:00');
    }

    public function testOffsetFuture()
    {
        $span = new Horde_Date_Span($this->now, $this->now->add(60));
        $repeater = new Horde_Date_Repeater_Month();
        $offsetSpan = $repeater->offset($span, 1, 'future');

        $this->assertEquals('2006-09-16 14:00:00', (string)$offsetSpan->begin);
        $this->assertEquals('2006-09-16 14:01:00', (string)$offsetSpan->end);
    }

    public function testOffsetPast()
    {
        $span = new Horde_Date_Span($this->now, $this->now->add(60));
        $repeater = new Horde_Date_Repeater_Month();
        $offsetSpan = $repeater->offset($span, 1, 'past');

        $this->assertEquals('2006-07-16 14:00:00', (string)$offsetSpan->begin);
        $this->assertEquals('2006-07-16 14:01:00', (string)$offsetSpan->end);
    }

}
