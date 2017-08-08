<?php
namespace Omeka\Mvc;

use Omeka\Site\Theme\Manager;
use Omeka\Site\Theme\Theme;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\Mvc\Application as ZendApplication;
use Zend\Mvc\MvcEvent;
use Zend\Session\Container;

class MvcListeners extends AbstractListenerAggregate
{
    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            [$this, 'redirectToInstallation']
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            [$this, 'redirectToMigration']
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            [$this, 'redirectToLogin']
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            [$this, 'authenticateApiKey']
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            [$this, 'prepareAdmin']
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            [$this, 'preparePublicSite']
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            [$this, 'checkExcessivePost']
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
        $url = $event->getRouter()->assemble([], ['name' => 'install']);
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
            $url = $event->getRouter()->assemble([], ['name' => 'migrate']);
        } else {
            $url = $event->getRouter()->assemble([], ['name' => 'maintenance']);
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
            $url = $event->getRouter()->assemble([], [
                'name' => 'login',
            ]);
            $session = Container::getDefaultManager()->getStorage();
            $session->offsetSet('redirect_url', $event->getRequest()->getUriString());
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
     * Prepare the site administrative interface.
     *
     * @param MvcEvent $event
     */
    public function prepareAdmin(MvcEvent $event)
    {
        $routeMatch = $event->getRouteMatch();
        if (!$routeMatch->getParam('__ADMIN__')) {
            return;
        }

        $event->getViewModel()->setTemplate('layout/layout-admin');

        if ($routeMatch->getParam('__SITEADMIN__')
            && $routeMatch->getParam('site-slug')
        ) {
            $this->prepareSite($event);
        }
    }

    /**
     * Prepare the public site.
     *
     * @param MvcEvent $event
     */
    public function preparePublicSite(MvcEvent $event)
    {
        $routeMatch = $event->getRouteMatch();
        if (!$routeMatch->getParam('__SITE__')) {
            return;
        }
        if (!$site = $this->prepareSite($event)) {
            return;
        }

        $services = $event->getApplication()->getServiceManager();

        $themeManager = $services->get('Omeka\Site\ThemeManager');
        $currentTheme = $themeManager->getCurrentTheme();
        if (Manager::STATE_ACTIVE !== $currentTheme->getState()) {
            $event->setError(ZendApplication::ERROR_EXCEPTION);
            $event->setName(MvcEvent::EVENT_DISPATCH_ERROR);
            $event->getApplication()->getEventManager()->triggerEvent($event);
            return;
        }

        // Add the theme view templates to the path stack.
        $services->get('ViewTemplatePathStack')
            ->addPath(sprintf('%s/themes/%s/view', OMEKA_PATH, $site->theme()));

        // Load theme view helpers on-demand.
        $helpers = $themeManager->getCurrentTheme()->getIni('helpers');
        if (is_array($helpers)) {
            foreach ($helpers as $helper) {
                $factory = function ($pluginManager) use ($site, $helper) {
                    require_once sprintf('%s/themes/%s/helper/%s.php', OMEKA_PATH, $site->theme(), $helper);
                    $helperClass = sprintf('\OmekaTheme\Helper\%s', $helper);
                    return new $helperClass;
                };
                $services->get('ViewHelperManager')->setFactory($helper, $factory);
            }
        }

        // Set the configured site locale to the translator.
        $siteSettings = $services->get('Omeka\Settings\Site');
        $locale = $siteSettings->get('locale');
        if ($locale) {
            $services->get('MvcTranslator')->getDelegatedTranslator()->setLocale($locale);
        }
    }

    public function checkExcessivePost(MvcEvent $event)
    {
        $request = $event->getRequest();
        $contentType = $request->getHeader('Content-Type');
        if ($request->isPost() && $contentType
            && $contentType->match(['application/x-www-form-urlencoded', 'multipart/form-data'])
            && !$_POST && !$_FILES
            && $request->getHeader('Content-Length', 0)
        ) {
            throw new Exception\RuntimeException('POST request exceeded maximum size');
        }
    }

    /**
     * Get the current site by slug and inject it where needed.
     *
     * Returns false if the site is not found or another error occured.
     *
     * @param MvcEvent $event
     * @return SiteRepresentation|false
     */
    protected function prepareSite(MvcEvent $event)
    {
        $services = $event->getApplication()->getServiceManager();
        $siteSlug = $event->getRouteMatch()->getParam('site-slug');

        try {
            $site = $services->get('Omeka\ApiManager')
                ->read('sites', ['slug' => $siteSlug])->getContent();
        } catch (\Exception $e) {
            $event->setError(ZendApplication::ERROR_EXCEPTION);
            $event->setParam('exception', $e);
            $event->setName(MvcEvent::EVENT_DISPATCH_ERROR);
            $event->getApplication()->getEventManager()->triggerEvent($event);
            return false;
        }

        // Inject the site into things that need it.
        $services->get('Omeka\Settings\Site')->setTargetId($site->id());
        $services->get('ControllerPluginManager')->get('currentSite')->setSite($site);

        // Set the site to the top level view model
        $event->getViewModel()->site = $site;

        // Set the current theme for this site.
        $themeManager = $services->get('Omeka\Site\ThemeManager');
        $currentTheme = $themeManager->getTheme($site->theme());
        if (!$currentTheme) {
            $currentTheme = new Theme('not_found');
            $currentTheme->setState(Manager::STATE_NOT_FOUND);
        }
        $themeManager->setCurrentTheme($currentTheme);

        return $site;
    }
}
