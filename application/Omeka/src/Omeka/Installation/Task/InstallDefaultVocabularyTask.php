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
        'o:namespace_uri' => 'omeka',
        'o:prefix'        => 'omeka',
        'o:label'         => 'Omeka',
        'o:comment'       => 'The default Omeka vocabulary containing custom classes and properties.',
    );

    /**
     * Install default vocabulary.
     */
    public function perform()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->create('vocabularies', $this->vocabulary);
        if ($response->isError()) {
            $this->addErrorStore($response->getErrorStore());
            return;
        }
        $this->addInfo(
            $this->getTranslator()->translate('Successfully installed the default Omeka vocabulary')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getTranslator()->translate('Install the default Omeka vocabulary');
    }

}
