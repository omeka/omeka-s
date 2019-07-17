<?php
namespace Omeka\Stdlib;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Adapter\FulltextSearchableInterface;
use Omeka\Api\ResourceInterface;
use Omeka\Entity\FulltextSearch as FulltextSearchEntity;

class FulltextSearch
{
    protected $em;

    public function __construct($em)
    {
        $this->em = $em;
    }

    /**
     * Save the fulltext of an API resource.
     *
     * @param ResourceInterface $resource
     * @param AdapterInterface $adapter
     */
    public function save(ResourceInterface $resource, AdapterInterface $adapter)
    {
        if (!($adapter instanceof FulltextSearchableInterface)) {
            return;
        }
        $searchId = $resource->getId();
        $searchResource = $adapter->getResourceName();
        $search = $this->em->find(
            'Omeka\Entity\FulltextSearch',
            ['id' => $searchId, 'resource' => $searchResource]
        );
        if (!$search) {
            $search = new FulltextSearchEntity($searchId, $searchResource);
            $this->em->persist($search);
        }
        $search->setOwner($adapter->getFulltextOwner($resource));
        $search->setIsPublic($adapter->getFulltextIsPublic($resource));
        $search->setTitle($adapter->getFulltextTitle($resource));
        $search->setText($adapter->getFulltextText($resource));
        $this->em->flush($search);
    }

    /**
     * Delete the fulltext of an API resource.
     *
     * @param int $resourceId
     * @param AdapterInterface $adapter
     */
    public function delete($resourceId, AdapterInterface $adapter)
    {
        if (!($adapter instanceof FulltextSearchableInterface)) {
            return;
        }
        $searchResource = $adapter->getResourceName();
        $search = $this->em->find(
            'Omeka\Entity\FulltextSearch',
            ['id' => $resourceId, 'resource' => $searchResource]
        );
        if ($search) {
            $this->em->remove($search);
            $this->em->flush($search);
        }
    }
}
