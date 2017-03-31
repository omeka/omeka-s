<?php
namespace Omeka\Job;

class BatchUpdate extends AbstractJob
{
    public function perform()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $resource = $this->getArg('resource');

        $response = $api->search(
            $resource, $this->getArg('query', []), ['returnScalar' => 'id']
        );

        // Batch update the resources in chunks.
        foreach (array_chunk($response->getContent(), 100) as $idsChunk) {
            $response = $api->batchUpdate(
                $resource, $idsChunk, $this->getArg('data', []), ['continueOnError' => true]
            );
        }
    }
}
