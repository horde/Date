<?php

declare(strict_types=1);
/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */

namespace Horde\Date\Test\Repeater;

use Horde_Date;
use Horde_Date_Repeater_Day;
use PHPUnit\Framework\TestCase;

/**
 * @category   Horde
 * @package    Date
 * @subpackage UnitTests
 */
class DayTest extends TestCase
{
    public function testNextFuture()
    {
        $repeater = new Horde_Date_Repeater_Day();
        $repeater->now = new Horde_Date('2009-01-01');
        $this->assertEquals('(2009-01-02 00:00:00..2009-01-03 00:00:00)', (string)$repeater->next('future'));
    }

    public function testNextPast()
    {
        $repeater = new Horde_Date_Repeater_Day();
        $repeater->now = new Horde_Date('2009-01-01');
        $this->assertEquals('(2008-12-31 00:00:00..2009-01-01 00:00:00)', (string)$repeater->next('past'));
    }

}
