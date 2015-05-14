<?php
namespace Omeka\Api\Representation;

class PropertyRepresentation extends AbstractVocabularyMemberRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getControllerName()
    {
        return 'property';
    }

    /**
     * Get the resource count of this property.
     *
     * @return int
     */
    public function itemCount()
    {
        $response = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('items',array('has_property' => array($this->id() => true)));
        return $response->getTotalResults();
    }
}
