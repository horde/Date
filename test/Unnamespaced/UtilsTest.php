<?php
/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */
namespace Horde\Date\Test;
use \PHPUnit\Framework\TestCase;
use \Horde_Date_Utils;

/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */
class UtilsTest extends TestCase
{
    public function testFirstDayOfWeek()
    {
        $this->assertEquals('2006-01-02', Horde_Date_Utils::firstDayOfWeek(1, 2006)->format('Y-m-d'));
        $this->assertEquals('2007-01-01', Horde_Date_Utils::firstDayOfWeek(1, 2007)->format('Y-m-d'));
        $this->assertEquals('2007-12-31', Horde_Date_Utils::firstDayOfWeek(1, 2008)->format('Y-m-d'));
        $this->assertEquals('2010-01-04', Horde_Date_Utils::firstDayOfWeek(1, 2010)->format('Y-m-d'));
    }

}
