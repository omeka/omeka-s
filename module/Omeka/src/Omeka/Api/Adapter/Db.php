<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Exception as ApiException;
use Omeka\Api\Adapter\AbstractAdapter;
use Zend\ServiceManager\ServiceLocatorInterface;

class Db extends AbstractAdapter
{
    public function setData(array $data)
    {
        if (!isset($data['entity_class'])) {
            throw new ApiException('An entity class is not registered.');
        }
        parent::setData($data);
    }
    
    public function search()
    {
        $entityManager = $this->getServiceLocator()->get('EntityManager');
        echo get_class($entityManager->getRepository($this->getData('entity_class')));
    }
}
