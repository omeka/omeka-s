<?php
namespace Omeka\DataType\Resource;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Exception;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\DataType\DataTypeWithOptionsInterface;
use Omeka\Entity;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Stdlib\Message;

abstract class AbstractResource implements DataTypeWithOptionsInterface
{
    /**
     * Get the class names of valid value resources.
     *
     * @return array
     */
    abstract public function getValidValueResources();

    public function getOptgroupLabel()
    {
        return 'Resource'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
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

    public function hydrate(array $valueObject, Entity\Value $value, AbstractEntityAdapter $adapter)
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
        // Limit value resources to those that are valid for the data type.
        $isValid = false;
        foreach ($this->getValidValueResources() as $validValueResource) {
            if ($valueResource instanceof $validValueResource) {
                $isValid = true;
                break;
            }
        }
        if (!$isValid) {
            $message = new Message(sprintf(
                'Invalid value resource %s for type %s', // @translate
                get_class($valueResource),
                $valueObject['type']
            ));
            $exception = new Exception\ValidationException;
            $exception->getErrorStore()->addError('value', $message);
            throw $exception;
        }
        $value->setValueResource($valueResource);
    }

    public function render(PhpRenderer $view, ValueRepresentation $value, $options = [])
    {
        return $value->valueResource()->linkPretty('square', null, null, null, is_array($options) ? $options['lang'] ?? [] : $options);
    }

    public function toString(ValueRepresentation $value)
    {
        return (string) $value->valueResource()->url(null, true);
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        return $value->valueResource()->valueRepresentation();
    }

    public function getFulltextText(PhpRenderer $view, ValueRepresentation $value)
    {
        return $value->valueResource()->title();
    }
}
