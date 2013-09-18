<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Exception;
use Omeka\Api\Adapter\AbstractAdapter;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Database API adapter.
 */
class Db extends AbstractAdapter
{
    public function setData(array $data)
    {
        if (!isset($data['entity_class'])) {
            throw new Exception('An entity class is not registered.');
        }
        parent::setData($data);
    }

    public function search()
    {
        $em = $this->getServiceLocator()->get('EntityManager');
        $entities = $em->getRepository($this->getData('entity_class'))->findAll();
        $entityArrays = array();
        foreach ($entities as $entity) {
            $entityArrays[] = $entity->toArray();
        }
        return $entityArrays;
    }

    public function create()
    {
    }

    public function read()
    {
    }

    public function update()
    {
    }

    public function delete()
    {
    }
}
