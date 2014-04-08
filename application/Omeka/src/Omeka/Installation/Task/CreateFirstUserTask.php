<?php
namespace Omeka\Installation\Task;

/**
 * Create the first user.
 */
class CreateFirstUserTask extends AbstractTask
{
    /**
     * Create the first user.
     */
    public function perform()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->create('users', array(
            'role'     => 'global_admin',
            'username' => $this->getVar('username'),
            'name'     => $this->getVar('name'),
            'email'    => $this->getVar('email'),
        ));
        if ($response->isError()) {
            $this->addErrorStore($response->getErrorStore());
            return;
        }
        // Set the password.
        $user = $response->getContent();
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $userEntity = $em->find('Omeka\Model\Entity\User', $user['id']);
        $userEntity->setPassword($this->getVar('password'));
        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getTranslator()->translate('Create the first user');
    }
}
