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
        'o:label'         => 'Custom Vocabulary',
        'o:comment'       => 'Custom classes and properties',
    );

    public function perform(Manager $manager)
    {
        $api = $manager->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->create('vocabularies', $this->vocabulary);
        if ($response->isError()) {
            $manager->addErrorStore($response->getErrorStore());
            return;
        }
    }
}
