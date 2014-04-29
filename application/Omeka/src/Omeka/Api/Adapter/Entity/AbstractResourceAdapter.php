<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Omeka\Model\Entity\Resource;
use Omeka\Model\Entity\Value;

/**
 * Abstract resource entity API adapter.
 *
 * Provides extra functionality for extracting and processing values for
 * entities that utilize Omeka's RDF data model (i.e. those that extend
 * \Omeka\Model\Entity\Resource).
 */
abstract class AbstractResourceAdapter extends AbstractEntityAdapter
{
    const VALUE_TYPE_RESOURCE = 'http://omeka.org/omeka_s/ns#valueTypeResource';
    const VALUE_TYPE_URI = 'http://omeka.org/omeka_s/ns#valueTypeUri';

    public function extractValues(Resource $resource)
    {
        $valueObjects = array();
        foreach ($resource->getValues() as $value) {

            $valueObject = array();
            switch ($value->getType()) {
                case Value::TYPE_RESOURCE:
                    $valueResource = $value->getValueResource();
                    $valueObject['@value'] = $this->getApiUrl($valueResource);
                    $valueObject['@type'] = self::VALUE_TYPE_RESOURCE;
                    $valueObject['resource_id'] = $valueResource->getId();
                    $valueObject['value_id'] = $value->getId();
                    break;
                case Value::TYPE_URI:
                    $valueObject['@value'] = $value->getValue();
                    $valueObject['@type'] = self::VALUE_TYPE_URI;
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

            $property = $value->getProperty();
            $vocabulary = $property->getVocabulary();

            $prefix = 'vocab' . $vocabulary->getId();
            $suffix = $property->getLocalName();
            $term = "$prefix:$suffix";

            $valueObjects[$term][] = $valueObject;
        }

        return $valueObjects;
    }
}
