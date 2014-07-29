<?php
namespace Omeka\Installation\Task;

/**
 * Task to clear identity from the session.
 */
class ClearSessionTask extends AbstractTask
{
    public function perform()
    {
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $auth->getStorage()->clear();
    }

    public function getName()
    {
        return $this->getTranslator()->translate('Clear identity from the session.');
    }
}
