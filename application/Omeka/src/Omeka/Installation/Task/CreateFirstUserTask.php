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
        $apiManager = $this->getServiceLocator()->get('Omeka\ApiManager');
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');

        $response = $apiManager->create('users', array(
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
        $user = $response->getContent()->jsonSerialize();
        $userEntity = $entityManager->find('Omeka\Model\Entity\User', $user['id']);
        $userEntity->setPassword($this->getVar('password'));
        $entityManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getTranslator()->translate('Create the first user');
    }
}
