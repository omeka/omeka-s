<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Manager;

/**
 * Create the first user.
 */
class CreateFirstUserTask implements TaskInterface
{
    public function perform(Manager $manager)
    {
        $apiManager = $manager->getServiceLocator()->get('Omeka\ApiManager');
        $entityManager = $manager->getServiceLocator()->get('Omeka\EntityManager');

        $vars = $manager->getVars('Omeka\Installation\Manager\CreateFirstUserTask');
        $response = $apiManager->create('users', array(
            'o:role'     => 'global_admin',
            'o:username' => $vars['username'],
            'o:name'     => $vars['name'],
            'o:email'    => $vars['email'],
        ));
        if ($response->isError()) {
            $manager->addErrorStore($response->getErrorStore());
            return;
        }

        // Set the password.
        $user = $response->getContent()->jsonSerialize();
        $userEntity = $entityManager->find('Omeka\Model\Entity\User', $user['o:id']);
        $userEntity->setPassword($vars['password']);
        $entityManager->flush();
    }
}
