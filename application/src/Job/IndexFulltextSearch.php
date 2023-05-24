<?php
namespace Omeka\Job;

use Omeka\Api\Adapter\FulltextSearchableInterface;
use Omeka\Api\Adapter\ResourceAdapter;
use Omeka\Api\Adapter\ValueAnnotationAdapter;

class IndexFulltextSearch extends AbstractJob
{
    /**
     * Build the fulltext index for compatible resources.
     */
    public function perform()
    {
        $services = $this->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');
        $em = $services->get('Omeka\EntityManager');
        $conn = $services->get('Omeka\Connection');
        $fulltext = $services->get('Omeka\FulltextSearch');
        $adapters = $services->get('Omeka\ApiAdapterManager');

        // First delete all rows from the fulltext table to clear out the
        // resources that don't belong.
        $conn->executeStatement('DELETE FROM `fulltext_search`');

        // Then iterate through all resource types and index the ones that are
        // fulltext searchable. Note that we don't index "resource" and "value
        // annotation" resources.
        foreach ($adapters->getRegisteredNames() as $adapterName) {
            $adapter = $adapters->get($adapterName);
            if ($adapter instanceof FulltextSearchableInterface
                && !($adapter instanceof ResourceAdapter)
                && !($adapter instanceof ValueAnnotationAdapter)
            ) {
                $page = 1;
                do {
                    if ($this->shouldStop()) {
                        return;
                    }
                    $response = $api->search(
                        $adapter->getResourceName(),
                        ['page' => $page, 'per_page' => 100],
                        ['responseContent' => 'resource']
                    );
                    foreach ($response->getContent() as $resource) {
                        $fulltext->save($resource, $adapter);
                    }
                    $em->clear(); // avoid a memory leak
                    $page++;
                } while ($response->getContent());
            }
        }
    }
}
