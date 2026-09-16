<?php

declare(strict_types=1);
/**
 * Timeobjects configuration class factory
 *
 * Creates instances of the TimeobjectsConfig class.
 *
 * Old pattern: globals $conf; $somethingDetail = $conf['something']['detail']; *
 * New pattern: $config = $injector->get(TimeobjectsConfig::class); $somethingDetail = $config->get('something.detail');
 *
 * Prefer DI over instantiating $config in your code.
 */

namespace Horde\Timeobjects;

use Horde\Core\Config\ConfigLoader;
use Horde\Injector\Injector;

class TimeobjectsConfigFactory
{
    public function __construct(private Injector $injector) {}

    public function create(): TimeobjectsConfig
    {
        $state = $this->injector->get(ConfigLoader::class)->load('timeobjects');
        return new TimeobjectsConfig($state->toArray());
    }
}
