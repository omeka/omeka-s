<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Exception;

use InvalidArgumentException as SplInvalidArgumentException;
use Laminas\ServiceManager\Initializer\InitializerInterface;

use function get_debug_type;
use function sprintf;

/**
 * @inheritDoc
 */
class InvalidArgumentException extends SplInvalidArgumentException implements ExceptionInterface
{
    public static function fromInvalidInitializer(mixed $initializer): self
    {
        return new self(sprintf(
            'An invalid initializer was registered. Expected a callable or an'
            . ' instance of "%s"; received "%s"',
            InitializerInterface::class,
            get_debug_type($initializer)
        ));
    }
}
