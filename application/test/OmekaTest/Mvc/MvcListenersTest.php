<?php
namespace OmekaTest\Mvc;

use Omeka\Mvc\MvcListeners;
use Omeka\Test\TestCase;
use Zend\Mvc\MvcEvent;

class MvcListenersTest extends TestCase
{
    protected $mvcListeners;

    public function setUp()
    {
        $this->mvcListeners = new MvcListeners;
    }

    public function testAttach()
    {
        $events = $this->getMock('Zend\EventManager\EventManagerInterface');
        $events->expects($this->exactly(7))
            ->method('attach')
            ->withConsecutive(
                array(
                    $this->equalTo(MvcEvent::EVENT_ROUTE),
                    $this->equalTo(array($this->mvcListeners, 'redirectToInstallation'))
                ),
                array(
                    $this->equalTo(MvcEvent::EVENT_ROUTE),
                    $this->equalTo(array($this->mvcListeners, 'redirectToMigration'))
                ),
                array(
                    $this->equalTo(MvcEvent::EVENT_ROUTE),
                    $this->equalTo(array($this->mvcListeners, 'redirectToLogin'))
                ),
                array(
                    $this->equalTo(MvcEvent::EVENT_ROUTE),
                    $this->equalTo(array($this->mvcListeners, 'authenticateApiKey'))
                ),
                array(
                    $this->equalTo(MvcEvent::EVENT_ROUTE),
                    $this->equalTo(array($this->mvcListeners, 'authorizeUserAgainstRoute')),
                    $this->equalTo(-1000)
                ),
                array(
                    $this->equalTo(MvcEvent::EVENT_ROUTE),
                    $this->equalTo(array($this->mvcListeners, 'prepareAdmin'))
                ),
                array(
                    $this->equalTo(MvcEvent::EVENT_ROUTE),
                    $this->equalTo(array($this->mvcListeners, 'prepareSite'))
                )
            );
        $this->mvcListeners->attach($events);
    }

    public function testRedirectToInstallation()
    {
        $event = $this->getEventForRedirectToInstallation(array('is_installed' => true));
        $return = $this->mvcListeners->redirectToInstallation($event);
        $this->assertNull($return);

        $event = $this->getEventForRedirectToInstallation(array('is_install_route' => true));
        $return = $this->mvcListeners->redirectToInstallation($event);
        $this->assertNull($return);

        $event = $this->getEventForRedirectToInstallation();
        $return = $this->mvcListeners->redirectToInstallation($event);
        $this->assertInstanceOf('Zend\Http\PhpEnvironment\Response', $return);
    }

    protected function getEventForRedirectToInstallation(array $options = array())
    {
        $options['is_installed'] = isset($options['is_installed']) ? true : false;
        $options['is_install_route'] = isset($options['is_install_route']) ? true : false;

        $event = $this->getMock('Zend\Mvc\MvcEvent');

        // Zend\Mvc\Application
        $application = $this->getMockBuilder('Zend\Mvc\Application')
            ->disableOriginalConstructor()
            ->getMock();
        $status = $this->getMock('Omeka\Mvc\Status');
        $status->expects($this->any())
            ->method('isInstalled')
            ->will($this->returnValue($options['is_installed'] ? true : false));
        $serviceManager = $this->getServiceManager(array(
            'Omeka\Status' => $status,
        ));
        $application->expects($this->any())
            ->method('getServiceManager')
            ->will($this->returnValue($serviceManager));
        $event->expects($this->any())
            ->method('getApplication')
            ->will($this->returnValue($application));

        // Zend\Mvc\Router\RouteMatch
        $routeMatch = $this->getMockBuilder('Zend\Mvc\Router\RouteMatch')
            ->disableOriginalConstructor()
            ->getMock();
        $routeMatch->expects($this->any())
            ->method('getMatchedRouteName')
            ->will($this->returnValue($options['is_install_route'] ? 'install' : 'foobar'));
        $event->expects($this->any())
            ->method('getRouteMatch')
            ->will($this->returnValue($routeMatch));

        // Zend\Mvc\Router\RouteStackInterface
        $router = $this->getMock('Zend\Mvc\Router\RouteStackInterface');
        $router->expects($this->any())
            ->method('assemble')
            ->with($this->equalTo(array()), $this->equalTo(array('name' => 'install')));
        $event->expects($this->any())
            ->method('getRouter')
            ->will($this->returnValue($router));

        // Zend\Http\PhpEnvironment\Response
        $headers = $this->getMock('Zend\Http\Headers');
        $headers->expects($this->any())
            ->method('addHeaderLine')
            ->with($this->equalTo('Location'));
        $response = $this->getMock('Zend\Http\PhpEnvironment\Response');
        $response->expects($this->any())
            ->method('getHeaders')
            ->will($this->returnValue($headers));
        $response->expects($this->any())
            ->method('setStatusCode')
            ->will($this->returnValue(302));
        $event->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($response));

        return $event;
    }
}
