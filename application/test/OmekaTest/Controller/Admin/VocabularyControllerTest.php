<?php
namespace OmekaTest\Controller\Admin;

use Laminas\Form\Element\Csrf;
use Laminas\Stdlib\Parameters;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Entity\User;
use Omeka\Entity\Vocabulary;
use Omeka\Form\ConfirmForm;
use Omeka\Form\VocabularyForm;
use Omeka\Form\VocabularyUpdateForm;
use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Permissions\Exception\PermissionDeniedException;
use Omeka\Stdlib\RdfImporter;
use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * Requests to the vocabulary pages, signed in as the test database's admin or
 * as a Supervisor.
 *
 * What a request writes is committed, so each test deletes the vocabulary and
 * the user it makes.
 */
class VocabularyControllerTest extends AbstractHttpControllerTestCase
{
    const NAMESPACE_URI = 'http://example.org/review#';

    const ADMIN = 'admin@example.com';

    const SUPERVISOR = 'vocabulary-supervisor@example.com';

    /**
     * @var int
     */
    protected $vocabularyId;

    public function setUp(): void
    {
        parent::setUp();
        $em = $this->getApplicationServiceLocator()->get('Omeka\EntityManager');
        $supervisor = new User;
        $supervisor->setEmail(self::SUPERVISOR);
        $supervisor->setName('Supervisor');
        $supervisor->setRole('site_admin');
        $supervisor->setIsActive(true);
        $em->persist($supervisor);
        $em->flush();

        $this->signIn();
        $vocabulary = $this->api()->create('vocabularies', [
            'o:namespace_uri' => self::NAMESPACE_URI,
            'o:prefix' => 'review',
            'o:label' => 'Review',
            'o:class' => [[
                'o:local_name' => 'Thing',
                'o:label' => '<i>stored class label</i>',
                'o:comment' => '<i>stored class comment</i>',
            ]],
            'o:property' => [[
                'o:local_name' => 'name',
                'o:label' => '<i>stored property label</i>',
                'o:comment' => '<i>stored property comment</i>',
            ]],
        ])->getContent();
        $this->vocabularyId = $vocabulary->id();
    }

    public function tearDown(): void
    {
        if ($this->vocabularyId && $this->vocabularyExists()) {
            $this->api()->delete('vocabularies', $this->vocabularyId);
        }
        $em = $this->getApplicationServiceLocator()->get('Omeka\EntityManager');
        if ($supervisor = $em->getRepository(User::class)->findOneBy(['email' => self::SUPERVISOR])) {
            $em->remove($supervisor);
            $em->flush();
        }
        parent::tearDown();
    }

    /**
     * Sign in as a user of the test database.
     *
     * @param string $email
     */
    protected function signIn($email = self::ADMIN)
    {
        $services = $this->getApplicationServiceLocator();
        $user = $services->get('Omeka\EntityManager')->getRepository(User::class)
            ->findOneBy(['email' => $email]);
        $services->get('Omeka\AuthenticationService')->getStorage()->write($user);
    }

    /**
     * The API of the application the next request goes to.
     *
     * @return \Omeka\Api\Manager
     */
    protected function api()
    {
        return $this->getApplicationServiceLocator()->get('Omeka\ApiManager');
    }

    /**
     * Send a request as a user, keeping the session, as a browser would.
     *
     * @param string $email
     * @param string $method
     * @param string $path
     * @param array $data
     * @param array $files Uploads, as PHP gives them in $_FILES
     * @return int The response's HTTP status
     */
    protected function requestAs($email, $method, $path, array $data = [], array $files = [])
    {
        $this->reset(true);
        $this->signIn($email);
        $this->getRequest()->setFiles(new Parameters($files));
        try {
            $this->dispatch($path, $method, $data);
        } catch (PermissionDeniedException $e) {
            // The test framework rethrows what Omeka answers with 403.
        }
        return $this->getResponse()->getStatusCode();
    }

    /**
     * A form's CSRF field, as a page showing the form would give it.
     *
     * @param string $formClass
     * @return array
     */
    protected function csrfField($formClass)
    {
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get($formClass);
        foreach ($form->getElements() as $element) {
            if ($element instanceof Csrf) {
                return [$element->getName() => $element->getValue()];
            }
        }
        $this->fail("No CSRF field in $formClass");
    }

    /**
     * Make a user the owner of the tests' vocabulary.
     *
     * @param string $email
     */
    protected function giveVocabularyTo($email)
    {
        $em = $this->getApplicationServiceLocator()->get('Omeka\EntityManager');
        $em->find(Vocabulary::class, $this->vocabularyId)
            ->setOwner($em->getRepository(User::class)->findOneBy(['email' => $email]));
        $em->flush();
    }

    /**
     * A diff, as the review screen posts it, that changes one property's
     * label.
     *
     * @param string $localName
     * @param string $from
     * @param string $to
     * @return string
     */
    protected function labelDiff($localName, $from, $to)
    {
        return json_encode([
            'classes' => ['new' => [], 'label' => [], 'comment' => []],
            'properties' => ['new' => [], 'label' => [$localName => [$from, $to]], 'comment' => []],
        ]);
    }

    /**
     * The messages of a type the last request left for the next page.
     *
     * @param int $type A Messenger constant
     * @return string[]
     */
    protected function messages($type)
    {
        $messages = $this->getApplicationServiceLocator()->get('ControllerPluginManager')->get('messenger')->get();
        return array_map('strval', $messages[$type] ?? []);
    }

    /**
     * A property's label as Omeka has it now.
     *
     * @param string $term
     * @return string
     */
    protected function propertyLabel($term)
    {
        $this->reset(true);
        $this->signIn();
        return $this->api()->search('properties', ['term' => $term])->getContent()[0]->label();
    }

    /**
     * Whether a vocabulary is in Omeka now, by default the tests' vocabulary.
     *
     * @param int|null $id
     * @return bool
     */
    protected function vocabularyExists($id = null)
    {
        $this->reset(true);
        $this->signIn();
        try {
            $this->api()->read('vocabularies', $id ?? $this->vocabularyId);
            return true;
        } catch (NotFoundException $e) {
            return false;
        }
    }

    /**
     * Have the next request read a file of the tests' in place of fetching
     * the URL it's given.
     *
     * @param string $file
     */
    protected function readFileForUrl($file)
    {
        $services = $this->getApplicationServiceLocator();
        $importer = new class($services->get('Omeka\ApiManager'), $services->get('Omeka\EntityManager')) extends RdfImporter {
            public $file;

            public function getMembers($strategy, $namespaceUri, array $options = [])
            {
                $options['file'] = $this->file;
                return parent::getMembers('file', $namespaceUri, $options);
            }
        };
        $importer->file = $file;
        $services->setAllowOverride(true);
        $services->setService('Omeka\RdfImporter', $importer);
        $services->setAllowOverride(false);
    }

    /**
     * The hidden CSRF fields of the forms on the last page requested.
     *
     * @return array
     */
    protected function csrfFields()
    {
        preg_match_all('/<input type="hidden" name="([a-z_]*csrf)" value="([^"]+)"/i', $this->getResponse()->getContent(), $matches);
        $this->assertNotEmpty($matches[1], 'No CSRF token on the page');
        return array_combine($matches[1], $matches[2]);
    }

    public function testReviewShowsValuesAsText()
    {
        $path = sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId);
        $this->dispatch($path);
        $csrf = $this->csrfFields();

        $this->reset(true);
        $this->signIn();
        $this->readFileForUrl(__DIR__ . '/_files/markup.ttl');
        $this->dispatch($path, 'POST', [
            'vocabulary-info' => [
                'o:label' => 'Review',
                'o:comment' => '',
                'o:namespace_uri' => self::NAMESPACE_URI,
            ],
            'vocabulary-file' => [
                'import_type' => 'url',
                'url' => 'http://example.org/review.ttl',
                'format' => 'turtle',
            ],
            'vocabulary-advanced' => [
                'lang' => '',
                'label_property' => '',
                'comment_property' => '',
            ],
        ] + $csrf);
        $this->assertResponseStatusCode(200);
        $this->assertTemplateName('omeka/admin/vocabulary/update');

        $page = $this->getResponse()->getContent();
        $values = [
            // Changed terms, from Omeka and from the file.
            '<i>stored class label</i>',
            '<i>file class label</i>',
            '<i>stored class comment</i>',
            '<i>file class comment</i>',
            '<i>stored property label</i>',
            '<i>file property label</i>',
            '<i>stored property comment</i>',
            '<i>file property comment</i>',
            // New terms.
            'Fish&Chips',
            '<i>new class label</i>',
            '<i>new class comment</i>',
            '<i>new property label</i>',
            '<i>new property comment</i>',
        ];
        foreach ($values as $value) {
            $this->assertTrue(str_contains($page, htmlspecialchars($value)), "Not shown as text: $value");
            $this->assertFalse(str_contains($page, $value), "Shown as markup: $value");
        }
        // What changed, named for people rather than by the diff's keys.
        $this->assertStringContainsString('<td>Label</td>', $page);
        $this->assertStringContainsString('<td>Comment</td>', $page);
        // Cancel goes to the list of vocabularies, not to one's own page.
        $this->assertStringContainsString('<a href="/admin/vocabulary" class="button">Cancel</a>', $page);
    }

    public function testEditRefusedForAnothersVocabulary()
    {
        $status = $this->requestAs(self::SUPERVISOR, 'GET', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId));
        $this->assertSame(403, $status);
    }

    public function testEditOpensForOwnVocabulary()
    {
        $this->giveVocabularyTo(self::SUPERVISOR);
        $status = $this->requestAs(self::SUPERVISOR, 'GET', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId));
        $this->assertSame(200, $status);
    }

    public function editsWithoutFile()
    {
        return [
            'upload with no file chosen' => ['upload'],
            'URL left empty' => ['url'],
        ];
    }

    /**
     * @dataProvider editsWithoutFile
     */
    public function testEditWithoutFileSaves($importType)
    {
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId), [
            'vocabulary-info' => [
                'o:label' => 'Renamed',
                'o:comment' => '',
                'o:namespace_uri' => self::NAMESPACE_URI,
            ],
            'vocabulary-file' => [
                'import_type' => $importType,
                'url' => '',
                'format' => 'guess',
            ],
            'vocabulary-advanced' => [
                'lang' => '',
                'label_property' => '',
                'comment_property' => '',
            ],
        ] + $this->csrfField(VocabularyForm::class), [
            // The file field, as a browser posts it with no file chosen.
            'vocabulary-file' => [
                'file' => ['name' => '', 'type' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0],
            ],
        ]);
        $this->assertSame(302, $status);
        $this->assertRedirectTo('/admin/vocabulary');
        $this->assertSame(['Vocabulary successfully updated'], $this->messages(Messenger::SUCCESS));
        $this->assertSame([], $this->messages(Messenger::ERROR));
        $this->reset(true);
        $this->signIn();
        $this->assertSame('Renamed', $this->api()->read('vocabularies', $this->vocabularyId)->getContent()->label());
    }

    /**
     * The edit form's fields, as a browser posts them, with a namespace
     * Dublin Core already has, which the API refuses.
     *
     * @param string $importType
     * @param string $url
     * @return array
     */
    protected function refusedEdit($importType, $url)
    {
        return [
            'vocabulary-info' => [
                'o:label' => 'Renamed',
                'o:comment' => '',
                'o:namespace_uri' => 'http://purl.org/dc/terms/',
            ],
            'vocabulary-file' => [
                'import_type' => $importType,
                'url' => $url,
                'format' => 'turtle',
            ],
            'vocabulary-advanced' => [
                'lang' => '',
                'label_property' => '',
                'comment_property' => '',
            ],
        ] + $this->csrfField(VocabularyForm::class);
    }

    public function testEditRefusedByApiShowsFormAgain()
    {
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId), $this->refusedEdit('upload', ''), [
            'vocabulary-file' => [
                'file' => ['name' => '', 'type' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0],
            ],
        ]);
        $this->assertSame(200, $status);
        $this->assertTemplateName('omeka/admin/vocabulary/edit');
        $page = $this->getResponse()->getContent();
        $this->assertStringContainsString('is already taken.', $page);
        $this->assertStringNotContainsString('Vocabulary successfully updated', $page);
        $this->reset(true);
        $this->signIn();
        $this->assertSame('Review', $this->api()->read('vocabularies', $this->vocabularyId)->getContent()->label());
    }

    public function testEditRefusedByApiStopsBeforeReview()
    {
        $data = $this->refusedEdit('url', 'http://example.org/review.ttl');
        $this->reset(true);
        $this->signIn();
        $this->readFileForUrl(__DIR__ . '/_files/markup.ttl');
        $this->dispatch(sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId), 'POST', $data);
        $this->assertResponseStatusCode(200);
        $this->assertTemplateName('omeka/admin/vocabulary/edit');
        $this->assertStringContainsString('is already taken.', $this->getResponse()->getContent());
    }

    public function testAcceptRefusedForPermanentVocabulary()
    {
        $dcterms = $this->api()->read('vocabularies', ['prefix' => 'dcterms'])->getContent();
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/update', $dcterms->id()), [
            'diff' => $this->labelDiff('title', 'Title', 'Changed'),
        ] + $this->csrfField(VocabularyUpdateForm::class));
        $this->assertSame(403, $status);
        $this->assertSame('Title', $this->propertyLabel('dcterms:title'));
    }

    public function testAcceptRefusedForAnothersVocabulary()
    {
        $status = $this->requestAs(self::SUPERVISOR, 'POST', sprintf('/admin/vocabulary/%s/update', $this->vocabularyId), [
            'diff' => $this->labelDiff('name', '<i>stored property label</i>', 'Changed'),
        ] + $this->csrfField(VocabularyUpdateForm::class));
        $this->assertSame(403, $status);
        $this->assertSame('<i>stored property label</i>', $this->propertyLabel('review:name'));
    }

    public function testAcceptAppliedForOwnVocabulary()
    {
        $this->giveVocabularyTo(self::SUPERVISOR);
        $status = $this->requestAs(self::SUPERVISOR, 'POST', sprintf('/admin/vocabulary/%s/update', $this->vocabularyId), [
            'diff' => $this->labelDiff('name', '<i>stored property label</i>', 'Changed'),
        ] + $this->csrfField(VocabularyUpdateForm::class));
        $this->assertSame(302, $status);
        $this->assertRedirectTo('/admin/vocabulary');
        $this->assertSame('Changed', $this->propertyLabel('review:name'));
    }

    public function invalidDiffs()
    {
        $none = ['new' => [], 'label' => [], 'comment' => []];
        $diff = function (array $properties) use ($none) {
            return json_encode(['classes' => $none, 'properties' => $properties + $none]);
        };
        return [
            'not JSON' => ['{'],
            'no classes' => [json_encode(['properties' => $none])],
            'changes not a list' => [$diff(['label' => 'Changed'])],
            'empty local name' => [$diff(['label' => ['' => ['Name', 'Changed']]])],
            'change not a pair' => [$diff(['label' => ['name' => 'Changed']])],
            'empty label' => [$diff(['label' => ['name' => ['Name', '']]])],
            'label too long' => [$diff(['label' => ['name' => ['Name', str_repeat('a', 256)]]])],
            'label not text' => [$diff(['label' => ['name' => ['Name', ['Changed']]]])],
            'comment not text' => [$diff(['comment' => ['name' => ['A name.', 5]]])],
            'new term without a label' => [$diff(['new' => ['added' => [null, 'Added.']]])],
            'new term without a comment' => [$diff(['new' => ['added' => ['Added']]])],
        ];
    }

    /**
     * @dataProvider invalidDiffs
     */
    public function testAcceptRefusesInvalidDiff($diff)
    {
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/update', $this->vocabularyId), [
            'diff' => $diff,
        ] + $this->csrfField(VocabularyUpdateForm::class));
        $this->assertSame(302, $status);
        $this->assertSame(['Invalid changes to the vocabulary'], $this->messages(Messenger::ERROR));
        $this->assertSame('<i>stored property label</i>', $this->propertyLabel('review:name'));
        $this->assertSame(1, $this->api()->search('properties', ['vocabulary_id' => $this->vocabularyId])->getTotalResults());
    }

    public function testAcceptTakesLabelOf255Characters()
    {
        $label = str_repeat('a', 255);
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/update', $this->vocabularyId), [
            'diff' => $this->labelDiff('name', '<i>stored property label</i>', $label),
        ] + $this->csrfField(VocabularyUpdateForm::class));
        $this->assertSame(302, $status);
        $this->assertSame($label, $this->propertyLabel('review:name'));
    }

    public function testAcceptSkipsTermsThatNoLongerExist()
    {
        // Changes to terms deleted since the review, beside one that's still
        // there.
        $diff = json_encode([
            'classes' => [
                'new' => [],
                'label' => ['Gone' => ['Gone', 'Changed']],
                'comment' => ['Gone' => [null, 'Changed.']],
            ],
            'properties' => [
                'new' => [],
                'label' => ['gone' => ['Gone', 'Changed'], 'name' => ['<i>stored property label</i>', 'Changed']],
                'comment' => ['gone' => [null, 'Changed.']],
            ],
        ]);
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/update', $this->vocabularyId), [
            'diff' => $diff,
        ] + $this->csrfField(VocabularyUpdateForm::class));
        $this->assertSame(302, $status);
        $this->assertSame(['Changes to the vocabulary successfully made'], $this->messages(Messenger::SUCCESS));
        $this->assertSame('Changed', $this->propertyLabel('review:name'));
    }

    public function testAcceptSkipsNewTermsThatAlreadyExist()
    {
        // Accepted twice, as from a review page gone back to: its new terms
        // are already in Omeka, beside one that isn't.
        $diff = json_encode([
            'classes' => [
                'new' => ['Thing' => ['Thing again', null]],
                'label' => [],
                'comment' => [],
            ],
            'properties' => [
                'new' => ['name' => ['Name again', null], 'added' => ['Added', null]],
                'label' => [],
                'comment' => [],
            ],
        ]);
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/update', $this->vocabularyId), [
            'diff' => $diff,
        ] + $this->csrfField(VocabularyUpdateForm::class));
        $this->assertSame(302, $status);
        $this->assertSame(['Changes to the vocabulary successfully made'], $this->messages(Messenger::SUCCESS));
        $this->assertSame('<i>stored property label</i>', $this->propertyLabel('review:name'));
        $this->assertSame('Added', $this->propertyLabel('review:added'));
        $this->assertSame(1, $this->api()->search('resource_classes', ['vocabulary_id' => $this->vocabularyId])->getTotalResults());
    }

    public function testDeleteRefusedForPermanentVocabulary()
    {
        $dcterms = $this->api()->read('vocabularies', ['prefix' => 'dcterms'])->getContent();
        $status = $this->requestAs(
            self::ADMIN,
            'POST',
            sprintf('/admin/vocabulary/%s/delete', $dcterms->id()),
            $this->csrfField(ConfirmForm::class)
        );
        $this->assertSame(403, $status);
        $this->assertTrue($this->vocabularyExists($dcterms->id()));
    }

    public function testDeleteRefusedForAnothersVocabulary()
    {
        $status = $this->requestAs(
            self::SUPERVISOR,
            'POST',
            sprintf('/admin/vocabulary/%s/delete', $this->vocabularyId),
            $this->csrfField(ConfirmForm::class)
        );
        $this->assertSame(403, $status);
        $this->assertTrue($this->vocabularyExists());
    }

    public function testDeleteAppliedForOwnVocabulary()
    {
        $this->giveVocabularyTo(self::SUPERVISOR);
        $status = $this->requestAs(
            self::SUPERVISOR,
            'POST',
            sprintf('/admin/vocabulary/%s/delete', $this->vocabularyId),
            $this->csrfField(ConfirmForm::class)
        );
        $this->assertSame(302, $status);
        $this->assertRedirectTo('/admin/vocabulary');
        $this->assertFalse($this->vocabularyExists());
    }
}
