<?php
namespace Omeka\Api\Representation;

class ValueAnnotationRepresentation extends AbstractResourceEntityRepresentation
{
    public function getResourceJsonLdType()
    {
        return ['o:ValueAnnotation', 'oa:Annotation'];
    }

    public function getResourceJsonLd()
    {
        $value = $this->annotatedValue();
        $valueJson = $value->jsonSerialize();
        // Unset @annotation to avoid a circular reference back to this object.
        unset($valueJson['@annotation']);
        return [
            'oa:hasTarget' => [
                '@type' => 'rdf:Statement',
                'rdf:subject' => $this->resource()->getReference(),
                'rdf:predicate' => ['@id' => $value->property()->uri()],
                'rdf:object' => $valueJson,
            ],
        ];
    }

    public function annotatedValue()
    {
        return new ValueRepresentation($this->resource->getValue(), $this->getServiceLocator());
    }

    public function resource()
    {
        return $this->getAdapter('resources')->getRepresentation($this->resource->getValue()->getResource());
    }

    public function displayValues(array $options = [])
    {
        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs(['values' => $this->values()]);
        $eventManager->trigger('rep.resource.value_annotation_display_values', $this, $args);
        $options['values'] = $args['values'];

        $partial = $this->getViewHelper('partial');
        return $partial('common/value-annotation-resource-values', $options);
    }
}
