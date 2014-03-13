<?php
namespace Omeka\Installation\Task;

/**
 * Install default vocabulary task.
 */
class InstallDefaultVocabularyTask extends AbstractTask
{
    /**
     * @var array
     */
    protected $vocabulary = array(
        'namespace_uri' => 'omeka',
        'label'         => 'Omeka',
        'comment'       => 'The default Omeka vocabulary containing custom classes and properties.',
    );

    /**
     * Install default vocabulary.
     */
    public function perform()
    {
        $api = $this->getServiceLocator()->get('ApiManager');
        $response = $api->create('vocabularies', $this->vocabulary);
        if ($response->isError()) {
            $this->addErrorStore($response->getErrorStore());
            return;
        }
        $this->addInfo('Successfully installed the default Omeka vocabulary');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Install the default Omeka vocabulary';
    }

}
