<?php declare(strict_types=1);

namespace DataTypeRdf\Service\DataType;

use DataTypeRdf\DataType\Html;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class HtmlFactory implements FactoryInterface
{
    /**
     * Create the service for RdfHtml datatype.
     *
     * @return Html
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Html($services->get('Omeka\HtmlPurifier'));
    }
}
