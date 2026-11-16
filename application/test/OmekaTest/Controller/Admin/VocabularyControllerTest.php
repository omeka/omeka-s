<?php
namespace OmekaTest\Controller\Admin;

use Omeka\Entity\User;
use Omeka\Stdlib\RdfImporter;
use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * Requests to the vocabulary pages, signed in as the test database's admin.
 *
 * What a request writes is committed, so each test deletes the vocabulary it
 * makes.
 */
class VocabularyControllerTest extends AbstractHttpControllerTestCase
{
    const NAMESPACE_URI = 'http://example.org/review#';

    /**
     * @var int
     */
    protected $vocabularyId;

    public function setUp(): void
    {
        parent::setUp();
        $this->signIn();
        $vocabulary = $this->getApplicationServiceLocator()->get('Omeka\ApiManager')->create('vocabularies', [
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
        if ($this->vocabularyId) {
            $this->reset(true);
            $this->signIn();
            $this->getApplicationServiceLocator()->get('Omeka\ApiManager')->delete('vocabularies', $this->vocabularyId);
        }
        parent::tearDown();
    }

    /**
     * Sign in as the test database's admin.
     */
    protected function signIn()
    {
        $services = $this->getApplicationServiceLocator();
        $admin = $services->get('Omeka\EntityManager')->getRepository(User::class)
            ->findOneBy(['email' => 'admin@example.com']);
        $services->get('Omeka\AuthenticationService')->getStorage()->write($admin);
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
    }
}
