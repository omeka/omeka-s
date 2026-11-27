<?php
namespace Omeka\Stdlib;

use Doctrine\DBAL\ParameterType;
use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Adapter\FulltextSearchableInterface;
use Omeka\Api\ResourceInterface;
use Omeka\Service\ConnectionFactory;

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

        if (ConnectionFactory::isSqlite($this->conn)) {
            $sql = 'INSERT INTO `fulltext_search` (
                `id`, `resource`, `owner_id`, `is_public`, `title`, `text`
            ) VALUES (
                :id, :resource, :owner_id, :is_public, :title, :text
            ) ON CONFLICT(`id`, `resource`) DO UPDATE SET
                `owner_id` = :owner_id, `is_public` = :is_public, `title` = :title, `text` = :text';
        } else {
            $sql = 'INSERT INTO `fulltext_search` (
                `id`, `resource`, `owner_id`, `is_public`, `title`, `text`
            ) VALUES (
                :id, :resource, :owner_id, :is_public, :title, :text
            ) ON DUPLICATE KEY UPDATE
                `owner_id` = :owner_id, `is_public` = :is_public, `title` = :title, `text` = :text';
        }
        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue('id', $resourceId, ParameterType::INTEGER);
        $stmt->bindValue('resource', $resourceName, ParameterType::STRING);
        $stmt->bindValue('owner_id', $ownerId, ParameterType::INTEGER);
        $stmt->bindValue('is_public', $adapter->getFulltextIsPublic($resource), ParameterType::BOOLEAN);
        $stmt->bindValue('title', $adapter->getFulltextTitle($resource), ParameterType::STRING);
        $stmt->bindValue('text', $adapter->getFulltextText($resource), ParameterType::STRING);
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

        $stmt->bindValue('id', $resourceId, ParameterType::INTEGER);
        $stmt->bindValue('resource', $resourceName, ParameterType::STRING);
        $stmt->executeStatement();
    }
}
