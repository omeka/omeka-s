<?php
namespace Omeka\Api\Representation;

class ValueAnnotationRepresentation extends AbstractResourceEntityRepresentation
{
    public function getResourceJsonLdType()
    {
        return 'o:ValueAnnotation';
    }

    public function getResourceJsonLd()
    {
        return [];
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
