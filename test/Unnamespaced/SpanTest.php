<?php
/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */
namespace Horde\Date\Test;
use \PHPUnit\Framework\TestCase;
use \Horde_Date;
use \Horde_Date_Span;

/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */
class SpanTest extends TestCase
{
    public function testWidth()
    {
        $s = new Horde_Date_Span(new Horde_Date('2006-08-16 00:00:00'), new Horde_Date('2006-08-17 00:00:00'));
        $this->assertEquals(60 * 60 * 24, $s->width());
    }

    public function testIncludes()
    {
        $s = new Horde_Date_Span(new Horde_Date('2006-08-16 00:00:00'), new Horde_Date('2006-08-17 00:00:00'));
        $this->assertTrue($s->includes(new Horde_Date('2006-08-16 12:00:00')));
        $this->assertFalse($s->includes(new Horde_Date('2006-08-15 00:00:00')));
        $this->assertFalse($s->includes(new Horde_Date('2006-08-18 00:00:00')));
    }

    public function testSpanMath()
    {
        $s = new Horde_Date_Span(new Horde_Date(1), new Horde_Date(2));
        $this->assertEquals(2, $s->add(1)->begin->timestamp());
        $this->assertEquals(3, $s->add(1)->end->timestamp());
        $this->assertEquals(0, $s->sub(1)->begin->timestamp());
        $this->assertEquals(1, $s->sub(1)->end->timestamp());
    }

}
