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
     * {@inheritDoc}
     */
    public function getJsonLdType()
    {
        return 'o:ResourceClass';
    }

    /**
     * Get the resource count of this resource class.
     *
     * @return int
     */
    public function itemCount()
    {
        $response = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('items', [
                'resource_class_id' => $this->id(),
                'limit' => 0,
            ]);
        return $response->getTotalResults();
    }
}
