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
        /** @var \Doctrine\ORM\EntityManager $em */
        $services = $this->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');
        $em = $services->get('Omeka\EntityManager');
        $fulltext = $services->get('Omeka\FulltextSearch');
        $adapters = $services->get('Omeka\ApiAdapterManager');

        // First loop to determine the total steps.
        $registeredNames = $adapters->getRegisteredNames();
        $totalSteps = 0;
        foreach ($registeredNames as $key => $adapterName) {
            $adapter = $adapters->get($adapterName);
            if ($adapter instanceof FulltextSearchableInterface) {
                $totalSteps += $api->search($adapter->getResourceName(), [], ['initialize' => false, 'finalize' => false])->getTotalResults();
            } else {
                unset($registeredNames[$key]);
            }
        }
        $this->setTotalSteps($totalSteps);

        $jobId = $this->job->getId();

        // Process indexation.
        foreach ($registeredNames as $adapterName) {
            $adapter = $adapters->get($adapterName);
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
                $resources = $response->getContent();
                foreach ($resources as $resource) {
                    $fulltext->save($resource, $adapter);
                }
                $this->addStep(count($resources));
                // Avoid a memory leak, but require to save and reload job data
                // to keep the log.
                $em->flush($this->job);
                $em->clear();
                // The internal logger doesn't work anymore after em->clear().
                $this->job = $em->find(\Omeka\Entity\Job::class, $jobId);
                ++$page;
            } while (count($resources));
        }
    }
}
