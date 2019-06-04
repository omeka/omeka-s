<?php
namespace Omeka\Job;

use Omeka\Api\Adapter\FulltextSearchableInterface;

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
        $fulltext = $services->get('Omeka\FulltextSearch');
        $adapters = $services->get('Omeka\ApiAdapterManager');
        foreach ($adapters->getRegisteredNames() as $adapterName) {
            $adapter = $adapters->get($adapterName);
            if ($adapter instanceof FulltextSearchableInterface) {
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
