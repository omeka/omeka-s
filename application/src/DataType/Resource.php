<?php
namespace Omeka\DataType;

use Omeka\Api\Exception;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use Zend\View\Renderer\PhpRenderer;

class Resource extends AbstractDataType
{
    public function getLabel()
    {
        return 'Resource';
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

    public function hydrate(array $valueObject, Value $value)
    {
        $value->setType('resource');
        $value->setValue(null); // set default
        $value->setLang(null); // set default
        $value->setUriLabel(null); // set default
        $valueResource = $this->getServiceLocator()->get('Omeka\EntityManager')->find(
            'Omeka\Entity\Resource',
            $valueObject['value_resource_id']
        );
        if (null === $valueResource) {
            throw new Exception\NotFoundException(sprintf(
                $this->getServiceLocator()->get('MvcTranslator')->translate(
                    'Resource not found with id %s.'),
                    $valueObject['value_resource_id']
                )
            );
        }
        if ($valueResource instanceof Media) {
            $exception = new Exception\ValidationException;
            $exception->getErrorStore()->addError(
                'value', $this->getServiceLocator()->get('MvcTranslator')
                    ->translate('A value resource cannot be Media.')
            );
            throw $exception;
        }
        $value->setValueResource($valueResource);
    }

    public function getTemplate(PhpRenderer $view)
    {
        return $view->partial('common/data-type/resource');
    }

    public function getHtml(PhpRenderer $view, ValueRepresentation $value)
    {
        $valueResource = $value->valueResource();
        return $valueResource->link($valueResource->displayTitle());
    }

    public function toString(ValueRepresentation $value)
    {
        return $value->valueResource()->url(null, true);
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        return $value->valueResource()->valueRepresentation();
    }
}
