<?php
namespace Omeka\Api\Representation;

class ResourceClassRepresentation extends AbstractVocabularyMemberRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getControllerName()
    {
        return 'resource-class';
    }

    /**
     * Get the resource count of this resource class.
     *
     * @return int
     */
    public function itemCount()
    {
        $response = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('items', array('resource_class_id' => $this->id()));
        return $response->getTotalResults();
    }
}
