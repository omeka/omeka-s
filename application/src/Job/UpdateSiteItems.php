<?php
namespace Omeka\Job;

use Doctrine\DBAL\Connection;
use Omeka\Job\Exception\InvalidArgumentException;

/**
 * Update item assignments for one or more site.
 *
 * The job accepts two arguments:
 *
 * - sites: An array of item queries keyed by their respective site IDs
 * - action: The update action
 *   - add: Keep existing items and assign the result set
 *   - replace: Unassign all items and assign the result set
 *   - remove: Unassign all items in the result set
 *   - remove_all: Unassign all items
 */
class UpdateSiteItems extends AbstractJob
{
    /**
     * @var Valid actions
     */
    protected $actions = ['add', 'replace', 'remove', 'remove_all'];

    public function perform()
    {
        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');
        $conn = $services->get('Omeka\Connection');

        $action = $this->getArg('action');
        $sites = $this->getArg('sites');

        // Grant "view-all" privileges to include private items. We need this
        // for situations when the Job has no owner, like during a migration.
        $acl->allow(null, 'Omeka\Entity\Resource', 'view-all');

        // Validate the user data.
        if (!is_string($action)) {
            throw new InvalidArgumentException('No "action" string passed to the UpdateSiteItems job');
        }
        if (!in_array($action, $this->actions)) {
            throw new InvalidArgumentException(sprintf('Invalid "action" string "%s" passed to the UpdateSiteItems job', $action));
        }
        if (!is_array($sites)) {
            throw new InvalidArgumentException('No "sites" array passed to the UpdateSiteItems job');
        }
        foreach ($sites as $siteId => $query) {
            if (!is_array($query)) {
                // If the query is not an array, assume an all-inclusive query.
                $sites[$siteId] = [];
            }
            $siteExists = $conn->fetchColumn('SELECT 1 FROM site WHERE id = ?', [$siteId], 0);
            if (false === $siteExists) {
                throw new InvalidArgumentException(sprintf('Invalid site ID "%s" passed to the UpdateSiteItems job', $siteId));
            }
        }

        // Update the site-item assignments.
        foreach ($sites as $siteId => $query) {
            $this->updateSiteItems($siteId, $query, $action);
        }
    }

    /**
     * Update item assignments for one site.
     *
     * Note that we chunk item IDs to avoid query/buffer/packet size limits.
     *
     * @param int $siteId
     * @param array $query
     * @param string $action
     */
    protected function updateSiteItems(int $siteId, array $query, string $action) : void
    {
        $services = $this->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');
        $conn = $services->get('Omeka\Connection');

        $itemIds = $api->search('items', $query, ['returnScalar' => 'id'])->getContent();

        if (in_array($action, ['replace', 'remove_all'])) {
            $conn->delete('item_site', ['site_id' => $siteId]);
        }

        if (in_array($action, ['add', 'replace'])) {
            foreach (array_chunk($itemIds, 1000) as $itemIdsChunk) {
                $values = [];
                $bindValues = [];
                foreach ($itemIdsChunk as $itemId) {
                    $values[] = '(?,?)';
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

        if (in_array($action, ['remove'])) {
            foreach (array_chunk($itemIds, 1000) as $itemIdsChunk) {
                $sql = sprintf('DELETE FROM item_site WHERE site_id = ? AND item_id IN (?)');
                $stmt = $conn->executeQuery($sql, [$siteId, $itemIdsChunk], [null, Connection::PARAM_INT_ARRAY]);
                $stmt->execute();
            }
        }
    }
}
