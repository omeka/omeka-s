<?php
namespace Omeka;

use Omeka\Event\ApiEvent;
use Omeka\Module\AbstractModule;
use Zend\EventManager\EventInterface;
use Zend\EventManager\SharedEventManagerInterface;

/**
 * The Omeka module.
 */
class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function attachShared(SharedEventManagerInterface $events)
    {
        $this->attach(
            'Omeka\Api\Adapter\Entity\ResourceClassAdapter',
            'create.post',
            array($this, 'assignDcmiPropertiesToClass')
        );
    }

    /**
     * Assign DCMI (Dublin Core) properties to every class.
     *
     * @param ApiEvent $event
     */
    public function assignDcmiPropertiesToClass(ApiEvent $event)
    {
        $ap = $this->getServiceLocator()->get('ApiManager');
        $class = $event->getResponse()->getContent();
        $response = $ap->search('properties', array(
            'vocabulary' => array(
                'namespace_uri' => 'http://purl.org/dc/terms/',
            ),
        ));
        foreach ($response->getContent() as $property) {
            $response = $ap->create('resource_class_properties', array(
                'resource_class' => array('id' => $class['id']),
                'property' => array('id' => $property['id']),
            ));
        }
    }
}
