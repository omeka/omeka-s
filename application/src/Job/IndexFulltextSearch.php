<?php
namespace Omeka\Job;

use Omeka\Api\Adapter\FulltextSearchableInterface;

class IndexFulltextSearch extends AbstractJob
{
    public function perform()
    {
        $services = $this->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');
        $adapters = $services->get('Omeka\ApiAdapterManager');
        foreach ($adapters->getRegisteredNames() as $adapterName) {
            $adapter = $adapters->get($adapterName);
            if ($adapter instanceof FulltextSearchableInterface) {
                // Run Module::saveFulltext() on every resource of this type.
                $response = $api->search($adapter->getResourceName(), [], ['returnScalar' => 'id']);
                foreach (array_chunk($response->getContent(), 100) as $idsChunk) {
                    if ($this->shouldStop()) {
                        return;
                    }
                    $api->batchUpdate($adapter->getResourceName(), $idsChunk, []);
                }
            }
        }
    }
}