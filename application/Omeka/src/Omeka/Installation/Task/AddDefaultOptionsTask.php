<?php
namespace Omeka\Installation\Task;

class AddDefaultOptionsTask extends AbstractTask
{
    protected $defaultOptions = array(
        'pagination_per_page' => 25,
    );

    public function perform()
    {
        $options = $this->getServiceLocator()->get('Omeka\Options');
        foreach ($this->defaultOptions as $id => $value) {
            $options->set($id, $value);
        }
    }

    public function getName()
    {
        return $this->getTranslator()->translate('Add default options.');
    }
}
