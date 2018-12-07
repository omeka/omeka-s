<?php
namespace Omeka\Job;

class BatchUpdate extends AbstractJob
{
    public function perform()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');

        $resource = $this->getArg('resource');
        $query = $this->getArg('query', []);
        $data = $this->getArg('data', []);

        $response = $api->search($resource, $query, ['returnScalar' => 'id']);

        // Batch update the resources in chunks.
        foreach (array_chunk($response->getContent(), 100) as $idsChunk) {
            if ($this->shouldStop()) {
                return;
            }
            foreach ($data as $collectionAction => $properties) {
                $api->batchUpdate('items', $idsChunk, $properties, [
                    'continueOnError' => true,
                    'collectionAction' => $collectionAction,
                ]);
            }
        }
    }
}
