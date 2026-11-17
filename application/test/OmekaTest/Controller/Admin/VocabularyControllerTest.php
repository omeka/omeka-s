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
use Omeka\Stdlib\VocabularyFileStore;
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

    /**
     * The tokens of the files a test's imports keep.
     *
     * @var string[]
     */
    protected $importFiles = [];

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
        foreach ($this->importFiles as $token) {
            $this->getApplicationServiceLocator()->get('Omeka\VocabularyFileStore')->remove($token);
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
     * The edit form's fields, as a browser posts them.
     *
     * @param array $info The vocabulary's own fields that differ
     * @return array
     */
    protected function edit(array $info = [])
    {
        return [
            'vocabulary-info' => $info + [
                'o:label' => 'Review',
                'o:comment' => '',
            ],
        ] + $this->csrfField(VocabularyForm::class);
    }

    /**
     * Post the form to change the tests' vocabulary's namespace URI.
     *
     * @param string $namespaceUri
     * @param string $email
     * @return int The response's HTTP status
     */
    protected function changeNamespace($namespaceUri, $email = self::ADMIN)
    {
        return $this->requestAs($email, 'POST', sprintf('/admin/vocabulary/%s/change-namespace', $this->vocabularyId), [
            'vocabulary-info' => ['o:namespace_uri' => $namespaceUri],
        ] + $this->csrfField(VocabularyForm::class));
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
            'vocabulary-file' => [
                'import_type' => 'url',
                'url' => 'http://example.org/review.ttl',
                'format' => 'turtle',
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
     * @param array $choices The reading chosen on the review page
     * @return int The response's HTTP status
     */
    protected function postReview($action, $token, $email = self::ADMIN, $vocabularyId = null, array $choices = [])
    {
        return $this->requestAs($email, 'POST', sprintf('/admin/vocabulary/%s/%s', $vocabularyId ?? $this->vocabularyId, $action), [
            'review' => $token,
        ] + $choices + $this->csrfField(ConfirmForm::class));
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

    public function testShowPage()
    {
        $status = $this->requestAs(self::ADMIN, 'GET', sprintf('/admin/vocabulary/%s', $this->vocabularyId));
        $this->assertSame(200, $status);
        $this->assertTemplateName('omeka/admin/vocabulary/show');
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $text = preg_replace('/\s+/', ' ', strip_tags($page));
        $this->assertStringContainsString('Prefix review Namespace URI http://example.org/review# Classes 1 Properties 1', $text);
        // The pages that change it, for one the user may change: the rarer
        // ones in the More actions menu.
        $this->assertStringContainsString(sprintf('<a class="button" href="/admin/vocabulary/%s/edit">Edit vocabulary</a>', $this->vocabularyId), $page);
        $this->assertMatchesRegularExpression(sprintf(
            '#<div class="page-action-menu">\s*<a href="\#" class="button expand" aria-expanded="false">More actions</a>\s*<ul class="collapsible">\s*<li><a href="/admin/vocabulary/%1$s/change-namespace">Change namespace</a></li>\s*<li><a href="/admin/vocabulary/%1$s/update">Update classes and properties</a></li>#',
            $this->vocabularyId
        ), $page);
        $this->assertStringContainsString(sprintf('<a href="/admin/vocabulary/%s/properties">1</a>', $this->vocabularyId), $page);
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
        $this->assertStringNotContainsString('Change namespace', $page);
    }

    public function testBrowseLinksToEachVocabularysPage()
    {
        $this->requestAs(self::ADMIN, 'GET', '/admin/vocabulary');
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString(sprintf('<a href="/admin/vocabulary/%s">Review</a>', $this->vocabularyId), $page);
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
        $this->assertMatchesRegularExpression(sprintf('#^/admin/vocabulary/%s/review\?review=[0-9a-f]{32}$#', $this->vocabularyId), $location);
        $this->tokens[] = substr($location, strpos($location, '=') + 1);
        $this->assertSame('<i>stored property label</i>', $this->propertyLabel('review:name'));
    }

    public function testUpdateWithoutAFile()
    {
        $data = $this->updateWithUrl();
        $data['vocabulary-file']['url'] = '';
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/update', $this->vocabularyId), $data);
        $this->assertSame(200, $status);
        $this->assertTemplateName('omeka/admin/vocabulary/update');
        $this->assertStringContainsString('Choose a file or enter a URL.', $this->getResponse()->getContent());
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
        $this->assertStringNotContainsString('name="vocabulary-info[o:label]"', $page);
        $this->assertStringNotContainsString('name="vocabulary-advanced[lang]"', $page);
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
        $this->assertStringContainsString('name="vocabulary-info[o:label]"', $page);
        $this->assertStringNotContainsString('name="vocabulary-info[o:namespace_uri]"', $page);
        $this->assertStringNotContainsString('name="vocabulary-file[import_type]"', $page);
        $this->assertStringNotContainsString('name="vocabulary-advanced[lang]"', $page);
        // Core's Cancel, one page back: Edit opens from the vocabulary's page
        // or the list.
        $this->assertStringContainsString('<a class="cancel button" href="#">Cancel</a>', $page);
        // The other changes, as on the vocabulary's page.
        $this->assertMatchesRegularExpression(sprintf(
            '#<a href="\#" class="button expand" aria-expanded="false">More actions</a>\s*<ul class="collapsible">\s*<li><a href="/admin/vocabulary/%1$s/change-namespace">Change namespace</a></li>\s*<li><a href="/admin/vocabulary/%1$s/update">Update classes and properties</a></li>#',
            $this->vocabularyId
        ), $page);
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

    public function testEditLeavesTheNamespace()
    {
        $status = $this->requestAs(self::ADMIN, 'POST', sprintf('/admin/vocabulary/%s/edit', $this->vocabularyId), $this->edit([
            'o:label' => 'Renamed',
            'o:namespace_uri' => 'http://example.org/moved#',
        ]));
        $this->assertSame(302, $status);
        $vocabulary = $this->vocabulary();
        $this->assertSame('Renamed', $vocabulary->label());
        $this->assertSame(self::NAMESPACE_URI, $vocabulary->namespaceUri());
    }

    public function testChangeNamespacePage()
    {
        $this->requestAs(self::ADMIN, 'GET', sprintf('/admin/vocabulary/%s/change-namespace', $this->vocabularyId));
        $this->assertTemplateName('omeka/admin/vocabulary/change-namespace');
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $text = preg_replace('/\s+/', ' ', strip_tags($page));
        $this->assertStringContainsString('Changing the namespace changes the URI of every class and property in this vocabulary. Nothing is deleted.', $text);
        $this->assertStringContainsString('Current namespace URI http://example.org/review#', $text);
        // The field starts with the vocabulary's own namespace URI, to edit.
        $this->assertStringContainsString('name="vocabulary-info[o:namespace_uri]"', $page);
        $this->assertStringContainsString('value="http://example.org/review#"', $page);
        $this->assertStringNotContainsString('name="vocabulary-info[o:label]"', $page);
        $this->assertStringContainsString(sprintf('<a class="button" href="/admin/vocabulary/%s">Cancel</a>', $this->vocabularyId), $page);
    }

    public function testChangeNamespace()
    {
        $status = $this->changeNamespace(' http://example.org/moved# ');
        $this->assertSame(302, $status);
        $this->assertRedirectTo(sprintf('/admin/vocabulary/%s', $this->vocabularyId));
        $this->assertSame(['Namespace successfully changed'], $this->messages(Messenger::SUCCESS));
        $vocabulary = $this->vocabulary();
        $this->assertSame('http://example.org/moved#', $vocabulary->namespaceUri());
        $this->assertSame('Review', $vocabulary->label());
        $this->assertSame('<i>stored property label</i>', $this->propertyLabel('review:name'));
    }

    public function testChangeNamespaceRefusesItsOwn()
    {
        $status = $this->changeNamespace(self::NAMESPACE_URI);
        $this->assertSame(200, $status);
        $this->assertTemplateName('omeka/admin/vocabulary/change-namespace');
        $this->assertStringContainsString('The vocabulary already has this namespace URI.', $this->getResponse()->getContent());
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
        $page = html_entity_decode($this->reviewPage($this->startReview('moved-name.ttl', null, [
            'namespace_uri' => 'http://example.org/moved#',
        ])), ENT_QUOTES | ENT_HTML5);
        $text = preg_replace('/\s+/', ' ', strip_tags($page));
        $this->assertStringContainsString('Accepting makes the changes below, from the file. It removes nothing.', $text);
        // The namespace URI is one of the vocabulary's changed fields, with
        // what it does to the terms.
        $this->assertStringContainsString('Namespace URI http://example.org/review# http://example.org/moved#', $text);
        $this->assertStringContainsString('Changing the namespace changes the URI of every class and property in this vocabulary. Nothing is deleted.', $text);
        $this->assertStringNotContainsString('new addresses', $text);
        $this->assertStringContainsString('Changed property labels (1)', $page);
        // The file has no class, so Omeka's is listed, as kept.
        $this->assertStringContainsString('Classes only in Omeka (1)', $page);
        $this->assertStringContainsString('Accepting keeps them.', $page);
    }

    public function testReviewWithoutChangesOffersOnlyBack()
    {
        $page = html_entity_decode($this->reviewPage($this->startReview('unchanged.ttl')), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('This vocabulary already matches the file.', $page);
        $this->assertMatchesRegularExpression('#<button type="submit" class="button" formaction="[^"]*/cancel">Back</button>#', $page);
        $this->assertStringNotContainsString('Accept changes', $page);

        // With changes, Cancel and Accept.
        $page = html_entity_decode($this->reviewPage($this->startReview('changed-name.ttl')), ENT_QUOTES | ENT_HTML5);
        $this->assertMatchesRegularExpression('#formaction="[^"]*/cancel">Cancel</button>#', $page);
        $this->assertStringContainsString('Accept changes', $page);
    }

    public function testLabelPropertyChosenOnReview()
    {
        $token = $this->startReview('changed-name.ttl');
        $page = html_entity_decode($this->reviewPage($token), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('Changed property labels (1)', $page);
        // Every choice is a row, its description folded behind its label.
        $this->assertStringContainsString('<input type="text" name="label_property" id="review-label-property" value="">', $page);
        $this->assertStringContainsString('<a href="#" class="expand" aria-expanded="false" aria-controls="review-label-property-description" aria-label="Expand"></a>', $page);
        $this->assertStringNotContainsString('Label and comment properties', $page);

        // Labels read from a property the file doesn't have are local names,
        // which never take the place of Omeka's labels.
        $page = html_entity_decode($this->reviewPage($token, ['label_property' => 'skos:prefLabel']), ENT_QUOTES | ENT_HTML5);
        $this->assertStringNotContainsString('Changed property labels', $page);
        // Shown in its field, as the reading uses it, and carried to Accept.
        $this->assertStringContainsString('<input type="text" name="label_property" id="review-label-property" value="skos:prefLabel">', $page);
        $this->assertStringContainsString('<input type="hidden" name="label_property" value="skos:prefLabel">', $page);

        // Accepted, it's the reading stored.
        $this->postReview('accept', $token, self::ADMIN, null, ['label_property' => 'skos:prefLabel']);
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
    }

    public function testAnotherCommentPropertyChosenOnReview()
    {
        $token = $this->startReview('changed-name.ttl');
        // Chosen, it's read, and shown as chosen, with Accept carrying it.
        $page = html_entity_decode($this->reviewPage($token, ['comments' => 'other', 'comment_property' => 'skos:definition']), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('<option value="other" selected>Another property…</option>', $page);
        $this->assertStringContainsString('<input type="text" name="comment_property" id="review-comment-property" value="skos:definition">', $page);
        $this->assertStringContainsString('<input type="hidden" name="comment_property" value="skos:definition">', $page);
        // Entered but not chosen, it isn't.
        $page = html_entity_decode($this->reviewPage($token, ['comments' => 'definition', 'comment_property' => 'skos:definition']), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('<option value="definition" selected>', $page);
        $this->assertStringContainsString('<input type="text" name="comment_property" id="review-comment-property" value="">', $page);
    }

    public function testAnotherCommentPropertyLeftBlankKeepsTheReviewsReading()
    {
        $this->api()->update('vocabularies', $this->vocabularyId, [
            'o:import_options' => ['format' => 'turtle', 'comment_property' => RdfImporter::DEFINITION_COMMENT_PROPERTY],
        ], [], ['isPartial' => true]);
        $token = $this->startReview('changed-name.ttl');
        $page = html_entity_decode($this->reviewPage($token, ['comments' => 'other', 'comment_property' => ' ']), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('<option value="definition" selected>', $page);
        $this->assertStringContainsString('<input type="hidden" name="comment_property" value="">', $page);
        $this->assertStringContainsString('<input type="hidden" name="comments" value="definition">', $page);
    }

    public function testAcceptAppliesNamespaceAndTermsTogether()
    {
        // As a module starts it, knowing the file's namespace.
        $token = $this->startReview('moved-name.ttl', null, ['namespace_uri' => 'http://example.org/moved#']);

        $status = $this->postReview('accept', $token);
        $this->assertSame(302, $status);
        $this->assertRedirectTo(sprintf('/admin/vocabulary/%s', $this->vocabularyId));
        $this->assertSame(['Changes to the vocabulary successfully made'], $this->messages(Messenger::SUCCESS));
        $vocabulary = $this->vocabulary();
        $this->assertSame('http://example.org/moved#', $vocabulary->namespaceUri());
        $this->assertSame('Changed', $this->propertyLabel('review:name'));
        // How the file was read is stored.
        $this->assertSame('turtle', $this->vocabulary()->importOptions()['format']);
    }

    public function testCancelChangesNothing()
    {
        $token = $this->startReview('changed-name.ttl');
        $status = $this->postReview('cancel', $token);
        $this->assertSame(302, $status);
        $this->assertRedirectTo(sprintf('/admin/vocabulary/%s', $this->vocabularyId));
        $this->assertSame('Review', $this->vocabulary()->label());
        $this->assertSame('<i>stored property label</i>', $this->propertyLabel('review:name'));
        // The review has ended, and its page says so on the vocabulary's.
        $status = $this->requestAs(self::ADMIN, 'GET', sprintf('/admin/vocabulary/%s/review?review=%s', $this->vocabularyId, $token));
        $this->assertSame(302, $status);
        $this->assertRedirectTo(sprintf('/admin/vocabulary/%s', $this->vocabularyId));
        $this->assertSame(['This review has ended. Update the vocabulary\'s classes and properties to review its changes again.'], $this->messages(Messenger::ERROR));
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
            'vocabulary-file' => [
                'import_type' => 'url',
                'url' => 'http://example.org/vocabulary',
                'format' => $format,
            ],
        ] + $this->csrfField(VocabularyForm::class));
        if (302 === $status) {
            parse_str((string) parse_url($this->getResponse()->getHeaders()->get('Location')->getUri(), PHP_URL_QUERY), $query);
            $this->importFiles[] = $query['file'] ?? '';
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
        return $this->requestAs(self::ADMIN, 'POST', $url, [
            'vocabulary-info' => $info + ['o:comment' => ''],
            'vocabulary-advanced' => $advanced + [
                'lang' => '',
                'comments' => 'comment',
                'label_property' => '',
                'comment_property' => '',
            ],
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
        $this->assertStringContainsString('name="vocabulary-file[import_type]"', $page);
        $this->assertStringNotContainsString('name="vocabulary-info[o:label]"', $page);
        $this->assertStringNotContainsString('name="vocabulary-advanced[comments]"', $page);
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
        $this->assertStringContainsString('The fields below are filled in from the file. Check them, then import.', $page);
        // Its languages to choose from, English by default.
        $this->assertMatchesRegularExpression('#<option value="en" selected>[^<]*</option>#', $page);
        $this->assertMatchesRegularExpression('#<option value="fr">[^<]*</option>#', $page);
        $this->assertStringContainsString('<option value="definition">', $page);
        // Another property's field, after the choice, which shows it.
        $this->assertMatchesRegularExpression('#<select name="vocabulary-advanced\[comments\]" id="comments" data-other-property="\#comment_property">.*?<option value="other">Another property…</option>.*?name="vocabulary-advanced\[comment_property\]".*?name="vocabulary-advanced\[label_property\]"#s', $page);
        $this->assertStringNotContainsString('name="vocabulary-file[import_type]"', $page);
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

    public function testImportDetailsAreReadOnceAtTheStart()
    {
        $this->startImport('../../../Stdlib/_files/details-declared.ttl');
        $url = $this->importDetailsUrl();
        // The kept file, emptied once the first step has read it.
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        file_put_contents($this->getApplicationServiceLocator()->get('Omeka\VocabularyFileStore')->getPath($query['file']), '');
        $this->requestAs(self::ADMIN, 'GET', $url);
        $this->assertStringContainsString('value="Declared vocabulary"', html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5));
    }

    public function testImportDetailsOfEndedImportsAreForgottenWhenOneStarts()
    {
        $this->startImport('../../../Stdlib/_files/details-declared.ttl');
        parse_str((string) parse_url($this->importDetailsUrl(), PHP_URL_QUERY), $query);
        $ended = $query['file'] . ':' . $query['format'];
        $session = new \Laminas\Session\Container('OmekaVocabularyImport');
        $this->assertArrayHasKey($ended, $session->getArrayCopy());
        // Its file gone, as when it's cancelled and cleared out.
        $this->getApplicationServiceLocator()->get('Omeka\VocabularyFileStore')->remove($query['file']);
        $this->startImport('../../../Stdlib/_files/details-declared.ttl');
        $this->assertArrayNotHasKey($ended, (new \Laminas\Session\Container('OmekaVocabularyImport'))->getArrayCopy());
    }

    public function testImportFromTheFileLandsOnTheVocabularysPage()
    {
        $tempDir = $this->getApplicationServiceLocator()->get('Config')['temp_dir'];
        $files = glob($tempDir . '/' . VocabularyFileStore::FILE_PREFIX . '*');
        try {
            $this->assertSame(302, $this->startImport('../../../Stdlib/_files/definitions.ttl'));
            $status = $this->finishImport($this->importDetailsUrl(), [
                'o:label' => 'Definitions',
                // Trimmed, as the file is read with it.
                'o:namespace_uri' => ' http://example.org/defs# ',
                'o:prefix' => 'defs',
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
            // The kept file is gone.
            $this->assertSame($files, glob($tempDir . '/' . VocabularyFileStore::FILE_PREFIX . '*'));
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
        $files = glob($tempDir . '/' . VocabularyFileStore::FILE_PREFIX . '*');
        $this->assertSame(200, $this->startImport('../../../Stdlib/_files/empty.ttl'));
        $this->assertStringContainsString('No classes or properties found in this file.', $this->getResponse()->getContent());
        $this->assertSame($files, glob($tempDir . '/' . VocabularyFileStore::FILE_PREFIX . '*'));
    }

    public function testImportOfAVocabularyOmekaHasWarnsThenRefuses()
    {
        // Its terms are in the tests' vocabulary's namespace.
        $this->assertSame(302, $this->startImport('markup.ttl'));
        $url = $this->importDetailsUrl();
        // Said on the details, where a namespace found wrong can be corrected.
        $this->requestAs(self::ADMIN, 'GET', $url);
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('Omeka already has a vocabulary with the namespace URI this file appears to use, "http://example.org/review#": Review. If the file is that vocabulary, update it from its page instead. If not, enter the file\'s own namespace URI below.', strip_tags($page));
        $this->assertStringContainsString(sprintf('<a href="/admin/vocabulary/%s">Review</a>', $this->vocabularyId), $page);
        // Refused if it's kept.
        try {
            $this->assertSame(200, $this->finishImport($url, [
                'o:label' => 'Review again',
                'o:namespace_uri' => 'http://example.org/review#',
                'o:prefix' => 'review2',
            ]));
            $this->assertStringContainsString('Omeka already has this vocabulary, as "Review" with the namespace URI "http://example.org/review#". To update it, use Update classes and properties on its page.', strip_tags(html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5)));
        } finally {
            $this->deleteImported('review2');
        }
    }

    public function testImportDetailsWarnOfAVocabularyTheUserMayNotUpdate()
    {
        // A file whose terms are Dublin Core's, which no one may update.
        $this->assertSame(302, $this->startImport('dcterms-title.ttl'));
        $this->requestAs(self::ADMIN, 'GET', $this->importDetailsUrl());
        $text = strip_tags(html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5));
        $this->assertStringContainsString('Omeka already has a vocabulary with the namespace URI this file appears to use, "http://purl.org/dc/terms/": Dublin Core. If the file is a different vocabulary, enter its own namespace URI below.', $text);
        $this->assertStringNotContainsString('update it from its page', $text);
    }

    public function testImportRefusesAnotherFormOfANamespaceOmekaHas()
    {
        try {
            $this->startImport('../../../Stdlib/_files/definitions.ttl');
            // Edited to another form of the tests' vocabulary's.
            $this->assertSame(200, $this->finishImport($this->importDetailsUrl(), [
                'o:label' => 'Review again',
                'o:namespace_uri' => 'https://example.org/review',
                'o:prefix' => 'review2',
            ]));
            $this->assertStringContainsString('Omeka already has this vocabulary, as "Review"', strip_tags(html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5)));
            $this->reset(true);
            $this->signIn();
            $this->assertSame(0, $this->api()->search('vocabularies', ['prefix' => 'review2'])->getTotalResults());
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
            $this->assertStringContainsString("The file doesn't say which namespace URI its classes and properties are in. Enter it below.", $page);
            $this->assertStringContainsString('name="vocabulary-info[o:namespace_uri]" required id="o:namespace_uri" data-confirm-ends-with="', $page);
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
        $status = $this->requestAs(self::ADMIN, 'GET', '/admin/vocabulary/import-details?file=unknown&format=turtle');
        $this->assertSame(302, $status);
        $this->assertRedirectTo('/admin/vocabulary/import');
        $this->assertSame(['This import has ended. Choose the file again.'], $this->messages(Messenger::ERROR));
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
        $this->assertTemplateName('omeka/admin/vocabulary/change-namespace');
        $page = html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('The namespace URI "http://purl.org/dc/terms/" is already taken.', $page);
        $this->assertStringNotContainsString('Namespace successfully changed', $page);
        $this->assertSame(self::NAMESPACE_URI, $this->vocabulary()->namespaceUri());
    }

    public function testChangeNamespaceRefusesAnotherFormOfAnotherVocabularys()
    {
        $this->reset(true);
        $this->signIn();
        $other = $this->api()->create('vocabularies', [
            'o:namespace_uri' => 'http://example.org/other#',
            'o:prefix' => 'other',
            'o:label' => 'Other',
        ])->getContent();
        try {
            // As an import of it is refused: its terms would be twice.
            $status = $this->changeNamespace('https://example.org/other#');
            $this->assertSame(200, $status);
            $this->assertStringContainsString('Another vocabulary, "Other", has this namespace URI in another form: "http://example.org/other#".', html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5));
            $this->assertSame(self::NAMESPACE_URI, $this->vocabulary()->namespaceUri());
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
        // Both namespaces, and how to move the vocabulary.
        $this->assertStringContainsString('None of this file\'s classes or properties are in this vocabulary\'s namespace, "http://example.org/review#". They use "http://example.org/moved#". If the vocabulary has moved, change its namespace, then update it again.', $page);
        $this->assertStringNotContainsString('Are you sure you used the correct namespace URI?', $page);
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
        $this->assertStringNotContainsString('Values deleted', $page);
    }

    public function testDeleteConfirmationSaysWhatDeletingTakes()
    {
        $status = $this->requestAs(self::ADMIN, 'GET', sprintf('/admin/vocabulary/%s/delete-confirm', $this->vocabularyId));
        $this->assertSame(200, $status);
        $page = preg_replace('/\s+/', ' ', $this->getResponse()->getContent());
        foreach (['Values deleted', 'Resource template properties deleted', 'Resources left without a class'] as $count) {
            $this->assertStringContainsString(sprintf('<h4>%s</h4> <div class="value">0</div>', $count), $page);
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
