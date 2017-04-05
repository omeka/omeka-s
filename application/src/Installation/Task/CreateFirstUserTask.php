<?php
namespace Omeka\Installation\Task;

use Omeka\Api\Exception\ValidationException;
use Omeka\Installation\Installer;

/**
 * Create the first user.
 */
class CreateFirstUserTask implements TaskInterface
{
    public function perform(Installer $installer)
    {
        $apiManager = $installer->getServiceLocator()->get('Omeka\ApiManager');
        $entityManager = $installer->getServiceLocator()->get('Omeka\EntityManager');

        $vars = $installer->getVars('Omeka\Installation\Task\CreateFirstUserTask');
        try {
            $response = $apiManager->create('users', [
                'o:is_active' => true,
                'o:role' => 'global_admin',
                'o:name' => $vars['name'],
                'o:email' => $vars['email'],
            ]);
        } catch (ValidationException $e) {
            $installer->addErrorStore($e->getErrorStore());
            return;
        }

        // Set the password.
        $user = $response->getContent()->jsonSerialize();
        $userEntity = $entityManager->find('Omeka\Entity\User', $user['o:id']);
        $userEntity->setPassword($vars['password']);
        $entityManager->flush();
    }
}
