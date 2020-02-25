<?php
namespace Omeka\Job;

use Omeka\Job\Exception\InvalidArgumentException;

/**
 * Update site-item associations for one or more site.
 *
 * The job requires an array of site IDs set to the "site_ids" argument. The job
 * also accepts an optional "replace" argument, which tells the process whether
 * to use the replace strategy (the default is false).
 *
 * There are two strategies for updating: add and replace.
 *
 * The default "add" strategy simply adds items that exist in the configured
 * item pool but don't already exist as associations. "Add" is the default
 * strategy because it is non-destructive, i.e. it doesn't delete existing
 * assiciations.
 *
 * The "replace" strategy deletes all existing associations and syncs the
 * associations with the configured item pool. Because this strategy is
 * destructive, you must expressly set the "replace" argument to true.
 *
 */
class UpdateSiteItems extends AbstractJob
{
    public function perform()
    {
        $siteIds = $this->getArg('site_ids');
        $replace = $this->getArg('replace', false);
        if (!is_array($siteIds)) {
            throw new InvalidArgumentException('No site_ids array passed to the UpdateSiteItems job');
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
            throw new InvalidArgumentException(sprintf('Invalid site ID "%s" passed to the UpdateSiteItems job', $siteId));
        }
        $itemIds = $api->search('items', json_decode($itemPool, true), ['returnScalar' => 'id'])->getContent();

        if ($replace) {
            $conn->delete('item_site', ['site_id' => $siteId]);
        }

        // Chunk item IDs to avoid query/buffer/packet size limits.
        foreach (array_chunk($itemIds, 1000) as $itemIdsChunk) {
            $values = [];
            $bindValues = [];
            foreach ($itemIdsChunk as $itemId) {
                $values[] = '(?, ?)';
                $bindValues[] = $itemId;
                $bindValues[] = $siteId;
            }
            // Note the use of IGNORE here to prevent duplicate-key errors.
            $sql = sprintf('INSERT IGNORE INTO item_site (item_id, site_id) VALUES %s', implode(',', $values));
            $stmt = $conn->prepare($sql);
            foreach ($bindValues as $position => $value) {
                $stmt->bindValue($position + 1, $value);
            }
            $stmt->execute();
        }
    }
}
