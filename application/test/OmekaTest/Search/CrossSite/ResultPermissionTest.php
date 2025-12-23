<?php
namespace OmekaTest\Search\CrossSite;

use Omeka\Test\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PermissionTest extends TestCase
{
    private $view;
    private $apiMock;
    private $identityMock;
    private $siteMock;
    private $userMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->view = $this->getMockBuilder('Laminas\View\Renderer\PhpRenderer')
            ->setMethods(['api', 'identity', 'params'])
            ->getMock();

        $this->apiMock = $this->createMock('Omeka\Api\Manager');
        $this->identityMock = $this->createMock('Omeka\Entity\User');
        $this->userMock = $this->createMock('Omeka\Entity\User');

        $this->view->expects($this->any())
            ->method('api')
            ->willReturn($this->apiMock);
    }

    /**
     * Test that public sites are visible to anonymous users
     */
    public function testPublicSitesVisibleToAnonymousUsers()
    {
        // Setup anonymous user (no identity)
        $this->view->expects($this->any())
            ->method('identity')
            ->willReturn(null);

        // Create mock public site
        $publicSite = $this->createMockSite(1, true);

        // Setup API response
        $response = $this->createMock('Omeka\Api\Response');
        $response->expects($this->once())
            ->method('getContent')
            ->willReturn([$publicSite]);

        $this->apiMock->expects($this->once())
            ->method('search')
            ->with('sites', ['sort_by' => 'title', 'sort_order' => 'asc'])
            ->willReturn($response);

        $visibleSiteIds = $this->executePermissionLogic(null);

        $this->assertContains(1, $visibleSiteIds);
    }

    /**
     * Test that private sites are not visible to anonymous users
     */
    public function testPrivateSitesNotVisibleToAnonymousUsers()
    {
        // Setup anonymous user (no identity)
        $this->view->expects($this->any())
            ->method('identity')
            ->willReturn(null);

        // Create mock private site
        $privateSite = $this->createMockSite(1, false);

        // Setup API response
        $response = $this->createMock('Omeka\Api\Response');
        $response->expects($this->once())
            ->method('getContent')
            ->willReturn([$privateSite]);

        $this->apiMock->expects($this->once())
            ->method('search')
            ->with('sites', ['sort_by' => 'title', 'sort_order' => 'asc'])
            ->willReturn($response);

        $visibleSiteIds = $this->executePermissionLogic(null);

        $this->assertEmpty($visibleSiteIds);
    }

    /**
     * Test that global admin can see all sites
     */
    public function testGlobalAdminCanSeeAllSites()
    {
        // Setup global admin user
        $this->userMock->expects($this->any())
            ->method('getRole')
            ->willReturn('global_admin');

        $this->userMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->view->expects($this->any())
            ->method('identity')
            ->willReturn($this->userMock);

        // Create both public and private sites
        $publicSite = $this->createMockSite(1, true);
        $privateSite = $this->createMockSite(2, false);

        // Setup API response
        $response = $this->createMock('Omeka\Api\Response');
        $response->expects($this->once())
            ->method('getContent')
            ->willReturn([$publicSite, $privateSite]);

        $this->apiMock->expects($this->once())
            ->method('search')
            ->with('sites', ['sort_by' => 'title', 'sort_order' => 'asc'])
            ->willReturn($response);

        $visibleSiteIds = $this->executePermissionLogic($this->userMock);

        $this->assertContains(1, $visibleSiteIds);
        $this->assertContains(2, $visibleSiteIds);
    }

    /**
     * Test that supervisor can see all sites
     */
    public function testSupervisorCanSeeAllSites()
    {
        // Setup supervisor user
        $this->userMock->expects($this->any())
            ->method('getRole')
            ->willReturn('supervisor');

        $this->userMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->view->expects($this->any())
            ->method('identity')
            ->willReturn($this->userMock);

        // Create both public and private sites
        $publicSite = $this->createMockSite(1, true);
        $privateSite = $this->createMockSite(2, false);

        // Setup API response
        $response = $this->createMock('Omeka\Api\Response');
        $response->expects($this->once())
            ->method('getContent')
            ->willReturn([$publicSite, $privateSite]);

        $this->apiMock->expects($this->once())
            ->method('search')
            ->with('sites', ['sort_by' => 'title', 'sort_order' => 'asc'])
            ->willReturn($response);

        $visibleSiteIds = $this->executePermissionLogic($this->userMock);

        $this->assertContains(1, $visibleSiteIds);
        $this->assertContains(2, $visibleSiteIds);
    }

    /**
     * Test that regular user with site permissions can see private site
     */
    public function testRegularUserWithPermissionsCanSeePrivateSite()
    {
        // Setup regular user
        $this->userMock->expects($this->any())
            ->method('getRole')
            ->willReturn('researcher');

        $this->userMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->view->expects($this->any())
            ->method('identity')
            ->willReturn($this->userMock);

        // Create private site with user permission
        $privateSite = $this->createMockSiteWithPermission(2, false, 1, 'viewer');

        // Setup API response
        $response = $this->createMock('Omeka\Api\Response');
        $response->expects($this->once())
            ->method('getContent')
            ->willReturn([$privateSite]);

        $this->apiMock->expects($this->once())
            ->method('search')
            ->with('sites', ['sort_by' => 'title', 'sort_order' => 'asc'])
            ->willReturn($response);

        $visibleSiteIds = $this->executePermissionLogic($this->userMock);

        $this->assertContains(2, $visibleSiteIds);
    }

    /**
     * Test that regular user without site permissions cannot see private site
     */
    public function testRegularUserWithoutPermissionsCannotSeePrivateSite()
    {
        // Setup regular user
        $this->userMock->expects($this->any())
            ->method('getRole')
            ->willReturn('researcher');

        $this->userMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->view->expects($this->any())
            ->method('identity')
            ->willReturn($this->userMock);

        // Create private site with NO user permission
        $privateSite = $this->createMockSiteWithPermission(2, false, 2, 'viewer'); // Different user ID

        // Setup API response
        $response = $this->createMock('Omeka\Api\Response');
        $response->expects($this->once())
            ->method('getContent')
            ->willReturn([$privateSite]);

        $this->apiMock->expects($this->once())
            ->method('search')
            ->with('sites', ['sort_by' => 'title', 'sort_order' => 'asc'])
            ->willReturn($response);

        $visibleSiteIds = $this->executePermissionLogic($this->userMock);

        $this->assertEmpty($visibleSiteIds);
    }

    /**
     * Test filtering of items with visible sites only
     */
    public function testItemFilteringWithVisibleSites()
    {
        // Setup user with access to site ID 1 only
        $visibleSiteIds = [1];

        // Create mock items
        $item1 = $this->createMockItemWithSites([1, 2]); // Has visible site
        $item2 = $this->createMockItemWithSites([2, 3]); // No visible sites
        $item3 = $this->createMockItemWithSites([1]);    // Has visible site

        $items = [$item1, $item2, $item3];

        $visibleItems = $this->filterItemsBySiteVisibility($items, $visibleSiteIds);

        $this->assertCount(2, $visibleItems);
        $this->assertContains($item1, $visibleItems);
        $this->assertContains($item3, $visibleItems);
        $this->assertNotContains($item2, $visibleItems);
    }

    /**
     * Test filtering of site pages with visible sites only
     */
    public function testSitePageFilteringWithVisibleSites()
    {
        // Setup user with access to site ID 1 only
        $visibleSiteIds = [1];

        // Create mock site pages
        $sitePage1 = $this->createMockSitePageWithSite(1); // Visible site
        $sitePage2 = $this->createMockSitePageWithSite(2); // Not visible site

        $sitePages = [$sitePage1, $sitePage2];

        $visibleCount = $this->countVisibleSitePages($sitePages, $visibleSiteIds);

        $this->assertEquals(1, $visibleCount);
    }

    /**
     * Test filtering of item sets with visible sites only
     */
    public function testItemSetFilteringWithVisibleSites()
    {
        // Setup user with access to site ID 1 only
        $visibleSiteIds = [1];

        // Create mock item sets
        $itemSet1 = $this->createMockItemSetWithSites([1, 2]); // Has visible site
        $itemSet2 = $this->createMockItemSetWithSites([2, 3]); // No visible sites

        $itemSets = [$itemSet1, $itemSet2];

        $visibleCount = $this->countVisibleItemSets($itemSets, $visibleSiteIds);

        $this->assertEquals(1, $visibleCount);
    }

    /**
     * Helper method to create mock site
     */
    private function createMockSite($id, $isPublic)
    {
        $site = $this->createMock('Omeka\Api\Representation\SiteRepresentation');
        $site->expects($this->any())
            ->method('id')
            ->willReturn($id);
        $site->expects($this->any())
            ->method('isPublic')
            ->willReturn($isPublic);
        $site->expects($this->any())
            ->method('sitePermissions')
            ->willReturn([]);

        return $site;
    }

    /**
     * Helper method to create mock site with permission
     */
    private function createMockSiteWithPermission($siteId, $isPublic, $userId, $role)
    {
        $site = $this->createMock('Omeka\Api\Representation\SiteRepresentation');
        $site->expects($this->any())
            ->method('id')
            ->willReturn($siteId);
        $site->expects($this->any())
            ->method('isPublic')
            ->willReturn($isPublic);

        $permission = $this->createMock('Omeka\Api\Representation\SitePermissionRepresentation');
        $permissionUser = $this->createMock('Omeka\Api\Representation\UserRepresentation');

        $permissionUser->expects($this->any())
            ->method('id')
            ->willReturn($userId);

        $permission->expects($this->any())
            ->method('user')
            ->willReturn($permissionUser);
        $permission->expects($this->any())
            ->method('role')
            ->willReturn($role);

        $site->expects($this->any())
            ->method('sitePermissions')
            ->willReturn([$permission]);

        return $site;
    }

    /**
     * Helper method to create mock item with sites
     */
    private function createMockItemWithSites($siteIds)
    {
        $item = $this->createMock('Omeka\Api\Representation\ItemRepresentation');
        $sites = [];

        foreach ($siteIds as $siteId) {
            $site = $this->createMock('Omeka\Api\Representation\SiteRepresentation');
            $site->expects($this->any())
                ->method('id')
                ->willReturn($siteId);
            $sites[] = $site;
        }

        $item->expects($this->any())
            ->method('sites')
            ->willReturn($sites);

        return $item;
    }

    /**
     * Helper method to create mock site page with site
     */
    private function createMockSitePageWithSite($siteId)
    {
        $sitePage = $this->createMock('Omeka\Api\Representation\SitePageRepresentation');
        $site = $this->createMock('Omeka\Api\Representation\SiteRepresentation');

        $site->expects($this->any())
            ->method('id')
            ->willReturn($siteId);

        $sitePage->expects($this->any())
            ->method('site')
            ->willReturn($site);

        return $sitePage;
    }

    /**
     * Helper method to create mock item set with sites
     */
    private function createMockItemSetWithSites($siteIds)
    {
        $itemSet = $this->createMock('Omeka\Api\Representation\ItemSetRepresentation');
        $sites = [];

        foreach ($siteIds as $siteId) {
            $site = $this->createMock('Omeka\Api\Representation\SiteRepresentation');
            $site->expects($this->any())
                ->method('id')
                ->willReturn($siteId);
            $sites[] = $site;
        }

        $itemSet->expects($this->any())
            ->method('sites')
            ->willReturn($sites);

        return $itemSet;
    }

    /**
     * Execute the permission logic (extracted from the view templates)
     */
    private function executePermissionLogic($user)
    {
        $visible_site_ids = [];

        // Get all visible sites using API
        $response = $this->apiMock->search('sites', ['sort_by' => 'title', 'sort_order' => 'asc']);
        $allSites = $response->getContent();

        // Filter based on permissions
        foreach ($allSites as $site) {
            $canView = false;

            // Check if site is public
            if ($site->isPublic()) {
                $canView = true;
            } elseif ($user) {
                // Check user permissions for private sites
                if (in_array($user->getRole(), ['global_admin', 'supervisor'])) {
                    $canView = true;
                } else {
                    // Check if the current user has any role on this site
                    $sitePermissions = $site->sitePermissions();
                    foreach ($sitePermissions as $permission) {
                        if ($permission->user()->id() === $user->getId() &&
                            in_array($permission->role(), ['viewer', 'editor', 'manager'])) {
                            $canView = true;
                            break;
                        }
                    }
                }
            }

            if ($canView) {
                $visible_site_ids[] = $site->id();
            }
        }

        return $visible_site_ids;
    }

    /**
     * Filter items by site visibility (extracted from the view templates)
     */
    private function filterItemsBySiteVisibility($items, $visibleSiteIds)
    {
        $visibleItems = [];
        foreach ($items as $item) {
            $sites = $item->sites();
            $hasVisibleSites = false;
            foreach ($sites as $site) {
                if (in_array($site->id(), $visibleSiteIds)) {
                    $hasVisibleSites = true;
                    break;
                }
            }
            if ($hasVisibleSites) {
                $visibleItems[] = $item;
            }
        }
        return $visibleItems;
    }

    /**
     * Count visible site pages (extracted from the view templates)
     */
    private function countVisibleSitePages($sitePages, $visibleSiteIds)
    {
        $visibleSitePageCount = 0;
        foreach ($sitePages as $sitePage) {
            $site = $sitePage->site();
            if (in_array($site->id(), $visibleSiteIds)) {
                $visibleSitePageCount++;
            }
        }
        return $visibleSitePageCount;
    }

    /**
     * Count visible item sets (extracted from the view templates)
     */
    private function countVisibleItemSets($itemSets, $visibleSiteIds)
    {
        $visibleItemSetCount = 0;
        foreach ($itemSets as $itemSet) {
            $sites = $itemSet->sites();
            $hasVisibleSites = false;
            foreach ($sites as $site) {
                if (in_array($site->id(), $visibleSiteIds)) {
                    $hasVisibleSites = true;
                    break;
                }
            }
            if ($hasVisibleSites) {
                $visibleItemSetCount++;
            }
        }
        return $visibleItemSetCount;
    }
}