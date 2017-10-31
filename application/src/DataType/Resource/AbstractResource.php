<?php
namespace Omeka\DataType\Resource;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Exception;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\DataType\AbstractDataType;
use Omeka\Entity\Value;
use Zend\View\Renderer\PhpRenderer;
use Omeka\Stdlib\Message;

abstract class AbstractResource extends AbstractDataType
{
    public function getOptgroupLabel()
    {
        return 'Resource'; // @translate
    }

    public function form(PhpRenderer $view)
    {
        return $view->partial('common/data-type/resource', [
            'dataType' => $this->getName(),
            'resource' => $view->resource,
        ]);
    }

    public function isValid(array $valueObject)
    {
        if (isset($valueObject['value_resource_id'])
            && is_numeric($valueObject['value_resource_id'])
        ) {
            return true;
        }
        return false;
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        $serviceLocator = $adapter->getServiceLocator();

        $value->setValue(null); // set default
        $value->setLang(null); // set default
        $value->setUri(null); // set default
        $valueResource = $serviceLocator->get('Omeka\EntityManager')->find(
            'Omeka\Entity\Resource',
            $valueObject['value_resource_id']
        );
        if (null === $valueResource) {
            throw new Exception\NotFoundException(sprintf(
                $serviceLocator->get('MvcTranslator')->translate(
                    'Resource not found with id %s.'),
                    $valueObject['value_resource_id']
                )
            );
        }
        if ($valueResource instanceof Media) {
            $exception = new Exception\ValidationException;
            $message = new Message(
                'A value resource cannot be Media.' // @translate
                );
            $exception->getErrorStore()->addError(
                'value', $message
            );
            throw $exception;
        }
        $value->setValueResource($valueResource);
    }

    public function render(PhpRenderer $view, ValueRepresentation $value)
    {
        return $value->valueResource()->linkPretty();
    }

    public function toString(ValueRepresentation $value)
    {
        return (string) $value->valueResource()->url(null, true);
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        return $value->valueResource()->valueRepresentation();
    }
}
