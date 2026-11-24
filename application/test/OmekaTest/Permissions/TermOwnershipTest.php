<?php
namespace OmekaTest\Permissions;

use Omeka\Api\Exception\PermissionDeniedException;
use Omeka\Entity\User;
use Omeka\Entity\Vocabulary;
use Omeka\Service\AclFactory;
use Omeka\Test\DbTestCase;

/**
 * A Supervisor may change, add, and delete the properties and classes of a
 * vocabulary they own, and not those of another's, through the API.
 */
class TermOwnershipTest extends DbTestCase
{
    /**
     * @var \Laminas\ServiceManager\ServiceManager
     */
    protected $services;

    /**
     * @var \Omeka\Permissions\Acl
     */
    protected $installAcl;

    /**
     * @var User
     */
    protected $supervisor;

    /**
     * @var int
     */
    protected $vocabularyId;

    public function setUp(): void
    {
        parent::setUp();
        $this->services = self::getApplication()->getServiceManager();
        $em = $this->services->get('Omeka\EntityManager');

        // The tests' application made its ACL while installing Omeka, when
        // every privilege is allowed, so check against one with the rules.
        // Making the module manager marks Omeka as installed, as in
        // SiteSettingsTest, so the new ACL gets the rules.
        $this->services->get('Omeka\ModuleManager');
        $this->installAcl = $this->services->get('Omeka\Acl');
        $this->services->setAllowOverride(true);
        $this->services->setService('Omeka\Acl', (new AclFactory)($this->services, 'Omeka\Acl'));
        $this->services->setAllowOverride(false);

        $this->supervisor = new User;
        $this->supervisor->setEmail('term-supervisor@example.com');
        $this->supervisor->setName('Supervisor');
        $this->supervisor->setRole('site_admin');
        $this->supervisor->setIsActive(true);
        $em->persist($this->supervisor);
        $em->flush();

        // The admin's vocabulary, and the admin's terms.
        $this->signIn($em->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']));
        $this->vocabularyId = $this->api()->create('vocabularies', [
            'o:namespace_uri' => 'http://example.org/terms#',
            'o:prefix' => 'terms',
            'o:label' => 'Terms',
            'o:class' => [['o:local_name' => 'Thing', 'o:label' => 'Thing']],
            'o:property' => [['o:local_name' => 'name', 'o:label' => 'Name']],
        ])->getContent()->id();
    }

    public function tearDown(): void
    {
        $this->services->get('Omeka\AuthenticationService')->clearIdentity();
        $this->services->setAllowOverride(true);
        $this->services->setService('Omeka\Acl', $this->installAcl);
        $this->services->setAllowOverride(false);
        parent::tearDown();
    }

    protected function signIn(User $user)
    {
        $this->services->get('Omeka\AuthenticationService')->getStorage()->write($user);
    }

    protected function api()
    {
        return $this->services->get('Omeka\ApiManager');
    }

    /**
     * The tests' vocabulary's terms, as [API resource name, id].
     *
     * @return array
     */
    protected function terms()
    {
        $terms = [];
        foreach (['resource_classes', 'properties'] as $resource) {
            $term = $this->api()->search($resource, ['vocabulary_id' => $this->vocabularyId])->getContent()[0];
            $terms[] = [$resource, $term->id()];
        }
        return $terms;
    }

    /**
     * A new term for the tests' vocabulary, as the API takes it.
     *
     * @return array
     */
    protected function newTerm()
    {
        return [
            'o:local_name' => 'added',
            'o:label' => 'Added',
            'o:vocabulary' => ['o:id' => $this->vocabularyId],
        ];
    }

    /**
     * Whether the API refuses a request as not permitted.
     *
     * @param callable $request
     * @return bool
     */
    protected function isRefused(callable $request)
    {
        try {
            $request();
        } catch (PermissionDeniedException $e) {
            return true;
        }
        return false;
    }

    public function testSupervisorRefusedForAnothersTerms()
    {
        $this->signIn($this->supervisor);
        foreach ($this->terms() as [$resource, $id]) {
            $this->assertTrue($this->isRefused(function () use ($resource, $id) {
                $this->api()->update($resource, $id, ['o:label' => 'Changed'], [], ['isPartial' => true]);
            }), "Update of $resource allowed");
            $this->assertTrue($this->isRefused(function () use ($resource, $id) {
                $this->api()->delete($resource, $id);
            }), "Delete of $resource allowed");
            $this->assertTrue($this->isRefused(function () use ($resource) {
                $this->api()->create($resource, $this->newTerm());
            }), "Create of $resource allowed");
        }
    }

    public function testSupervisorAllowedForTheirVocabularysTerms()
    {
        // The vocabulary is the Supervisor's, the terms still the admin's.
        $em = $this->services->get('Omeka\EntityManager');
        $em->find(Vocabulary::class, $this->vocabularyId)->setOwner($this->supervisor);
        $em->flush();

        $this->signIn($this->supervisor);
        foreach ($this->terms() as [$resource, $id]) {
            $this->api()->update($resource, $id, ['o:label' => 'Changed'], [], ['isPartial' => true]);
            $this->assertSame('Changed', $this->api()->read($resource, $id)->getContent()->label());
            $this->api()->delete($resource, $id);
            $this->assertSame(0, $this->api()->search($resource, ['id' => $id])->getTotalResults());
            $added = $this->api()->create($resource, $this->newTerm())->getContent();
            $this->assertSame($this->vocabularyId, $added->vocabulary()->id());
        }
    }
}
