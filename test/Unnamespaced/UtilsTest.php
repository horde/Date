<?php

declare(strict_types=1);
/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */

namespace Horde\Date\Test;

use Horde_Date_Utils;
use PHPUnit\Framework\TestCase;

/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 * @coversNothing
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
