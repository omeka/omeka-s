<?php
namespace Omeka\Job;

class BatchUpdate extends AbstractJob
{
    public function perform()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');

        // Get all resource IDs the passed query returns.
        $limit = 100;
        $offset = 0;
        $resourceIds = [];
        do {
            $query = array_merge(
                $this->getArg('query', []),
                ['limit' => $limit, 'offset' => $offset]
            );
            $response = $api->search($this->getArg('resource'), $query);
            foreach ($response->getContent() as $resource) {
                $resourceIds[] = $resource->id();
            }
            $offset = $offset + $limit;
        } while ($response->getTotalCount() > $offset);

        // Batch update the resources in chunks.
        foreach (array_chunk($resourceIds, 100) as $resourceIdsChunk) {
            $response = $api->batchUpdate(
                $this->getArg('resource'),
                $resourceIdsChunk,
                $this->getArg('data', []),
                ['continueOnError' => true]
            );
        }
    }
}
