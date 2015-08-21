<?php
namespace Omeka;

use Omeka\Event\Event as OmekaEvent;
use Omeka\Module\AbstractModule;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\Session\Container;

/**
 * The Omeka module.
 */
class Module extends AbstractModule
{
    /**
     * This Omeka version.
     */
    const VERSION = '0.2.3-alpha';

    /**
     * @var array View helpers that need service manager injection
     */
    protected $viewHelpers = array(
        'api'        => 'Omeka\View\Helper\Api',
        'assetUrl'   => 'Omeka\View\Helper\AssetUrl',
        'i18n'       => 'Omeka\View\Helper\I18n',
        'media'      => 'Omeka\View\Helper\Media',
        'pagination' => 'Omeka\View\Helper\Pagination',
        'trigger'    => 'Omeka\View\Helper\Trigger',
        'setting'    => 'Omeka\View\Helper\Setting',
        'params'     => 'Omeka\View\Helper\Params',
        'blockLayout' => 'Omeka\View\Helper\BlockLayout',
        'userIsAllowed' => 'Omeka\View\Helper\UserIsAllowed',
    );

    /**
     * {@inheritDoc}
     */
    public function onBootstrap(MvcEvent $event)
    {
        $this->configureSession();

        parent::onBootstrap($event);

        $serviceManager = $this->getServiceLocator();
        $viewHelperManager = $serviceManager->get('ViewHelperManager');

        // Inject the service manager into view helpers that need it.
        foreach ($this->viewHelpers as $helperName => $helperClass) {
            $viewHelperManager->setFactory($helperName,
                function ($helperPluginManager) use ($helperClass, $serviceManager) {
                    return new $helperClass($serviceManager);
                });
        }

        // Set the ACL to navigation.
        $acl = $serviceManager->get('Omeka\Acl');
        $navigation = $viewHelperManager->get('Navigation');
        $navigation->setAcl($acl);
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return array_merge(
            include __DIR__ . '/config/module.config.php',
            include __DIR__ . '/config/routes.config.php',
            include __DIR__ . '/config/navigation.config.php'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Zend\View\Helper\Navigation\AbstractHelper',
            'isAllowed',
            array($this, 'navigationPageIsAllowed')
        );

        $sharedEventManager->attach(
            'Omeka\Entity\Media',
            OmekaEvent::ENTITY_REMOVE_POST,
            array($this, 'deleteMediaFiles')
        );

        $sharedEventManager->attach(
            'Omeka\Api\Representation\MediaRepresentation',
            OmekaEvent::JSON_LD_FILTER,
            array($this, 'filterHtmlMediaJsonLd')
        );

        $sharedEventManager->attach(
            'Omeka\Api\Representation\MediaRepresentation',
            OmekaEvent::JSON_LD_FILTER,
            array($this, 'filterYoutubeMediaJsonLd')
        );

        $sharedEventManager->attach(
            'Omeka\Api\Adapter\MediaAdapter',
            array(OmekaEvent::API_SEARCH_QUERY, OmekaEvent::API_FIND_QUERY),
            array($this, 'filterMedia')
        );
    }

    /**
     * Determine whether a navigation page is allowed.
     *
     * @param Event $event
     * @return bool
     */
    public function navigationPageIsAllowed(Event $event)
    {
        $accepted = true;
        $params   = $event->getParams();
        $acl      = $params['acl'];
        $page     = $params['page'];

        if (!$acl) {
            return $accepted;
        }

        $resource  = $page->getResource();
        $privilege = $page->getPrivilege();

        if ($resource || $privilege) {
            $accepted = $acl->hasResource($resource)
                && $acl->userIsAllowed($resource, $privilege);
        }

        $event->stopPropagation();
        return $accepted;
    }

    /**
     * Delete all files associated with a removed Media entity.
     *
     * @param Event $event
     */
    public function deleteMediaFiles(Event $event)
    {
        $fileManager = $this->getServiceLocator()->get('Omeka\File\Manager');
        $media = $event->getTarget();

        if ($media->hasOriginal()) {
            $fileManager->deleteOriginal($media);
        }

        if ($media->hasThumbnails()) {
            $fileManager->deleteThumbnails($media);
       }
    }

    /**
     * Filter the JSON-LD for HTML media.
     *
     * @param Event $event
     */
    public function filterHtmlMediaJsonLd(Event $event)
    {
        if ('html' !== $event->getTarget()->type()) {
            return;
        }
        $data = $event->getTarget()->mediaData();
        $jsonLd = $event->getParam('jsonLd');
        $jsonLd['@context']['cnt'] = 'http://www.w3.org/2011/content#';
        $jsonLd['@type'] = 'cnt:ContentAsText';
        $jsonLd['cnt:chars'] = $data['html'];
        $jsonLd['cnt:characterEncoding'] = 'UTF-8';
        $event->setParam('jsonLd', $jsonLd);
    }

    /**
     * Filter the JSON-LD for YouTube media.
     *
     * @param Event $event
     */
    public function filterYoutubeMediaJsonLd(Event $event)
    {
        if ('youtube' !== $event->getTarget()->type()) {
            return;
        }
        $data = $event->getTarget()->mediaData();
        $jsonLd = $event->getParam('jsonLd');
        $jsonLd['@context']['time'] = 'http://www.w3.org/2006/time#';
        $jsonLd['time:hasBeginning'] = array(
            '@value' => $data['start'],
            '@type' => 'time:seconds',
        );
        $jsonLd['time:hasEnd'] = array(
            '@value' => $data['end'],
            '@type' => 'time:seconds',
        );
        $event->setParam('jsonLd', $jsonLd);
    }

    /**
     * Filter media belonging to private items.
     *
     * @param Event $event
     */
    public function filterMedia(Event $event)
    {
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        if ($acl->userIsAllowed('Omeka\Entity\Resource', 'view-all')) {
            return;
        }

        $adapter = $event->getTarget();
        $itemAlias = $adapter->createAlias();
        $qb = $event->getParam('queryBuilder');
        $qb->innerJoin('Omeka\Entity\Media.item', $itemAlias);

        // Users can view media they do not own that belong to public items.
        $expression = $qb->expr()->eq("$itemAlias.isPublic", true);

        $identity = $this->getServiceLocator()
            ->get('Omeka\AuthenticationService')->getIdentity();
        if ($identity) {
            // Users can view all media they own.
            $expression = $qb->expr()->orX(
                $expression,
                $qb->expr()->eq(
                    "$itemAlias.owner",
                    $adapter->createNamedParameter($qb, $identity)
                )
            );
        }
        $qb->andWhere($expression);
    }

    /**
     * Configure Zend's default session manager.
     */
    private function configureSession()
    {
        $sessionManager = Container::getDefaultManager();
        $config = $sessionManager->getConfig();
        $config->setOptions(array(
            'name' => md5(OMEKA_PATH),
            'cookie_httponly' => true,
            'use_strict_mode' => true,
            'use_only_cookies' => true,
        ));
    }
}
