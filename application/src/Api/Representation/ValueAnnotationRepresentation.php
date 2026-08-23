<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Adapter\ValueAnnotationAdapter;
use Omeka\Entity\ValueAnnotation;

class ValueAnnotationRepresentation extends AbstractResourceEntityRepresentation
{
    /**
     * @var \Omeka\Api\Representation\ValueRepresentation
     */
    protected $value;

    /**
     * Construct the value annotation representation object.
     */
    public function __construct(
        ValueAnnotation $valueAnnotation,
        ValueAnnotationAdapter $adapter,
        ValueRepresentation $value
    ) {
        parent::__construct($valueAnnotation, $adapter);
        $this->value = $value;
    }

    public function getResourceJsonLdType()
    {
        return 'o:ValueAnnotation';
    }

    public function getResourceJsonLd()
    {
        return [];
    }

    /**
     * Get the resource representation.
     */
    public function resource(): AbstractResourceEntityRepresentation
    {
        return $this->value->resource();
    }

    /**
     * Get the value representation.
     */
    public function resourceValue(): ValueRepresentation
    {
        return $this->value;
    }

    public function displayValues(array $options = [])
    {
        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs(['values' => $this->values()]);
        $eventManager->trigger('rep.resource.value_annotation_display_values', $this, $args);
        $options['resource'] = $this->resource();
        $options['valueAnnotation'] = $this;
        $options['values'] = $args['values'];
        $options['templateProperties'] = [];

        $partial = $this->getViewHelper('partial');
        return $partial('common/value-annotation-resource-values', $options);
    }
}
