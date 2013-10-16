<?php
namespace Omeka\Install;

use Omeka\Install\TaskAbstract;
use Omeka\Install\TaskInterface;
use Doctrine\ORM\EntityManager;
use Omeka\Model\Entity\Vocabulary;
use Omeka\Model\Entity\Property;
use Omeka\Model\Entity\User;

class TaskInstallDc extends TaskAbstract implements TaskInterface
{
    public function perform()
    {
    }
    
    protected function createVocab()
    {
        
    }
    
    protected function createProperties()
    {
        
    }
}