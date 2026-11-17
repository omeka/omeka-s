<?php
namespace Omeka\Stdlib;

use Doctrine\ORM\EntityManager;
use EasyRdf\Graph as RdfGraph;
use EasyRdf\Resource as RdfResource;
use Omeka\Api\Exception\ValidationException;
use Omeka\Api\Manager as ApiManager;
use Omeka\Entity\Property;
use Omeka\Entity\Vocabulary;
use Omeka\Entity\ResourceClass;

class RdfImporter
{
    /**
     * The longest local name the database can store, in characters.
     */
    const LOCAL_NAME_MAX_LENGTH = 190;

    /**
     * The longest label the database can store, in characters.
     */
    const LABEL_MAX_LENGTH = 255;

    /**
     * The default label property.
     */
    const LABEL_PROPERTY = 'skos:prefLabel|rdfs:label|foaf:name|rss:title|dc:title|dc11:title';

    /**
     * The default comment property: a term's comment, else its definition or
     * description.
     */
    const COMMENT_PROPERTY = 'rdfs:comment|skos:definition|dcterms:description|dc11:description';

    /**
     * A comment property that reads a term's definition or description
     * first.
     */
    const DEFINITION_COMMENT_PROPERTY = 'skos:definition|dcterms:description|dc11:description|rdfs:comment';

    /**
     * The comment readings forms offer, by name.
     */
    const COMMENT_READINGS = [
        'comment' => self::COMMENT_PROPERTY,
        'definition' => self::DEFINITION_COMMENT_PROPERTY,
    ];

    /**
     * The options that say how a file is read, which a vocabulary stores.
     */
    const READING_OPTIONS = ['format', 'lang', 'label_property', 'comment_property'];

    /**
     * PHP's memory limit while reading a file, where it's lower.
     */
    const MEMORY_LIMIT = '256M';

    /**
     * The base a file is read against when its namespace URI isn't known, so
     * terms named relative to the file can be told apart. The .invalid domain
     * is reserved.
     */
    const RELATIVE_BASE = 'http://relative.invalid/';

    /**
     * @var \Omeka\Api\Manager
     */
    protected $apiManager;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * The last file read, [key, graph], so a request parses it once.
     *
     * @var array|null
     */
    protected $lastFileGraph;

    /**
     * Class types to import.
     *
     * @var array
     */
    protected $classTypes = [
        'rdfs:Class',
        'owl:Class',
    ];

    /**
     * The property types to import.
     *
     * Not included are the OWL DL properties owl:AnnotationProperty and
     * owl:OntologyProperty because they typically serve internal annotative
     * purposes.
     *
     * @var array
     */
    protected $propertyTypes = [
        'rdf:Property',
        'owl:ObjectProperty',
        'owl:DatatypeProperty',
        'owl:SymmetricProperty',
        'owl:TransitiveProperty',
        'owl:FunctionalProperty',
        'owl:InverseFunctionalProperty',
    ];

    public function __construct(ApiManager $apiManager, EntityManager $entityManager)
    {
        $this->apiManager = $apiManager;
        $this->entityManager = $entityManager;
    }

    /**
     * Get the members of the specified vocabulary.
     *
     * @param string $strategy The import strategy to use (e.g. "file", "url")
     * @param string $namespaceUri The namespace URI of the vocabulary
     * @param array $options
     *   - format: (optional)  The format of the RDF file. If not given, the RDF
     *     parser will attempt to guess the format.
     *   - file: (required for "file" strategy) The RDF file path
     *   - url: (required for "url" strategy) The URL of the RDF file.
     *   - label_property: (optional) The RDF property containing the preferred
     *     property label (defaults to "skos:prefLabel|rdfs:label|foaf:name|rss:title|dc:title|dc11:title")
     *   - comment_property: (optional) The RDF property containing the preferred
     *     property comment (defaults to self::COMMENT_PROPERTY)
     *   - lang: (optional) The preferred language of labels and comments
     * @return array
     */
    public function getMembers($strategy, $namespaceUri, array $options = [])
    {
        $options = $this->getReadingOptions($options);
        return $this->getGraphMembers($this->getGraph($strategy, $namespaceUri, $options), $namespaceUri, $options);
    }

    /**
     * Get reading options, with defaults for those not given.
     *
     * @param array $options
     * @return array
     */
    protected function getReadingOptions(array $options)
    {
        if (!isset($options['format'])) {
            // EasyRDF should guess the format if none given.
            $options['format'] = 'guess';
        }
        if (!isset($options['label_property'])) {
            $options['label_property'] = self::LABEL_PROPERTY;
        }
        if (!isset($options['comment_property'])) {
            $options['comment_property'] = self::COMMENT_PROPERTY;
        }
        if (!isset($options['lang'])) {
            $options['lang'] = null;
        }
        return $options;
    }

    /**
     * Get the classes and properties a graph has in a namespace.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @param array $options Reading options, with defaults
     * @return array
     */
    protected function getGraphMembers(RdfGraph $graph, $namespaceUri, array $options)
    {
        return [
            'classes' => $this->getMembersOfTypes(
                $graph,
                $this->classTypes,
                $namespaceUri,
                $options['label_property'],
                $options['comment_property'],
                $options['lang']
            ),
            'properties' => $this->getMembersOfTypes(
                $graph,
                $this->propertyTypes,
                $namespaceUri,
                $options['label_property'],
                $options['comment_property'],
                $options['lang']
            ),
        ];
    }

    /**
     * Get the full RDF graph of a vocabulary file from EasyRdf.
     *
     * @param string $strategy
     * @param string $namespaceUri The base for the file's relative URIs
     * @param array $options The file or URL, and the format
     * @return RdfGraph
     */
    protected function getGraph($strategy, $namespaceUri, array $options)
    {
        $format = $options['format'] ?? 'guess';
        switch ($strategy) {
            case 'file':
                // Import from a file
                if (!isset($options['file'])) {
                    throw new ValidationException('No file specified for the file import strategy.'); // @translate
                }
                $file = $options['file'];
                if (!is_readable($file)) {
                    throw new ValidationException('Could not read vocabulary file.'); // @translate
                }
                clearstatcache(true, $file);
                $key = implode("\0", [$file, filemtime($file), filesize($file), $format, $namespaceUri]);
                if ($this->lastFileGraph && $this->lastFileGraph[0] === $key) {
                    return $this->lastFileGraph[1];
                }
                $graph = $this->readGraph(function (RdfGraph $graph, $format) use ($file, $namespaceUri) {
                    $graph->parseFile($file, $format, $namespaceUri);
                }, $format, 'Could not parse vocabulary file.'); // @translate
                $this->lastFileGraph = [$key, $graph];
                return $graph;
            case 'url':
                // Import from a URL.
                if (!isset($options['url'])) {
                    throw new ValidationException('No URL specified for the URL import strategy.'); // @translate
                }
                $url = $options['url'];
                return $this->readGraph(function (RdfGraph $graph, $format) use ($url) {
                    $graph->load($url, $format);
                }, $format, 'Could not load vocabulary from URL.'); // @translate
            default:
                throw new ValidationException('Unsupported import strategy.'); // @translate
        }
    }

    /**
     * Import an RDF vocabulary, including its classes and properties.
     *
     * @see self::getMembers()
     * @param string $strategy
     * @param array $vocab Vocab info supported by the vocabulary entity adapter
     * @param array $options
     * @return \Omeka\Api\Response
     */
    public function import($strategy, array $vocab, array $options = [])
    {
        // Trimmed as the API saves it, so the file is read with the same.
        $vocab['o:namespace_uri'] = trim((string) $vocab['o:namespace_uri']);
        $reading = $this->getReadingOptions($options);
        $graph = $this->getGraph($strategy, $vocab['o:namespace_uri'], $reading);
        $members = $this->getGraphMembers($graph, $vocab['o:namespace_uri'], $reading);

        // Convert to format that the API understands.
        $vocabMembers = ['o:class' => [], 'o:property' => []];
        foreach ($members['classes'] as $localName => $info) {
            $vocabMembers['o:class'][] = [
                'o:local_name' => $localName,
                'o:label' => $info['label'],
                'o:comment' => $info['comment'],
            ];
        }
        foreach ($members['properties'] as $localName => $info) {
            $vocabMembers['o:property'][] = [
                'o:local_name' => $localName,
                'o:label' => $info['label'],
                'o:comment' => $info['comment'],
            ];
        }

        if (!$members['classes'] && !$members['properties']) {
            throw $this->getNoMembersException($graph, $vocab['o:namespace_uri']);
        }
        $this->checkTermLengths($members);

        return $this->apiManager->create('vocabularies', array_merge($vocab, $vocabMembers, [
            'o:import_options' => array_intersect_key($options, array_flip(self::READING_OPTIONS)),
        ]));
    }

    /**
     * Refuse a file with no classes or properties in a namespace, suggesting
     * the namespace URI its terms use.
     *
     * @param string $strategy
     * @param string $namespaceUri
     * @param array $options As for getMembers()
     * @throws ValidationException
     */
    public function checkNamespaceUri($strategy, $namespaceUri, array $options = [])
    {
        $graph = $this->getGraph($strategy, $namespaceUri, $options);
        if (!array_sum($this->countMembers($graph, $namespaceUri))) {
            throw $this->getNoMembersException($graph, $namespaceUri);
        }
    }

    /**
     * Suggest the namespace URI of a file's classes and properties, for when
     * they don't use the one given.
     *
     * @see self::findNamespaceUri()
     * @param RdfGraph $graph
     * @param string $namespaceUri The namespace URI given
     * @return string|null
     */
    public function suggestNamespaceUri(RdfGraph $graph, $namespaceUri)
    {
        return $this->findNamespaceUri($graph, $namespaceUri)[0];
    }

    /**
     * Get what a vocabulary file says of its vocabulary, for an import to
     * start from.
     *
     * @param string $strategy "file" or "url"
     * @param array $options The file or URL, and the format; and lang: the
     *   language to prefer for the label and comment
     * @return array
     *   - namespace_uri: null for a file that names its terms relative to
     *     itself
     *   - namespace_source: "declared", "ontology", "variant" (another form
     *     of either), "terms", or "relative"
     *   - prefix, label, comment: null where the file says nothing
     *   - languages: of all its classes and properties
     *   - classes, properties: how many it has in the namespace
     * @throws ValidationException When the file can't be read, or has no
     *   classes or properties
     */
    public function getVocabularyDetails($strategy, array $options = [])
    {
        $graph = $this->getGraph($strategy, self::RELATIVE_BASE, $options);
        [$namespaceUri, $source] = $this->findNamespaceUri($graph);
        if (null === $namespaceUri) {
            throw new ValidationException('No classes or properties found in this file.'); // @translate
        }
        $lang = $options['lang'] ?? null;
        $counts = $this->countMembers($graph, $namespaceUri);
        return [
            'namespace_uri' => 'relative' === $source ? null : $namespaceUri,
            'namespace_source' => $source,
            'prefix' => $this->getDeclaredPrefix($graph, $namespaceUri),
            'label' => $this->getVocabularyText($graph, $namespaceUri, 'dcterms:title|dc11:title|rdfs:label', $lang, false),
            // Some element sets describe themselves only in skos:note.
            'comment' => $this->getVocabularyText($graph, $namespaceUri, 'dcterms:description|dc11:description|rdfs:comment|skos:definition', $lang, true)
                ?? $this->getVocabularyText($graph, $namespaceUri, 'skos:note', $lang, true),
            // Of every class and property, as the namespace URI found may be
            // corrected.
            'languages' => $this->getGraphLanguages($graph, null, self::LABEL_PROPERTY . '|' . self::COMMENT_PROPERTY),
            'classes' => $counts['classes'],
            'properties' => $counts['properties'],
        ];
    }

    /**
     * Update a vocabulary given a diff.
     *
     * A change to a term that no longer exists, or a new term that already
     * exists, is skipped.
     *
     * @param int $vocabId
     * @param array $diff
     * @throws ValidationException When a term is too long to store
     */
    public function update($vocabId, array $diff)
    {
        // Refuse what the database can't store before writing anything.
        $written = ['classes' => [], 'properties' => []];
        foreach (['classes', 'properties'] as $type) {
            foreach ($diff[$type]['new'] as $localName => $info) {
                $written[$type][$localName] = ['label' => $info[0]];
            }
            foreach ($diff[$type]['label'] as $localName => $change) {
                $written[$type][$localName] = ['label' => $change[1]];
            }
        }
        $this->checkTermLengths($written);

        $em = $this->entityManager;
        $vocabulary = $em->find('Omeka\Entity\Vocabulary', $vocabId);
        foreach (['properties' => Property::class, 'classes' => ResourceClass::class] as $type => $entityClass) {
            $changes = $diff[$type];
            $terms = [];
            $localNames = array_map('strval', array_keys($changes['new'] + $changes['label'] + $changes['comment']));
            if ($localNames) {
                foreach ($em->getRepository($entityClass)->findBy(['vocabulary' => $vocabulary, 'localName' => $localNames]) as $term) {
                    $terms[$term->getLocalName()] = $term;
                }
            }
            foreach ($changes['new'] as $localName => $info) {
                if (isset($terms[$localName])) {
                    continue;
                }
                $term = new $entityClass;
                $term->setVocabulary($vocabulary);
                $term->setLocalName($localName);
                $term->setLabel($info[0]);
                $term->setComment($info[1]);
                $em->persist($term);
            }
            foreach ($changes['label'] as $localName => $change) {
                if (isset($terms[$localName])) {
                    $terms[$localName]->setLabel($change[1]);
                }
            }
            foreach ($changes['comment'] as $localName => $change) {
                if (isset($terms[$localName])) {
                    $terms[$localName]->setComment($change[1]);
                }
            }
        }
        $em->flush();
    }

    /**
     * Get the diff between a stored vocab and one represented in an RDF graph.
     *
     * @see self::getMembers()
     * @param string $strategy
     * @param string $namespaceUri The namespace URI to read the file with
     * @param array $options As for getMembers(), defaulting to the stored
     *   vocabulary's reading, and vocabulary_id: (optional) the stored
     *   vocabulary to compare with, else the one with the namespace URI
     * @return array
     */
    public function getDiff($strategy, $namespaceUri, array $options = [])
    {
        $vocabulary = $this->apiManager->search('vocabularies', isset($options['vocabulary_id'])
            ? ['id' => (int) $options['vocabulary_id']]
            : ['namespace_uri' => $namespaceUri])->getContent()[0] ?? null;
        if ($vocabulary) {
            $options += $vocabulary->importOptions() ?? [];
        }

        // Get classes and properties from the database.
        $stored = $vocabulary
            ? $this->getStoredMembers(['vocabulary_id' => $vocabulary->id()])
            : ['classes' => [], 'properties' => []];

        // Get classes and properties from the RDF graph.
        $options = $this->getReadingOptions($options);
        $graph = $this->getGraph($strategy, $namespaceUri, $options);
        $members = $this->getGraphMembers($graph, $namespaceUri, $options);
        $this->checkTermLengths($members);

        $diff = [
            'classes' => $this->calculateDiff($stored['classes'], $members['classes']),
            'properties' => $this->calculateDiff($stored['properties'], $members['properties']),
        ];
        // The local name is only the importer's stand-in for a missing label,
        // so it never replaces Omeka's. A label can also be the local name
        // itself, so the file says which terms have none.
        foreach ($diff as $type => $changes) {
            foreach ($changes['label'] as $localName => $change) {
                if ((string) $localName === $change[1]
                    && null === $this->getText($graph->resource($namespaceUri . $localName), $options['label_property'], $options['lang'])
                ) {
                    unset($diff[$type]['label'][$localName]);
                }
            }
        }
        return $diff;
    }

    /**
     * Get the classes and properties a vocabulary has that a file doesn't,
     * which an update keeps.
     *
     * @param int $vocabularyId
     * @param string $strategy
     * @param array $options As for getMembers(), and namespace_uri:
     *   (optional) the namespace URI to read the file with
     * @return array Labels by local name, under classes and properties
     */
    public function getMissingTerms($vocabularyId, $strategy, array $options = [])
    {
        $namespaceUri = $options['namespace_uri']
            ?? $this->apiManager->read('vocabularies', $vocabularyId)->getContent()->namespaceUri();
        $stored = $this->getStoredMembers(['vocabulary_id' => (int) $vocabularyId]);
        $members = $this->getMembers($strategy, $namespaceUri, $options);
        $missing = ['classes' => [], 'properties' => []];
        foreach (['classes', 'properties'] as $type) {
            foreach (array_diff_key($stored[$type], $members[$type]) as $localName => $member) {
                $missing[$type][$localName] = $member['label'];
            }
        }
        return $missing;
    }

    /**
     * Get the languages of the labels and comments of a file's classes and
     * properties in a namespace.
     *
     * @param string $strategy
     * @param string $namespaceUri
     * @param array $options As for getMembers()
     * @return string[] Sorted
     */
    public function getLanguages($strategy, $namespaceUri, array $options = [])
    {
        return $this->getGraphLanguages(
            $this->getGraph($strategy, $namespaceUri, $options),
            $namespaceUri,
            ($options['label_property'] ?? self::LABEL_PROPERTY) . '|' . ($options['comment_property'] ?? self::COMMENT_PROPERTY)
        );
    }
    /**
     * Guess how a vocabulary's file was read, for a vocabulary with no stored
     * reading: the language whose labels match the most of Omeka's, and the
     * comment property that would change fewer of its comments.
     *
     * @param int $vocabularyId
     * @param string $strategy
     * @param string $namespaceUri The namespace URI to read the file with
     * @param array $options As for getMembers()
     * @return array ['lang' => string|null, 'comment_property' => string]
     */
    public function guessReading($vocabularyId, $strategy, $namespaceUri, array $options = [])
    {
        $labelProperty = $options['label_property'] ?? self::LABEL_PROPERTY;
        $graph = $this->getGraph($strategy, $namespaceUri, $options);
        $stored = $this->getStoredMembers(['vocabulary_id' => (int) $vocabularyId]);
        $languages = $this->getGraphLanguages($graph, $namespaceUri, $labelProperty);

        // Ties go to the default language; none ("First available") wins only
        // outright.
        $default = $this->getDefaultLanguage($languages);
        $lang = $default;
        $mostMatches = -1;
        foreach (array_unique(array_merge([$default], $languages, [null])) as $candidate) {
            $matches = 0;
            foreach ($stored as $terms) {
                foreach ($terms as $localName => $term) {
                    $resource = $graph->resource($namespaceUri . $localName);
                    $label = $this->getLabel($resource, $labelProperty, $candidate, (string) $localName);
                    $matches += $this->getComparableText($label) === $this->getComparableText($term['label']) ? 1 : 0;
                }
            }
            if ($matches > $mostMatches) {
                [$lang, $mostMatches] = [$candidate, $matches];
            }
        }

        $changes = [];
        foreach ([self::COMMENT_PROPERTY, self::DEFINITION_COMMENT_PROPERTY] as $commentProperty) {
            $changes[$commentProperty] = 0;
            foreach (['classes' => $this->classTypes, 'properties' => $this->propertyTypes] as $type => $types) {
                $members = $this->getMembersOfTypes($graph, $types, $namespaceUri, $labelProperty, $commentProperty, $lang);
                $changes[$commentProperty] += count($this->calculateDiff($stored[$type], $members)['comment']);
            }
        }
        $commentProperty = $changes[self::DEFINITION_COMMENT_PROPERTY] < $changes[self::COMMENT_PROPERTY]
            ? self::DEFINITION_COMMENT_PROPERTY
            : self::COMMENT_PROPERTY;

        return ['lang' => $lang, 'comment_property' => $commentProperty];
    }

    /**
     * Get the languages of a property's values on a graph's classes and
     * properties in a namespace, or in any.
     *
     * @param RdfGraph $graph
     * @param string|null $namespaceUri Null for any
     * @param string $property A property, or alternatives as "a|b"
     * @return string[] Sorted
     */
    protected function getGraphLanguages(RdfGraph $graph, $namespaceUri, $property)
    {
        $languages = [];
        foreach (array_merge($this->classTypes, $this->propertyTypes) as $type) {
            foreach ($graph->allOfType($type) as $resource) {
                if (null !== $namespaceUri && $resource->getUri() !== $namespaceUri . $resource->localName()) {
                    continue;
                }
                try {
                    $literals = $this->getLiterals($resource, $property);
                } catch (ValidationException $e) {
                    // Reading the term reports it.
                    continue;
                }
                foreach ($literals as $literal) {
                    if ($literal->getLang()) {
                        $languages[$literal->getLang()] = true;
                    }
                }
            }
        }
        $languages = array_keys($languages);
        sort($languages);
        return $languages;
    }

    /**
     * Get the default language of a file's terms: English, else a regional
     * English, else the first.
     *
     * @param string[] $languages
     * @return string|null
     */
    protected function getDefaultLanguage(array $languages)
    {
        if (in_array('en', $languages, true)) {
            return 'en';
        }
        return current(preg_grep('/^en-/i', $languages)) ?: ($languages[0] ?? null);
    }

    /**
     * Get the classes and properties of a vocabulary in Omeka.
     *
     * @param array $query A query for the API, by vocabulary
     * @return array
     */
    protected function getStoredMembers(array $query)
    {
        $stored = ['classes' => [], 'properties' => []];
        foreach (['classes' => 'resource_classes', 'properties' => 'properties'] as $type => $resource) {
            foreach ($this->apiManager->search($resource, $query)->getContent() as $term) {
                $stored[$type][$term->localName()] = [
                    'label' => $term->label(),
                    'comment' => $term->comment(),
                ];
            }
        }
        return $stored;
    }

    /**
     * Calculate the difference between two vocabulary members.
     *
     * Only gets the diffs that we can process safely: add new members and
     * update existing labels and comments. It doesn't get the diffs we cannot
     * process safely: delete existing members or update existing local names.
     * Nor does it empty an existing comment.
     *
     * @param array $from
     * @param array $to
     * @return array
     */
    protected function calculateDiff(array $from, array $to)
    {
        $diff = ['new' => [], 'label' => [], 'comment' => []];
        foreach ($to as $localName => $info) {
            if (array_key_exists($localName, $from)) {
                // Existing member.
                if ($this->getComparableText($from[$localName]['label']) !== $this->getComparableText($info['label'])) {
                    // Updated label.
                    $diff['label'][$localName] = [$from[$localName]['label'], $info['label']];
                }
                if ('' !== $this->getComparableText($info['comment'])
                    && $this->getComparableText($from[$localName]['comment']) !== $this->getComparableText($info['comment'])
                ) {
                    // Updated comment.
                    $diff['comment'][$localName] = [$from[$localName]['comment'], $info['comment']];
                }
            } else {
                // New member.
                $diff['new'][$localName] = [$info['label'], $info['comment']];
            }
        }
        return $diff;
    }

    /**
     * Get a label or comment as it's compared: spacing collapsed, and a
     * missing value empty.
     *
     * @param string|null $value
     * @return string
     */
    protected function getComparableText($value)
    {
        $text = (string) $value;
        // Text that isn't valid UTF-8 can't be matched as such.
        return trim(preg_replace('/\s+/u', ' ', $text) ?? preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Read RDF into a new graph.
     *
     * A guessed format can be wrong without failing: EasyRdf takes a Turtle
     * file that mentions RDFa near its start for RDFa, which reads nothing.
     * So a guess that fails or reads nothing gives way to each format in
     * turn.
     *
     * @param callable $read Reads into the graph it's passed, in the format
     *   it's passed
     * @param string $format A format, or "guess"
     * @param string $error The message when nothing can be read
     * @return RdfGraph
     */
    protected function readGraph(callable $read, $format, $error)
    {
        $this->raiseMemoryLimit();
        $graph = new RdfGraph;
        try {
            $read($graph, $format);
        } catch (\EasyRdf\Http\Exception | \InvalidArgumentException $e) {
            // Another format won't change these.
            throw new ValidationException($error);
        } catch (\EasyRdf\Exception $e) {
            $graph = null;
        }
        if ('guess' === $format && (!$graph || !$graph->countTriples())) {
            foreach (['rdfxml', 'turtle', 'jsonld', 'ntriples'] as $try) {
                $attempt = new RdfGraph;
                try {
                    $read($attempt, $try);
                } catch (\Throwable $e) {
                    continue;
                }
                if ($attempt->countTriples()) {
                    return $attempt;
                }
            }
        }
        if (!$graph) {
            throw new ValidationException($error);
        }
        return $graph;
    }

    /**
     * Raise PHP's memory limit to self::MEMORY_LIMIT where it's lower and the
     * host allows it.
     */
    protected function raiseMemoryLimit()
    {
        $limit = (string) ini_get('memory_limit');
        if ('-1' !== $limit && $this->getBytes($limit) < $this->getBytes(self::MEMORY_LIMIT)) {
            ini_set('memory_limit', self::MEMORY_LIMIT);
        }
    }

    /**
     * Get the bytes in a PHP memory size, such as "128M".
     *
     * @param string $size
     * @return int
     */
    protected function getBytes($size)
    {
        $size = trim($size);
        $units = ['k' => 1024, 'm' => 1024 ** 2, 'g' => 1024 ** 3];
        return (int) $size * ($units[strtolower(substr($size, -1))] ?? 1);
    }

    /**
     * Refuse classes and properties whose local names or labels are too long
     * to store, naming them.
     *
     * @param array $members Classes and properties, as getMembers() returns
     *   them
     * @throws ValidationException
     */
    protected function checkTermLengths(array $members)
    {
        $longLocalNames = [];
        $longLabels = [];
        foreach (['classes', 'properties'] as $type) {
            foreach ($members[$type] ?? [] as $localName => $member) {
                $localName = (string) $localName;
                $name = $localName;
                if (mb_strlen($localName) > self::LOCAL_NAME_MAX_LENGTH) {
                    $name = mb_substr($localName, 0, 40) . '…';
                    $longLocalNames[] = $name;
                }
                if (mb_strlen((string) $member['label']) > self::LABEL_MAX_LENGTH) {
                    $longLabels[] = $name;
                }
            }
        }
        if (!$longLocalNames && !$longLabels) {
            return;
        }
        $exception = new ValidationException('Some classes or properties have local names or labels longer than Omeka can store.'); // @translate
        if ($longLocalNames) {
            $exception->getErrorStore()->addError('o:local_name', new Message(
                'These classes or properties have local names longer than %1$s characters: %2$s', // @translate
                self::LOCAL_NAME_MAX_LENGTH,
                implode(', ', $longLocalNames)
            ));
        }
        if ($longLabels) {
            $exception->getErrorStore()->addError('o:label', new Message(
                'These classes or properties have labels longer than %1$s characters: %2$s', // @translate
                self::LABEL_MAX_LENGTH,
                implode(', ', $longLabels)
            ));
        }
        throw $exception;
    }

    /**
     * Get the refusal of a file with no classes or properties in a namespace.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @return ValidationException
     */
    protected function getNoMembersException(RdfGraph $graph, $namespaceUri)
    {
        $exception = new ValidationException('No classes or properties found. Are you sure you used the correct namespace URI?'); // @translate
        $suggestion = $this->suggestNamespaceUri($graph, $namespaceUri);
        if (null !== $suggestion) {
            $exception->getErrorStore()->addError('o:namespace_uri', new Message(
                'The file\'s classes and properties use the namespace URI "%s".', // @translate
                $suggestion
            ));
        }
        return $exception;
    }

    /**
     * Count the classes and properties a graph has in a namespace, each once.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @return array classes, properties
     */
    protected function countMembers(RdfGraph $graph, $namespaceUri)
    {
        $counts = [];
        foreach (['classes' => $this->classTypes, 'properties' => $this->propertyTypes] as $kind => $types) {
            $uris = [];
            foreach ($types as $type) {
                foreach ($graph->allOfType($type) as $resource) {
                    if ($resource->getUri() === $namespaceUri . $resource->localName()) {
                        $uris[$resource->getUri()] = true;
                    }
                }
            }
            $counts[$kind] = count($uris);
        }
        return $counts;
    }

    /**
     * Find the namespace URI of a file's vocabulary, and where it was found.
     *
     * Tries groups in turn: the other forms of one given; those the file
     * declares; and its ontology records' URIs. From the first group with
     * terms, it takes the form with the most, as a file may also declare the
     * vocabularies it reuses. Else it takes the one most terms use.
     *
     * @param RdfGraph $graph
     * @param string|null $except A namespace URI given, which isn't found
     * @return array [namespace URI, source], or [null, null]
     */
    protected function findNamespaceUri(RdfGraph $graph, $except = null)
    {
        $groups = [];
        if (null !== $except) {
            $groups[] = ['variant', [$except]];
        }
        $declared = [];
        $vann = 'http://purl.org/vocab/vann/preferredNamespaceUri';
        foreach ($graph->resourcesMatching($vann) as $resource) {
            foreach ($resource->all("<$vann>") as $value) {
                $declared[] = trim((string) $value);
            }
        }
        $groups[] = ['declared', $declared];
        $ontologies = [];
        foreach ($graph->allOfType('owl:Ontology') as $ontology) {
            // Not one named relative to the file, as with rdf:about="".
            if (!$ontology->isBNode() && preg_match('#^https?://#', $ontology->getUri())
                && 0 !== strpos($ontology->getUri(), self::RELATIVE_BASE)
            ) {
                $ontologies[] = $ontology->getUri();
            }
        }
        $groups[] = ['ontology', $ontologies];
        foreach ($groups as [$source, $bases]) {
            $best = null;
            $bestCount = 0;
            $bestIsBase = false;
            foreach ($bases as $base) {
                foreach (NamespaceUri::getForms($base) as $form) {
                    if ($form === $except) {
                        continue;
                    }
                    $count = array_sum($this->countMembers($graph, $form));
                    if ($count > $bestCount) {
                        [$best, $bestCount, $bestIsBase] = [$form, $count, $form === $base];
                    }
                }
            }
            if (null !== $best) {
                return [$best, $bestIsBase ? $source : 'variant'];
            }
        }
        $mostUsed = $this->getMostUsedNamespaceUri($graph);
        if (null !== $mostUsed && $mostUsed !== $except && array_sum($this->countMembers($graph, $mostUsed))) {
            return [$mostUsed, 0 === strpos($mostUsed, self::RELATIVE_BASE) ? 'relative' : 'terms'];
        }
        return [null, null];
    }

    /**
     * Get the prefix a file declares for its vocabulary, if Omeka can take
     * it. Only its own records are read, as a file may declare the prefixes
     * of vocabularies it reuses.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @return string|null
     */
    protected function getDeclaredPrefix(RdfGraph $graph, $namespaceUri)
    {
        $subjects = $this->getOwnOntologies($graph, $namespaceUri);
        $subjects[] = $graph->resource($namespaceUri);
        $subjects[] = $graph->resource(rtrim($namespaceUri, '#/'));
        foreach ($subjects as $subject) {
            try {
                $prefix = trim((string) $subject->get('<http://purl.org/vocab/vann/preferredNamespacePrefix>'));
            } catch (\Throwable $e) {
                continue;
            }
            if ('' !== $prefix) {
                return preg_match(Vocabulary::PREFIX_REGEX, $prefix) ? $prefix : null;
            }
        }
        return null;
    }

    /**
     * Get a file's ontology records for a namespace.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @return RdfResource[]
     */
    protected function getOwnOntologies(RdfGraph $graph, $namespaceUri)
    {
        $base = rtrim($namespaceUri, '#/');
        $own = [];
        foreach ($graph->allOfType('owl:Ontology') as $ontology) {
            if (rtrim((string) $ontology->getUri(), '#/') === $base) {
                $own[] = $ontology;
            }
        }
        return $own;
    }

    /**
     * Get the records a file may describe its vocabulary in, in the order
     * they're read: its ontology records, or its only one; the namespace
     * resource, with and without its closing # or /; and the record most of
     * its terms name with rdfs:isDefinedBy.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @return RdfResource[]
     */
    protected function getVocabularyRecords(RdfGraph $graph, $namespaceUri)
    {
        $records = $this->getOwnOntologies($graph, $namespaceUri);
        if (!$records) {
            $all = $graph->allOfType('owl:Ontology');
            $records = 1 === count($all) ? $all : [];
        }
        $records[] = $graph->resource($namespaceUri);
        $records[] = $graph->resource(rtrim($namespaceUri, '#/'));
        $counts = [];
        foreach ($graph->resources() as $resource) {
            if ($resource->isBNode() || 0 !== strpos($resource->getUri(), $namespaceUri)) {
                continue;
            }
            foreach ($resource->allResources('rdfs:isDefinedBy') as $record) {
                if (!$record->isBNode()) {
                    $counts[$record->getUri()] = ($counts[$record->getUri()] ?? 0) + 1;
                }
            }
        }
        if ($counts) {
            arsort($counts);
            $records[] = $graph->resource(array_key_first($counts));
        }
        return $records;
    }

    /**
     * Get a file's text for its vocabulary, such as its title, from the first
     * of its records that has it. A title is one line; a description keeps
     * its paragraphs, and all its values.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @param string $properties Alternatives, as "a|b"
     * @param string|null $lang
     * @param bool $paragraphs
     * @return string|null
     */
    protected function getVocabularyText(RdfGraph $graph, $namespaceUri, $properties, $lang, $paragraphs)
    {
        foreach ($this->getVocabularyRecords($graph, $namespaceUri) as $record) {
            try {
                $values = $this->getVocabularyValues($record, $properties, $lang);
            } catch (\Throwable $e) {
                continue;
            }
            $texts = [];
            foreach ($values as $value) {
                $text = trim(str_replace(["\r\n", "\r"], "\n", (string) $value));
                if ('' === $text || !mb_check_encoding($text, 'UTF-8')) {
                    continue;
                }
                if (!$paragraphs) {
                    return preg_replace('/\s+/u', ' ', $text);
                }
                $texts[] = preg_replace("/\n\s*\n\s*/u", "\n\n", $text);
            }
            if ($texts) {
                return implode("\n\n", $texts);
            }
        }
        return null;
    }

    /**
     * Get a record's values for the first of some properties that has them
     * in a language: the one asked for, then English, then any.
     *
     * @param RdfResource $record
     * @param string $properties Alternatives, as "a|b"
     * @param string|null $lang
     * @return \EasyRdf\Literal[]
     */
    protected function getVocabularyValues(RdfResource $record, $properties, $lang)
    {
        foreach (array_unique(array_filter([$lang, 'en'])) as $language) {
            foreach (explode('|', $properties) as $property) {
                $values = array_filter($record->all($property, 'literal'), function ($value) use ($language) {
                    return in_array((string) $value->getLang(), [$language, ''], true);
                });
                if (array_filter($values, function ($value) use ($language) {
                    return $language === $value->getLang();
                })) {
                    return array_values($values);
                }
            }
        }
        foreach (explode('|', $properties) as $property) {
            $values = $record->all($property, 'literal');
            if ($values) {
                $first = (string) $values[0]->getLang();
                return array_values(array_filter($values, function ($value) use ($first) {
                    return in_array((string) $value->getLang(), [$first, ''], true);
                }));
            }
        }
        return [];
    }

    /**
     * Get the namespace URI most of a graph's classes and properties use.
     *
     * @param RdfGraph $graph
     * @return string|null
     */
    protected function getMostUsedNamespaceUri(RdfGraph $graph)
    {
        $counts = [];
        foreach (array_merge($this->classTypes, $this->propertyTypes) as $type) {
            foreach ($graph->allOfType($type) as $resource) {
                $uri = (string) $resource->getUri();
                $localName = (string) $resource->localName();
                // Without a base, a file's relative URIs resolve against its
                // path on the server.
                if ($resource->isBNode() || '' === $localName || !preg_match('#^https?://#', $uri)) {
                    continue;
                }
                $namespaceUri = substr($uri, 0, -strlen($localName));
                $counts[$namespaceUri] = ($counts[$namespaceUri] ?? 0) + 1;
            }
        }
        arsort($counts);
        return key($counts);
    }

    /**
     * Get all members of the specified types.
     *
     * @param RdfGraph $graph
     * @param array $types
     * @param string $namespaceUri
     * @param string $labelProperty
     * @param string $commentProperty
     * @param string $lang
     */
    protected function getMembersOfTypes(RdfGraph $graph, array $types,
        $namespaceUri, $labelProperty, $commentProperty, $lang
    ) {
        $members = [];
        foreach ($types as $type) {
            foreach ($graph->allOfType($type) as $resource) {
                // The resource must be a local member of the vocabulary.
                if ($resource->getUri() === $namespaceUri . $resource->localName()) {
                    $members[$resource->localName()] = [
                        'label' => $this->getLabel($resource, $labelProperty, $lang, $resource->localName()),
                        'comment' => $this->getComment($resource, $commentProperty, $lang),
                    ];
                }
            }
        }
        return $members;
    }

    /**
     * Get the label from an RDF resource.
     *
     * Attempts to get the label of the passed language. If one does not exist
     * it defaults to the first available label, if any. It's trimmed, as the
     * API trims one.
     *
     * @param RdfResource $resource
     * @param string $labelProperty
     * @param string $lang
     * @param string $default
     * @return string
     */
    protected function getLabel(RdfResource $resource, $labelProperty, $lang, $default)
    {
        $label = $this->getText($resource, $labelProperty, $lang);
        return null === $label ? $default : trim($label);
    }

    /**
     * Get the comment from an RDF resource.
     *
     * Attempts to get the comment of the passed language. If one does not exist
     * it defaults to the first available comment, if any.
     *
     * @param RdfResource $resource
     * @param string $commentProperty
     * @param string $lang
     * @return string|null
     */
    protected function getComment(RdfResource $resource, $commentProperty, $lang)
    {
        return $this->getText($resource, $commentProperty, $lang);
    }

    /**
     * Get the text of a resource's first literal for a property that isn't
     * blank, in the passed language if there is one, else in any. Files often
     * leave a blank literal as a placeholder for a translation.
     *
     * @param RdfResource $resource
     * @param string $property A property, or alternatives as "a|b"
     * @param string|null $lang
     * @return string|null
     */
    protected function getText(RdfResource $resource, $property, $lang)
    {
        $literals = $lang ? $this->getLiterals($resource, $property, $lang) : [];
        foreach (array_merge($literals, $this->getLiterals($resource, $property)) as $literal) {
            // A typed literal's value may be a number, a boolean, or a date.
            $text = (string) $literal;
            if ('' !== trim($text)) {
                return $text;
            }
        }
        return null;
    }

    /**
     * Get a resource's literals for a property, in a language if one is
     * passed. EasyRdf checks a typed literal against its datatype only here.
     *
     * @param RdfResource $resource
     * @param string $property A property, or alternatives as "a|b"
     * @param string|null $lang
     * @return \EasyRdf\Literal[]
     * @throws ValidationException Naming the term
     */
    protected function getLiterals(RdfResource $resource, $property, $lang = null)
    {
        try {
            return $resource->all($property, 'literal', $lang);
        } catch (\InvalidArgumentException | \UnexpectedValueException $e) {
            $exception = new ValidationException('A class or property has a label or comment that isn\'t valid for its datatype.'); // @translate
            $exception->getErrorStore()->addError('term', new Message(
                '%1$s: %2$s',
                $resource->getUri(),
                $e->getMessage()
            ));
            throw $exception;
        }
    }
}
