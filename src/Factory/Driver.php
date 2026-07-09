<?php

namespace Horde\Timeobjects\Factory;

use Horde\Timeobjects\Driver\Base;
use Horde\Timeobjects\Exception;
use Psr\Log\LoggerInterface;

/**
 * Factory for TimeObjects_Driver
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Timeobjects
 */
class Driver
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Creates a concrete TimeObjects_Driver object.
     *
     * @param string $name   The driver type to create.
     * @param array $params  Any driver parameters.
     *
     * @return Base
     * @throws Exception
     */
    public function create($name, array $params = [])
    {
        $short = basename($name);
        $class = 'Horde\\Timeobjects\\Driver\\' . $short;

        switch ($short) {
            case 'Weather':
                if (!class_exists('Horde_Service_Weather')) {
                    throw new Exception('Horde_Service_Weather is not installed');
                }
                break;
            case 'FacebookEvents':
                if (!class_exists('Horde_Service_Facebook')) {
                    throw new Exception('Horde_Service_Facebook is not installed');
                }
                break;
            default:
                throw new Exception(sprintf('Unable to load the definition of %s', $class));
        }

        return new $class($params, $this->logger);
    }
}
