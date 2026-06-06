<?php
namespace OmekaTest\Search\CrossSite;

use Omeka\Test\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ItemSetResultPermissionTest extends TestCase
{
    private $view;
    private $apiMock;
    private $userMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->view = $this->getMockBuilder('Laminas\View\Renderer\PhpRenderer')
            ->setMethods(['api', 'identity', 'params', 'hyperlink', 'pagination', 'translate', 'escapeHtml', 'url'])
            ->getMock();

        $this->apiMock = $this->createMock('Omeka\Api\Manager');
        $this->userMock = $this->createMock('Omeka\Entity\User');

        $this->view->expects($this->any())
            ->method('api')
            ->willReturn($this->apiMock);
    }

    /**
     * Test that item sets with only private sites are not visible to anonymous users
     */
    public function testItemSetsWithPrivateSitesNotVisibleToAnonymousUsers()
    {
        // Setup anonymous user (no identity)
        $this->view->expects($this->any())
            ->method('identity')
            ->willReturn(null);

        // Create private site
        $privateSite = $this->createMockSite(1, false);

        // Setup API response for site permissions check
        $response = $this->createMock('Omeka\Api\Response');
        $response->expects($this->once())
            ->method('getContent')
            ->willReturn([$privateSite]);

        $this->apiMock->expects($this->once())
            ->method('search')
            ->with('sites', ['sort_by' => 'title', 'sort_order' => 'asc'])
            ->willReturn($response);

        // Create item set with only private sites
        $itemSetWithPrivateSite = $this->createMockItemSetWithSites([1]);
        $itemSets = [$itemSetWithPrivateSite];

        $visibleItemSets = $this->executeItemSetFiltering($itemSets, null);

        $this->assertEmpty($visibleItemSets);
    }

    /**
     * Test that item sets with public sites are visible to anonymous users
     */
    public function testItemSetsWithPublicSitesVisibleToAnonymousUsers()
    {
        // Setup anonymous user (no identity)
        $this->view->expects($this->any())
            ->method('identity')
            ->willReturn(null);

        // Create public site
        $publicSite = $this->createMockSite(1, true);

        // Setup API response for site permissions check
        $response = $this->createMock('Omeka\Api\Response');
        $response->expects($this->once())
            ->method('getContent')
            ->willReturn([$publicSite]);

        $this->apiMock->expects($this->once())
            ->method('search')
            ->with('sites', ['sort_by' => 'title', 'sort_order' => 'asc'])
            ->willReturn($response);

        // Create item set with public site
        $itemSetWithPublicSite = $this->createMockItemSetWithSites([1]);
        $itemSets = [$itemSetWithPublicSite];

        $visibleItemSets = $this->executeItemSetFiltering($itemSets, null);

        $this->assertCount(1, $visibleItemSets);
        $this->assertContains($itemSetWithPublicSite, $visibleItemSets);
    }

    /**
     * Test that global admin can see item sets from all sites
     */
    public function testGlobalAdminCanSeeItemSetsFromAllSites()
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

        // Create item sets with different site combinations
        $itemSetPublic = $this->createMockItemSetWithSites([1]);
        $itemSetPrivate = $this->createMockItemSetWithSites([2]);
        $itemSetBoth = $this->createMockItemSetWithSites([1, 2]);
        $itemSets = [$itemSetPublic, $itemSetPrivate, $itemSetBoth];

        $visibleItemSets = $this->executeItemSetFiltering($itemSets, $this->userMock);

        $this->assertCount(3, $visibleItemSets);
        $this->assertContains($itemSetPublic, $visibleItemSets);
        $this->assertContains($itemSetPrivate, $visibleItemSets);
        $this->assertContains($itemSetBoth, $visibleItemSets);
    }

    /**
     * Test that supervisor can see item sets from all sites
     */
    public function testSupervisorCanSeeItemSetsFromAllSites()
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

        // Create item sets with private sites
        $itemSetPrivate = $this->createMockItemSetWithSites([2]);
        $itemSets = [$itemSetPrivate];

        $visibleItemSets = $this->executeItemSetFiltering($itemSets, $this->userMock);

        $this->assertCount(1, $visibleItemSets);
        $this->assertContains($itemSetPrivate, $visibleItemSets);
    }

    /**
     * Test that regular user with site permissions can see item sets from permitted private sites
     */
    public function testRegularUserWithPermissionsCanSeeItemSetsFromPermittedSites()
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

        // Create private site with user permission and one without
        $privateSiteWithPermission = $this->createMockSiteWithPermission(1, false, 1, 'viewer');
        $privateSiteWithoutPermission = $this->createMockSite(2, false);

        // Setup API response
        $response = $this->createMock('Omeka\Api\Response');
        $response->expects($this->once())
            ->method('getContent')
            ->willReturn([$privateSiteWithPermission, $privateSiteWithoutPermission]);

        $this->apiMock->expects($this->once())
            ->method('search')
            ->with('sites', ['sort_by' => 'title', 'sort_order' => 'asc'])
            ->willReturn($response);

        // Create item sets with different site associations
        $itemSetPermitted = $this->createMockItemSetWithSites([1]);         // User has access
        $itemSetNotPermitted = $this->createMockItemSetWithSites([2]);      // User has no access
        $itemSetMixed = $this->createMockItemSetWithSites([1, 2]);          // User has partial access
        $itemSets = [$itemSetPermitted, $itemSetNotPermitted, $itemSetMixed];

        $visibleItemSets = $this->executeItemSetFiltering($itemSets, $this->userMock);

        $this->assertCount(2, $visibleItemSets);
        $this->assertContains($itemSetPermitted, $visibleItemSets);
        $this->assertNotContains($itemSetNotPermitted, $visibleItemSets);
        $this->assertContains($itemSetMixed, $visibleItemSets); // Should be visible because it has at least one accessible site
    }

    /**
     * Test that regular user without permissions cannot see item sets from private sites
     */
    public function testRegularUserWithoutPermissionsCannotSeeItemSetsFromPrivateSites()
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

        // Create private site with permission for different user
        $privateSiteWithoutUserPermission = $this->createMockSiteWithPermission(1, false, 2, 'viewer'); // Different user ID

        // Setup API response
        $response = $this->createMock('Omeka\Api\Response');
        $response->expects($this->once())
            ->method('getContent')
            ->willReturn([$privateSiteWithoutUserPermission]);

        $this->apiMock->expects($this->once())
            ->method('search')
            ->with('sites', ['sort_by' => 'title', 'sort_order' => 'asc'])
            ->willReturn($response);

        // Create item set with private site user cannot access
        $itemSetPrivate = $this->createMockItemSetWithSites([1]);
        $itemSets = [$itemSetPrivate];

        $visibleItemSets = $this->executeItemSetFiltering($itemSets, $this->userMock);

        $this->assertEmpty($visibleItemSets);
    }

    /**
     * Test mixed visibility scenarios - item sets with both public and private sites
     */
    public function testMixedVisibilityScenarios()
    {
        // Setup regular user with limited permissions
        $this->userMock->expects($this->any())
            ->method('getRole')
            ->willReturn('researcher');

        $this->userMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->view->expects($this->any())
            ->method('identity')
            ->willReturn($this->userMock);

        // Create sites with different permissions
        $publicSite = $this->createMockSite(1, true);                                    // Public - accessible
        $privateSiteAccessible = $this->createMockSiteWithPermission(2, false, 1, 'viewer'); // Private but accessible
        $privateSiteInaccessible = $this->createMockSite(3, false);                     // Private - not accessible

        // Setup API response
        $response = $this->createMock('Omeka\Api\Response');
        $response->expects($this->once())
            ->method('getContent')
            ->willReturn([$publicSite, $privateSiteAccessible, $privateSiteInaccessible]);

        $this->apiMock->expects($this->once())
            ->method('search')
            ->with('sites', ['sort_by' => 'title', 'sort_order' => 'asc'])
            ->willReturn($response);

        // Create item sets with various site combinations
        $itemSetOnlyPublic = $this->createMockItemSetWithSites([1]);           // Should be visible
        $itemSetOnlyAccessiblePrivate = $this->createMockItemSetWithSites([2]); // Should be visible
        $itemSetOnlyInaccessiblePrivate = $this->createMockItemSetWithSites([3]); // Should NOT be visible
        $itemSetMixedAccessible = $this->createMockItemSetWithSites([1, 2]);   // Should be visible
        $itemSetMixedInaccessible = $this->createMockItemSetWithSites([1, 3]); // Should be visible (has public site)
        $itemSetAllPrivate = $this->createMockItemSetWithSites([2, 3]);        // Should be visible (has accessible private)
        $itemSets = [
            $itemSetOnlyPublic,
            $itemSetOnlyAccessiblePrivate,
            $itemSetOnlyInaccessiblePrivate,
            $itemSetMixedAccessible,
            $itemSetMixedInaccessible,
            $itemSetAllPrivate
        ];

        $visibleItemSets = $this->executeItemSetFiltering($itemSets, $this->userMock);

        $this->assertCount(5, $visibleItemSets);
        $this->assertContains($itemSetOnlyPublic, $visibleItemSets);
        $this->assertContains($itemSetOnlyAccessiblePrivate, $visibleItemSets);
        $this->assertNotContains($itemSetOnlyInaccessiblePrivate, $visibleItemSets);
        $this->assertContains($itemSetMixedAccessible, $visibleItemSets);
        $this->assertContains($itemSetMixedInaccessible, $visibleItemSets);
        $this->assertContains($itemSetAllPrivate, $visibleItemSets);
    }

    /**
     * Test site link filtering - only visible sites should have links displayed
     */
    public function testSiteLinkFiltering()
    {
        // Setup user with access to site 1 only
        $visibleSiteIds = [1];

        // Create item set with multiple sites
        $itemSet = $this->createMockItemSetWithSites([1, 2, 3]);

        // Get sites for the item set
        $sites = $itemSet->sites();

        $visibleSiteLinks = [];
        foreach ($sites as $site) {
            if (in_array($site->id(), $visibleSiteIds)) {
                $visibleSiteLinks[] = $site;
            }
        }

        $this->assertCount(1, $visibleSiteLinks);
        $this->assertEquals(1, $visibleSiteLinks[0]->id());
    }

    /**
     * Test that different user roles can access appropriate private site item sets
     */
    public function testDifferentRoleAccess()
    {
        $testRoles = ['viewer', 'editor', 'manager'];

        foreach ($testRoles as $role) {
            // Setup user with specific role
            $user = $this->createMock('Omeka\Entity\User');
            $user->expects($this->any())
                ->method('getRole')
                ->willReturn('researcher');
            $user->expects($this->any())
                ->method('getId')
                ->willReturn(1);

            $this->view->expects($this->any())
                ->method('identity')
                ->willReturn($user);

            // Create private site with user having the current role
            $privateSite = $this->createMockSiteWithPermission(1, false, 1, $role);

            // Setup API response
            $response = $this->createMock('Omeka\Api\Response');
            $response->expects($this->any())
                ->method('getContent')
                ->willReturn([$privateSite]);

            $this->apiMock->expects($this->any())
                ->method('search')
                ->with('sites', ['sort_by' => 'title', 'sort_order' => 'asc'])
                ->willReturn($response);

            // Create item set with private site
            $itemSet = $this->createMockItemSetWithSites([1]);
            $itemSets = [$itemSet];

            $visibleItemSets = $this->executeItemSetFiltering($itemSets, $user);

            $this->assertCount(1, $visibleItemSets, "User with role '$role' should be able to see item set from private site");
            $this->assertContains($itemSet, $visibleItemSets);
        }
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
        $itemSet->expects($this->any())
            ->method('displayTitle')
            ->willReturn('Test Item Set ' . implode(',', $siteIds));

        return $itemSet;
    }

    /**
     * Execute the item set filtering logic (extracted from the view template)
     */
    private function executeItemSetFiltering($itemSets, $user)
    {
        $visible_site_ids = [];

        // Get all visible sites using API
        $response = $this->apiMock->search('sites', ['sort_by' => 'title', 'sort_order' => 'asc']);
        $allSites = $response->getContent();

        // Filter based on permissions (same logic as other templates)
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

        // Filter item sets to only show those with visible sites
        $visibleItemSets = [];
        foreach ($itemSets as $itemSet) {
            $sites = $itemSet->sites();
            $hasVisibleSites = false;
            foreach ($sites as $site) {
                if (in_array($site->id(), $visible_site_ids)) {
                    $hasVisibleSites = true;
                    break;
                }
            }
            if ($hasVisibleSites) {
                $visibleItemSets[] = $itemSet;
            }
        }

        return $visibleItemSets;
    }
}