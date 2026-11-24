<?php
namespace OmekaTest\Controller\Admin;

use Laminas\Form\Element\Csrf;
use Laminas\Http\Client as HttpClient;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Stdlib\Parameters;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Exception\ValidationException;
use Omeka\Entity\User;
use Omeka\Entity\Vocabulary;
use Omeka\File\Stager;
use Omeka\Form\ConfirmForm;
use Omeka\Form\VocabularyFileForm;
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
     * The client answering the importer's requests in place of a server,
     * put in each request's services.
     *
     * @var HttpClient|null
     */
    protected $servedClient;

    /**
     * The tokens of the reviews a test starts.
     *
     * @var string[]
     */
    protected $tokens = [];

    /**
     * The tokens of the imports a test starts, canceled after it, so their
     * files don't stay.
     *
     * @var string[]
     */
    protected $importTokens = [];

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
        $this->reset(true);
        $this->signIn();
        foreach ($this->tokens as $token) {
            $this->getApplicationServiceLocator()->get('Omeka\VocabularyReview')->cancel($token);
        }
        foreach ($this->importTokens as $token) {
            $this->getApplicationServiceLocator()->get('Omeka\VocabularyImport')->cancel($token);
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
        if ($this->servedClient) {
            $services = $this->getApplicationServiceLocator();
            $services->setAllowOverride(true);
            $services->setService('Omeka\HttpClientNoPrivate', $this->servedClient);
        }
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
     * Create another vocabulary.
     *
     * @return int Its id
     */
    protected function createVocabulary($namespaceUri, $prefix, $label)
    {
        return $this->api()->create('vocabularies', [
            'o:namespace_uri' => $namespaceUri,
            'o:prefix' => $prefix,
            'o:label' => $label,
        ])->getContent()->id();
    }

    /**
     * Answer the importer's requests with a file of the tests', in place of
     * the server at the URL asked for.
     *
     * @param string $file A file in _files
     */
    protected function serveFile($file)
    {
        $client = new class extends HttpClient {
            public $body;

            public function send(?Request $request = null)
            {
                return (new Response)->setStatusCode(200)->setContent($this->body);
            }
        };
        $client->body = file_get_contents(__DIR__ . '/_files/' . $file);
        $this->servedClient = $client;
    }

    /**
     * The edit form's fields, as a browser posts them.
     *
     * @param array $info The vocabulary's own fields that differ
     * @return array
     */
    protected function edit(array $info = [])
    {
        return $info + [
            'o:label' => 'Review',
            'o:comment' => '',
            'o:namespace_uri' => self::NAMESPACE_URI,
        ] + $this->csrfField(VocabularyForm::class);
    }

    /**
     * Post the edit form to change the tests' vocabulary's namespace URI.
     *
     * @param string $namespaceUri
     * @param string $email
     * @return int The response's HTTP status
     */
    protected function changeNamespace($namespaceUri, $email = self::ADMIN)
    {
        return $this->requestAs($email, 'POST', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId), $this->edit([
            'o:namespace_uri' => $namespaceUri,
        ]));
    }

    /**
     * The fields of the form to update from a file, as a browser posts them,
     * with a URL to read.
     *
     * @return array
     */
    protected function updateWithUrl()
    {
        return [
            'import_type' => 'url',
            'url' => 'http://example.org/review.ttl',
            'format' => 'turtle',
        ] + $this->csrfField(VocabularyFileForm::class);
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
     * Post a review's Accept, to the review page with the review and the
     * choices shown in the URL, as the page's form posts it.
     *
     * @param string $token
     * @param string $email
     * @param int|null $vocabularyId By default, the tests' vocabulary
     * @param array $choices The choices the review page showed
     * @return int The response's HTTP status
     */
    protected function postAccept($token, $email = self::ADMIN, $vocabularyId = null, array $choices = [])
    {
        $query = http_build_query(['token' => $token] + $choices);
        return $this->requestAs($email, 'POST', sprintf('/admin/vocabulary/%s/review?%s', $vocabularyId ?? $this->vocabularyId, $query), ['accept' => '1'] + $this->csrfField(ConfirmForm::class));
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
        $status = $this->requestAs(self::ADMIN, 'GET', sprintf('/admin/vocabulary/%s/review?%s', $this->vocabularyId, http_build_query(['token' => $token] + $query)));
        $this->assertSame(200, $status);
        $this->assertTemplateName('omeka/admin/vocabulary/review');
        return $this->getResponse()->getContent();
    }

    /**
     * The query of a review page's Accept: the review, and the choices it
     * applies.
     *
     * @param string $page The page, entities decoded
     * @return array
     */
    protected function acceptQuery($page)
    {
        $this->assertMatchesRegularExpression('#<form [^>]*action="([^"]*/review\?[^"]*)"#', $page);
        preg_match('#<form [^>]*action="([^"]*/review\?[^"]*)"#', $page, $match);
        parse_str((string) parse_url($match[1], PHP_URL_QUERY), $query);
        return $query;
    }

    public function testShowPage()
    {
        $status = $this->requestAs(self::ADMIN, 'GET', sprintf('/admin/vocabulary/%s', $this->vocabularyId));
        $this->assertSame(200, $status);
        $this->assertTemplateName('omeka/admin/vocabulary/show');
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $text = preg_replace('/\s+/', ' ', strip_tags($page));
        $this->assertStringContainsString('Prefix review Namespace URI http://example.org/review# Classes 1 Properties 1', $text);
        // How much uses it, as in the Details sidebar.
        $this->assertStringContainsString('Properties 1 Values using its properties 0 Resource templates using its properties 0 Resources using its classes 0', $text);
        // The pages that change it, for one the user may change.
        $this->assertStringContainsString(sprintf('<a class="button" href="/admin/vocabulary/%s/update">Update classes and properties</a>', $this->vocabularyId), $page);
        $this->assertStringContainsString(sprintf('<a class="button" href="/admin/vocabulary/%s/edit">Edit vocabulary</a>', $this->vocabularyId), $page);
        $this->assertStringContainsString(sprintf('<a href="/admin/vocabulary/%s/properties">1</a>', $this->vocabularyId), $page);
        $this->assertStringContainsString('<a class="o-icon-left" href="/admin/vocabulary">Back to vocabulary list</a>', $page);
    }

    public function testShowPageOffersNoChangesToAVocabularyTheUserMayNotChange()
    {
        // Another's, for a Supervisor.
        $this->requestAs(self::SUPERVISOR, 'GET', sprintf('/admin/vocabulary/%s', $this->vocabularyId));
        $this->assertSame(200, $this->getResponse()->getStatusCode());
        $this->assertStringNotContainsString('Edit vocabulary', $this->getResponse()->getContent());

        // A permanent one, for anyone.
        $this->reset(true);
        $this->signIn();
        $dcterms = $this->api()->search('vocabularies', ['prefix' => 'dcterms'])->getContent()[0];
        $this->requestAs(self::ADMIN, 'GET', sprintf('/admin/vocabulary/%s', $dcterms->id()));
        $this->assertSame(200, $this->getResponse()->getStatusCode());
        $page = $this->getResponse()->getContent();
        $this->assertStringContainsString('http://purl.org/dc/terms/', $page);
        $this->assertStringNotContainsString('Edit vocabulary', $page);
        $this->assertStringNotContainsString('Update classes and properties', $page);
    }

    public function testBrowseLinksToEachVocabularysPage()
    {
        $this->requestAs(self::ADMIN, 'GET', '/admin/vocabulary');
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString(sprintf('<a href="/admin/vocabulary/%s">Review</a>', $this->vocabularyId), $page);
    }

    public function testClassesAndPropertiesLeadBackToTheVocabularysPage()
    {
        foreach (['classes', 'properties'] as $action) {
            $this->reset(true);
            $this->requestAs(self::ADMIN, 'GET', sprintf('/admin/vocabulary/%s/%s', $this->vocabularyId, $action));
            $this->assertSame(200, $this->getResponse()->getStatusCode());
            $this->assertStringContainsString(
                sprintf('<a class="o-icon-left" href="/admin/vocabulary/%s">Back to vocabulary</a>', $this->vocabularyId),
                html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5)
            );
        }
    }

    public function testBrowseShowsEachVocabularysOwner()
    {
        $this->requestAs(self::ADMIN, 'GET', '/admin/vocabulary');
        $page = preg_replace('/\s+/', ' ', html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5));
        $this->assertStringContainsString('?sort_by=owner_name', $page);
        // The tests' vocabulary, which the admin made; Omeka's own have none.
        $admin = $this->vocabulary()->owner();
        $this->assertMatchesRegularExpression(sprintf('#<a href="/admin/vocabulary/%s">Review</a>.*?<td><a href="/admin/user/%s">%s</a></td>#', $this->vocabularyId, $admin->id(), preg_quote($admin->name(), '#')), $page);
        $this->assertMatchesRegularExpression('#>Dublin Core</a>.*?<td>\[No owner\]</td>#', $page);
    }

    public function testUpdateFromFileStartsAReviewSavingNothing()
    {
        $this->serveFile('changed-name.ttl');
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/update', $this->vocabularyId), $this->updateWithUrl());
        $this->assertSame(302, $status);
        $location = $this->getResponse()->getHeaders()->get('Location')->getUri();
        $this->assertMatchesRegularExpression(sprintf('#^/admin/vocabulary/%s/review\?token=[0-9a-f]{32}$#', $this->vocabularyId), $location);
        $this->tokens[] = substr($location, strpos($location, '=') + 1);
        $this->assertSame('<i>stored property label</i>', $this->propertyLabel('review:name'));
    }

    public function testUpdateWithoutAFile()
    {
        $data = $this->updateWithUrl();
        $data['url'] = '';
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/update', $this->vocabularyId), $data);
        $this->assertSame(200, $status);
        $this->assertTemplateName('omeka/admin/vocabulary/update');
        $this->assertStringContainsString('Choose a file or enter a URL.', $this->getResponse()->getContent());
    }

    public function testAFileTooLargeToUploadSaysWhatToDo()
    {
        // As PHP passes an upload over upload_max_filesize.
        $files = ['file' => ['name' => 'large.ttl', 'type' => 'text/turtle', 'tmp_name' => '', 'error' => UPLOAD_ERR_INI_SIZE, 'size' => 0]];
        $data = ['import_type' => 'upload', 'format' => 'guess'] + $this->csrfField(VocabularyFileForm::class);
        foreach (['/admin/vocabulary/import', sprintf('/admin/vocabulary/%s/update', $this->vocabularyId)] as $path) {
            $this->assertSame(200, $this->requestAs(self::ADMIN, 'POST', $path, $data, $files));
            $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
            $this->assertStringContainsString("The file is larger than this server accepts for uploads. Choose URL and enter the file's address instead.", $page, $path);
            $this->assertStringNotContainsString('php.ini', $page, $path);
        }
    }

    public function testUpdateStartsWithTheStoredFormat()
    {
        $this->api()->update('vocabularies', $this->vocabularyId, [
            'o:import_options' => ['format' => 'rdfxml'],
        ], [], ['isPartial' => true]);
        $this->requestAs(self::ADMIN, 'GET', sprintf('/admin/vocabulary/%s/update', $this->vocabularyId));
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('<option value="rdfxml" selected>', $page);
        // The update form takes only the file: how it's read is chosen on
        // the review.
        $this->assertStringNotContainsString('name="o:label"', $page);
        $this->assertStringNotContainsString('name="lang"', $page);
        $this->assertStringContainsString(sprintf('<a class="button" href="/admin/vocabulary/%s">Cancel</a>', $this->vocabularyId), $page);
    }

    public function testUpdateShowsATurtleFormatAsOneChoice()
    {
        // Turtle and Notation3 are read alike, so they're one option, which
        // alone shows as chosen.
        $this->api()->update('vocabularies', $this->vocabularyId, [
            'o:import_options' => ['format' => 'turtle'],
        ], [], ['isPartial' => true]);
        $this->requestAs(self::ADMIN, 'GET', sprintf('/admin/vocabulary/%s/update', $this->vocabularyId));
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertSame(1, substr_count($page, ' selected>'));
        $this->assertStringContainsString('<option value="turtle" selected>Turtle or Notation3 (.ttl, .n3)</option>', $page);
    }

    public function testEditHasOnlyTheVocabularysOwnFields()
    {
        $this->requestAs(self::ADMIN, 'GET', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId));
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('name="o:label"', $page);
        // The namespace URI starts as the vocabulary's own, which the script
        // doesn't ask about when saved unchanged.
        $this->assertMatchesRegularExpression('#<input type="text" name="o:namespace_uri"[^>]* data-saved-namespace-uri="http://example.org/review\#" value="http://example.org/review\#">#', $page);
        $this->assertStringContainsString('Changing it changes the URI of every class and property in this vocabulary.', $page);
        $this->assertStringNotContainsString('name="o:prefix"', $page);
        $this->assertStringNotContainsString('name="import_type"', $page);
        $this->assertStringNotContainsString('name="lang"', $page);
        // Cancel leads to the vocabulary's page, as Save does.
        $this->assertStringContainsString(sprintf('<a class="button" href="/admin/vocabulary/%s">Cancel</a>', $this->vocabularyId), $page);
        $this->assertStringNotContainsString('More actions', $page);
    }

    public function testEditSavesLabelAndCommentAtOnce()
    {
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId), $this->edit([
            'o:label' => 'Renamed',
            'o:comment' => 'A comment.',
        ]));
        $this->assertSame(302, $status);
        $this->assertRedirectTo(sprintf('/admin/vocabulary/%s', $this->vocabularyId));
        $this->assertSame(['Vocabulary successfully updated'], $this->messages(Messenger::SUCCESS));
        $vocabulary = $this->vocabulary();
        $this->assertSame('Renamed', $vocabulary->label());
        $this->assertSame('A comment.', $vocabulary->comment());
    }

    public function testChangeNamespace()
    {
        $status = $this->changeNamespace(' http://example.org/moved# ');
        $this->assertSame(302, $status);
        $this->assertRedirectTo(sprintf('/admin/vocabulary/%s', $this->vocabularyId));
        $this->assertSame(['Vocabulary successfully updated'], $this->messages(Messenger::SUCCESS));
        $vocabulary = $this->vocabulary();
        $this->assertSame('http://example.org/moved#', $vocabulary->namespaceUri());
        $this->assertSame('Review', $vocabulary->label());
        $this->assertSame('<i>stored property label</i>', $this->propertyLabel('review:name'));
    }

    public function testEditShowsARefusedNamespaceUriAtItsField()
    {
        // The API's refusal goes on the field it names, which has the API's
        // name.
        $this->reset(true);
        $this->signIn();
        $otherId = $this->api()->create('vocabularies', [
            'o:namespace_uri' => 'http://example.org/taken#',
            'o:prefix' => 'taken',
            'o:label' => 'Taken',
        ])->getContent()->id();
        try {
            $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId), $this->edit([
                'o:namespace_uri' => 'http://example.org/taken#',
            ]));
            $this->assertSame(200, $status);
            $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
            $this->assertMatchesRegularExpression('#id="o:namespace_uri_field">.*?<ul class="messages">\s*<li>The namespace URI "http://example.org/taken\#" is already taken\.</li>#s', $page);
        } finally {
            $this->reset(true);
            $this->signIn();
            $this->api()->delete('vocabularies', $otherId);
        }
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

    public function testReviewShowsAPendingNamespaceAndTermsOnlyInOmeka()
    {
        // The file is read in the pending namespace.
        $page = html_entity_decode($this->reviewPage($this->startReview('other-form-name.ttl', null, [
            'new_namespace_uri' => 'https://example.org/review#',
        ])), ENT_QUOTES | ENT_HTML5);
        $text = preg_replace('/\s+/', ' ', strip_tags($page));
        $this->assertStringContainsString('Accepting makes the changes below, from the file.', $text);
        // The namespace URI is one of the vocabulary's changed fields, with
        // what it does to the terms.
        $this->assertStringContainsString('Namespace URI http://example.org/review# https://example.org/review#', $text);
        $this->assertStringContainsString('Changing the namespace changes the URI of every class and property in this vocabulary.', $text);
        $this->assertStringNotContainsString('new addresses', $text);
        $this->assertStringContainsString('Changed property labels (1)', $page);
        // The file has no class, so Omeka's is listed, as kept.
        $this->assertStringContainsString('Classes only in Omeka (1)', $page);
        $this->assertStringContainsString('Accepting keeps them.', $page);
    }

    public function testReviewWithoutChangesOffersOnlyBack()
    {
        $page = html_entity_decode($this->reviewPage($this->startReview('unchanged.ttl')), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('With the choices above, the file makes no changes to this vocabulary.', $page);
        // Made inactive by the script until a choice changes.
        $this->assertStringContainsString('<button type="submit" class="review-show" data-watch="fieldset :input">Show changes</button>', $page);
        // Back leads to the vocabulary's page, as Cancel does.
        $this->assertStringContainsString(sprintf('<a class="button" href="/admin/vocabulary/%s">Back</a>', $this->vocabularyId), $page);
        $this->assertStringNotContainsString('Accept changes', $page);

        // With changes, Cancel and Accept.
        $page = html_entity_decode($this->reviewPage($this->startReview('changed-name.ttl')), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString(sprintf('<a class="button" href="/admin/vocabulary/%s">Cancel</a>', $this->vocabularyId), $page);
        // Made inactive by the script while a choice differs from those the
        // changes were worked out with.
        $this->assertStringContainsString('<button type="submit" name="accept" value="1" class="review-accept" data-inactive-title="Show changes first">Accept changes</button>', $page);
    }

    public function testLabelPropertyChosenOnReview()
    {
        $token = $this->startReview('changed-name.ttl');
        $page = html_entity_decode($this->reviewPage($token), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('Changed property labels (1)', $page);
        // Every choice is a row of the import's own fields, sent by GET
        // under its own name, without the CSRF token a query can't carry.
        $this->assertMatchesRegularExpression('#<select name="labels" id="labels" data-other-field="\#label_property">\s*<option value="label" selected(="selected")?>Preferred label, else label</option>#', $page);
        $this->assertStringContainsString('<input type="text" name="label_property" id="label_property" value="">', $page);
        $this->assertStringContainsString('<div class="field-description">The property\'s full URI, in angle brackets, such as <http://www.w3.org/2004/02/skos/core#altLabel>.', $page);
        $this->assertMatchesRegularExpression('#<form [^>]*method="get"[^>]*>#', $page);
        preg_match('#<form [^>]*class="review-choices"[^>]*>.*?</form>#s', $page, $choices);
        $this->assertStringNotContainsString('csrf', $choices[0]);
        $this->assertStringNotContainsString('Label and comment properties', $page);

        // Labels read from a property the file doesn't have are local names,
        // which never take the place of Omeka's labels.
        $page = html_entity_decode($this->reviewPage($token, ['labels' => 'other', 'label_property' => 'skos:prefLabel']), ENT_QUOTES | ENT_HTML5);
        $this->assertStringNotContainsString('Changed property labels', $page);
        // Shown in its field, as the import options use it, and carried to
        // Accept.
        $this->assertMatchesRegularExpression('#<select name="labels"[^>]*>.*?<option value="other" selected(="selected")?>#s', $page);
        $this->assertStringContainsString('<input type="text" name="label_property" id="label_property" value="skos:prefLabel">', $page);
        $accept = $this->acceptQuery($page);
        $this->assertSame('other', $accept['labels']);
        $this->assertSame('skos:prefLabel', $accept['label_property']);

        // Accepted, it's stored in the import options.
        $this->postAccept($token, self::ADMIN, null, ['labels' => 'other', 'label_property' => 'skos:prefLabel']);
        $this->assertSame('skos:prefLabel', $this->vocabulary()->importOptions()['label_property']);
    }

    public function testChangedChoicesRecompute()
    {
        $token = $this->startReview('multilingual-name.ttl');
        $page = $this->reviewPage($token, ['lang' => 'en']);
        $this->assertMatchesRegularExpression('#<ins>Name</ins>#', $page);
        $page = $this->reviewPage($token, ['lang' => 'fr']);
        $this->assertMatchesRegularExpression('#<ins>Nom</ins>#', $page);
        $this->assertStringContainsString('<option value="fr" selected>', html_entity_decode($page, ENT_QUOTES | ENT_HTML5));
        // Entered under "Another language…", it's read the same, and shown
        // as chosen.
        $page = $this->reviewPage($token, ['lang' => 'other', 'other_lang' => ' fr ']);
        $this->assertMatchesRegularExpression('#<ins>Nom</ins>#', $page);
        $this->assertStringContainsString('<option value="fr" selected>', html_entity_decode($page, ENT_QUOTES | ENT_HTML5));
    }

    public function testAReviewsLanguageTheFileLacksIsUnderAnotherLanguage()
    {
        // As on import details.
        $this->api()->update('vocabularies', $this->vocabularyId, [
            'o:import_options' => ['format' => 'turtle', 'lang' => 'de'],
        ], [], ['isPartial' => true]);
        $token = $this->startReview('multilingual-name.ttl');
        $page = html_entity_decode($this->reviewPage($token), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('<option value="other" selected>Another language…</option>', $page);
        $this->assertStringContainsString('<input type="text" name="other_lang" id="other_lang" value="de">', $page);
    }

    public function testAnotherLanguageLeftBlankKeepsTheReviewsImportOptions()
    {
        $this->api()->update('vocabularies', $this->vocabularyId, [
            'o:import_options' => ['format' => 'turtle', 'lang' => 'fr'],
        ], [], ['isPartial' => true]);
        $token = $this->startReview('multilingual-name.ttl');
        $page = $this->reviewPage($token, ['lang' => 'other', 'other_lang' => ' ']);
        $this->assertMatchesRegularExpression('#<ins>Nom</ins>#', $page);
        // And Accept stores it.
        $this->postAccept($token, self::ADMIN, null, ['lang' => 'other', 'other_lang' => ' ']);
        $this->assertSame('fr', $this->vocabulary()->importOptions()['lang']);
    }

    public function testAnotherCommentPropertyChosenOnReview()
    {
        $token = $this->startReview('changed-name.ttl');
        // Chosen, it's read, and shown as chosen, with Accept carrying it.
        $page = html_entity_decode($this->reviewPage($token, ['comments' => 'other', 'comment_property' => 'skos:definition']), ENT_QUOTES | ENT_HTML5);
        // Under the same label as on import details.
        $this->assertMatchesRegularExpression('#<fieldset>\s*<legend>Classes and properties</legend>.*?<label for="comments">Comments from</label>.*?</fieldset>#s', $page);
        $this->assertStringContainsString('<label for="comments">Comments from</label>', $page);
        $this->assertStringContainsString('<option value="other">Another language…</option>', $page);
        $this->assertStringContainsString('<option value="other" selected>Another property…</option>', $page);
        $this->assertStringContainsString('<input type="text" name="comment_property" id="comment_property" value="skos:definition">', $page);
        $accept = $this->acceptQuery($page);
        $this->assertSame('other', $accept['comments']);
        $this->assertSame('skos:definition', $accept['comment_property']);
        // Entered but not chosen, it isn't.
        $page = html_entity_decode($this->reviewPage($token, ['comments' => 'definition', 'comment_property' => 'skos:definition']), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('<option value="definition" selected>', $page);
        $this->assertStringContainsString('<input type="text" name="comment_property" id="comment_property" value="">', $page);
    }

    public function testAnotherCommentPropertyLeftBlankKeepsTheReviewsImportOptions()
    {
        $this->api()->update('vocabularies', $this->vocabularyId, [
            'o:import_options' => ['format' => 'turtle', 'comment_property' => RdfImporter::DEFINITION_COMMENT_PROPERTY],
        ], [], ['isPartial' => true]);
        $token = $this->startReview('changed-name.ttl');
        $page = html_entity_decode($this->reviewPage($token, ['comments' => 'other', 'comment_property' => ' ']), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('<option value="definition" selected>', $page);
        // And Accept stores it.
        $this->postAccept($token, self::ADMIN, null, ['comments' => 'other', 'comment_property' => ' ']);
        $this->assertSame(RdfImporter::DEFINITION_COMMENT_PROPERTY, $this->vocabulary()->importOptions()['comment_property']);
    }

    public function testAcceptAppliesNamespaceAndTermsTogether()
    {
        // As a module starts it, knowing the file's namespace.
        $token = $this->startReview('other-form-name.ttl', null, ['new_namespace_uri' => 'https://example.org/review#']);

        $status = $this->postAccept($token);
        $this->assertSame(302, $status);
        $this->assertRedirectTo(sprintf('/admin/vocabulary/%s', $this->vocabularyId));
        $this->assertSame(['Vocabulary successfully updated'], $this->messages(Messenger::SUCCESS));
        $vocabulary = $this->vocabulary();
        $this->assertSame('https://example.org/review#', $vocabulary->namespaceUri());
        $this->assertSame('Changed', $this->propertyLabel('review:name'));
        // How the file was read is stored.
        $this->assertSame('turtle', $this->vocabulary()->importOptions()['format']);
    }

    public function testRefusedAcceptDrawsTheReviewAgain()
    {
        $token = $this->startReview('changed-name.ttl');
        // Without the CSRF field.
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/review?token=%s&lang=en', $this->vocabularyId, $token), ['accept' => '1']);
        $this->assertSame(200, $status);
        $this->assertTemplateName('omeka/admin/vocabulary/review');
        // Still open, with the choices shown, for Accept to post again.
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertSame('en', $this->acceptQuery($page)['lang']);
        $this->assertSame('<i>stored property label</i>', $this->propertyLabel('review:name'));
        $this->assertNotNull($this->getApplicationServiceLocator()->get('Omeka\VocabularyReview')->get($token));
    }

    public function testAnEndedReviewSaysSoOnTheVocabularysPage()
    {
        $token = $this->startReview('changed-name.ttl');
        $this->getApplicationServiceLocator()->get('Omeka\VocabularyReview')->cancel($token);
        $status = $this->requestAs(self::ADMIN, 'GET', sprintf('/admin/vocabulary/%s/review?token=%s', $this->vocabularyId, $token));
        $this->assertSame(302, $status);
        $this->assertRedirectTo(sprintf('/admin/vocabulary/%s', $this->vocabularyId));
        $this->assertSame(['This review has ended.'], $this->messages(Messenger::ERROR));
    }

    /**
     * Post a file of the tests' to the first step of an import, by URL.
     *
     * @param string $file A file in _files, or a path from there
     * @param string $format
     * @return int The response's HTTP status
     */
    protected function startImport($file, $format = 'turtle')
    {
        $this->serveFile($file);
        $status = $this->requestAs(self::ADMIN, 'POST', '/admin/vocabulary/import', [
            'import_type' => 'url',
            'url' => 'http://example.org/vocabulary',
            'format' => $format,
        ] + $this->csrfField(VocabularyFileForm::class));
        if (302 === $status) {
            parse_str((string) parse_url($this->getResponse()->getHeaders()->get('Location')->getUri(), PHP_URL_QUERY), $query);
            $this->importTokens[] = $query['token'] ?? '';
        }
        return $status;
    }

    /**
     * The URL of the details step an import went on to.
     *
     * @return string
     */
    protected function importDetailsUrl()
    {
        return $this->getResponse()->getHeaders()->get('Location')->getUri();
    }

    /**
     * Post the details step of an import.
     *
     * @param string $url
     * @param array $info The vocabulary's own fields
     * @param array $advanced How its file is read
     * @return int The response's HTTP status
     */
    protected function finishImport($url, array $info, array $advanced = [])
    {
        return $this->requestAs(self::ADMIN, 'POST', $url, ['import' => '1'] + $info + ['o:comment' => ''] + $advanced + [
            'lang' => '',
            'comments' => 'comment',
            'label_property' => '',
            'comment_property' => '',
        ] + $this->csrfField(VocabularyForm::class));
    }

    /**
     * Delete the vocabularies with a prefix, as a test that imports one ends.
     *
     * @param string $prefix
     */
    protected function deleteImported($prefix)
    {
        $this->reset(true);
        $this->signIn();
        foreach ($this->api()->search('vocabularies', ['prefix' => $prefix])->getContent() as $vocabulary) {
            $this->api()->delete('vocabularies', $vocabulary->id());
        }
    }

    public function testImportTakesOnlyTheFileFirst()
    {
        $this->requestAs(self::ADMIN, 'GET', '/admin/vocabulary/import');
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('name="import_type"', $page);
        $this->assertStringNotContainsString('name="o:label"', $page);
        $this->assertStringNotContainsString('name="comments"', $page);
        // Cancel leads to the list, as the next page's does.
        $this->assertStringContainsString('<a class="button" href="/admin/vocabulary">Cancel</a>', $page);
    }

    public function testImportReadsTheFileThenShowsItsDetails()
    {
        $this->assertSame(302, $this->startImport('../../../Stdlib/_files/details-declared.ttl'));
        $url = $this->importDetailsUrl();
        $this->assertStringStartsWith('/admin/vocabulary/import-details?', $url);
        $this->requestAs(self::ADMIN, 'GET', $url);
        $this->assertTemplateName('omeka/admin/vocabulary/import-details');
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        // As the file gives them.
        $this->assertStringContainsString('value="Declared vocabulary"', $page);
        $this->assertStringContainsString('value="http://example.org/declared#"', $page);
        $this->assertStringContainsString('value="decl"', $page);
        // Its languages to choose from, English by default.
        $this->assertMatchesRegularExpression('#<option value="en" selected>[^<]*</option>#', $page);
        $this->assertMatchesRegularExpression('#<option value="fr">[^<]*</option>#', $page);
        $this->assertStringContainsString('<option value="definition">', $page);
        // Another language's and another property's fields, each after the
        // choice that shows it.
        $this->assertMatchesRegularExpression('#<select name="lang" id="lang" data-other-field="\#other_lang">.*?<option value="other">Another language…</option>.*?name="other_lang"#s', $page);
        // Labels before comments, as everywhere else.
        $this->assertMatchesRegularExpression('#<select name="labels" id="labels" data-other-field="\#label_property">.*?<option value="other">Another property…</option>.*?name="label_property".*?<select name="comments" id="comments" data-other-field="\#comment_property">.*?<option value="other">Another property…</option>.*?name="comment_property"#s', $page);
        // The choices of labels and comments under their own label, after
        // the vocabulary's own fields.
        $this->assertMatchesRegularExpression('#name="o:prefix".*?<fieldset[^>]*>\s*<legend>Classes and properties</legend>.*?name="lang".*?</fieldset>#s', $page);
        // The CSRF field, rendered apart from the fieldsets.
        $this->assertStringContainsString(sprintf('name="%s"', key($this->csrfField(VocabularyForm::class))), $page);
        // That the prefix can't be changed later, at its field.
        $this->assertStringContainsString("Enter a concise vocabulary identifier used as a shorthand for the namespace URI. It can't be changed after importing.", $page);
        $this->assertStringNotContainsString('name="import_type"', $page);
    }

    public function testImportWithAnotherLanguage()
    {
        try {
            $this->assertSame(302, $this->startImport('../../../Stdlib/_files/details-declared.ttl'));
            $this->assertSame(302, $this->finishImport($this->importDetailsUrl(), [
                'o:label' => 'Declared',
                'o:namespace_uri' => 'http://example.org/declared#',
                'o:prefix' => 'decl',
            ], ['lang' => 'other', 'other_lang' => ' pt-BR ']));
            $this->reset(true);
            $this->signIn();
            $vocabulary = $this->api()->read('vocabularies', ['prefix' => 'decl'])->getContent();
            $this->assertSame('pt-BR', $vocabulary->importOptions()['lang']);
        } finally {
            $this->deleteImported('decl');
        }
    }

    public function testImportDetailsOfAFileWithNoLanguagesOfferFirstAvailableAndAnother()
    {
        try {
            $this->assertSame(302, $this->startImport('../../../Stdlib/_files/definitions.ttl'));
            $url = $this->importDetailsUrl();
            $this->requestAs(self::ADMIN, 'GET', $url);
            $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
            $this->assertMatchesRegularExpression('#<select name="lang"[^>]*>\s*<option value="" selected>First available</option>\s*<option value="other">Another language…</option>\s*</select>#', $page);
            $this->assertStringContainsString('name="other_lang"', $page);
            // Another language pairs with a property the file's default ones
            // don't show, and is kept.
            $this->assertSame(302, $this->finishImport($url, [
                'o:label' => 'Definitions',
                'o:namespace_uri' => 'http://example.org/defs#',
                'o:prefix' => 'defs',
            ], ['lang' => 'other', 'other_lang' => 'fr']));
            $this->reset(true);
            $this->signIn();
            $vocabulary = $this->api()->read('vocabularies', ['prefix' => 'defs'])->getContent();
            $this->assertSame('fr', $vocabulary->importOptions()['lang']);
        } finally {
            $this->deleteImported('defs');
        }
    }

    public function testImportDetailsStartInTheInstallationsLanguage()
    {
        $settings = $this->getApplicationServiceLocator()->get('Omeka\Settings');
        $original = $settings->get('locale');
        try {
            // The tag itself or its language, before a language in a
            // region; else English.
            foreach (['en_US' => 'en', 'fr' => 'fr-CA', 'de' => 'en'] as $locale => $expected) {
                $settings->set('locale', $locale);
                $this->startImport('../../../Stdlib/_files/details-regional.ttl');
                $this->requestAs(self::ADMIN, 'GET', $this->importDetailsUrl());
                $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
                $this->assertMatchesRegularExpression(sprintf('#<option value="%s" selected>#', preg_quote($expected, '#')), $page, "For the locale $locale");
            }
        } finally {
            $this->getApplicationServiceLocator()->get('Omeka\Settings')->set('locale', $original);
        }
    }

    public function testImportDetailsShowWhatImportingAdds()
    {
        $this->assertSame(302, $this->startImport('../../../Stdlib/_files/details-declared.ttl'));
        $this->requestAs(self::ADMIN, 'GET', $this->importDetailsUrl());
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        // Over a list, made inactive by the script until a field the list
        // depends on changes.
        $this->assertMatchesRegularExpression('#<button type="submit" formnovalidate class="review-show" data-watch="[^"]*">Show classes and properties</button>#', $page);
        $this->assertStringContainsString('<a href="#import-classes">Classes (1)</a>', $page);
        $this->assertStringContainsString('<a href="#import-properties">Properties (1)</a>', $page);
        // In the language chosen, English, with the file's prefix.
        $this->assertMatchesRegularExpression('#<span class="field-term">decl:Thing</span></td>\s*<td><span class="review-value">Thing</span></td>#', $page);
        $this->assertStringContainsString('<span class="field-term">decl:name</span>', $page);
    }

    public function testShowClassesAndPropertiesImportsNothing()
    {
        $this->assertSame(302, $this->startImport('../../../Stdlib/_files/details-declared.ttl'));
        // Not yet valid to import: no prefix.
        $this->assertSame(200, $this->requestAs(self::ADMIN, 'POST', $this->importDetailsUrl(), [
            'o:label' => 'Declared vocabulary',
            'o:comment' => '',
            'o:namespace_uri' => 'http://example.org/declared#',
            'o:prefix' => '',
            'lang' => 'fr',
            'comments' => 'comment',
            'label_property' => '',
            'comment_property' => '',
        ]));
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        // In French now, without a prefix.
        $this->assertMatchesRegularExpression('#<span class="field-term">Thing</span></td>\s*<td><span class="review-value">Chose</span></td>#', $page);
        $this->assertStringNotContainsString('<li class="error">', $page);
        $this->reset(true);
        $this->signIn();
        $this->assertSame(0, $this->api()->search('vocabularies', ['namespace_uri' => 'http://example.org/declared#'])->getTotalResults());
    }

    public function testShowClassesAndPropertiesImportsNothingThatCouldBeImported()
    {
        // Complete and valid, with the CSRF field: only Import's name tells
        // an import from drawing the page again.
        $this->assertSame(302, $this->startImport('../../../Stdlib/_files/details-declared.ttl'));
        $url = $this->importDetailsUrl();
        $info = [
            'o:label' => 'Declared vocabulary',
            'o:comment' => '',
            'o:namespace_uri' => 'http://example.org/declared#',
            'o:prefix' => 'decl',
        ];
        try {
            $this->assertSame(200, $this->requestAs(self::ADMIN, 'POST', $url, $info + $this->csrfField(VocabularyForm::class)));
            $this->assertTemplateName('omeka/admin/vocabulary/import-details');
            $this->reset(true);
            $this->signIn();
            $this->assertSame(0, $this->api()->search('vocabularies', ['namespace_uri' => 'http://example.org/declared#'])->getTotalResults());
            // The same post with Import's name imports it.
            $this->assertSame(302, $this->finishImport($url, $info));
            $this->reset(true);
            $this->signIn();
            $this->assertSame(1, $this->api()->search('vocabularies', ['namespace_uri' => 'http://example.org/declared#'])->getTotalResults());
        } finally {
            $this->deleteImported('decl');
        }
    }

    public function testShowClassesAndPropertiesInAnotherNamespaceSaysWhyThereAreNone()
    {
        $this->assertSame(302, $this->startImport('../../../Stdlib/_files/details-declared.ttl'));
        $this->requestAs(self::ADMIN, 'POST', $this->importDetailsUrl(), [
            'o:label' => 'Declared vocabulary', 'o:comment' => '', 'o:namespace_uri' => 'http://example.org/other#', 'o:prefix' => 'decl',
        ]);
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('None of the file\'s classes or properties are in the namespace URI "http://example.org/other#". The file uses "http://example.org/declared#".', $page);
        $this->assertStringNotContainsString('id="import-classes"', $page);
        // And at the field that's wrong.
        $this->assertMatchesRegularExpression('#id="o:namespace_uri_field">.*?<ul class="messages">\s*<li>None of the file\'s classes or properties#s', $page);
    }

    public function testShowClassesAndPropertiesWarnsOfAVocabularyOmekaHas()
    {
        $this->assertSame(302, $this->startImport('../../../Stdlib/_files/definitions.ttl'));
        // Another form of the tests' vocabulary's namespace URI, entered.
        $this->requestAs(self::ADMIN, 'POST', $this->importDetailsUrl(), [
            'o:label' => 'Definitions', 'o:comment' => '', 'o:namespace_uri' => 'https://example.org/review', 'o:prefix' => 'defs',
        ]);
        $text = strip_tags(html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5));
        // Saying which form it has, since Review changes moves it, and that
        // importing anyway is the user's choice.
        $this->assertSame(1, substr_count($text, 'Review uses another form of this namespace URI, http://example.org/review#, so the file may hold the same vocabulary. To update it from the file and move it to this namespace URI, click Review changes. To import the file as a new vocabulary anyway, give it a prefix of its own and click Import.'));
    }

    public function testImportWithNoTermsInTheNamespaceIsNotTriedAndSaysWhyOnce()
    {
        try {
            $this->assertSame(302, $this->startImport('../../../Stdlib/_files/details-declared.ttl'));
            $url = $this->importDetailsUrl();
            $this->assertSame(200, $this->finishImport($url, [
                'o:label' => 'Declared vocabulary',
                'o:namespace_uri' => 'http://example.org/other#',
                'o:prefix' => 'decl',
            ]));
            $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
            // Once among the messages, from the list, and at its field: the
            // import isn't tried, as the list has said why it would fail.
            $this->assertSame(2, substr_count($page, 'None of the file\'s classes or properties are in the namespace URI "http://example.org/other#".'));
            $this->assertMatchesRegularExpression('#id="o:namespace_uri_field">.*?<ul class="messages">\s*<li>None of the file\'s classes or properties are in the namespace URI "http://example.org/other\#"\.#s', $page);

            // Still once among the messages when another field is refused
            // too, not again under the namespace URI's label.
            $this->assertSame(200, $this->finishImport($url, [
                'o:label' => ' ',
                'o:namespace_uri' => 'http://example.org/other#',
                'o:prefix' => 'decl',
            ]));
            $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
            $this->assertStringContainsString('<li class="error">Label: Value is required and can\'t be empty</li>', $page);
            $this->assertSame(2, substr_count($page, 'None of the file\'s classes or properties are in the namespace URI "http://example.org/other#".'));
            $this->assertStringNotContainsString('Namespace URI: None of the file', $page);
        } finally {
            $this->deleteImported('decl');
        }
    }

    /**
     * Hand an import to the import details page, as a module would.
     *
     * @param string $file A file in _files, or a path relative to it
     * @param array $vocabulary
     * @param array $importOptions
     * @return string The import details page's URL
     */
    protected function handImport($file, array $vocabulary = [], array $importOptions = [])
    {
        $this->reset(true);
        $this->signIn();
        $url = $this->getApplicationServiceLocator()->get('Omeka\VocabularyImport')->start(__DIR__ . '/_files/' . $file, $vocabulary, $importOptions);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->importTokens[] = $query['token'];
        return $url;
    }

    public function testAHandedImportStartsWithWhatItWasHanded()
    {
        $url = $this->handImport('../../../Stdlib/_files/details-declared.ttl', [
            'o:label' => 'Handed vocabulary',
            'o:comment' => 'From a module.',
            'o:prefix' => 'handed',
        ], [
            'format' => 'turtle',
            'lang' => 'fr',
            'comment_property' => RdfImporter::DEFINITION_COMMENT_PROPERTY,
        ]);
        $this->assertMatchesRegularExpression('#^/admin/vocabulary/import-details\?token=[0-9a-f]{32}$#', $url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('turtle', $this->getApplicationServiceLocator()->get('Omeka\VocabularyImport')->get($query['token'])['import_options']['format']);
        $this->assertSame(200, $this->requestAs(self::ADMIN, 'GET', $url));
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('value="Handed vocabulary"', $page);
        $this->assertStringContainsString('From a module.</textarea>', $page);
        $this->assertStringContainsString('value="handed"', $page);
        // What it wasn't handed, from the file.
        $this->assertStringContainsString('value="http://example.org/declared#"', $page);
        $this->assertMatchesRegularExpression('#name="lang".*?<option value="fr" selected>#s', $page);
        $this->assertStringContainsString('<option value="definition" selected>', $page);
    }

    public function testAHandedImportEndsAsAnyDoes()
    {
        try {
            $url = $this->handImport('../../../Stdlib/_files/definitions.ttl', ['o:label' => 'Definitions', 'o:prefix' => 'defs'], ['format' => 'turtle']);
            $this->assertSame(302, $this->finishImport($url, [
                'o:label' => 'Definitions',
                'o:namespace_uri' => 'http://example.org/defs#',
                'o:prefix' => 'defs',
            ]));
            $location = $this->getResponse()->getHeaders()->get('Location')->getUri();
            $this->reset(true);
            $this->signIn();
            $vocabulary = $this->api()->read('vocabularies', ['prefix' => 'defs'])->getContent();
            // On the new vocabulary's page.
            $this->assertSame(sprintf('/admin/vocabulary/%s', $vocabulary->id()), $location);
            // And what it was handed is forgotten.
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $this->assertNull($this->getApplicationServiceLocator()->get('Omeka\VocabularyImport')->get($query['token']));
        } finally {
            $this->deleteImported('defs');
        }
    }

    public function testAHandedFileThatCantBeImportedIsRefusedAtOnce()
    {
        $this->reset(true);
        $this->signIn();
        $services = $this->getApplicationServiceLocator();
        $pattern = $services->get('Config')['temp_dir'] . '/' . Stager::FILE_PREFIX . '*';
        $files = glob($pattern);
        try {
            $services->get('Omeka\VocabularyImport')->start(__DIR__ . '/../../Stdlib/_files/no-terms.ttl', [], ['format' => 'turtle']);
            $this->fail('A file with no classes or properties was handed on.');
        } catch (ValidationException $e) {
            // Before the details page, and without keeping the file.
            $this->assertSame($files, glob($pattern));
        }
    }

    public function testAHandedFileThatCantBeStagedIsRefusedForTheAdmin()
    {
        $this->reset(true);
        $this->signIn();
        try {
            $this->getApplicationServiceLocator()->get('Omeka\VocabularyImport')->start(__DIR__ . '/_files/missing.ttl');
            $this->fail('A missing file was handed on.');
        } catch (ValidationException $e) {
            // Not the staging failure's own message, which names a path.
            $this->assertSame('Could not read vocabulary file.', $e->getMessage());
        }
    }

    public function testAnImportHandedOnlyItsFileHoldsOnlyItsFormat()
    {
        // As Import starts one.
        $url = $this->handImport('markup.ttl', [], ['format' => 'turtle']);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $import = $this->getApplicationServiceLocator()->get('Omeka\VocabularyImport')->get($query['token']);
        $this->assertFileExists($import['files']['source']);
        $this->assertSame([], $import['vocabulary']);
        $this->assertSame(['format' => 'turtle'], $import['import_options']);
        $this->assertSame(200, $this->requestAs(self::ADMIN, 'GET', $url));
    }

    public function testAHandedFormatImportDetailsDoNotOfferIsGuessed()
    {
        $url = $this->handImport('markup.ttl', [], ['format' => 'n3']);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('guess', $this->getApplicationServiceLocator()->get('Omeka\VocabularyImport')->get($query['token'])['import_options']['format']);
        $this->assertSame(200, $this->requestAs(self::ADMIN, 'GET', $url));
    }

    public function testEndingAnImportForgetsItAndRemovesItsFile()
    {
        $url = $this->handImport('markup.ttl', ['o:label' => 'Markup'], ['format' => 'turtle']);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $vocabularyImport = $this->getApplicationServiceLocator()->get('Omeka\VocabularyImport');
        $path = $vocabularyImport->get($query['token'])['files']['source'];
        $this->assertFileExists($path);
        $vocabularyImport->cancel($query['token']);
        $this->assertNull($vocabularyImport->get($query['token']));
        $this->assertFileDoesNotExist($path);
    }

    public function testImportFromTheFileLandsOnTheVocabularysPage()
    {
        $tempDir = $this->getApplicationServiceLocator()->get('Config')['temp_dir'];
        $files = glob($tempDir . '/' . Stager::FILE_PREFIX . '*');
        try {
            $this->assertSame(302, $this->startImport('../../../Stdlib/_files/definitions.ttl'));
            $status = $this->finishImport($this->importDetailsUrl(), [
                'o:label' => 'Definitions',
                // Trimmed, as the file is read with it.
                'o:namespace_uri' => ' http://example.org/defs# ',
                // Trimmed, as the API trims it, not refused.
                'o:prefix' => ' defs ',
            ]);
            $this->assertSame(302, $status);
            $location = $this->getResponse()->getHeaders()->get('Location')->getUri();
            $this->assertStringContainsString('Vocabulary successfully imported.', implode(' ', $this->messages(Messenger::SUCCESS)));
            $this->reset(true);
            $this->signIn();
            $vocabulary = $this->api()->read('vocabularies', ['prefix' => 'defs'])->getContent();
            $this->assertSame(sprintf('/admin/vocabulary/%s', $vocabulary->id()), $location);
            $this->assertSame('http://example.org/defs#', $vocabulary->namespaceUri());
            $this->assertSame(3, $vocabulary->propertyCount() + $vocabulary->resourceClassCount());
            // The staged file is gone.
            $this->assertSame($files, glob($tempDir . '/' . Stager::FILE_PREFIX . '*'));
        } finally {
            $this->deleteImported('defs');
        }
    }

    public function importsWithComments()
    {
        return [
            'the comment, by default' => [[], RdfImporter::COMMENT_PROPERTY, 'Usage note on titles.'],
            'the definition, chosen' => [['comments' => 'definition'], RdfImporter::DEFINITION_COMMENT_PROPERTY, "The work's title."],
            'another property, chosen' => [['comments' => 'other', 'comment_property' => 'skos:definition'], 'skos:definition', "The work's title."],
            'another property, entered but not chosen' => [['comments' => 'comment', 'comment_property' => 'skos:definition'], RdfImporter::COMMENT_PROPERTY, 'Usage note on titles.'],
        ];
    }

    /**
     * @dataProvider importsWithComments
     */
    public function testImportReadsCommentsAsChosen(array $advanced, $commentProperty, $titleComment)
    {
        try {
            $this->startImport('../../../Stdlib/_files/definitions.ttl');
            $status = $this->finishImport($this->importDetailsUrl(), [
                'o:label' => 'Definitions',
                'o:namespace_uri' => 'http://example.org/defs#',
                'o:prefix' => 'defs',
            ], $advanced);
            $this->assertSame(302, $status);
            $this->reset(true);
            $this->signIn();
            $vocabulary = $this->api()->read('vocabularies', ['prefix' => 'defs'])->getContent();
            $this->assertSame($commentProperty, $vocabulary->importOptions()['comment_property']);
            $title = $this->api()->search('properties', ['term' => 'defs:title'])->getContent()[0];
            $this->assertSame($titleComment, $title->comment());
        } finally {
            $this->deleteImported('defs');
        }
    }

    public function testARefusedImportKeepsItsFileToTryAgain()
    {
        try {
            $this->startImport('../../../Stdlib/_files/definitions.ttl');
            $url = $this->importDetailsUrl();
            // A prefix another vocabulary has.
            $this->assertSame(200, $this->finishImport($url, [
                'o:label' => 'Definitions',
                'o:namespace_uri' => 'http://example.org/defs#',
                'o:prefix' => 'dcterms',
            ]));
            $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
            $this->assertStringContainsString('The prefix "dcterms" is already taken.', $page);
            // And at its field.
            $this->assertMatchesRegularExpression('#id="o:prefix_field">.*?<ul class="messages">\s*<li>The prefix "dcterms" is already taken\.</li>#s', $page);
            // The API's refusal has no message of its own to show.
            $this->assertStringNotContainsString('<li class="error"></li>', $page);
            $this->assertSame(302, $this->finishImport($url, [
                'o:label' => 'Definitions',
                'o:namespace_uri' => 'http://example.org/defs#',
                'o:prefix' => 'defs',
            ]));
        } finally {
            $this->deleteImported('defs');
        }
    }

    public function testImportOfAFileWithoutTermsIsRefusedAtTheStart()
    {
        $tempDir = $this->getApplicationServiceLocator()->get('Config')['temp_dir'];
        $files = glob($tempDir . '/' . Stager::FILE_PREFIX . '*');
        $this->assertSame(200, $this->startImport('../../../Stdlib/_files/no-terms.ttl'));
        $this->assertStringContainsString('No classes or properties found in this file.', $this->getResponse()->getContent());
        $this->assertSame($files, glob($tempDir . '/' . Stager::FILE_PREFIX . '*'));
    }

    public function testImportOfAnEmptyFileSaysSo()
    {
        $this->assertSame(200, $this->startImport('../../../Stdlib/_files/empty.ttl', 'guess'));
        $this->assertStringContainsString('The vocabulary file is empty.', $this->getResponse()->getContent());
    }

    /**
     * Post an import's details that are refused, and check that nothing was
     * imported.
     *
     * @param array $data The details page's post
     * @return string The page, with its messages
     */
    protected function refusedImport(array $data)
    {
        try {
            $this->assertSame(302, $this->startImport('../../../Stdlib/_files/definitions.ttl'));
            $this->assertSame(200, $this->requestAs(self::ADMIN, 'POST', $this->importDetailsUrl(), ['import' => '1'] + $data));
            $this->assertTemplateName('omeka/admin/vocabulary/import-details');
            $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
            $this->reset(true);
            $this->signIn();
            $this->assertSame(0, $this->api()->search('vocabularies', ['namespace_uri' => 'http://example.org/defs#'])->getTotalResults());
            return $page;
        } finally {
            $this->deleteImported('defs');
        }
    }

    public function testImportDetailsRefuseAnEmptyLabel()
    {
        $page = $this->refusedImport([
            'o:label' => ' ', 'o:comment' => '', 'o:namespace_uri' => 'http://example.org/defs#', 'o:prefix' => 'defs',
            'lang' => '', 'comments' => 'comment', 'label_property' => '', 'comment_property' => '',
        ] + $this->csrfField(VocabularyForm::class));
        $this->assertStringContainsString('Label: Value is required and can\'t be empty', $page);
    }

    public function testTheChoicesHaveNoCsrfFieldOfTheirOwn()
    {
        // Import details post them inside the vocabulary's form, which has
        // one, and the review page sends them by GET.
        $this->assertSame(302, $this->startImport('../../../Stdlib/_files/definitions.ttl'));
        $this->assertSame(200, $this->requestAs(self::ADMIN, 'GET', $this->importDetailsUrl()));
        $page = $this->getResponse()->getContent();
        $this->assertStringContainsString('name="vocabularyform_csrf"', $page);
        $this->assertStringNotContainsString('vocabularyimportoptionsform_csrf', $page);
        $this->assertStringNotContainsString('vocabularyimportoptionsform_csrf', $this->reviewPage($this->startReview('changed-name.ttl')));
    }

    public function testImportDetailsNameAMalformedProperty()
    {
        // Show classes and properties, with Another property… entered
        // wrongly: the property is named, not a term.
        $this->assertSame(302, $this->startImport('../../../Stdlib/_files/definitions.ttl'));
        $this->assertSame(200, $this->requestAs(self::ADMIN, 'POST', $this->importDetailsUrl(), [
            'o:label' => 'Definitions', 'o:comment' => '', 'o:namespace_uri' => 'http://example.org/defs#', 'o:prefix' => 'defs',
            'lang' => '', 'labels' => 'other', 'label_property' => 'skos:altLabel|', 'comments' => 'comment', 'comment_property' => '',
        ] + $this->csrfField(VocabularyForm::class)));
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('The label property "skos:altLabel|" isn\'t valid. Enter a property\'s full URI, in angle brackets, or several separated by |, such as <http://www.w3.org/2004/02/skos/core#altLabel>.', $page);
        $this->assertStringNotContainsString('valid for its datatype', $page);
        // And beside its field, which is the choices' form's.
        $this->assertMatchesRegularExpression('#id="label_property_field">(?:(?!<div class="field" ).)*<ul class="messages"><li>The label property "skos:altLabel\|" isn\'t valid\.#s', $page);
    }

    public function testImportDetailsReadALanguageTheListDoesNotOffer()
    {
        // As Another language… would, and as the review page reads one in its
        // URL.
        try {
            $this->startImport('../../../Stdlib/_files/definitions.ttl');
            $this->assertSame(302, $this->finishImport($this->importDetailsUrl(), [
                'o:label' => 'Definitions',
                'o:namespace_uri' => 'http://example.org/defs#',
                'o:prefix' => 'defs',
            ], ['lang' => 'de']));
            $this->reset(true);
            $this->signIn();
            $vocabulary = $this->api()->read('vocabularies', ['prefix' => 'defs'])->getContent();
            $this->assertSame('de', $vocabulary->importOptions()['lang']);
        } finally {
            $this->deleteImported('defs');
        }
    }

    public function testAnImportPostedWithoutItsCsrfFieldMakesNothing()
    {
        $page = $this->refusedImport([
            'o:label' => 'Definitions', 'o:comment' => '', 'o:namespace_uri' => 'http://example.org/defs#', 'o:prefix' => 'defs',
            'lang' => '', 'comments' => 'comment', 'label_property' => '', 'comment_property' => '',
        ]);
        $this->assertStringContainsString('CSRF: Value is required and can\'t be empty', $page);
    }

    public function testImportOfAVocabularyOmekaHasWarnsThenRefuses()
    {
        // Its terms are in the tests' vocabulary's namespace.
        $this->assertSame(302, $this->startImport('markup.ttl'));
        $url = $this->importDetailsUrl();
        // Said on the details, where a namespace found wrong can be corrected.
        $this->requestAs(self::ADMIN, 'GET', $url);
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('Review already uses this namespace URI. To update it from the file, click Review changes. To import the file as a new vocabulary, enter its own namespace URI.', strip_tags($page));
        $this->assertStringContainsString(sprintf('<a href="/admin/vocabulary/%s">Review</a>', $this->vocabularyId), $page);
        // After Import, which Enter still means.
        $this->assertMatchesRegularExpression('#<button name="import" value="1">Import</button>\s*<button type="submit" name="review_changes" value="1" formnovalidate>Review changes</button>#', $page);
        // The form posts to its own page.
        $this->assertMatchesRegularExpression('#<form [^>]*action="/admin/vocabulary/import-details\?token=[0-9a-f]+"#', $page);
        // Without the file's classes and properties, which importing under
        // the namespace URI wouldn't add.
        $this->assertStringNotContainsString('class="field-term"', $page);
        // Refused if it's kept.
        try {
            $this->assertSame(200, $this->finishImport($url, [
                'o:label' => 'Review again',
                'o:namespace_uri' => 'http://example.org/review#',
                'o:prefix' => 'review2',
            ]));
            $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
            $this->assertStringContainsString('Review already uses this namespace URI. To update it from the file, click Review changes. To import the file as a new vocabulary, enter its own namespace URI.', strip_tags($page));
            $this->assertStringContainsString('name="review_changes"', $page);
        } finally {
            $this->deleteImported('review2');
        }
    }

    public function testImportDetailsWarnOfAVocabularyTheUserMayNotUpdate()
    {
        // A file whose terms are Dublin Core's, which no one may update.
        $this->assertSame(302, $this->startImport('dcterms-title.ttl'));
        $url = $this->importDetailsUrl();
        $this->requestAs(self::ADMIN, 'GET', $url);
        $text = strip_tags(html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5));
        $this->assertStringContainsString('Dublin Core already uses this namespace URI. To import the file as a new vocabulary, enter its own namespace URI.', $text);
        $this->assertStringNotContainsString('Review changes', $text);
        $this->assertStringNotContainsString('class="field-term"', $this->getResponse()->getContent());
        // Nor will it start a review.
        $this->assertSame(200, $this->requestAs(self::ADMIN, 'POST', $url, [
            'review_changes' => '1',
            'o:label' => 'Title', 'o:comment' => '', 'o:namespace_uri' => 'http://purl.org/dc/terms/', 'o:prefix' => '',
        ]));
        $this->assertTemplateName('omeka/admin/vocabulary/import-details');

        // Another form of it entered: said, with no Review changes.
        $this->requestAs(self::ADMIN, 'POST', $url, [
            'o:label' => 'Title', 'o:comment' => '', 'o:namespace_uri' => 'https://purl.org/dc/terms/', 'o:prefix' => '',
        ]);
        $text = strip_tags(html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5));
        $this->assertStringContainsString('Dublin Core uses another form of this namespace URI, http://purl.org/dc/terms/, so the file may hold the same vocabulary. To import the file as a new vocabulary anyway, give it a prefix of its own and click Import.', $text);
        $this->assertStringNotContainsString('Review changes', $text);
    }

    public function testReviewChangesHandsTheFileToUpdatesReview()
    {
        // Its terms are in the tests' vocabulary's namespace.
        $this->assertSame(302, $this->startImport('markup.ttl'));
        $url = $this->importDetailsUrl();
        $this->assertSame(302, $this->requestAs(self::ADMIN, 'POST', $url, [
            'review_changes' => '1',
            'o:label' => '', 'o:comment' => '', 'o:namespace_uri' => 'http://example.org/review#', 'o:prefix' => '',
        ] + $this->csrfField(VocabularyForm::class)));
        $location = $this->getResponse()->getHeaders()->get('Location')->getUri();
        $this->assertStringStartsWith(sprintf('/admin/vocabulary/%s/review?token=', $this->vocabularyId), $location);
        $token = $this->tokens[] = substr($location, strpos($location, '=') + 1);
        // The review's changes are the file's, with no namespace change.
        $page = $this->reviewPage($token);
        $this->assertStringContainsString('<ins>&lt;i&gt;file</ins> property label&lt;/i&gt;', $page);
        $this->assertStringNotContainsString('<td role="rowheader">Namespace URI</td>', $page);
        // The import is over, its file with the review now.
        $this->assertSame(302, $this->requestAs(self::ADMIN, 'GET', $url));
        $this->assertRedirectTo('/admin/vocabulary/import');
    }

    public function testReviewChangesKeepsTheChoicesMadeOnTheDetails()
    {
        $this->assertSame(302, $this->startImport('multilingual-name.ttl'));
        $this->assertSame(302, $this->requestAs(self::ADMIN, 'POST', $this->importDetailsUrl(), [
            'review_changes' => '1',
            'o:label' => '', 'o:comment' => '', 'o:namespace_uri' => 'http://example.org/review#', 'o:prefix' => '',
            'lang' => 'fr', 'comments' => 'definition', 'label_property' => '', 'comment_property' => '',
        ] + $this->csrfField(VocabularyForm::class)));
        $location = $this->getResponse()->getHeaders()->get('Location')->getUri();
        $token = $this->tokens[] = substr($location, strpos($location, '=') + 1);
        $page = $this->reviewPage($token);
        $this->assertMatchesRegularExpression('#<ins>Nom</ins>#', $page);
        $page = html_entity_decode($page, ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('<option value="fr" selected>', $page);
        $this->assertStringContainsString('<option value="definition" selected>', $page);
    }

    public function testReviewChangesKeepsADefaultChosenOverTheStoredChoice()
    {
        // Read in French before; import details start there.
        $this->api()->update('vocabularies', $this->vocabularyId, [
            'o:import_options' => ['format' => 'turtle', 'lang' => 'fr'],
        ], [], ['isPartial' => true]);
        $this->assertSame(302, $this->startImport('multilingual-name.ttl'));
        // First available and the default label, chosen on the details.
        $this->assertSame(302, $this->requestAs(self::ADMIN, 'POST', $this->importDetailsUrl(), [
            'review_changes' => '1',
            'o:label' => '', 'o:comment' => '', 'o:namespace_uri' => 'http://example.org/review#', 'o:prefix' => '',
            'lang' => '', 'labels' => 'label', 'comments' => 'comment',
        ] + $this->csrfField(VocabularyForm::class)));
        $location = $this->getResponse()->getHeaders()->get('Location')->getUri();
        $token = $this->tokens[] = substr($location, strpos($location, '=') + 1);
        // The review starts with them, not with the stored French.
        $page = html_entity_decode($this->reviewPage($token), ENT_QUOTES | ENT_HTML5);
        $this->assertMatchesRegularExpression('#<select name="lang"[^>]*><option value="" selected>#', $page);
    }

    public function testAHandedImportOfAVocabularyOmekaHasStartsAsItWasReadWithWhatItWasHanded()
    {
        $this->api()->update('vocabularies', $this->vocabularyId, [
            'o:import_options' => ['format' => 'turtle', 'lang' => 'fr', 'comment_property' => RdfImporter::DEFINITION_COMMENT_PROPERTY],
        ], [], ['isPartial' => true]);
        $url = $this->handImport('multilingual-name.ttl', [], ['format' => 'turtle', 'lang' => 'en']);
        $this->requestAs(self::ADMIN, 'GET', $url);
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        // The language it was handed, and the comments as Omeka read them.
        $this->assertMatchesRegularExpression('#name="lang".*?<option value="en" selected>#s', $page);
        $this->assertStringContainsString('<option value="definition" selected>', $page);
    }

    public function testImportDetailsOfAVocabularyReadWithoutALanguageStartOnFirstAvailable()
    {
        // Import options given through the API may leave the language out,
        // which reads the file as First available does.
        $this->api()->update('vocabularies', $this->vocabularyId, ['o:import_options' => ['format' => 'turtle']], [], ['isPartial' => true]);
        $this->assertSame(302, $this->startImport('multilingual-name.ttl'));
        $url = $this->importDetailsUrl();
        $this->requestAs(self::ADMIN, 'GET', $url);
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertMatchesRegularExpression('#name="lang"[^>]*><option value="" selected>#', $page);
    }

    public function testImportDetailsFindTheVocabularyWithTheNamespaceUriAsGiven()
    {
        // The tests' vocabulary in http, and another in https.
        $httpsId = $this->createVocabulary('https://example.org/review#', 'reviewhttps', 'Review in https');
        try {
            // Its terms are in the https form.
            $this->assertSame(302, $this->startImport('other-form.ttl'));
            $url = $this->importDetailsUrl();
            $this->requestAs(self::ADMIN, 'GET', $url);
            $text = strip_tags(html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5));
            $this->assertStringContainsString('Review in https already uses this namespace URI.', $text);
            // And Review changes reviews that one.
            $this->assertSame(302, $this->requestAs(self::ADMIN, 'POST', $url, [
                'review_changes' => '1',
                'o:label' => '', 'o:comment' => '', 'o:namespace_uri' => 'https://example.org/review#', 'o:prefix' => '',
            ] + $this->csrfField(VocabularyForm::class)));
            $location = $this->getResponse()->getHeaders()->get('Location')->getUri();
            $this->assertStringStartsWith(sprintf('/admin/vocabulary/%s/review?token=', $httpsId), $location);
            $this->tokens[] = substr($location, strpos($location, '=') + 1);
        } finally {
            $this->reset(true);
            $this->signIn();
            $this->api()->delete('vocabularies', $httpsId);
        }
    }

    public function testImportDetailsOfAVocabularyOmekaHasStartAsItWasRead()
    {
        $this->api()->update('vocabularies', $this->vocabularyId, [
            'o:import_options' => ['format' => 'turtle', 'lang' => 'fr', 'comment_property' => RdfImporter::DEFINITION_COMMENT_PROPERTY],
        ], [], ['isPartial' => true]);
        $this->assertSame(302, $this->startImport('multilingual-name.ttl'));
        $url = $this->importDetailsUrl();
        $this->requestAs(self::ADMIN, 'GET', $url);
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertMatchesRegularExpression('#name="lang".*?<option value="fr" selected>#s', $page);
        $this->assertStringContainsString('<option value="definition" selected>', $page);
        // The review reads it so, with the page left as it was.
        $this->assertSame(302, $this->requestAs(self::ADMIN, 'POST', $url, [
            'review_changes' => '1',
            'o:label' => '', 'o:comment' => '', 'o:namespace_uri' => 'http://example.org/review#', 'o:prefix' => '',
            'lang' => 'fr', 'comments' => 'definition', 'label_property' => '', 'comment_property' => '',
        ] + $this->csrfField(VocabularyForm::class)));
        $location = $this->getResponse()->getHeaders()->get('Location')->getUri();
        $token = $this->tokens[] = substr($location, strpos($location, '=') + 1);
        $page = html_entity_decode($this->reviewPage($token), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('<option value="fr" selected>', $page);
        $this->assertStringContainsString('<option value="definition" selected>', $page);
    }

    public function testImportDetailsStartOnALanguageTheFileNoLongerHasAsAnother()
    {
        $this->api()->update('vocabularies', $this->vocabularyId, [
            'o:import_options' => ['format' => 'turtle', 'lang' => 'de'],
        ], [], ['isPartial' => true]);
        $this->assertSame(302, $this->startImport('multilingual-name.ttl'));
        $this->requestAs(self::ADMIN, 'GET', $this->importDetailsUrl());
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('<option value="other" selected>Another language…</option>', $page);
        $this->assertMatchesRegularExpression('#<input type="text" name="other_lang"[^>]*value="de"#', $page);
    }

    public function testReviewChangesNeedsTheFormsCsrfField()
    {
        $this->assertSame(302, $this->startImport('markup.ttl'));
        $url = $this->importDetailsUrl();
        $this->assertSame(200, $this->requestAs(self::ADMIN, 'POST', $url, [
            'review_changes' => '1',
            'o:label' => '', 'o:comment' => '', 'o:namespace_uri' => 'http://example.org/review#', 'o:prefix' => '',
        ]));
        $this->assertTemplateName('omeka/admin/vocabulary/import-details');
        // The import goes on.
        $this->assertSame(200, $this->requestAs(self::ADMIN, 'GET', $url));
    }

    public function testReviewChangesRefusedSaysWhyOnce()
    {
        // The file's terms use https; the namespace URI entered is the
        // vocabulary's own http form, which has none of them.
        $this->assertSame(302, $this->startImport('other-form.ttl'));
        $this->assertSame(200, $this->requestAs(self::ADMIN, 'POST', $this->importDetailsUrl(), [
            'review_changes' => '1',
            'o:label' => '', 'o:comment' => '', 'o:namespace_uri' => 'http://example.org/review#', 'o:prefix' => '',
        ] + $this->csrfField(VocabularyForm::class)));
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertSame(1, substr_count($page, 'None of the file\'s classes or properties are in the namespace URI "http://example.org/review#".'));
    }

    public function testAnImportWithFormErrorsStillSaysWhatReviewChangesIsFor()
    {
        $this->assertSame(302, $this->startImport('markup.ttl'));
        $this->assertSame(200, $this->finishImport($this->importDetailsUrl(), [
            'o:label' => 'Review again',
            'o:namespace_uri' => 'http://example.org/review#',
            'o:prefix' => 'not a prefix',
        ]));
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('The prefix may only contain letters, numbers, hyphens, and underscores.', $page);
        $this->assertStringContainsString('Review already uses this namespace URI. To update it from the file, click Review changes.', strip_tags($page));
        $this->assertStringContainsString('name="review_changes"', $page);
    }

    public function testReviewChangesOfAnotherFormOfTheNamespaceChangesItToo()
    {
        $this->assertSame(302, $this->startImport('other-form.ttl'));
        $this->assertSame(302, $this->requestAs(self::ADMIN, 'POST', $this->importDetailsUrl(), [
            'review_changes' => '1',
            'o:label' => '', 'o:comment' => '', 'o:namespace_uri' => 'https://example.org/review#', 'o:prefix' => '',
        ] + $this->csrfField(VocabularyForm::class)));
        $location = $this->getResponse()->getHeaders()->get('Location')->getUri();
        $token = $this->tokens[] = substr($location, strpos($location, '=') + 1);
        $text = preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($this->reviewPage($token), ENT_QUOTES | ENT_HTML5)));
        $this->assertStringContainsString('Namespace URI http://example.org/review# https://example.org/review#', $text);
    }

    public function testImportAcceptsAnotherFormOfANamespaceOmekaHas()
    {
        try {
            // A file whose terms are in the https form of the tests'
            // vocabulary's namespace URI.
            $this->assertSame(302, $this->startImport('other-form.ttl'));
            $url = $this->importDetailsUrl();
            // Said, with the classes and properties importing would add,
            // since namespace URIs are opaque and the user decides.
            $this->requestAs(self::ADMIN, 'GET', $url);
            $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
            $this->assertStringContainsString('Review uses another form of this namespace URI, http://example.org/review#, so the file may hold the same vocabulary.', strip_tags($page));
            $this->assertStringContainsString('class="field-term"', $page);
            // Imported, with no warning carried to the new vocabulary's page.
            $this->assertSame(302, $this->finishImport($url, [
                'o:label' => 'Review again',
                'o:namespace_uri' => 'https://example.org/review#',
                'o:prefix' => 'review2',
            ]));
            $this->assertSame([], $this->messages(Messenger::WARNING));
            $this->reset(true);
            $this->signIn();
            $this->assertSame(1, $this->api()->search('vocabularies', ['prefix' => 'review2'])->getTotalResults());
        } finally {
            $this->deleteImported('review2');
        }
    }

    public function testImportOfAFileNamingItsTermsRelativeToItselfAsksForTheNamespace()
    {
        try {
            $this->assertSame(302, $this->startImport('../../../Stdlib/_files/details-relative.rdf', 'rdfxml'));
            $url = $this->importDetailsUrl();
            $this->requestAs(self::ADMIN, 'GET', $url);
            $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
            $this->assertStringContainsString("The file doesn't give a namespace URI for its classes and properties. Enter it below.", $page);
            // Its permanence is said once, at the field.
            $this->assertSame(1, substr_count($page, "It can't be changed after importing."));
            $this->assertStringContainsString('name="o:namespace_uri" required id="o:namespace_uri"', $page);
            // The prompt for one that doesn't end with / or #, among the
            // script's translations.
            $this->assertStringContainsString('Would you like to save anyway?', $page);
            $this->assertStringContainsString('value=""', $page);
            $this->assertSame(302, $this->finishImport($url, [
                'o:label' => 'Relative vocabulary',
                'o:namespace_uri' => 'http://example.org/relative#',
                'o:prefix' => 'relative',
            ]));
            $this->reset(true);
            $this->signIn();
            $vocabulary = $this->api()->read('vocabularies', ['prefix' => 'relative'])->getContent();
            $this->assertSame(1, $vocabulary->resourceClassCount());
            $this->assertSame(1, $vocabulary->propertyCount());
        } finally {
            $this->deleteImported('relative');
        }
    }

    public function testImportDetailsOfAnImportThatHasEnded()
    {
        $status = $this->requestAs(self::ADMIN, 'GET', '/admin/vocabulary/import-details?token=unknown');
        $this->assertSame(302, $status);
        $this->assertRedirectTo('/admin/vocabulary/import');
        $this->assertSame(['This import has ended. Choose the file or enter its URL again.'], $this->messages(Messenger::ERROR));
    }

    public function testReviewChangesWithoutAVocabularyToUpdateSaysSo()
    {
        // As when the page's namespace URI was changed to one no vocabulary
        // has after it was drawn: no review, the page again, saying why.
        $this->assertSame(302, $this->startImport('unchanged.ttl'));
        $status = $this->requestAs(self::ADMIN, 'POST', $this->importDetailsUrl(), [
            'review_changes' => '1',
            'o:label' => '', 'o:comment' => '', 'o:namespace_uri' => 'http://example.org/unrelated#', 'o:prefix' => '',
        ] + $this->csrfField(VocabularyForm::class));
        $this->assertSame(200, $status);
        $this->assertTemplateName('omeka/admin/vocabulary/import-details');
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('No vocabulary you may update uses this namespace URI, so there are no changes to review.', $page);
    }

    public function testAnErrorWithNothingToSayStillSaysSomething()
    {
        $controller = $this->getApplicationServiceLocator()->get('ControllerManager')->get('Omeka\Controller\Admin\Vocabulary');
        $getErrors = new \ReflectionMethod($controller, 'getErrors');
        $getErrors->setAccessible(true);
        $this->assertSame(['Something went wrong'], $getErrors->invoke($controller, new \Exception));
        $this->assertSame(['Something went wrong'], $getErrors->invoke($controller, new \Omeka\Api\Exception\ValidationException));
        // A message, or reasons, are said instead.
        $this->assertSame(['Could not read vocabulary file.'], $getErrors->invoke($controller, new \Exception('Could not read vocabulary file.')));
        $refusal = new \Omeka\Api\Exception\ValidationException;
        $refusal->getErrorStore()->addError('o:prefix', 'The prefix cannot be empty.');
        $this->assertSame([['o:prefix' => ['The prefix cannot be empty.']]], $getErrors->invoke($controller, $refusal));
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

    public function testChangeNamespaceRefusesATakenOne()
    {
        $status = $this->changeNamespace('http://purl.org/dc/terms/');
        $this->assertSame(200, $status);
        $this->assertTemplateName('omeka/admin/vocabulary/edit');
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('The namespace URI "http://purl.org/dc/terms/" is already taken.', $page);
        $this->assertStringNotContainsString('Vocabulary successfully updated', $page);
        $this->assertSame(self::NAMESPACE_URI, $this->vocabulary()->namespaceUri());
    }

    public function testChangeNamespaceAcceptsAnotherFormOfAnotherVocabularys()
    {
        $this->reset(true);
        $this->signIn();
        $other = $this->api()->create('vocabularies', [
            'o:namespace_uri' => 'http://example.org/other#',
            'o:prefix' => 'other',
            'o:label' => 'Other',
        ])->getContent();
        try {
            // Namespace URIs are opaque: only an exact match is taken.
            $this->assertSame(302, $this->changeNamespace('https://example.org/other#'));
            $this->assertSame('https://example.org/other#', $this->vocabulary()->namespaceUri());
        } finally {
            $this->reset(true);
            $this->signIn();
            $this->api()->delete('vocabularies', $other->id());
        }
    }

    public function testChangeNamespaceToAnotherFormOfItsOwn()
    {
        $status = $this->changeNamespace('https://example.org/review#');
        $this->assertSame(302, $status);
        $this->assertSame('https://example.org/review#', $this->vocabulary()->namespaceUri());
    }

    public function testChangeNamespaceRefusedForAnothersVocabulary()
    {
        $status = $this->changeNamespace('http://example.org/moved#', self::SUPERVISOR);
        $this->assertSame(403, $status);
        $this->assertSame(self::NAMESPACE_URI, $this->vocabulary()->namespaceUri());
    }

    public function testChangeNamespaceAppliedForOwnVocabulary()
    {
        $this->giveVocabularyTo(self::SUPERVISOR);
        $this->changeNamespace('http://example.org/moved#', self::SUPERVISOR);
        $this->assertSame('http://example.org/moved#', $this->vocabulary()->namespaceUri());
    }

    public function testUpdateRefusesAFileWithoutTermsInTheNamespace()
    {
        // The vocabulary's terms in another namespace, as another
        // vocabulary's file would have them.
        $this->serveFile('moved-name.ttl');
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/update', $this->vocabularyId), $this->updateWithUrl());
        $this->assertSame(200, $status);
        $this->assertTemplateName('omeka/admin/vocabulary/update');
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        // Both namespace URIs, as the importer refuses, beside the row
        // saying what to do when a vocabulary has moved.
        $this->assertStringContainsString('None of the file\'s classes or properties are in the namespace URI "http://example.org/review#". The file uses "http://example.org/moved#".', $page);
        $this->assertStringContainsString(sprintf('If the vocabulary has moved to a new namespace URI, change it first with <a href="/admin/vocabulary/%s/edit">Edit vocabulary</a>.', $this->vocabularyId), $page);
    }

    public function testUpdateWithTermsTooLongToStoreNamesThem()
    {
        $this->serveFile('long-label.ttl');
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/update', $this->vocabularyId), $this->updateWithUrl());
        $this->assertSame(200, $status);
        $this->assertTemplateName('omeka/admin/vocabulary/update');
        $page = $this->getResponse()->getContent();
        $this->assertStringContainsString('Some classes or properties have local names or labels longer than Omeka can store.', $page);
        $this->assertStringContainsString('These classes or properties have labels longer than 255 characters: name', $page);
    }

    public function testUpdateRefusedForAnothersVocabulary()
    {
        $status = $this->requestAs(self::SUPERVISOR, 'GET', sprintf('/admin/vocabulary/%s/update', $this->vocabularyId));
        $this->assertSame(403, $status);
    }

    public function testReviewRefusedForAnothersVocabulary()
    {
        $token = $this->startReview('changed-name.ttl');
        $status = $this->requestAs(self::SUPERVISOR, 'GET', sprintf('/admin/vocabulary/%s/review?token=%s', $this->vocabularyId, $token));
        $this->assertSame(403, $status);
    }

    public function testReviewRefusedForPermanentVocabulary()
    {
        $dcterms = $this->api()->read('vocabularies', ['prefix' => 'dcterms'])->getContent();
        try {
            $this->startReview('dcterms-title.ttl', $dcterms->id());
            $this->fail('A review of Dublin Core started');
        } catch (PermissionDeniedException $e) {
        }
        $this->assertSame('Title', $this->propertyLabel('dcterms:title'));
    }

    public function testAcceptRefusedForAnothersVocabulary()
    {
        $token = $this->startReview('changed-name.ttl');
        $status = $this->postAccept($token, self::SUPERVISOR);
        $this->assertSame(403, $status);
        $this->assertSame('<i>stored property label</i>', $this->propertyLabel('review:name'));
    }

    public function testAcceptAppliedForOwnVocabulary()
    {
        $this->giveVocabularyTo(self::SUPERVISOR);
        $token = $this->startReview('changed-name.ttl');
        $status = $this->postAccept($token, self::SUPERVISOR);
        $this->assertSame(302, $status);
        $this->assertRedirectTo(sprintf('/admin/vocabulary/%s', $this->vocabularyId));
        $this->assertSame('Changed', $this->propertyLabel('review:name'));
    }

    public function testEditDeleteLoadsItsConfirmationWhenAsked()
    {
        $this->requestAs(self::ADMIN, 'GET', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId));
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        // The confirmation, which says what deleting takes, loads into the
        // sidebar from the page the list's sidebar uses.
        $this->assertStringContainsString(sprintf('<a class="delete button sidebar-content" data-sidebar-selector="#delete" data-sidebar-content-url="/admin/vocabulary/%s/delete-confirm" href="#">Delete</a>', $this->vocabularyId), $page);
        $this->assertStringContainsString('<div id="delete" class="sidebar">', $page);
        // Its counts aren't taken with the page.
        $this->assertStringNotContainsString('<h4>Values deleted</h4>', $page);
    }

    public function testDetailsAndDeleteConfirmationSayHowMuchUsesIt()
    {
        // The details of any vocabulary, Omeka's own included, and the
        // confirmation of deleting one.
        $dcterms = $this->api()->read('vocabularies', ['prefix' => 'dcterms'])->getContent();
        $labels = [
            sprintf('/admin/vocabulary/%s/show-details', $dcterms->id()) => ['Values using its properties', 'Resource templates using its properties', 'Resources using its classes'],
            sprintf('/admin/vocabulary/%s/delete-confirm', $this->vocabularyId) => ['Values deleted', 'Resource templates that lose properties', 'Resources left without a class'],
        ];
        foreach ($labels as $path => $counts) {
            $status = $this->requestAs(self::ADMIN, 'GET', $path);
            $this->assertSame(200, $status, $path);
            $page = preg_replace('/\s+/', ' ', $this->getResponse()->getContent());
            foreach ($counts as $count) {
                $this->assertMatchesRegularExpression(sprintf('#<h4>%s</h4> <div class="value">\d+</div>#', $count), $page, $path);
            }
        }
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
