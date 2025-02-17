<?php declare(strict_types=1);

namespace Common\Service\Stdlib;

use Common\Stdlib\EasyMeta;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class EasyMetaFactory implements FactoryInterface
{
    /**
     * Create the EasyMeta service.
     *
     * @return EasyMeta
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new EasyMeta(
            $services->get('Omeka\Connection'),
            $services->get('Omeka\DataTypeManager')
        );
    }
}
