<?php
namespace Omeka\Job;

use Omeka\Api\Adapter\FulltextSearchableInterface;
use Omeka\Job\Exception;

class UpdateSiteItems extends AbstractJob
{
    /**
     * Update site-item associations for one or more site.
     */
    public function perform()
    {
        $siteIds = $this->getArg('site_ids');
        $replace = $this->getArg('replace', false);
        if (!is_array($siteIds)) {
            throw new Exception\InvalidArgumentException('No site_ids array passed to the UpdateSiteItems job');
        }

        // Grant "view-all" privileges to include private items. We need this
        // for situations when the Job has no owner, like during a migration.
        $this->getServiceLocator()->get('Omeka\Acl')->allow('view-all');

        foreach ($siteIds as $siteId) {
            $this->updateSiteItems($siteId, $replace);
        }
    }

    /**
     * Update site-item associations for one site.
     *
     * There are two strategies for updating site/items: add and replace. The
     * "add" strategy simply adds associations that don't already exist in the
     * item pool (it is default because it is non-destructive). The "replace"
     * strategy deletes all existing associations and syncs the associations
     * with the configured item pool. To replace, set $replace to true.
     *
     * @param int $siteId
     * @param bool $replace
     */
    protected function updateSiteItems(int $siteId, bool $replace = false) : void
    {
        $services = $this->getServiceLocator();
        $conn = $services->get('Omeka\Connection');
        $api = $services->get('Omeka\ApiManager');

        $itemPool = $conn->fetchColumn('SELECT item_pool FROM site WHERE id = ?', [$siteId], 0);
        if (false === $itemPool) {
            throw new Exception\InvalidArgumentException(sprintf('Invalid site ID "%s" passed to the UpdateSiteItems job', $siteId));
        }
        $itemIds = $api->search('items', json_decode($itemPool, true), ['returnScalar' => 'id'])->getContent();

        if ($replace) {
            $conn->delete('item_site', ['site_id' => $siteId]);
        }

        // Chunk to avoid query/buffer/packet size limits.
        foreach (array_chunk($itemIds, 1000) as $itemIdsChunk) {
            $values = [];
            $bindValues = [];
            foreach ($itemIdsChunk as $itemId) {
                $values[] = '(?, ?)';
                $bindValues[] = $itemId;
                $bindValues[] = $siteId;
            }
            $sql = sprintf('INSERT IGNORE INTO item_site (item_id, site_id) VALUES %s', implode(',', $values));
            $stmt = $conn->prepare($sql);
            foreach ($bindValues as $position => $value) {
                $stmt->bindValue($position + 1, $value);
            }
            $stmt->execute();
        }
    }
}
