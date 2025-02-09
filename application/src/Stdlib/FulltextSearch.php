<?php
namespace Omeka\Stdlib;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Adapter\FulltextSearchableInterface;
use Omeka\Api\ResourceInterface;
use PDO;

class FulltextSearch
{
    protected $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
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
        $resourceId = $resource->getId();
        $resourceName = $adapter->getResourceName();
        $owner = $adapter->getFulltextOwner($resource);
        $ownerId = $owner ? $owner->getId() : null;

        $sql = 'INSERT INTO `fulltext_search` (
            `id`, `resource`, `owner_id`, `is_public`, `title`, `text`
        ) VALUES (
            :id, :resource, :owner_id, :is_public, :title, :text
        ) ON DUPLICATE KEY UPDATE
            `owner_id` = :owner_id, `is_public` = :is_public, `title` = :title, `text` = :text';
        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue('id', $resourceId, PDO::PARAM_INT);
        $stmt->bindValue('resource', $resourceName, PDO::PARAM_STR);
        $stmt->bindValue('owner_id', $ownerId, PDO::PARAM_INT);
        $stmt->bindValue('is_public', $adapter->getFulltextIsPublic($resource), PDO::PARAM_BOOL);
        $stmt->bindValue('title', $adapter->getFulltextTitle($resource), PDO::PARAM_STR);
        $stmt->bindValue('text', $adapter->getFulltextText($resource), PDO::PARAM_STR);
        $stmt->executeStatement();
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
        $resourceName = $adapter->getResourceName();

        $sql = 'DELETE FROM `fulltext_search` WHERE `id` = :id AND `resource` = :resource';
        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue('id', $resourceId, PDO::PARAM_INT);
        $stmt->bindValue('resource', $resourceName, PDO::PARAM_STR);
        $stmt->executeStatement();
    }
}
