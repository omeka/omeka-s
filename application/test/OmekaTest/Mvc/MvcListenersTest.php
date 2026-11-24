<?php
namespace OmekaTest\Mvc;

use Omeka\Mvc\MvcListeners;
use Omeka\Test\TestCase;

class MvcListenersTest extends TestCase
{
    protected $mvcListeners;

    public function setUp(): void
    {
        $this->mvcListeners = new MvcListeners;
    }

    public function testRedirectToInstallation()
    {
        $event = $this->getEventForRedirectToInstallation(['is_installed' => true]);
        $return = $this->mvcListeners->redirectToInstallation($event);
        $this->assertNull($return);

        $event = $this->getEventForRedirectToInstallation(['is_install_route' => true]);
        $return = $this->mvcListeners->redirectToInstallation($event);
        $this->assertNull($return);

        $event = $this->getEventForRedirectToInstallation();
        $return = $this->mvcListeners->redirectToInstallation($event);
        $this->assertInstanceOf('Laminas\Http\PhpEnvironment\Response', $return);
    }

    /**
     * @dataProvider apiKeyProvider
     */
    public function testAuthenticateApiKey(array $headers, array $query, $expectedIdentity, $expectedCredential)
    {
        $request = new \Laminas\Http\Request;
        foreach ($headers as $name => $value) {
            $request->getHeaders()->addHeaderLine($name, $value);
        }
        $request->setQuery(new \Laminas\Stdlib\Parameters($query));

        $adapter = $this->getMockBuilder('Omeka\\Authentication\\Adapter\\KeyAdapter')
            ->disableOriginalConstructor()
            ->getMock();
        $auth = $this->getMockBuilder('Laminas\\Authentication\\AuthenticationService')
            ->disableOriginalConstructor()
            ->getMock();
        $auth->expects($this->any())
            ->method('getAdapter')
            ->will($this->returnValue($adapter));

        if (is_null($expectedIdentity)) {
            $adapter->expects($this->never())->method('setIdentity');
            $auth->expects($this->never())->method('authenticate');
        } else {
            $adapter->expects($this->once())
                ->method('setIdentity')
                ->with($this->equalTo($expectedIdentity));
            $adapter->expects($this->once())
                ->method('setCredential')
                ->with($this->equalTo($expectedCredential));
            $auth->expects($this->once())->method('authenticate');
        }

        $application = $this->getMockBuilder('Laminas\Mvc\Application')
            ->disableOriginalConstructor()
            ->getMock();
        $application->expects($this->any())
            ->method('getServiceManager')
            ->will($this->returnValue($this->getServiceManager([
                'Omeka\AuthenticationService' => $auth,
            ])));

        $routeMatch = $this->getMockBuilder('Laminas\Router\RouteMatch')
            ->disableOriginalConstructor()
            ->getMock();
        $routeMatch->expects($this->any())
            ->method('getParam')
            ->with($this->equalTo('__KEYAUTH__'))
            ->will($this->returnValue(true));

        $event = $this->createMock('Laminas\Mvc\MvcEvent');
        $event->expects($this->any())->method('getRouteMatch')->will($this->returnValue($routeMatch));
        $event->expects($this->any())->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->any())->method('getApplication')->will($this->returnValue($application));

        $this->mvcListeners->authenticateApiKey($event);
    }

    public function apiKeyProvider()
    {
        return [
            'basic auth header' => [
                ['Authorization' => 'Basic ' . base64_encode('head_id:head_cred')],
                [],
                'head_id', 'head_cred',
            ],
            'query parameters take precedence over the header' => [
                ['Authorization' => 'Basic ' . base64_encode('head_id:head_cred')],
                ['key_identity' => 'query_id', 'key_credential' => 'query_cred'],
                'query_id', 'query_cred',
            ],
            'a partial query parameter is not completed with the header' => [
                ['Authorization' => 'Basic ' . base64_encode('head_id:head_cred')],
                ['key_identity' => 'query_id'],
                null, null,
            ],
            'a credential with a colon' => [
                ['Authorization' => 'Basic ' . base64_encode('head_id:head:cred')],
                [],
                'head_id', 'head:cred',
            ],
            'another scheme is skipped' => [
                ['Authorization' => 'Bearer ' . base64_encode('head_id:head_cred')],
                [],
                null, null,
            ],
            'a basic value that is not base64' => [
                ['Authorization' => 'Basic not base64!'],
                [],
                null, null,
            ],
            'a basic value without a colon' => [
                ['Authorization' => 'Basic ' . base64_encode('head_id')],
                [],
                null, null,
            ],
            'no key' => [
                [],
                [],
                null, null,
            ],
        ];
    }

    protected function getEventForRedirectToInstallation(array $options = [])
    {
        $options['is_installed'] = isset($options['is_installed']) ? true : false;
        $options['is_install_route'] = isset($options['is_install_route']) ? true : false;

        $event = $this->createMock('Laminas\Mvc\MvcEvent');

        // Laminas\Mvc\Application
        $application = $this->getMockBuilder('Laminas\Mvc\Application')
            ->disableOriginalConstructor()
            ->getMock();
        $status = $this->getMockBuilder('Omeka\Mvc\Status')->disableOriginalConstructor()->getMock();
        $status->expects($this->any())
            ->method('isInstalled')
            ->will($this->returnValue($options['is_installed'] ? true : false));
        $serviceManager = $this->getServiceManager([
            'Omeka\Status' => $status,
        ]);
        $application->expects($this->any())
            ->method('getServiceManager')
            ->will($this->returnValue($serviceManager));
        $event->expects($this->any())
            ->method('getApplication')
            ->will($this->returnValue($application));

        // Laminas\Mvc\Router\RouteMatch
        $routeMatch = $this->getMockBuilder('Laminas\Router\RouteMatch')
            ->disableOriginalConstructor()
            ->getMock();
        $routeMatch->expects($this->any())
            ->method('getMatchedRouteName')
            ->will($this->returnValue($options['is_install_route'] ? 'install' : 'foobar'));
        $event->expects($this->any())
            ->method('getRouteMatch')
            ->will($this->returnValue($routeMatch));

        // Laminas\Mvc\Router\RouteStackInterface
        $router = $this->createMock('Laminas\Router\RouteStackInterface');
        $router->expects($this->any())
            ->method('assemble')
            ->with($this->equalTo([]), $this->equalTo(['name' => 'install']));
        $event->expects($this->any())
            ->method('getRouter')
            ->will($this->returnValue($router));

        // Laminas\Http\PhpEnvironment\Response
        $headers = $this->createMock('Laminas\Http\Headers');
        $headers->expects($this->any())
            ->method('addHeaderLine')
            ->with($this->equalTo('Location'));
        $response = $this->createMock('Laminas\Http\PhpEnvironment\Response');
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
