<?php

namespace Horde\Timeobjects\Driver;

use Horde\Timeobjects\Exception;
use Horde_Date;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Base TimeObjects_Driver.
 *
 * Copyright 2009-2026 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package TimeObjects
 */
abstract class Base
{
    protected $_params = [];

    /**
     * PSR-3 logger for driver diagnostics.
     */
    protected LoggerInterface $logger;

    /**
     * Constructor
     *
     * @param array $params            The parameter array.
     * @param LoggerInterface|null $logger  PSR-3 logger. Defaults to a
     *                                      NullLogger so drivers instantiated
     *                                      without DI stay silent.
     */
    public function __construct(array $params, ?LoggerInterface $logger = null)
    {
        $this->_params = array_merge($this->_params, $params);
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Get a list of TimeObjects.
     *
     * @param $start
     * @param $end
     *
     * @return array  The array of time objects.
     */
    abstract public function listTimeObjects(?Horde_Date $start = null, ?Horde_Date $end = null);

    /**
     * Ensure we have minimum requirements for concrete driver to run.
     *
     */
    abstract public function ensure();

    /**
     * Factory method
     *
     * @param $name
     * @param $params
     *
     * @return Base
     */
    public function factory($name, array $params = [])
    {
        $class = 'Horde\\Timeobjects\\Driver\\' . basename($name);
        if (class_exists($class)) {
            return new $class($params, $this->logger);
        } else {
            throw new Exception(sprintf('Unable to load the definition of %s', $class));
        }
    }

}
