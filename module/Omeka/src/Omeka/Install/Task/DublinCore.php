<?php
namespace Omeka\Install\Task;

use Omeka\Install\Task\AbstractTask;
use Omeka\Install\Task\TaskInterface;
use Doctrine\ORM\EntityManager;
use Omeka\Model\Entity\Vocabulary;
use Omeka\Model\Entity\Property;
use Omeka\Model\Entity\User;

/**
 * Installation task to add Dublin Core
 * @author patrickmj
 *
 */
class DublinCore extends AbstractTask implements TaskInterface
{
    public function perform()
    {
    }
    
    protected function createVocabulary()
    {
        
    }
    
    protected function createProperties()
    {
        
    }
}