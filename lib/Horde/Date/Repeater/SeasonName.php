<?php

declare(strict_types=1);
/**
 * Copyright 2009-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Date
 */

/**
 * Date repeater.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2009-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Date
 */
class Horde_Date_Repeater_SeasonName extends Horde_Date_Repeater_Season
{
    /**
     * 91 * 24 * 60 * 60
     */
    public const SEASON_SECONDS = 7862400;

    public $summer = ['jul 21', 'sep 22'];
    public $autumn = ['sep 23', 'dec 21'];
    public $winter = ['dec 22', 'mar 19'];
    public $spring = ['mar 20', 'jul 20'];
    public $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function next($pointer = 'future')
    {
        parent::next($pointer);
        throw new Horde_Date_Repeater_Exception('Not implemented');
    }

    public function this($pointer = 'future')
    {
        parent::this($pointer);
        throw new Horde_Date_Repeater_Exception('Not implemented');
    }

    public function width()
    {
        return self::SEASON_SECONDS;
    }

    public function __toString()
    {
        return parent::__toString() . '-season-' . $this->type;
    }

}
