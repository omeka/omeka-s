<?php
namespace Omeka\Api\Representation\Entity;

class ItemRepresentation extends AbstractResourceEntityRepresentation
{
    /**
     * @var array Cache of media representations
     */
    protected $media;

    /**
     * {@inheritDoc}
     */
    public function getControllerName()
    {
        return 'item';
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceJsonLd()
    {
        $mediaReferences = array();
        $itemSetReferences = array();
        $item = $this->getData();
        foreach ($item->getMedia() as $media) {
            $mediaReferences[] =  $this->getReference(
                null, $media, $this->getAdapter('media')
            );
        }
        foreach ($item->getItemSets() as $itemSet) {
            $itemSetReferences[] = $this->getReference(
                null, $itemSet, $this->getAdapter('item_sets')
            );
        }
        return array(
            'o:media' => $mediaReferences,
            'o:item_set' => $itemSetReferences,
        );
    }

    /**
     * Get the media associated with this item.
     *
     * @return array Array of MediaRepresentations
     */
    public function media()
    {
        if (isset($this->media)) {
            return $this->media;
        }
        $this->media = array();
        $mediaAdapter = $this->getAdapter('media');
        foreach ($this->getData()->getMedia() as $mediaEntity) {
            $this->media[] = $mediaAdapter->getRepresentation(null, $mediaEntity);
        }
        return $this->media;
    }
}
