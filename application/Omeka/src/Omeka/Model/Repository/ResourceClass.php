<?php
namespace Omeka\Model\Repository;

use Omeka\Model\Entity\ResourceClass as ResourceClassEntity;

class ResourceClass extends AbstractRepository
{
    /**
     * @var Omeka\Model\Entity\ResourceClass rdfs:Resource entity cache
     */
    protected $rdfsResource;

    /**
     * Get the rdfs:Resource resource class entity.
     *
     * @return ResourceClassEntity
     */
    public function getRdfsResource()
    {
        if (!$this->rdfsResource instanceof ResourceClassEntity) {
            $dql = "
            SELECT rc
            FROM Omeka\Model\Entity\ResourceClass rc
            JOIN rc.vocabulary v
            WHERE v.prefix = 'rdfs'
            AND rc.localName = 'Resource'";
            $this->rdfsResource = $this->getEntityManager()
                ->createQuery($dql)
                ->getSingleResult();
        }
        return $this->rdfsResource;
    }
}
