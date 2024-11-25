<?php
namespace Omeka\Job;

class BatchDelete extends AbstractJob
{
    public function perform()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $resource = $this->getArg('resource');
        $query = $this->getArg('query', []);
        $response = $api->search($resource, $query, ['returnScalar' => 'id']);

        // Batch delete the resources in chunks.
        $batchChunkSize = $settings->get('batch_chunk_size', 100);
        foreach (array_chunk($response->getContent(), $batchChunkSize) as $idsChunk) {
            if ($this->shouldStop()) {
                return;
            }
            $api->batchDelete($resource, $idsChunk, [], ['continueOnError' => true]);
        }
    }
}
