<?php
namespace Omeka\Install\Task;

use Omeka\Install\Task\AbstractTask;
use Omeka\Install\Task\TaskInterface;
use Doctrine\ORM\EntityManager;
use Omeka\Model\Entity\User;

/**
 * Task to install the first user
 * @author patrickmj
 *
 */
class UserOne extends AbstractTask implements TaskInterface
{
    protected $taskName = 'Create User One';
    
    public function perform()
    {
        $em = $this->getServiceLocator()->get('EntityManager');
        $user = new User;
        $user->setUsername('userone');
        $em->persist($user);
        try {
            $em->flush();
            $this->result->setSuccess(true);
        } catch(\Exception $e) {
            $this->result->addMessage($e->getMessage());
            $this->result->setSuccess(false);
        }
    }
}