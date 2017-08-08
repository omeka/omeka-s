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
        $dataRemove = $this->getArg('data_remove', []);
        $dataAppend = $this->getArg('data_append', []);

        $response = $api->search($resource, $query, ['returnScalar' => 'id']);

        // Batch update the resources in chunks.
        foreach (array_chunk($response->getContent(), 100) as $idsChunk) {
            if ($this->shouldStop()) {
                return;
            }
            if ($data) {
                $api->batchUpdate($resource, $idsChunk, $data, [
                    'continueOnError' => true,
                ]);
            }
            if ($dataRemove) {
                $api->batchUpdate($resource, $idsChunk, $dataRemove, [
                    'continueOnError' => true,
                    'collectionAction' => 'remove',
                ]);
            }
            if ($dataAppend) {
                $api->batchUpdate($resource, $idsChunk, $dataAppend, [
                    'continueOnError' => true,
                    'collectionAction' => 'append',
                ]);
            }
        }
    }
}
