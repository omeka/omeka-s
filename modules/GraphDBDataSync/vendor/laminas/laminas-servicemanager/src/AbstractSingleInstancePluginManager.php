<?php

declare(strict_types=1);

namespace Laminas\ServiceManager;

use Laminas\ServiceManager\Exception\InvalidServiceException;

use function get_debug_type;
use function sprintf;

/**
 * Abstract PluginManagerInterface implementation providing plugin validation.
 * Implementations define the `$instanceOf` property to indicate what class types constitute valid plugins, omitting the
 *   requirement to define the `validate()` method.
 *
 * @template InstanceType of object
 * @template-extends AbstractPluginManager<InstanceType>
 */
abstract class AbstractSingleInstancePluginManager extends AbstractPluginManager
{
    /**
     * An object type that the created instance must be instanced of
     *
     * @var class-string<InstanceType>
     */
    protected string $instanceOf;

    /**
     * {@inheritDoc}
     */
    public function validate(mixed $instance): void
    {
        if ($instance instanceof $this->instanceOf) {
            return;
        }

        throw new InvalidServiceException(sprintf(
            'Plugin manager "%s" expected an instance of type "%s", but "%s" was received',
            static::class,
            $this->instanceOf,
            get_debug_type($instance)
        ));
    }
}
