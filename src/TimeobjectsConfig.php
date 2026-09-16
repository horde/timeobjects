<?php

declare(strict_types=1);
/**
 * Timeobjects configuration class
 *
 * Provides access to the Timeobjects configuration settings.
 *
 * Old pattern: globals $conf; $somethingDetail = $conf['something']['detail']; *
 * New pattern: $config = $injector->get(TimeobjectsConfig::class); $somethingDetail = $config->get('something.detail');
 *
 * Prefer DI over instantiating $config in your code.
 */

namespace Horde\Timeobjects;

use Horde\Core\Config\State;
use Horde\Injector\Attribute\Factory;

#[Factory(factory: TimeobjectsConfigFactory::class, method: 'create')]
class TimeobjectsConfig extends State {}
