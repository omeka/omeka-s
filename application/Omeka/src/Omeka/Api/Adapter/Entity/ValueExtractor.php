<?php
namespace Omeka\Api\Adapter\Entity;

use Omeka\Model\Entity\Resource;
use Omeka\Model\Entity\Value;

class ValueExtractor
{
    /**
     * @var AbstractEntityAdapter
     */
    protected $adapter;

    /**
     * @param AbstractEntityAdapter $adapter
     */
    public function __construct(AbstractEntityAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Extract all values of a resource.
     *
     * @param Resource $resource
     * @return array JSON-LD formatted
     */
    public function extract(Resource $resource)
    {
        $context = array();
        $valueObjects = array();
        foreach ($resource->getValues() as $value) {
            $valueObject = $this->extractValue($value);

            $property = $value->getProperty();
            $vocabulary = $property->getVocabulary();

            $prefix = 'vocab' . $vocabulary->getId();
            $suffix = $property->getLocalName();
            $term = "$prefix:$suffix";
            if (!array_key_exists($prefix, $context)) {
                $context[$prefix] = $vocabulary->getNamespaceUri();
            }

            $valueObjects[$term][] = $valueObject;
        }
        $valueObjects['@context'] = $context;
        return $valueObjects;
    }

    /**
     * Extract a value.
     *
     * @param Value $value
     * @return array JSON-LD formatted
     */
    public function extractValue(Value $value)
    {
        $valueObject = array();
        switch ($value->getType()) {
            case Value::TYPE_RESOURCE:
                $valueResource = $value->getValueResource();
                $valueObject['@id'] = $this->adapter->getApiUrl($valueResource);
                $valueObject['value_resource_id'] = $valueResource->getId();
                $valueObject['value_id'] = $value->getId();
                break;
            case Value::TYPE_URI:
                $valueObject['@id'] = $value->getValue();
                $valueObject['value_id'] = $value->getId();
                break;
            case Value::TYPE_LITERAL:
            default:
                $valueObject['@value'] = $value->getValue();
                if ($value->getLang()) {
                    $valueObject['@language'] = $value->getLang();
                }
                $valueObject['is_html'] = $value->getIsHtml();
                $valueObject['value_id'] = $value->getId();
                break;
        }
        return $valueObject;
    }
}
