<?php
namespace Omeka\Install;

use Omeka\Install\TaskAbstract;
use Omeka\Install\TaskInterface;
use Doctrine\ORM\EntityManager;
use Omeka\Model\Entity\User;

class TaskCreateUserOne extends TaskAbstract implements TaskInterface
{
    protected $taskName = 'Create User One';
    
    public function perform()
    {
        $em = $this->getServiceLocator()->get('EntityManager');
        $user = new User;
        $user->setUsername('userone');
        $em->persist($user);
        $em->flush();
        return true;
    }
}