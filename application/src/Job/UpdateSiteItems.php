<?php
namespace Omeka\Job;

use Omeka\Job\Exception\InvalidArgumentException;

/**
 * Update site-item associations for one or more site.
 *
 * This job requires an array of site IDs set to the "site_ids" argument, and an
 * update action set to the "action" argument.
 *
 * There are three update actions: add, replace, and remove_all. The "add"
 * action simply adds items that exist in the configured item pool but aren't
 * already assigned. The "replace" action deletes all existing assignments and
 * syncs the assignments with the configured item pool. The "remove_all" action
 * deletes all existing assignments.
 *
 */
class UpdateSiteItems extends AbstractJob
{
    /**
     * @var Valid actions
     */
    protected $actions = ['add', 'replace', 'remove_all'];

    public function perform()
    {
        $siteIds = $this->getArg('site_ids');
        if (!is_array($siteIds)) {
            throw new InvalidArgumentException('No "site_ids" array passed to the UpdateSiteItems job');
        }

        $action = $this->getArg('action');
        if (!is_string($action)) {
            throw new InvalidArgumentException('No "action" string passed to the UpdateSiteItems job');
        }
        if (!in_array($action, $this->actions)) {
            throw new InvalidArgumentException(sprintf('Invalid "action" string "%s" passed to the UpdateSiteItems job', $action));
        }

        // Grant "view-all" privileges to include private items. We need this
        // for situations when the Job has no owner, like during a migration.
        $this->getServiceLocator()->get('Omeka\Acl')->allow(null, 'Omeka\Entity\Resource', 'view-all');

        foreach ($siteIds as $siteId) {
            $this->updateSiteItems($siteId, $action);
        }
    }

    /**
     * Update site-item associations for one site.
     *
     * @param int $siteId
     * @param string $action
     */
    protected function updateSiteItems(int $siteId, string $action) : void
    {
        $services = $this->getServiceLocator();
        $conn = $services->get('Omeka\Connection');
        $api = $services->get('Omeka\ApiManager');

        $itemPool = $conn->fetchColumn('SELECT item_pool FROM site WHERE id = ?', [$siteId], 0);
        if (false === $itemPool) {
            throw new InvalidArgumentException(sprintf('Invalid site ID "%s" passed to the UpdateSiteItems job', $siteId));
        }
        $itemIds = $api->search('items', json_decode($itemPool, true), ['returnScalar' => 'id'])->getContent();

        if (in_array($action, ['replace', 'remove_all'])) {
            $conn->delete('item_site', ['site_id' => $siteId]);
        }

        if (in_array($action, ['add', 'replace'])) {
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
}
