<?php

namespace Horde\Timeobjects;

use Horde_Registry_Api;
use Horde\Timeobjects\Factory\Driver as DriverFactory;
use Psr\Log\LoggerInterface;

/**
 * API methods for exposing various bits of data via the listTimeObjects API.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Timeobjects
 */
class Api extends Horde_Registry_Api
{
    /**
     * Links.
     *
     * @var array
     */
    protected $_links = [
        // @TODO: Probably implement a URL endpoint or something so we can
        // link to the correct external site depending on what time object
        // category we are referring to.
        'show' => '#',
    ];

    /**
     * Returns the available categories.
     *
     * @return array  An array of available time object categories.
     */
    public function listTimeObjectCategories()
    {
        $injector = $GLOBALS['injector'];
        $factory = $injector->getInstance(DriverFactory::class);
        $logger = $injector->getInstance(LoggerInterface::class);
        // Drivers depend on optional Composer suggest packages. Missing
        // packages are normal — skip without error spam. ensure() also
        // returns false when the matching Horde conf is disabled.
        $tests = [
            'Weather' => [
                'title' => _("Weather"),
                'requires' => 'Horde_Service_Weather',
            ],
            'FacebookEvents' => [
                'title' => _("Facebook Events"),
                'requires' => 'Horde_Service_Facebook',
            ],
        ];
        $drivers = [];
        foreach ($tests as $driver => $meta) {
            if (!class_exists($meta['requires'])) {
                continue;
            }
            try {
                if ($factory->create($driver)->ensure()) {
                    $drivers[$driver] = ['title' => $meta['title'], 'type' => 'single'];
                }
            } catch (Exception $e) {
                $logger->error(
                    sprintf('TimeObjects driver "%s" failed to initialize: %s', $driver, $e->getMessage()),
                    ['exception' => $e, 'driver' => $driver]
                );
            }
        }
        return $drivers;
    }

    /**
     * Returns time objects for the requested category.
     *
     * @param array $time_categories  An array of categories to list.
     * @param mixed $start            The start of the time period to list for.
     * @param mixed $end              The end of the time period to list for.
     *
     * @return array  A list of time object hashes.
     */
    public function listTimeObjects($time_categories, $start, $end)
    {
        $injector = $GLOBALS['injector'];
        $factory = $injector->getInstance(DriverFactory::class);
        $logger = $injector->getInstance(LoggerInterface::class);
        $return = [];
        foreach ($time_categories as $category) {
            try {
                $return = array_merge(
                    $return,
                    $factory->create($category)->listTimeObjects($start, $end)
                );
            } catch (Exception $e) {
                $logger->error(
                    sprintf('TimeObjects category "%s" failed to list: %s', $category, $e->getMessage()),
                    ['exception' => $e, 'category' => $category]
                );
            }
        }
        return $return;
    }
}
