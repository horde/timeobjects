<?php

/**
 * Bootstrap for the Timeobjects application.
 *
 * Defines TIMEOBJECTS_BASE / HORDE_BASE and pulls in the Horde framework
 * core so Horde\Timeobjects\Application (in src/) is loadable.
 *
 * The class itself lives at Horde\Timeobjects\Application in src/. The
 * Registry PSR-4-probes that name first (Core/lib/Horde/Registry.php:924).
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package TimeObjects
 */
/* Determine the base directories. */
if (!defined('TIMEOBJECTS_BASE')) {
    define('TIMEOBJECTS_BASE', realpath(__DIR__ . '/..'));
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(TIMEOBJECTS_BASE . '/config/horde.local.php')) {
        include TIMEOBJECTS_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', realpath(TIMEOBJECTS_BASE . '/..'));
    }
}

/* Load the Horde Framework core (needed to autoload
 *  Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';
