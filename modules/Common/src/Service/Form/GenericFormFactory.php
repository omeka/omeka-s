<?php declare(strict_types=1);

namespace Common\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * The factory allows to pass options without issue when getForm() is used.
 */
class GenericFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new $requestedName(null, $options ?? []);
    }
}
