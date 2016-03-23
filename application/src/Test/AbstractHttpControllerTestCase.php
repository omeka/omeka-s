<?php

namespace Omeka\Test;
use Omeka\Entity\User;
use Omeka\Acl;
use Zend\Authentication\AuthenticationService;
use Zend\Permissions\Acl\AclInterface;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase as ZendAbstractHttpControllerTestCase;

use Omeka\Entity\Site;
use Omeka\Entity\SitePage;
use Omeka\Settings;
use Zend\Http\Request as HttpRequest;
abstract class AbstractHttpControllerTestCase extends ZendAbstractHttpControllerTestCase
{

  public function setUp() {

    parent::setUp();

  }

  public function postDispatch($url, $data) {
    return $this->dispatch($url, HttpRequest::METHOD_POST,$data);
  }

  public function connectAdminUser() {
    $entityManager = $this->getApplicationServiceLocator()->get('Omeka\EntityManager');
    $user =  $entityManager->find('Omeka\Entity\User',1);

    $user->setIsActive(true);
    $user->setRole('global_admin');
    $user->setName('Tester');
    $user->setEmail('admin@example.com');
    $aclMock = $this->getMock('Omeka\Permissions\Acl');
    $aclMock->expects($this->any())
            ->method('userIsAllowed')
            ->will($this->returnValue(true));

    $authMock = $this->getMock('Zend\Authentication\AuthenticationService');
    $authMock->expects($this->any())
             ->method('hasIdentity')
             ->will($this->returnValue(true));

    $authMock->expects($this->any())
             ->method('getIdentity')
             ->will($this->returnValue($user));

    $this->getApplicationServiceLocator()->setAllowOverride(true);


    $this->getApplicationServiceLocator()->setService('Omeka\AuthenticationService', $authMock);
    $this->getApplicationServiceLocator()->setService('Omeka\Acl', $aclMock);


  }
    public function getApplication()
    {
        // Return the application immediately if already set.
        if ($this->application) {
            return $this->application;
        }
        $config = require OMEKA_PATH . '/config/application.config.php';
        $reader = new \Zend\Config\Reader\Ini;
        $testConfig = [
            'connection' => $reader->fromFile(OMEKA_PATH . '/application/test/config/database.ini')
        ];
        $config = array_merge($config, $testConfig);

        \Zend\Console\Console::overrideIsConsole($this->getUseConsoleRequest());
        $this->application = \Omeka\Mvc\Application::init($config);

        $events = $this->application->getEventManager();
        $events->detach($this->application->getServiceManager()->get('SendResponseListener'));

        return $this->application;
    }

    protected function resetApplication()
    {
        $this->application = null;
    }

    protected function login($email, $password)
    {
        $serviceLocator = $this->getApplication()->getServiceManager();
        $auth = $serviceLocator->get('Omeka\AuthenticationService');
        $adapter = $auth->getAdapter();
        $adapter->setIdentity($email);
        $adapter->setCredential($password);
        return $auth->authenticate();
    }


    protected function addSite($title) {
      $site = new Site;
      $site->setTitle($title);
      $site->setSlug($title);
      $site->setTheme('default');
      $site->setNavigation([["type" =>"browse","data" => ["label"=>"Browse","query"=>""],"links"=>[]]]);
      $site->setIsPublic(true);
      $site->setItemPool(true);
      $this->persistAndSave($site);
      return $site;
    }

    public function persistAndSave($entity)
    {

      $em= $this->getApplicationServiceLocator()->get('Omeka\EntityManager');

      $em->persist($entity);
      $em->flush();
    }


    public function cleanTable($table_name) {
      $this->getApplicationServiceLocator()->get('Omeka\Connection')->exec('DELETE FROM '.$table_name);
    }


    public function delete($entity)
    {

      $em= $this->getApplicationServiceLocator()->get('Omeka\EntityManager');
      $entity=$em->find('Omeka\Entity\Site',1);
      $em->remove($entity);
      $em->flush();

    }


    public function setSettings($id,$value)
    {
      $settings = $this->getApplicationServiceLocator()->get('Omeka\Settings');
      $settings->set($id,$value);
    }

}
