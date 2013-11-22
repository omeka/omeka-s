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
            'create.pre',
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
        static $properties;
        if (!$properties) {
            $ap = $this->getServiceLocator()->get('ApiManager');
            $response = $ap->search('properties', array(
                'vocabulary' => array(
                    'namespace_uri' => 'http://purl.org/dc/terms/',
                ),
            ));
            $properties = $response->getContent();
        }

        $request = $event->getRequest();
        $data = $request->getContent();
        foreach ($properties as $property) {
            $data['properties'][] = array(
                'id' => $property['id'],
            );
        }
        $request->setContent($data);
    }
}
