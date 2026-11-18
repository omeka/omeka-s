<?php
namespace Omeka\Stdlib;

use EasyRdf\Http;
use EasyRdf\Http\Client as HttpClient;
use EasyRdf\Http\Response;
use Omeka\Api\Exception\ValidationException;
use Omeka\Test\DbTestCase;

class RdfImporterTest extends DbTestCase
{
    /**
     * @var RdfImporter
     */
    protected $rdfImporter;

    public function setUp(): void
    {
        parent::setUp();

        $services = self::getApplication()->getServiceManager();
        $apiManager = $services->get('Omeka\ApiManager');
        $entityManager = $services->get('Omeka\EntityManager');
        $this->rdfImporter = new RdfImporter($apiManager, $entityManager);
    }

    public function tearDown(): void
    {
        if ($this->httpClient) {
            Http::setDefaultHttpClient($this->httpClient);
        }
        parent::tearDown();
    }

    /**
     * EasyRdf's HTTP client, while a test answers its requests.
     *
     * @var HttpClient|null
     */
    protected $httpClient;

    /**
     * Answer EasyRdf's requests with a response, in place of a server.
     *
     * @param Response $response
     * @return HttpClient The client answering, which counts the requests in
     *   $requests
     */
    protected function answerRequests(Response $response)
    {
        $client = new class extends HttpClient {
            public $response;
            public $requests = 0;

            public function request($method = null)
            {
                $this->requests++;
                return $this->response;
            }
        };
        $client->response = $response;
        $this->httpClient = Http::getDefaultHttpClient();
        Http::setDefaultHttpClient($client);
        return $client;
    }

    public function testGetMembers()
    {
        $strategy = 'file';
        $namespaceUri = 'http://localhost.test';
        $options = [
            'format' => 'jsonld',
            'file' => __DIR__ . '/_files/test.json',
        ];
        // Issue in library ml/jsonld for php 7.2
        // @see https://github.com/lanthaler/JsonLD/pull/92
        $members = $this->rdfImporter->getMembers($strategy, $namespaceUri, $options);
        $this->assertNotNull($members);
    }

    public function testGetMembersReadsTypedLiteralsAsText()
    {
        $members = $this->rdfImporter->getMembers('file', 'http://example.org/typed#', [
            'format' => 'turtle',
            'file' => __DIR__ . '/_files/typed-literals.ttl',
        ]);
        $this->assertSame(['Number' => ['label' => '1', 'comment' => '2020-01-01']], $members['classes']);
        $this->assertSame(['flag' => ['label' => 'false', 'comment' => 'true']], $members['properties']);
    }

    /**
     * The terms of one of the tests' files.
     *
     * @param string $file A file in _files
     * @param string $namespaceUri
     * @param array $options The import options
     * @return array
     */
    protected function members($file, $namespaceUri, array $options = [])
    {
        return $this->rdfImporter->getMembers('file', $namespaceUri, ['file' => __DIR__ . '/_files/' . $file] + $options);
    }

    public function testReadingInALanguageWithEmptyValues()
    {
        $members = $this->members('multilingual.ttl', 'http://example.org/multi#', ['format' => 'turtle', 'lang' => 'fr']);
        // Empty French values are placeholders, so the English ones are read.
        $this->assertSame(['label' => 'Work', 'comment' => 'A creative work.'], $members['classes']['CreativeWork']);
        $this->assertSame(['label' => 'Titre', 'comment' => "Le titre de l'oeuvre."], $members['properties']['title']);
        // With only placeholders, blank or empty, a term has its local name
        // for a label and no comment.
        $this->assertSame(['label' => 'subtitle', 'comment' => null], $members['properties']['subtitle']);
    }

    public function testReadingAFileThatMentionsRdfa()
    {
        // Guessing its format, EasyRdf takes the Turtle file for RDFa and
        // reads nothing from it, so the formats are tried in turn.
        $members = $this->members('rdfa-mention.ttl', 'http://example.org/mention#', ['format' => 'guess']);
        $this->assertSame(['Thing'], array_keys($members['classes']));
        // Read as Turtle, it has its terms.
        $members = $this->members('rdfa-mention.ttl', 'http://example.org/mention#', ['format' => 'turtle']);
        $this->assertSame(['Thing'], array_keys($members['classes']));
    }

    public function testReadingAUrlWhoseFormatIsMisread()
    {
        // A server says the Turtle file is HTML, so EasyRdf reads it as RDFa,
        // and reads nothing. Asked again for RDF/XML and then Turtle, the
        // server sends the same file, which reads as Turtle.
        $client = $this->answerRequests(new Response(200, ['Content-Type' => 'text/html'], file_get_contents(__DIR__ . '/_files/rdfa-mention.ttl')));
        $members = $this->rdfImporter->getMembers('url', 'http://example.org/mention#', ['url' => 'http://example.org/mention.ttl']);
        $this->assertSame(['Thing'], array_keys($members['classes']));
        $this->assertSame(3, $client->requests);
    }

    public function testReadingAUrlTheServerRefuses()
    {
        // Another format wouldn't change the server's answer, so it's asked
        // once.
        $client = $this->answerRequests(new Response(404, [], 'Not found'));
        try {
            $this->rdfImporter->getMembers('url', 'http://example.org/mention#', ['url' => 'http://example.org/missing.ttl']);
            $this->fail('The URL was read');
        } catch (ValidationException $e) {
            $this->assertSame('Could not load vocabulary from URL.', $e->getMessage());
        }
        $this->assertSame(1, $client->requests);
    }

    public function testReadingInAFormatTheFileIsNotIn()
    {
        // A format the user chose isn't second-guessed.
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Could not parse vocabulary file.');
        $this->members('rdfa-mention.ttl', 'http://example.org/mention#', ['format' => 'rdfxml']);
    }

    public function testReadingAnEmptyFile()
    {
        // Some formats read an empty file as empty RDF, but none reads
        // anything from it, so it's an error, as when guessing alone.
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Could not parse vocabulary file.');
        $this->members('empty.ttl', 'http://example.org/empty#', ['format' => 'guess']);
    }

    public function testImportingWithAnotherFormOfTheNamespace()
    {
        try {
            $this->rdfImporter->import('file', [
                'o:namespace_uri' => 'http://example.org/multi',
                'o:prefix' => 'multi',
                'o:label' => 'Multi',
            ], [
                'file' => __DIR__ . '/_files/multilingual.ttl',
                'format' => 'turtle',
            ]);
            $this->fail('The vocabulary was imported');
        } catch (ValidationException $e) {
            // As the API refuses: no message of its own, only its reason,
            // naming the namespace URI given and the one the file uses.
            $this->assertSame('', $e->getMessage());
            $errors = $e->getErrorStore()->getErrors();
            $this->assertSame(['o:namespace_uri'], array_keys($errors));
            $this->assertSame(
                ['None of the file\'s classes or properties are in the namespace URI "http://example.org/multi". The file uses "http://example.org/multi#".'],
                array_map('strval', $errors['o:namespace_uri'])
            );
        }
    }

    /**
     * Check that a request is refused for the terms of long-terms.ttl that
     * are too long to store, naming those and not the ones at the limits.
     *
     * @param callable $request
     */
    protected function assertRefusesLongTerms(callable $request)
    {
        try {
            $request();
            $this->fail('Terms too long to store were accepted');
        } catch (ValidationException $e) {
            $this->assertSame('Some classes or properties have local names or labels longer than Omeka can store.', $e->getMessage());
            $errors = [];
            foreach ($e->getErrorStore()->getErrors() as $field => $messages) {
                $errors[$field] = array_map('strval', $messages);
            }
            $this->assertSame([
                'o:local_name' => ['These classes or properties have local names longer than 190 characters: ' . str_repeat('b', 40) . '…'],
                'o:label' => ['These classes or properties have labels longer than 255 characters: overLimit'],
            ], $errors);
        }
    }

    public function testImportRefusesTermsTooLongToStore()
    {
        $this->assertRefusesLongTerms(function () {
            $this->rdfImporter->import('file', [
                'o:namespace_uri' => 'http://example.org/long#',
                'o:prefix' => 'long',
                'o:label' => 'Long',
            ], [
                'file' => __DIR__ . '/_files/long-terms.ttl',
                'format' => 'turtle',
            ]);
        });
    }

    public function testGetDiffRefusesTermsTooLongToStore()
    {
        $this->assertRefusesLongTerms(function () {
            $this->rdfImporter->getDiff('file', 'http://example.org/long#', [
                'file' => __DIR__ . '/_files/long-terms.ttl',
                'format' => 'turtle',
            ]);
        });
    }

    public function testUpdateRefusesTermsTooLongToStore()
    {
        $vocabularyId = $this->rdfImporter->import('file', [
            'o:namespace_uri' => 'http://example.org/multi#',
            'o:prefix' => 'multi',
            'o:label' => 'Multi',
        ], [
            'file' => __DIR__ . '/_files/multilingual.ttl',
            'format' => 'turtle',
        ])->getContent()->id();
        // The same terms as long-terms.ttl, as new terms and a new label.
        $this->assertRefusesLongTerms(function () use ($vocabularyId) {
            $this->rdfImporter->update($vocabularyId, [
                'classes' => [
                    'new' => [
                        str_repeat('a', 190) => ['Local name at the limit', null],
                        str_repeat('b', 191) => ['Local name over the limit', null],
                    ],
                    'label' => [],
                    'comment' => [],
                ],
                'properties' => [
                    'new' => ['atLimit' => [str_repeat('x', 255), null]],
                    'label' => ['overLimit' => ['Over the limit', str_repeat('y', 256)]],
                    'comment' => [],
                ],
            ]);
        });
        // Nothing was written, not even the terms at the limits.
        $api = self::getApplication()->getServiceManager()->get('Omeka\ApiManager');
        $this->assertSame(1, $api->search('resource_classes', ['vocabulary_id' => $vocabularyId])->getTotalResults());
        $this->assertSame(2, $api->search('properties', ['vocabulary_id' => $vocabularyId])->getTotalResults());
    }

    public function testLabelsAreTrimmedAsTheApiTrimsThem()
    {
        $members = $this->members('padded-labels.ttl', 'http://example.org/padded#', ['format' => 'turtle']);
        $this->assertSame('Padded', $members['classes']['Thing']['label']);
        $this->assertSame(str_repeat('x', 255), $members['properties']['full']['label']);
        // At the limit once trimmed, so imported rather than refused.
        $vocabulary = $this->rdfImporter->import('file', [
            'o:namespace_uri' => 'http://example.org/padded#',
            'o:prefix' => 'padded',
            'o:label' => 'Padded',
        ], [
            'file' => __DIR__ . '/_files/padded-labels.ttl',
            'format' => 'turtle',
        ])->getContent();
        $this->assertSame(1, $vocabulary->propertyCount());
    }

    public function testAFileIsParsedOnceUntilItChanges()
    {
        $services = self::getApplication()->getServiceManager();
        $importer = new class($services->get('Omeka\ApiManager'), $services->get('Omeka\EntityManager')) extends RdfImporter {
            public $reads = 0;

            protected function readGraph(callable $read, $format, $error)
            {
                $this->reads++;
                return parent::readGraph($read, $format, $error);
            }
        };
        $file = tempnam(sys_get_temp_dir(), 'omeka-test-');
        copy(__DIR__ . '/_files/multilingual.ttl', $file);
        try {
            $options = ['file' => $file, 'format' => 'turtle'];
            // As a review reads its file for its changes, its terms only in
            // Omeka, and its languages, with any import options.
            $importer->getMembers('file', 'http://example.org/multi#', $options + ['lang' => 'fr']);
            $importer->getLanguages('file', 'http://example.org/multi#', $options);
            $importer->checkHasMembers('file', 'http://example.org/multi#', $options);
            $this->assertSame(1, $importer->reads);
            // Read another way, or changed, it's read again.
            $importer->getMembers('file', 'http://example.org/multi#', ['format' => 'guess'] + $options);
            $this->assertSame(2, $importer->reads);
            touch($file, time() + 10);
            $importer->getMembers('file', 'http://example.org/multi#', ['format' => 'guess'] + $options);
            $this->assertSame(3, $importer->reads);
        } finally {
            unlink($file);
        }
    }

    /**
     * What a file of the tests' says of its vocabulary.
     *
     * @param string $file A file in _files
     * @param array $options
     * @return array
     */
    protected function details($file, array $options = [])
    {
        return $this->rdfImporter->getVocabularyDetails('file', ['file' => __DIR__ . '/_files/' . $file, 'format' => 'turtle'] + $options);
    }

    public function testReadingAnotherGraphFreesTheLastOne()
    {
        // As an import's details and then the import itself read one file,
        // with different bases, so the second read can't reuse the first.
        $options = ['file' => __DIR__ . '/_files/details-declared.ttl', 'format' => 'turtle'];
        $details = $this->rdfImporter->getVocabularyDetails('file', $options);
        $lastFileGraph = new \ReflectionProperty($this->rdfImporter, 'lastFileGraph');
        $lastFileGraph->setAccessible(true);
        $first = \WeakReference::create($lastFileGraph->getValue($this->rdfImporter)[1]);
        $this->rdfImporter->getMembers('file', $details['namespace_uri'], $options);
        $this->assertNull($first->get());
    }

    public function testVocabularyDetailsAsDeclared()
    {
        $details = $this->details('details-declared.ttl');
        $this->assertSame('http://example.org/declared#', $details['namespace_uri']);
        $this->assertSame('declared', $details['namespace_source']);
        // Its own prefix, not the one of a vocabulary it reuses.
        $this->assertSame('decl', $details['prefix']);
        // In English by default, its description's values and paragraphs
        // each a paragraph. Its line breaks are kept, without the
        // indentation all of a value's later lines share, but with any
        // indentation of the text's own.
        $this->assertSame('Declared vocabulary', $details['label']);
        $this->assertSame("First paragraph,\nwrapped and indented.\n\nSecond paragraph.\n\nA changelog.\n\nRules:\n1. A rule,\n   with a hanging indent.\n2. Another.", $details['comment']);
        // Its definitions' languages too.
        $this->assertSame(['de', 'en', 'es', 'fr'], $details['languages']);

        // In another language asked for.
        $details = $this->details('details-declared.ttl', ['lang' => 'fr']);
        $this->assertSame('Vocabulaire déclaré', $details['label']);
        $this->assertSame('Une description.', $details['comment']);
    }

    public function testVocabularyDetailsInTheFilesLanguageNearestThePreferredOne()
    {
        // The language alone finds it in a region, for the title as for the
        // classes and properties.
        $details = $this->details('details-regional.ttl', ['lang' => 'fr']);
        $this->assertSame('fr-CA', $details['lang']);
        $this->assertSame('Régional', $details['label']);
        // English, where the file doesn't have the language.
        $details = $this->details('details-regional.ttl', ['lang' => 'de-at']);
        $this->assertSame('en', $details['lang']);
        $this->assertSame('Regional', $details['label']);
    }

    public function testVocabularyDetailsSkipAPrefixOmekaCannotUse()
    {
        // The ontology's prefix has a dot, so the namespace's is taken.
        $this->assertSame('prefixes', $this->details('details-prefixes.ttl')['prefix']);
    }

    public function testVocabularyDetailsOfAFileThatAlsoDeclaresAReusedVocabulary()
    {
        // Its own namespace, which has the most terms, though the other's
        // is declared first.
        $details = $this->details('details-reused.ttl');
        $this->assertSame('http://example.org/own#', $details['namespace_uri']);
        $this->assertSame('declared', $details['namespace_source']);
        // The languages of every class and property, as the namespace URI
        // found may be corrected.
        $this->assertSame(['it'], $details['languages']);
    }

    public function testVocabularyDetailsInAnotherFormOfTheDeclaredNamespace()
    {
        $details = $this->details('details-variant.ttl');
        $this->assertSame('http://example.org/variant#', $details['namespace_uri']);
        $this->assertSame('other_form', $details['namespace_source']);
        // A prefix Omeka can't take is left for the admin to give.
        $this->assertNull($details['prefix']);
        $this->assertNull($details['label']);
        // A note, where there's no description.
        $this->assertSame('Described in a note.', $details['comment']);
    }

    public function testVocabularyDetailsFromItsTermsAlone()
    {
        $details = $this->details('details-terms.ttl');
        $this->assertSame('http://example.org/terms#', $details['namespace_uri']);
        $this->assertSame('terms', $details['namespace_source']);
        $this->assertNull($details['prefix']);
        // From the record its terms name.
        $this->assertSame('Terms by their record', $details['label']);
    }

    public function testVocabularyDetailsOfAFileNamingItsTermsRelativeToItself()
    {
        $details = $this->rdfImporter->getVocabularyDetails('file', ['file' => __DIR__ . '/_files/details-relative.rdf', 'format' => 'rdfxml']);
        // Its namespace URI is to be given.
        $this->assertNull($details['namespace_uri']);
        $this->assertSame('relative', $details['namespace_source']);
        $this->assertSame('Relative vocabulary', $details['label']);
        // Read with one, its terms are in it.
        $members = $this->rdfImporter->getMembers('file', 'http://example.org/relative#', ['file' => __DIR__ . '/_files/details-relative.rdf', 'format' => 'rdfxml']);
        $this->assertSame(['Thing'], array_keys($members['classes']));
        $this->assertSame(['name'], array_keys($members['properties']));
    }

    public function testVocabularyDetailsOfAFileWithoutTerms()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No classes or properties found in this file.');
        $this->details('empty.ttl');
    }

    public function testCommentsAreReadFromCommentsElseDefinitionsByDefault()
    {
        $comments = function (array $options) {
            $members = $this->members('descriptions.ttl', 'http://example.org/descriptions#', ['format' => 'turtle'] + $options);
            return array_map(function ($term) {
                return $term['comment'];
            }, $members['properties']);
        };
        // A term with only a definition or description isn't left without a
        // comment; one with both has its comment.
        $this->assertSame([
            'defined' => 'Its definition.',
            'described' => 'Its description.',
            'describedInDc11' => 'Its older description.',
            'both' => 'Its comment.',
            'bare' => null,
        ], $comments([]));
        // Chosen, its definition comes first.
        $this->assertSame([
            'defined' => 'Its definition.',
            'described' => 'Its description.',
            'describedInDc11' => 'Its older description.',
            'both' => 'Its definition.',
            'bare' => null,
        ], $comments(['comment_property' => RdfImporter::DEFINITION_COMMENT_PROPERTY]));
    }

    public function memoryLimits()
    {
        return [
            'lower' => ['128M', '256M'],
            'lower, in kilobytes' => ['131072K', '256M'],
            'higher' => ['512M', '512M'],
            'higher, in gigabytes' => ['1G', '1G'],
            'none' => ['-1', '-1'],
        ];
    }

    /**
     * @dataProvider memoryLimits
     */
    public function testReadingRaisesALowerMemoryLimit($limit, $expected)
    {
        $original = ini_get('memory_limit');
        ini_set('memory_limit', $limit);
        try {
            $this->members('multilingual.ttl', 'http://example.org/multi#', ['format' => 'turtle']);
            $this->assertSame($expected, ini_get('memory_limit'));
        } finally {
            ini_set('memory_limit', $original);
        }
    }

    /**
     * Store the vocabulary the comparison tests compare files with.
     *
     * @return int Its id
     */
    protected function storeComparedVocabulary()
    {
        $api = self::getApplication()->getServiceManager()->get('Omeka\ApiManager');
        return $api->create('vocabularies', [
            'o:namespace_uri' => 'http://example.org/compare#',
            'o:prefix' => 'compare',
            'o:label' => 'Compare',
            'o:class' => [
                ['o:local_name' => 'Thing', 'o:label' => 'Thing', 'o:comment' => 'A thing.'],
            ],
            'o:property' => [
                ['o:local_name' => 'name', 'o:label' => 'Name', 'o:comment' => 'A name.'],
                ['o:local_name' => 'note', 'o:label' => 'Note', 'o:comment' => ''],
                ['o:local_name' => 'unlabeled', 'o:label' => 'Unlabeled', 'o:comment' => 'Has a label in Omeka.'],
                ['o:local_name' => 'title', 'o:label' => 'Title', 'o:comment' => 'The title.'],
                ['o:local_name' => 'onlyInOmeka', 'o:label' => 'Only in Omeka', 'o:comment' => null],
            ],
        ])->getContent()->id();
    }

    public function testComparingAFile()
    {
        $this->storeComparedVocabulary();
        $diff = $this->rdfImporter->getDiff('file', 'http://example.org/compare#', [
            'file' => __DIR__ . '/_files/compare.ttl',
            'format' => 'turtle',
        ]);
        // Differences only in spacing, and an empty comment against none,
        // aren't changes. Nor is a value the file doesn't have, which would
        // empty a comment or reduce a label to the local name.
        $this->assertSame([
            'classes' => [
                'new' => [],
                'label' => [],
                'comment' => [],
            ],
            'properties' => [
                'new' => ['added' => ['Added', 'A new term.']],
                'label' => ['title' => ['Title', 'Heading']],
                'comment' => ['title' => ['The title.', "The work's heading."]],
            ],
        ], $diff);
    }

    public function testUpdateSkipsChangesItCannotMake()
    {
        $vocabularyId = $this->storeComparedVocabulary();
        // Accepted from a review read before some terms were deleted and
        // others added, beside changes it can make.
        $this->rdfImporter->update($vocabularyId, [
            'classes' => [
                'new' => ['Thing' => ['Thing again', null]],
                'label' => ['Gone' => ['Gone', 'Changed']],
                'comment' => ['Gone' => [null, 'Changed.']],
            ],
            'properties' => [
                'new' => ['name' => ['Name again', null], 'added' => ['Added', null]],
                'label' => ['gone' => ['Gone', 'Changed'], 'title' => ['Title', 'Heading']],
                'comment' => ['gone' => [null, 'Changed.']],
            ],
        ]);
        $api = self::getApplication()->getServiceManager()->get('Omeka\ApiManager');
        $labels = [];
        foreach (['resource_classes', 'properties'] as $resource) {
            foreach ($api->search($resource, ['vocabulary_id' => $vocabularyId])->getContent() as $term) {
                $labels[$term->localName()] = $term->label();
            }
        }
        ksort($labels);
        // Existing terms keep their labels where a new term of their name
        // was skipped; the change and the new term that could be made were.
        $this->assertSame([
            'Thing' => 'Thing',
            'added' => 'Added',
            'name' => 'Name',
            'note' => 'Note',
            'onlyInOmeka' => 'Only in Omeka',
            'title' => 'Heading',
            'unlabeled' => 'Unlabeled',
        ], $labels);
    }

    public function testComparingALabelThatIsItsLocalName()
    {
        // Imported in German, compared in English.
        self::getApplication()->getServiceManager()->get('Omeka\ApiManager')->create('vocabularies', [
            'o:namespace_uri' => 'http://example.org/local#',
            'o:prefix' => 'local',
            'o:label' => 'Local names',
            'o:class' => [['o:local_name' => 'Part', 'o:label' => 'Teil']],
            'o:property' => [['o:local_name' => 'unlabeled', 'o:label' => 'Unlabeled']],
        ]);
        $diff = $this->rdfImporter->getDiff('file', 'http://example.org/local#', [
            'file' => __DIR__ . '/_files/local-names.ttl',
            'format' => 'turtle',
            'lang' => 'en',
        ]);
        // The English label is the local name, and a change like any other.
        $this->assertSame(['Part' => ['Teil', 'Part']], $diff['classes']['label']);
        // A term with no label keeps Omeka's.
        $this->assertSame([], $diff['properties']['label']);
    }

    public function testGetMembersNotInFile()
    {
        $vocabularyId = $this->storeComparedVocabulary();
        $notInFile = $this->rdfImporter->getMembersNotInFile('file', 'http://example.org/compare#', [
            'file' => __DIR__ . '/_files/compare.ttl',
            'format' => 'turtle',
            'vocabulary_id' => $vocabularyId,
        ]);
        $this->assertSame([
            'classes' => [],
            'properties' => ['onlyInOmeka' => 'Only in Omeka'],
        ], $notInFile);
    }

    public function testGetMembersNotInFileReadingInAnotherNamespace()
    {
        $vocabularyId = $this->storeComparedVocabulary();
        $notInFile = $this->rdfImporter->getMembersNotInFile('file', 'http://example.org/moved#', [
            'file' => __DIR__ . '/_files/compare-moved.ttl',
            'format' => 'turtle',
            'vocabulary_id' => $vocabularyId,
        ]);
        // The file has only name and title, in its own namespace.
        $this->assertSame([
            'classes' => ['Thing' => 'Thing'],
            'properties' => [
                'note' => 'Note',
                'unlabeled' => 'Unlabeled',
                'onlyInOmeka' => 'Only in Omeka',
            ],
        ], $notInFile);
    }

    /**
     * Import multilingual.ttl in French.
     *
     * @return \Omeka\Api\Representation\VocabularyRepresentation
     */
    protected function importInFrench()
    {
        return $this->rdfImporter->import('file', [
            'o:namespace_uri' => 'http://example.org/multi#',
            'o:prefix' => 'multi',
            'o:label' => 'Multi',
        ], [
            'file' => __DIR__ . '/_files/multilingual.ttl',
            'format' => 'turtle',
            'lang' => 'fr',
        ])->getContent();
    }

    public function testReadingAnInvalidTypedLiteralNamesTheTerm()
    {
        try {
            $this->members('invalid-literal.ttl', 'http://example.org/invalid#', ['format' => 'turtle']);
            $this->fail('The file was read');
        } catch (ValidationException $e) {
            $this->assertSame('A class or property has a label or comment that isn\'t valid for its datatype.', $e->getMessage());
            $detail = (string) $e->getErrorStore()->getErrors()['term'][0];
            $this->assertStringStartsWith('http://example.org/invalid#Thing: ', $detail);
        }
    }

    public function testReadingAnEmptyUrl()
    {
        // As a URL that can't be loaded, not EasyRdf's own message.
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Could not load vocabulary from URL.');
        $this->rdfImporter->getMembers('url', 'http://example.org/multi#', ['url' => '']);
    }

    public function testImportTrimsTheNamespace()
    {
        $vocabulary = $this->rdfImporter->import('file', [
            'o:namespace_uri' => " http://example.org/multi#\n",
            'o:prefix' => 'multi',
            'o:label' => 'Multi',
        ], [
            'file' => __DIR__ . '/_files/multilingual.ttl',
            'format' => 'turtle',
        ])->getContent();
        $this->assertSame('http://example.org/multi#', $vocabulary->namespaceUri());
        $this->assertSame(3, $vocabulary->propertyCount() + $vocabulary->resourceClassCount());
    }

    public function testImportAcceptsAnotherFormOfANamespaceOmekaHas()
    {
        // Only the import form refuses it; scripts and modules may import it.
        self::getApplication()->getServiceManager()->get('Omeka\ApiManager')->create('vocabularies', [
            'o:namespace_uri' => 'https://example.org/multi#',
            'o:prefix' => 'multihttps',
            'o:label' => 'Multi over https',
        ]);
        $this->assertSame('http://example.org/multi#', $this->importInFrench()->namespaceUri());
    }

    public function testImportStoresHowTheFileWasRead()
    {
        // The import options, not the file's path.
        $this->assertSame(['format' => 'turtle', 'lang' => 'fr'], $this->importInFrench()->importOptions());
    }

    public function testComparingReadsTheFileAsTheVocabularysWas()
    {
        $this->importInFrench();
        $options = ['file' => __DIR__ . '/_files/multilingual.ttl'];
        // Read in French, as it was imported, the file has no changes.
        $diff = $this->rdfImporter->getDiff('file', 'http://example.org/multi#', $options);
        $this->assertSame([], $diff['properties']['label']);
        // Told to read it in English, it has.
        $diff = $this->rdfImporter->getDiff('file', 'http://example.org/multi#', $options + ['lang' => 'en']);
        $this->assertSame(['title' => ['Titre', 'Title']], $diff['properties']['label']);
    }

    public function testGetLanguages()
    {
        $languages = $this->rdfImporter->getLanguages('file', 'http://example.org/multi#', [
            'file' => __DIR__ . '/_files/multilingual.ttl',
            'format' => 'turtle',
        ]);
        $this->assertSame(['en', 'fr'], $languages);
    }

    /**
     * Import a file as a vocabulary, read as asked, and guess how it was
     * read.
     *
     * @param string $file A file in _files
     * @param string $namespaceUri
     * @param array $importOptions The import options to import it with
     * @return array The guess
     */
    protected function guessImportOptions($file, $namespaceUri, array $importOptions)
    {
        $options = ['file' => __DIR__ . '/_files/' . $file, 'format' => 'turtle'];
        $vocabularyId = $this->rdfImporter->import('file', [
            'o:namespace_uri' => $namespaceUri,
            'o:prefix' => 'guessed',
            'o:label' => 'Guessed',
        ], $options + $importOptions)->getContent()->id();
        return $this->rdfImporter->guessImportOptions('file', $namespaceUri, ['vocabulary_id' => $vocabularyId] + $options);
    }

    public function importOptionsGuesses()
    {
        $comment = RdfImporter::COMMENT_PROPERTY;
        $definition = RdfImporter::DEFINITION_COMMENT_PROPERTY;
        return [
            'in French' => ['multilingual.ttl', 'http://example.org/multi#', ['lang' => 'fr'], ['lang' => 'fr', 'comment_property' => $comment]],
            'in English' => ['multilingual.ttl', 'http://example.org/multi#', ['lang' => 'en'], ['lang' => 'en', 'comment_property' => $comment]],
            // The first available labels are English, which wins the tie.
            'in the first available' => ['multilingual.ttl', 'http://example.org/multi#', [], ['lang' => 'en', 'comment_property' => $comment]],
            'definitions' => ['definitions.ttl', 'http://example.org/defs#', ['comment_property' => $definition], ['lang' => null, 'comment_property' => $definition]],
            'comments' => ['definitions.ttl', 'http://example.org/defs#', ['comment_property' => $comment], ['lang' => null, 'comment_property' => $comment]],
        ];
    }

    /**
     * @dataProvider importOptionsGuesses
     */
    public function testGuessImportOptions($file, $namespaceUri, array $importOptions, array $guess)
    {
        $this->assertSame($guess, $this->guessImportOptions($file, $namespaceUri, $importOptions));
    }

    public function testComparingAFileInAnotherNamespace()
    {
        $vocabularyId = $this->storeComparedVocabulary();
        $diff = $this->rdfImporter->getDiff('file', 'http://example.org/moved#', [
            'file' => __DIR__ . '/_files/compare-moved.ttl',
            'format' => 'turtle',
            'vocabulary_id' => $vocabularyId,
        ]);
        // Omeka's terms are found by the vocabulary, so across the change of
        // namespace only the real change shows.
        $this->assertSame([
            'classes' => ['new' => [], 'label' => [], 'comment' => []],
            'properties' => [
                'new' => [],
                'label' => ['title' => ['Title', 'Heading']],
                'comment' => [],
            ],
        ], $diff);

        // An id that isn't one matches no vocabulary, not every one, so the
        // file's terms are new.
        $diff = $this->rdfImporter->getDiff('file', 'http://example.org/moved#', [
            'file' => __DIR__ . '/_files/compare-moved.ttl',
            'format' => 'turtle',
            'vocabulary_id' => 'not an id',
        ]);
        $this->assertSame(['name', 'title'], array_keys($diff['properties']['new']));
    }

    public function testCheckNamespaceUri()
    {
        $options = ['file' => __DIR__ . '/_files/compare-moved.ttl', 'format' => 'turtle'];
        // Its own namespace passes.
        $this->rdfImporter->checkHasMembers('file', 'http://example.org/moved#', $options);
        // Another is refused, with the one its terms use.
        try {
            $this->rdfImporter->checkHasMembers('file', 'http://example.org/compare#', $options);
            $this->fail('The file was taken');
        } catch (ValidationException $e) {
            $this->assertSame(
                'None of the file\'s classes or properties are in the namespace URI "http://example.org/compare#". The file uses "http://example.org/moved#".',
                (string) $e->getErrorStore()->getErrors()['o:namespace_uri'][0]
            );
        }
        // A file with no classes or properties anywhere names only the one
        // given.
        try {
            $this->rdfImporter->checkHasMembers('file', 'http://example.org/compare#', ['file' => __DIR__ . '/_files/empty.ttl', 'format' => 'turtle']);
            $this->fail('The file was taken');
        } catch (ValidationException $e) {
            $this->assertSame(
                'None of the file\'s classes or properties are in the namespace URI "http://example.org/compare#".',
                (string) $e->getErrorStore()->getErrors()['o:namespace_uri'][0]
            );
        }
    }

    public function namespaceSuggestions()
    {
        $prefixes = <<<'TTL'
            @prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
            @prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
            @prefix owl: <http://www.w3.org/2002/07/owl#> .
            @prefix vann: <http://purl.org/vocab/vann/> .

            TTL;
        // Two terms elsewhere, so the namespace most terms use isn't the one
        // expected, where a declared or ontology namespace is.
        $elsewhere = '<http://example.org/elsewhere#a> a rdf:Property . <http://example.org/elsewhere#b> a rdf:Property .';
        return [
            'another form of the given one' => [
                $prefixes . '<http://example.org/ns#Thing> a rdfs:Class .',
                'https://example.org/ns',
                'http://example.org/ns#',
            ],
            'the one the file declares' => [
                $prefixes . '<http://example.org/doc> vann:preferredNamespaceUri "http://example.org/declared/" .
                <http://example.org/declared/Thing> a rdfs:Class . ' . $elsewhere,
                'http://example.org/given#',
                'http://example.org/declared/',
            ],
            'a form of the ontology\'s URI' => [
                $prefixes . '<http://example.org/ontology> a owl:Ontology .
                <http://example.org/ontology#Thing> a owl:Class . ' . $elsewhere,
                'http://example.org/given#',
                'http://example.org/ontology#',
            ],
            'the one most terms use' => [
                $prefixes . '<http://example.org/few#a> a rdf:Property . ' . $elsewhere,
                'http://example.org/given#',
                'http://example.org/elsewhere#',
            ],
            'none, without terms' => [
                $prefixes . '<http://example.org/doc> rdfs:label "No terms" .',
                'http://example.org/given#',
                null,
            ],
        ];
    }

    /**
     * @dataProvider namespaceSuggestions
     */
    public function testRefusingAFileSuggestsTheNamespaceUriItsTermsUse($turtle, $namespaceUri, $suggestion)
    {
        $file = tempnam(sys_get_temp_dir(), 'omeka-test-');
        file_put_contents($file, $turtle);
        try {
            $this->rdfImporter->checkHasMembers('file', $namespaceUri, ['file' => $file, 'format' => 'turtle']);
            $this->fail('A file without terms in the namespace was taken');
        } catch (ValidationException $e) {
            // Named after the one given, where the file's is known.
            $expected = null === $suggestion
                ? sprintf('None of the file\'s classes or properties are in the namespace URI "%s".', $namespaceUri)
                : sprintf('None of the file\'s classes or properties are in the namespace URI "%s". The file uses "%s".', $namespaceUri, $suggestion);
            $this->assertSame([$expected], array_map('strval', $e->getErrorStore()->getErrors()['o:namespace_uri']));
        } finally {
            unlink($file);
        }
    }
}
