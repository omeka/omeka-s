<?php
namespace Omeka\Job;

class BatchDelete extends AbstractJob
{
    public function perform()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $resource = $this->getArg('resource');
        $query = $this->getArg('query', []);
        $response = $api->search($resource, $query, ['returnScalar' => 'id']);

        // Batch delete the resources in chunks.
        foreach (array_chunk($response->getContent(), 100) as $idsChunk) {
            if ($this->shouldStop()) {
                return;
            }
            $api->batchDelete($resource, $idsChunk, [], ['continueOnError' => true]);
        }
    }
}
