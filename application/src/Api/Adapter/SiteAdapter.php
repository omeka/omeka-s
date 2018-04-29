<?php
namespace Omeka\Api\Adapter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Exception\ValidationException;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\SitePermission;
use Omeka\Entity\SiteItemSet;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;

class SiteAdapter extends AbstractEntityAdapter
{
    use SiteSlugTrait;

    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'sites';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\SiteRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Entity\Site';
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $this->hydrateOwner($request, $entity);
        $title = null;

        if (Request::CREATE === $request->getOperation()) {
            // Automatically add the site owner as a site administrator.
            $user = $this->getServiceLocator()->get('Omeka\AuthenticationService')->getIdentity();
            if ($user) {
                $sitePermission = new SitePermission;
                $sitePermission->setSite($entity);
                $sitePermission->setUser($user);
                $sitePermission->setRole('admin');
                $entity->getSitePermissions()->add($sitePermission);
            }
        }
        if ($this->shouldHydrate($request, 'o:theme')) {
            $entity->setTheme($request->getValue('o:theme'));
        }
        if ($this->shouldHydrate($request, 'o:title')) {
            $title = trim($request->getValue('o:title', ''));
            $entity->setTitle($title);
        }
        if ($this->shouldHydrate($request, 'o:slug')) {
            $default = null;
            $slug = trim($request->getValue('o:slug', ''));
            if ($slug === ''
                && $request->getOperation() === Request::CREATE
                && is_string($title)
                && $title !== ''
            ) {
                $slug = $this->getAutomaticSlug($title);
            }
            $entity->setSlug($slug);
        }
        if ($this->shouldHydrate($request, 'o:navigation')) {
            $default = [];
            if ($request->getOperation() === Request::CREATE) {
                $default = $this->getDefaultNavigation();
            }
            $entity->setNavigation($request->getValue('o:navigation', $default));
        }
        if ($this->shouldHydrate($request, 'o:item_pool')) {
            $entity->setItemPool($request->getValue('o:item_pool', []));
        }
        if ($this->shouldHydrate($request, 'o:is_public')) {
            $entity->setIsPublic($request->getValue('o:is_public', true));
        }

        if ($this->shouldHydrate($request, 'o:page')) {
            $pagesData = $request->getValue('o:page', []);
            $adapter = $this->getAdapter('site_pages');
            $retainPages = [];
            foreach ($pagesData as $pageData) {
                if (isset($pageData['o:id'])) {
                    $page = $adapter->findEntity($pageData['o:id']);
                    $retainPages[] = $page;
                }
            }

            $pages = $entity->getPages();
            // Remove pages not included in request.
            foreach ($pages as $page) {
                if (!in_array($page, $retainPages, true)) {
                    $pages->removeElement($page);
                }
            }

            if ($request->getOperation() === Request::CREATE) {
                $class = $adapter->getEntityClass();
                $page = new $class;
                $page->setSite($entity);
                $translator = $this->getServiceLocator()->get('MvcTranslator');
                $subErrorStore = new ErrorStore;
                $subrequest = new Request(Request::CREATE, 'site_pages');
                $subrequest->setContent(
                        [
                            'o:title' => $translator->translate('Welcome'),
                            'o:slug' => 'welcome',
                            'o:block' => [
                                [
                                    'o:layout' => 'html',
                                    'o:data' => ['html' => $translator->translate('Welcome to your new site. This is an example page.')],
                                ],
                            ],
                        ]
                    );
                try {
                    $adapter->hydrateEntity($subrequest, $page, $subErrorStore);
                } catch (ValidationException $e) {
                    $errorStore->mergeErrors($e->getErrorStore(), 'o:page');
                }
                $pages->add($page);
            }
        }

        $sitePermissionsData = $request->getValue('o:site_permission');
        if ($this->shouldHydrate($request, 'o:site_permission')
            && is_array($sitePermissionsData)
        ) {
            $userAdapter = $this->getAdapter('users');
            $sitePermissions = $entity->getSitePermissions();
            $sitePermissionsToRetain = [];

            foreach ($sitePermissionsData as $sitePermissionData) {
                if (!isset($sitePermissionData['o:user']['o:id'])) {
                    continue;
                }
                if (!isset($sitePermissionData['o:role'])) {
                    continue;
                }

                $user = $userAdapter->findEntity($sitePermissionData['o:user']['o:id']);
                $criteria = Criteria::create()
                    ->where(Criteria::expr()->eq('user', $user));
                $sitePermission = $sitePermissions->matching($criteria)->first();

                if (!$sitePermission) {
                    $sitePermission = new SitePermission;
                    $sitePermission->setSite($entity);
                    $sitePermission->setUser($user);
                    $entity->getSitePermissions()->add($sitePermission);
                }

                $sitePermission->setRole($sitePermissionData['o:role']);
                $sitePermissionsToRetain[] = $sitePermission;
            }
            foreach ($sitePermissions as $sitePermissionId => $sitePermission) {
                if (!in_array($sitePermission, $sitePermissionsToRetain)) {
                    $sitePermissions->remove($sitePermissionId);
                }
            }
        }

        if ($this->shouldHydrate($request, 'o:site_item_set')) {
            $itemSetsData = $request->getValue('o:site_item_set', []);
            $siteItemSets = $entity->getSiteItemSets();
            $itemSetsAdapter = $this->getAdapter('item_sets');
            $siteItemSetsToRetain = [];

            $position = 1;
            foreach ($itemSetsData as $itemSetData) {
                if (!isset($itemSetData['o:item_set']['o:id'])) {
                    continue;
                }
                $itemSet = $itemSetsAdapter->findEntity($itemSetData['o:item_set']['o:id']);
                $criteria = Criteria::create()->where(Criteria::expr()->eq('itemSet', $itemSet));
                $siteItemSet = $siteItemSets->matching($criteria)->first();
                if (!$siteItemSet) {
                    $siteItemSet = new SiteItemSet;
                    $siteItemSet->setSite($entity);
                    $siteItemSet->setItemSet($itemSet);
                    $siteItemSets->add($siteItemSet);
                }
                $siteItemSet->setPosition($position++);
                $siteItemSetsToRetain[] = $siteItemSet;
            }
            foreach ($siteItemSets as $siteItemSet) {
                if (!in_array($siteItemSet, $siteItemSetsToRetain)) {
                    $siteItemSets->removeElement($siteItemSet);
                }
            }
        }

        $this->updateTimestamps($request, $entity);
    }

    /**
     * {@inheritDoc}
     */
    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        $title = $entity->getTitle();
        if (!is_string($title) || $title === '') {
            $errorStore->addError('o:title', 'A site must have a title.'); // @translate
        }
        $slug = $entity->getSlug();
        if (!is_string($slug) || $slug === '') {
            $errorStore->addError('o:slug', 'The slug cannot be empty.'); // @translate
        }
        if (preg_match('/[^a-zA-Z0-9_-]/u', $slug)) {
            $errorStore->addError('o:slug', 'A slug can only contain letters, numbers, underscores, and hyphens.'); // @translate
        }
        if (!$this->isUnique($entity, ['slug' => $slug])) {
            $errorStore->addError('o:slug', new Message(
                'The slug "%s" is already taken.', // @translate
                $slug
            ));
        }

        if (false == $entity->getTheme()) {
            $errorStore->addError('o:theme', 'A site must have a theme.'); // @translate
        }

        $this->validateNavigation($entity, $errorStore);
        if (!is_array($entity->getItemPool())) {
            $errorStore->addError('o:item_pool', 'A site must have item pool data.'); // @translate
        }
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['owner_id'])) {
            $userAlias = $this->createAlias();
            $qb->innerJoin(
                'Omeka\Entity\Site.owner',
                $userAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$userAlias.id",
                $this->createNamedParameter($qb, $query['owner_id']))
            );
        }
    }

    /**
     * Validate navigation.
     *
     * Prevent corrupt navigation data by validating prior to saving.
     *
     * @param EntityInterface $entity
     * @param ErrorStore $errorStore
     */
    protected function validateNavigation(EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $navigation = $entity->getNavigation();

        if (!is_array($navigation)) {
            $errorStore->addError('o:navigation', 'Invalid navigation: navigation must be an array'); // @translate
            return;
        }

        $pagesInNavigation = [];
        $manager = $this->getServiceLocator()->get('Omeka\Site\NavigationLinkManager');
        $validateLinks = function ($linksIn) use (&$validateLinks, $manager, $errorStore, $pagesInNavigation) {
            foreach ($linksIn as $key => $data) {
                if (!isset($data['type'])) {
                    $errorStore->addError('o:navigation', 'Invalid navigation: link missing type'); // @translate
                    return;
                }
                if (!isset($data['data'])) {
                    $errorStore->addError('o:navigation', 'Invalid navigation: link missing data'); // @translate
                    return;
                }
                if (!$manager->get($data['type'])->isValid($data['data'], $errorStore)) {
                    $errorStore->addError('o:navigation', 'Invalid navigation: invalid link data'); // @translate
                    return;
                }
                if ('page' === $data['type']) {
                    if (in_array($data['data']['id'], $pagesInNavigation)) {
                        $errorStore->addError('o:navigation', 'Invalid navigation: page links must be unique'); // @translate
                        return;
                    }
                    $pagesInNavigation[] = $data['data']['id'];
                }
                if (isset($data['links'])) {
                    if (!is_array($data['links'])) {
                        $errorStore->addError('o:navigation', 'Invalid navigation: links must be an array'); // @translate
                        return;
                    }
                    $validateLinks($data['links']);
                }
            }
        };
        $validateLinks($navigation);
    }

    /**
     * Get the default nav array for new sites with no specified
     * navigation.
     *
     * The default is to just include a link to the browse page.
     *
     * @return array
     */
    protected function getDefaultNavigation()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return [
            [
                'type' => 'browse',
                'data' => [
                    'label' => $translator->translate('Browse'),
                    'query' => '',
                ],
                'links' => [],
            ],
        ];
    }
}
