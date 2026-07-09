<?php

namespace Horde\Timeobjects;

use Horde_Registry_Application;

/**
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Content through this API.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package TimeObjects
 */
class Application extends Horde_Registry_Application
{
    public $version = '3.0.0-beta2';
}
