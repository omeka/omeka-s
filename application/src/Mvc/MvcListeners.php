<?php
namespace Omeka\Mvc;

use Omeka\Service\Exception\ConfigException;
use Omeka\Site\Navigation\Translator;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\Mvc\MvcEvent;
use Zend\Permissions\Acl\Exception\InvalidArgumentException as AclInvalidArgumentException;
use Zend\View\Model\ViewModel;

class MvcListeners extends AbstractListenerAggregate
{
    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            array($this, 'redirectToInstallation')
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            array($this, 'redirectToMigration')
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            array($this, 'redirectToLogin')
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            array($this, 'authenticateApiKey')
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            array($this, 'authorizeUserAgainstRoute'),
            -1000
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            array($this, 'prepareAdmin')
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            array($this, 'prepareSite')
        );
    }

    /**
     * Redirect all requests to install route if Omeka is not installed.
     *
     * @param MvcEvent $event
     * @return Zend\Http\PhpEnvironment\Response
     */
    public function redirectToInstallation(MvcEvent $event)
    {
        $serviceLocator = $event->getApplication()->getServiceManager();
        if ($serviceLocator->get('Omeka\Status')->isInstalled()) {
            // Omeka is installed
            return;
        }
        $matchedRouteName = $event->getRouteMatch()->getMatchedRouteName();
        if ('install' == $matchedRouteName) {
            // On the install route
            return;
        }
        $url = $event->getRouter()->assemble(array(), array('name' => 'install'));
        $response = $event->getResponse();
        $response->getHeaders()->addHeaderLine('Location', $url);
        $response->setStatusCode(302);
        $response->sendHeaders();
        return $response;
    }

    /**
     * Redirect requests if Omeka needs database migrations.
     *
     * Updates the installed version when the code version is out of sync and
     * there are no migrations to perform. When there are migrations to perform,
     * redirects to a migrate page in the admin route, and to a maintenance page
     * on all other routes.
     *
     * @param MvcEvent $event
     * @return Zend\Http\PhpEnvironment\Response
     */
    public function redirectToMigration(MvcEvent $event)
    {
        $routeMatch = $event->getRouteMatch();
        $matchedRouteName = $routeMatch->getMatchedRouteName();

        if ('install' == $matchedRouteName) {
            // No need to continue when installing the software.
            return;
        }

        $serviceLocator = $event->getApplication()->getServiceManager();
        $status = $serviceLocator->get('Omeka\Status');

        if (!$status->needsVersionUpdate()) {
            // No need to continue when the version is up to date.
            return;
        }
        if (!$status->needsMigration()) {
            // There are no migrations. Update the installed version and return.
            $serviceLocator->get('Omeka\Settings')
                ->set('version', $status->getVersion());
            return;
        }
        if ('migrate' == $matchedRouteName || 'maintenance' == $matchedRouteName) {
            // Already on the migrate or maintenance route. Do not redirect.
            return;
        }

        if ($routeMatch->getParam('__ADMIN__')) {
            $url = $event->getRouter()->assemble(array(), array('name' => 'migrate'));
        } else {
            $url = $event->getRouter()->assemble(array(), array('name' => 'maintenance'));
        }
        $response = $event->getResponse();
        $response->getHeaders()->addHeaderLine('Location', $url);
        $response->setStatusCode(302);
        $response->sendHeaders();
        return $response;
    }

    /**
     * Redirect all admin requests to login route if user not logged in.
     *
     * @param MvcEvent $event
     * @return Zend\Http\PhpEnvironment\Response
     */
    public function redirectToLogin(MvcEvent $event)
    {
        $serviceLocator = $event->getApplication()->getServiceManager();
        $auth = $serviceLocator->get('Omeka\AuthenticationService');

        if ($auth->hasIdentity()) {
            // User is logged in.
            return;
        }

        $routeMatch = $event->getRouteMatch();
        if ($routeMatch->getParam('__ADMIN__')) {
            // This is an admin request.
            $url = $event->getRouter()->assemble(array(), array(
                'name' => 'login',
                'query' => array('redirect' => $event->getRequest()->getUriString())
            ));
            $response = $event->getResponse();
            $response->getHeaders()->addHeaderLine('Location', $url);
            $response->setStatusCode(302);
            $response->sendHeaders();
            return $response;
        }
    }

    /**
     * Authorize the current user against an API key.
     *
     * @param MvcEvent $event
     */
    public function authenticateApiKey(MvcEvent $event)
    {
        $status = $event->getApplication()->getServiceManager()
            ->get('Omeka\Status');

        if (!$status->isApiRequest()) {
            // This is not an API request.
            return;
        }

        $identity = $event->getRequest()->getQuery('key_identity');
        $credential = $event->getRequest()->getQuery('key_credential');

        if (is_null($identity) || is_null($credential)) {
            // No identity/credential key to authenticate against.
            return;
        }

        $auth = $event->getApplication()->getServiceManager()
            ->get('Omeka\AuthenticationService');
        $auth->getAdapter()->setIdentity($identity);
        $auth->getAdapter()->setCredential($credential);
        $auth->authenticate();
    }

    /**
     * Authorize the current user against the requested route.
     *
     * @param MvcEvent $event
     */
    public function authorizeUserAgainstRoute(MvcEvent $event)
    {
        $routeMatch = $event->getRouteMatch();
        $controller = $routeMatch->getParam('controller');
        $action = $routeMatch->getParam('action');
        $acl = $event->getApplication()->getServiceManager()->get('Omeka\Acl');

        try {
            if (!$acl->userIsAllowed($controller, $action)) {
                // User not allowed is 403 Forbidden.
                $response = $event->getResponse();
                $response->setStatusCode(403);

                $model = new ViewModel;
                $model->setTemplate('error/403');

                $event->setResponse($response);
                $event->getViewModel()->addChild($model);
                $event->setError(Application::ERROR_ROUTER_PERMISSION_DENIED);
            }
        } catch (AclInvalidArgumentException $e) {
            // ACL resource not found is 404 Not Found, automatically set during
            // MvcEvent::EVENT_DISPATCH_ERROR.
            $event->setParam('exception', $e);
        }
    }

    /**
     * Prepare the administrative interface.
     *
     * @param MvcEvent $event
     */
    public function prepareAdmin(MvcEvent $event)
    {
        $routeMatch = $event->getRouteMatch();
        if (!$routeMatch->getParam('__ADMIN__')
            && 'migrate' !== $routeMatch->getMatchedRouteName()
        ) {
            // Not an admin route; do nothing.
            return;
        }
        $event->getApplication()
            ->getServiceManager()
            ->get('ViewTemplatePathStack')
            ->addPath(sprintf('%s/application/view-admin', OMEKA_PATH));
    }

    /**
     * Prepare the site interface.
     *
     * @param MvcEvent $event
     */
    public function prepareSite(MvcEvent $event)
    {
        $routeMatch = $event->getRouteMatch();
        if (!$routeMatch->getParam('__SITE__')) {
            return;
        }

        $serviceLocator = $event->getApplication()->getServiceManager();
        $entityManager = $serviceLocator->get('Omeka\EntityManager');

        $sql = 'SELECT s FROM Omeka\Entity\Site s WHERE s.slug = :slug';
        $site = $entityManager->createQuery($sql)
            ->setParameter('slug', $routeMatch->getParam('site-slug'))
            ->getOneOrNullResult();

        if (!$site) {
            // Site not found, set minimal layout and 404 status
            $event->getViewModel()->setTemplate('error/404');
            $event->getResponse()->setStatusCode(404);
            return;
        }

        $acl = $serviceLocator->get('Omeka\Acl');
        if (!$acl->userIsAllowed($site, 'read')) {
            // Site is restricted, set minimal layout and 404 status
            $event->getViewModel()->setTemplate('error/404');
            $event->getResponse()->setStatusCode(404);
            return;
        }

        // Set the current theme.
        $theme = $site->getTheme();
        $themeManager = $serviceLocator->get('Omeka\Site\ThemeManager');
        $themeManager->setCurrentTheme($theme);

        // Add the theme view templates to the path stack.
        $serviceLocator->get('ViewTemplatePathStack')
            ->addPath(sprintf('%s/themes/%s/view', OMEKA_PATH, $theme));

        // Load theme view helpers on-demand.
        $helpers = $themeManager->getCurrentTheme()->getIni('helpers');
        if (is_array($helpers)) {
            foreach ($helpers as $helper) {
                $factory = function ($pluginManager) use ($theme, $helper) {
                    require_once sprintf('%s/themes/%s/helper/%s.php', OMEKA_PATH, $theme, $helper);
                    $helperClass = sprintf('\OmekaTheme\Helper\%s', $helper);
                    return new $helperClass;
                };
                $serviceLocator->get('ViewHelperManager')->setFactory($helper, $factory);
            }
        }

        $translator = new Translator;
        $config = $serviceLocator->get('Config');
        $config['navigation']['site'] = $translator->toZend($site);
        $allowOverride = $serviceLocator->getAllowOverride();
        $serviceLocator->setAllowOverride(true);
        $serviceLocator->setService('Config', $config);
        $serviceLocator->setAllowOverride($allowOverride);
    }
}
