<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Manager;

/**
 * Install default vocabulary task.
 */
class InstallDefaultVocabularyTask implements TaskInterface
{
    /**
     * @var array
     */
    protected $vocabulary = array(
        'o:namespace_uri' => 'omeka',
        'o:prefix'        => 'omeka',
        'o:label'         => 'Omeka',
        'o:comment'       => 'Custom classes and properties',
    );

    public function perform(Manager $manager)
    {
        $api = $manager->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->create('vocabularies', $manager->vocabulary);
        if ($response->isError()) {
            $manager->addErrorStore($response->getErrorStore());
            return;
        }
    }
}
