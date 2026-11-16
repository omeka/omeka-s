<?php
namespace OmekaTest\Controller\Admin;

use EasyRdf\Http;
use EasyRdf\Http\Client as HttpClient;
use EasyRdf\Http\Response;
use Laminas\Form\Element\Csrf;
use Laminas\Stdlib\Parameters;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Entity\User;
use Omeka\Entity\Vocabulary;
use Omeka\Form\ConfirmForm;
use Omeka\Form\VocabularyForm;
use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Permissions\Exception\PermissionDeniedException;
use Omeka\Stdlib\RdfImporter;
use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * Requests to the vocabulary pages, signed in as the test database's admin or
 * as a Supervisor.
 *
 * What a request writes is committed, so each test deletes the vocabulary and
 * the user it makes, and ends the reviews it starts.
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

    /**
     * EasyRdf's HTTP client, while a test answers its requests.
     *
     * @var HttpClient|null
     */
    protected $httpClient;

    /**
     * The tokens of the reviews a test starts.
     *
     * @var string[]
     */
    protected $tokens = [];

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
        if ($this->httpClient) {
            Http::setDefaultHttpClient($this->httpClient);
        }
        $this->reset(true);
        $this->signIn();
        foreach ($this->tokens as $token) {
            $this->getApplicationServiceLocator()->get('Omeka\VocabularyReview')->cancel($token);
        }
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
     * The tests' vocabulary as Omeka has it now.
     *
     * @return \Omeka\Api\Representation\VocabularyRepresentation
     */
    protected function vocabulary()
    {
        $this->reset(true);
        $this->signIn();
        return $this->api()->read('vocabularies', $this->vocabularyId)->getContent();
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
     * Answer EasyRdf's requests with a file of the tests', in place of the
     * server at the URL asked for.
     *
     * @param string $file A file in _files
     */
    protected function serveFile($file)
    {
        $client = new class extends HttpClient {
            public $body;

            public function request($method = null)
            {
                return new Response(200, ['Content-Type' => 'text/turtle'], $this->body);
            }
        };
        $client->body = file_get_contents(__DIR__ . '/_files/' . $file);
        $this->httpClient ??= Http::getDefaultHttpClient();
        Http::setDefaultHttpClient($client);
    }

    /**
     * The edit form's fields, as a browser posts them, with a URL to read.
     *
     * @param array $info The vocabulary's own fields that differ
     * @return array
     */
    protected function editWithUrl(array $info = [])
    {
        return [
            'vocabulary-info' => $info + [
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
        ] + $this->csrfField(VocabularyForm::class);
    }

    /**
     * Start a review of a file of the tests', as a module would.
     *
     * @param string $file A file in _files
     * @param int|null $vocabularyId By default, the tests' vocabulary
     * @param array $reviewOptions
     * @return string The review's token
     */
    protected function startReview($file, $vocabularyId = null, array $reviewOptions = [])
    {
        $this->reset(true);
        $this->signIn();
        $url = $this->getApplicationServiceLocator()->get('Omeka\VocabularyReview')->start(
            $vocabularyId ?? $this->vocabularyId,
            __DIR__ . '/_files/' . $file,
            ['format' => 'turtle'],
            $reviewOptions
        );
        return $this->tokens[] = substr($url, strpos($url, '=') + 1);
    }

    /**
     * Post to a review's Accept or Cancel.
     *
     * @param string $action "accept" or "cancel"
     * @param string $token
     * @param string $email
     * @param int|null $vocabularyId By default, the tests' vocabulary
     * @return int The response's HTTP status
     */
    protected function postReview($action, $token, $email = self::ADMIN, $vocabularyId = null)
    {
        return $this->requestAs($email, 'POST', sprintf('/admin/vocabulary/%s/%s', $vocabularyId ?? $this->vocabularyId, $action), [
            'review' => $token,
        ] + $this->csrfField(ConfirmForm::class));
    }

    /**
     * The review page of a review, as text and attribute values read.
     *
     * @param string $token
     * @param array $query Choices made on it
     * @return string
     */
    protected function reviewPage($token, array $query = [])
    {
        $status = $this->requestAs(self::ADMIN, 'GET', sprintf('/admin/vocabulary/%s/review?%s', $this->vocabularyId, http_build_query(['review' => $token] + $query)));
        $this->assertSame(200, $status);
        $this->assertTemplateName('omeka/admin/vocabulary/review');
        return $this->getResponse()->getContent();
    }

    public function testEditWithAFileStartsAReviewSavingNothing()
    {
        $this->serveFile('markup.ttl');
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId), $this->editWithUrl([
            'o:label' => 'Renamed',
        ]));
        $this->assertSame(302, $status);
        $location = $this->getResponse()->getHeaders()->get('Location')->getUri();
        $this->assertMatchesRegularExpression(sprintf('#^/admin/vocabulary/%s/review\?review=[0-9a-f]{32}$#', $this->vocabularyId), $location);
        $this->tokens[] = substr($location, strpos($location, '=') + 1);
        $this->assertSame('Review', $this->vocabulary()->label());
    }

    public function testReviewShowsValuesAsText()
    {
        $page = $this->reviewPage($this->startReview('markup.ttl'));
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
        // A changed value's words are marked where they differ, which splits
        // the value.
        $text = preg_replace('#</?(ins|del)>#', '', $page);
        foreach ($values as $value) {
            $this->assertTrue(str_contains($text, htmlspecialchars($value)), "Not shown as text: $value");
            $this->assertFalse(str_contains($page, $value), "Shown as markup: $value");
        }
    }

    public function testReviewShowsPendingFieldsAndTermsOnlyInOmeka()
    {
        // The file is read in the pending namespace.
        $page = html_entity_decode($this->reviewPage($this->startReview('moved-name.ttl', null, [
            'label' => 'Renamed',
            'namespace_uri' => 'http://example.org/moved#',
        ])), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('Its classes and properties get new addresses wherever Omeka publishes them.', $page);
        $this->assertStringContainsString('http://example.org/moved#', $page);
        $this->assertStringContainsString('Renamed', $page);
        $this->assertStringContainsString('Changed property labels (1)', $page);
        // The file has no class, so Omeka's is listed, as kept.
        $this->assertStringContainsString('Classes only in Omeka (1)', $page);
        $this->assertStringContainsString('Accepting keeps them.', $page);
    }

    public function testChangedChoicesRecompute()
    {
        $token = $this->startReview('multilingual-name.ttl');
        $page = $this->reviewPage($token, ['lang' => 'en']);
        $this->assertMatchesRegularExpression('#<ins>Name</ins>#', $page);
        $page = $this->reviewPage($token, ['lang' => 'fr']);
        $this->assertMatchesRegularExpression('#<ins>Nom</ins>#', $page);
        $this->assertStringContainsString('<option value="fr" selected>', html_entity_decode($page, ENT_QUOTES | ENT_HTML5));
    }

    public function testAcceptAppliesNamespaceAndTermsTogether()
    {
        $this->serveFile('moved-name.ttl');
        $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId), $this->editWithUrl([
            'o:namespace_uri' => 'http://example.org/moved#',
        ]));
        $location = $this->getResponse()->getHeaders()->get('Location')->getUri();
        $token = $this->tokens[] = substr($location, strpos($location, '=') + 1);

        $status = $this->postReview('accept', $token);
        $this->assertSame(302, $status);
        $this->assertRedirectTo('/admin/vocabulary');
        $this->assertSame(['Changes to the vocabulary successfully made'], $this->messages(Messenger::SUCCESS));
        $vocabulary = $this->vocabulary();
        $this->assertSame('http://example.org/moved#', $vocabulary->namespaceUri());
        $this->assertSame('Changed', $this->propertyLabel('review:name'));
        // How the file was read is stored.
        $this->assertSame('turtle', $this->vocabulary()->importOptions()['format']);
    }

    public function testCancelChangesNothing()
    {
        $token = $this->startReview('changed-name.ttl', null, ['label' => 'Renamed']);
        $status = $this->postReview('cancel', $token);
        $this->assertSame(302, $status);
        $this->assertRedirectTo('/admin/vocabulary');
        $this->assertSame('Review', $this->vocabulary()->label());
        $this->assertSame('<i>stored property label</i>', $this->propertyLabel('review:name'));
        // The review has ended.
        $status = $this->requestAs(self::ADMIN, 'GET', sprintf('/admin/vocabulary/%s/review?review=%s', $this->vocabularyId, $token));
        $this->assertSame(302, $status);
        $this->assertSame(['This review has ended. Edit the vocabulary to review its changes again.'], $this->messages(Messenger::ERROR));
    }

    public function testCommentsChoiceIsOnTheImportFormOnly()
    {
        $this->requestAs(self::ADMIN, 'GET', '/admin/vocabulary/import');
        $this->assertStringContainsString('<option value="definition">', html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5));
        // An edit chooses on review.
        $this->requestAs(self::ADMIN, 'GET', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId));
        $this->assertStringNotContainsString('<option value="definition">', html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5));
    }

    public function testImportRefusesAnotherFormOfANamespaceOmekaHas()
    {
        $this->serveFile('markup.ttl');
        $status = $this->requestAs(self::ADMIN, 'POST', '/admin/vocabulary/import', [
            'vocabulary-info' => [
                'o:namespace_uri' => 'https://example.org/review',
                'o:prefix' => 'review2',
                'o:label' => 'Review again',
                'o:comment' => '',
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
        ] + $this->csrfField(VocabularyForm::class));
        $this->assertSame(200, $status);
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('Omeka already has this vocabulary, as "Review" with the namespace URI "http://example.org/review#". To update it, edit it.', strip_tags($page));
        $this->assertStringContainsString(sprintf('<a href="/admin/vocabulary/%s/edit">edit it</a>', $this->vocabularyId), $page);
        $this->reset(true);
        $this->signIn();
        $this->assertSame(0, $this->api()->search('vocabularies', ['prefix' => 'review2'])->getTotalResults());
    }

    public function importsWithComments()
    {
        return [
            'the comment, by default' => [[], RdfImporter::COMMENT_PROPERTY, 'Usage note on titles.'],
            'the definition, chosen' => [['comments' => 'definition'], RdfImporter::DEFINITION_COMMENT_PROPERTY, "The work's title."],
            'a comment property entered, over the choice' => [['comments' => 'definition', 'comment_property' => 'rdfs:comment'], 'rdfs:comment', 'Usage note on titles.'],
        ];
    }

    /**
     * @dataProvider importsWithComments
     */
    public function testImportReadsCommentsAsChosen(array $advanced, $commentProperty, $titleComment)
    {
        $this->serveFile('../../../Stdlib/_files/definitions.ttl');
        try {
            $status = $this->requestAs(self::ADMIN, 'POST', '/admin/vocabulary/import', [
                'vocabulary-info' => [
                    'o:namespace_uri' => 'http://example.org/defs#',
                    'o:prefix' => 'defs',
                    'o:label' => 'Definitions',
                    'o:comment' => '',
                ],
                'vocabulary-file' => [
                    'import_type' => 'url',
                    'url' => 'http://example.org/defs.ttl',
                    'format' => 'turtle',
                ],
                'vocabulary-advanced' => $advanced + [
                    'lang' => '',
                    'label_property' => '',
                    'comment_property' => '',
                ],
            ] + $this->csrfField(VocabularyForm::class));
            $this->assertSame(302, $status);
            $this->reset(true);
            $this->signIn();
            $vocabulary = $this->api()->read('vocabularies', ['prefix' => 'defs'])->getContent();
            $this->assertSame($commentProperty, $vocabulary->importOptions()['comment_property']);
            $title = $this->api()->search('properties', ['term' => 'defs:title'])->getContent()[0];
            $this->assertSame($titleComment, $title->comment());
        } finally {
            $this->reset(true);
            $this->signIn();
            foreach ($this->api()->search('vocabularies', ['prefix' => 'defs'])->getContent() as $vocabulary) {
                $this->api()->delete('vocabularies', $vocabulary->id());
            }
        }
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

    public function testEditStartsWithHowTheFileWasRead()
    {
        $this->api()->update('vocabularies', $this->vocabularyId, [
            'o:import_options' => [
                'format' => 'rdfxml',
                'lang' => 'fr',
                'comment_property' => 'skos:definition',
            ],
        ], [], ['isPartial' => true]);
        $status = $this->requestAs(self::ADMIN, 'GET', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId));
        $this->assertSame(200, $status);
        // Attribute values as a browser reads them.
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('<option value="rdfxml" selected>', $page);
        $this->assertMatchesRegularExpression('/<input[^>]* name="vocabulary-advanced\[lang\]"[^>]* value="fr">/', $page);
        $this->assertMatchesRegularExpression('/<input[^>]* name="vocabulary-advanced\[comment_property\]"[^>]* value="skos:definition">/', $page);
        // What the stored reading doesn't say is as the form has it.
        $this->assertMatchesRegularExpression('/<input[^>]* name="vocabulary-file\[import_type\]"[^>]* value="upload" checked>/', $page);
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
        $data = $this->editWithUrl(['o:label' => 'Renamed']);
        $data['vocabulary-file']['import_type'] = $importType;
        $data['vocabulary-file']['url'] = '';
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId), $data, [
            // The file field, as a browser posts it with no file chosen.
            'vocabulary-file' => [
                'file' => ['name' => '', 'type' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0],
            ],
        ]);
        $this->assertSame(302, $status);
        $this->assertRedirectTo('/admin/vocabulary');
        $this->assertSame(['Vocabulary successfully updated'], $this->messages(Messenger::SUCCESS));
        $this->assertSame([], $this->messages(Messenger::ERROR));
        $this->assertSame('Renamed', $this->vocabulary()->label());
    }

    public function testEditRefusedByApiShowsFormAgain()
    {
        $data = $this->editWithUrl(['o:label' => 'Renamed', 'o:namespace_uri' => 'http://purl.org/dc/terms/']);
        $data['vocabulary-file']['import_type'] = 'upload';
        $data['vocabulary-file']['url'] = '';
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId), $data, [
            'vocabulary-file' => [
                'file' => ['name' => '', 'type' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0],
            ],
        ]);
        $this->assertSame(200, $status);
        $this->assertTemplateName('omeka/admin/vocabulary/edit');
        $page = $this->getResponse()->getContent();
        $this->assertStringContainsString('is already taken.', $page);
        $this->assertStringNotContainsString('Vocabulary successfully updated', $page);
        $this->assertSame('Review', $this->vocabulary()->label());
    }

    public function testEditWithAFileRefusesATakenNamespaceBeforeReview()
    {
        $this->serveFile('changed-name.ttl');
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId), $this->editWithUrl([
            'o:namespace_uri' => 'http://purl.org/dc/terms/',
        ]));
        $this->assertSame(200, $status);
        $this->assertTemplateName('omeka/admin/vocabulary/edit');
        $this->assertStringContainsString('is already taken.', html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5));
    }

    public function testEditWithTermsTooLongToStoreNamesThem()
    {
        $this->serveFile('long-label.ttl');
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId), $this->editWithUrl());
        $this->assertSame(200, $status);
        $this->assertTemplateName('omeka/admin/vocabulary/edit');
        $page = $this->getResponse()->getContent();
        $this->assertStringContainsString('Some classes or properties have local names or labels longer than Omeka can store.', $page);
        $this->assertStringContainsString('These classes or properties have labels longer than 255 characters: name', $page);
    }

    public function testReviewRefusedForAnothersVocabulary()
    {
        $token = $this->startReview('changed-name.ttl');
        $status = $this->requestAs(self::SUPERVISOR, 'GET', sprintf('/admin/vocabulary/%s/review?review=%s', $this->vocabularyId, $token));
        $this->assertSame(403, $status);
    }

    public function testAcceptRefusedForPermanentVocabulary()
    {
        $dcterms = $this->api()->read('vocabularies', ['prefix' => 'dcterms'])->getContent();
        $token = $this->startReview('dcterms-title.ttl', $dcterms->id());
        $status = $this->postReview('accept', $token, self::ADMIN, $dcterms->id());
        $this->assertSame(403, $status);
        $this->assertSame('Title', $this->propertyLabel('dcterms:title'));
    }

    public function testAcceptRefusedForAnothersVocabulary()
    {
        $token = $this->startReview('changed-name.ttl');
        $status = $this->postReview('accept', $token, self::SUPERVISOR);
        $this->assertSame(403, $status);
        $this->assertSame('<i>stored property label</i>', $this->propertyLabel('review:name'));
    }

    public function testAcceptAppliedForOwnVocabulary()
    {
        $this->giveVocabularyTo(self::SUPERVISOR);
        $token = $this->startReview('changed-name.ttl');
        $status = $this->postReview('accept', $token, self::SUPERVISOR);
        $this->assertSame(302, $status);
        $this->assertRedirectTo('/admin/vocabulary');
        $this->assertSame('Changed', $this->propertyLabel('review:name'));
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
