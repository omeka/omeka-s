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
     * The properties a term's label is read from by default, in order.
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
     * The comment properties the Comments field offers, by option value.
     */
    const COMMENT_PROPERTY_CHOICES = [
        'comment' => self::COMMENT_PROPERTY,
        'definition' => self::DEFINITION_COMMENT_PROPERTY,
    ];

    /**
     * The import options: how a file is read, as its format, language, and
     * label and comment properties. A vocabulary stores them, so an update
     * reads the next version of its file the same way.
     */
    const IMPORT_OPTIONS = ['format', 'lang', 'label_property', 'comment_property'];

    /**
     * The memory limit to raise PHP's to while reading a file, if PHP's is
     * lower.
     */
    const MEMORY_LIMIT = '256M';

    /**
     * A fake base for reading a file before its namespace URI is known. A
     * term the file names without one, such as rdf:ID="Agent", comes out as
     * http://relative.invalid/#Agent, which shows the file gave no namespace,
     * so the import asks for it. The .invalid domain can never be real.
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
        $options = $this->addDefaultImportOptions($options);
        return $this->getGraphMembers($this->getGraph($strategy, $namespaceUri, $options), $namespaceUri, $options);
    }

    /**
     * Get import options, with defaults for those not given.
     *
     * @param array $options
     * @return array
     */
    protected function addDefaultImportOptions(array $options)
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
     * @param array $options Import options, with defaults
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
                // Free the last graph before reading another, so the two
                // aren't in memory at once: an import's details and then the
                // import itself read one file twice, with different bases,
                // and a large file's graph can take most of the memory limit.
                // An EasyRdf graph refers to itself, so it's freed only when
                // cycles are collected.
                if ($this->lastFileGraph) {
                    $this->lastFileGraph = null;
                    gc_collect_cycles();
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
        // Trim it as the API will when saving, so the file is read with the
        // namespace URI that gets stored.
        $vocab['o:namespace_uri'] = trim((string) $vocab['o:namespace_uri']);
        $withDefaults = $this->addDefaultImportOptions($options);
        $graph = $this->getGraph($strategy, $vocab['o:namespace_uri'], $withDefaults);
        $members = $this->getGraphMembers($graph, $vocab['o:namespace_uri'], $withDefaults);

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
            'o:import_options' => array_intersect_key($options, array_flip(self::IMPORT_OPTIONS)),
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
    public function checkHasMembers($strategy, $namespaceUri, array $options = [])
    {
        $graph = $this->getGraph($strategy, $namespaceUri, $options);
        if (!array_sum($this->countMembers($graph, $namespaceUri))) {
            throw $this->getNoMembersException($graph, $namespaceUri);
        }
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
     *   - namespace_source: where it was found: "declared"
     *     (vann:preferredNamespaceUri), "ontology" (an owl:Ontology's URI),
     *     "other_form" (another form of either), "terms" (the namespace most
     *     of its terms use), or "relative" (none: its terms are named
     *     relative to the file)
     *   - prefix, label, comment: null where the file says nothing
     *   - languages: of all its classes and properties
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
        return [
            'namespace_uri' => 'relative' === $source ? null : $namespaceUri,
            'namespace_source' => $source,
            'prefix' => $this->getVocabularyPrefix($graph, $namespaceUri),
            'label' => $this->getVocabularyTitle($graph, $namespaceUri, $lang),
            'comment' => $this->getVocabularyDescription($graph, $namespaceUri, $lang),
            // From every class and property, in any namespace, since the
            // admin may correct the namespace URI found.
            'languages' => $this->getGraphLanguages($graph, null, self::LABEL_PROPERTY . '|' . self::COMMENT_PROPERTY),
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
     * @param array $options As for getMembers(), those not given coming from
     *   the stored vocabulary's import options; and vocabulary_id: (optional)
     *   the stored vocabulary to compare with, else the one with the
     *   namespace URI
     * @return array
     */
    public function getDiff($strategy, $namespaceUri, array $options = [])
    {
        $vocabulary = $this->getStoredVocabulary($namespaceUri, $options);
        if ($vocabulary) {
            $options += $vocabulary->importOptions() ?? [];
        }

        // Get classes and properties from the database.
        $stored = $this->getStoredMembers($vocabulary);

        // Get classes and properties from the RDF graph.
        $options = $this->addDefaultImportOptions($options);
        $graph = $this->getGraph($strategy, $namespaceUri, $options);
        $members = $this->getGraphMembers($graph, $namespaceUri, $options);
        $this->checkTermLengths($members);

        $diff = [
            'classes' => $this->calculateDiff($stored['classes'], $members['classes']),
            'properties' => $this->calculateDiff($stored['properties'], $members['properties']),
        ];
        // A term with no label gets its local name as its label, such as
        // birthDate. That stand-in never replaces Omeka's label. A real label
        // can also be its local name, so a change is dropped only where the
        // file has no label for the term.
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
     * Get the classes and properties a stored vocabulary has that a file
     * doesn't, which an update keeps.
     *
     * @param string $strategy
     * @param string $namespaceUri The namespace URI to read the file with
     * @param array $options As for getMembers(), and vocabulary_id:
     *   (optional) the stored vocabulary, else the one with the namespace URI
     * @return array Labels by local name, under classes and properties
     */
    public function getMembersNotInFile($strategy, $namespaceUri, array $options = [])
    {
        $stored = $this->getStoredMembers($this->getStoredVocabulary($namespaceUri, $options));
        $members = $this->getMembers($strategy, $namespaceUri, $options);
        $notInFile = ['classes' => [], 'properties' => []];
        foreach (['classes', 'properties'] as $type) {
            foreach (array_diff_key($stored[$type], $members[$type]) as $localName => $member) {
                $notInFile[$type][$localName] = $member['label'];
            }
        }
        return $notInFile;
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
     * Guess the import options of a vocabulary with none stored: the
     * language whose labels match the most of Omeka's, and the comment
     * property that would change fewer of its comments.
     *
     * @param string $strategy
     * @param string $namespaceUri The namespace URI to read the file with
     * @param array $options As for getMembers(), and vocabulary_id:
     *   (optional) the stored vocabulary, else the one with the namespace URI
     * @return array ['lang' => string|null, 'comment_property' => string]
     */
    public function guessImportOptions($strategy, $namespaceUri, array $options = [])
    {
        $labelProperty = $options['label_property'] ?? self::LABEL_PROPERTY;
        $graph = $this->getGraph($strategy, $namespaceUri, $options);
        $stored = $this->getStoredMembers($this->getStoredVocabulary($namespaceUri, $options));
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
                    // An invalid typed literal; reading the term reports it.
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
     * Get the stored vocabulary a file is compared with: the one the
     * vocabulary_id option names, else the one with the namespace URI.
     *
     * @param string $namespaceUri
     * @param array $options
     * @return \Omeka\Api\Representation\VocabularyRepresentation|null
     */
    protected function getStoredVocabulary($namespaceUri, array $options)
    {
        return $this->apiManager->search('vocabularies', isset($options['vocabulary_id'])
            ? ['id' => (int) $options['vocabulary_id']]
            : ['namespace_uri' => $namespaceUri])->getContent()[0] ?? null;
    }

    /**
     * Get the classes and properties of a vocabulary in Omeka, none without
     * a vocabulary.
     *
     * @param \Omeka\Api\Representation\VocabularyRepresentation|null $vocabulary
     * @return array
     */
    protected function getStoredMembers($vocabulary)
    {
        $stored = ['classes' => [], 'properties' => []];
        if (!$vocabulary) {
            return $stored;
        }
        foreach (['classes' => 'resource_classes', 'properties' => 'properties'] as $type => $resource) {
            foreach ($this->apiManager->search($resource, ['vocabulary_id' => $vocabulary->id()])->getContent() as $term) {
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
        // preg_replace() with /u fails on text that isn't valid UTF-8, so
        // that's matched byte by byte.
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
            // Retrying in another format can't fix a failed fetch or a bad
            // URL.
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
                if (mb_strlen($localName) > Vocabulary::LOCAL_NAME_MAX_LENGTH) {
                    $name = mb_substr($localName, 0, 40) . '…';
                    $longLocalNames[] = $name;
                }
                if (mb_strlen((string) $member['label']) > Vocabulary::LABEL_MAX_LENGTH) {
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
                Vocabulary::LOCAL_NAME_MAX_LENGTH,
                implode(', ', $longLocalNames)
            ));
        }
        if ($longLabels) {
            $exception->getErrorStore()->addError('o:label', new Message(
                'These classes or properties have labels longer than %1$s characters: %2$s', // @translate
                Vocabulary::LABEL_MAX_LENGTH,
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
        $suggestion = $this->findNamespaceUri($graph, $namespaceUri)[0];
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
     * Tries groups in turn: the other forms of $except; the namespace URIs
     * the file declares (vann:preferredNamespaceUri); and its owl:Ontology
     * URIs. From the first group that matches any terms, it takes the form
     * that matches the most, as a file may also declare the vocabularies it
     * reuses. Else it takes the namespace most terms use.
     *
     * @param RdfGraph $graph
     * @param string|null $except A namespace URI that matched no terms, which
     *   isn't returned itself
     * @return array [namespace URI, source], or [null, null]
     */
    protected function findNamespaceUri(RdfGraph $graph, $except = null)
    {
        $groups = [];
        if (null !== $except) {
            $groups[] = ['other_form', [$except]];
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
                foreach (Vocabulary::getNamespaceUriForms($base) as $form) {
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
                return [$best, $bestIsBase ? $source : 'other_form'];
            }
        }
        $mostUsed = $this->getMostUsedNamespaceUri($graph);
        if (null !== $mostUsed && $mostUsed !== $except && array_sum($this->countMembers($graph, $mostUsed))) {
            return [$mostUsed, 0 === strpos($mostUsed, self::RELATIVE_BASE) ? 'relative' : 'terms'];
        }
        return [null, null];
    }

    /**
     * Get the prefix a file declares for its vocabulary
     * (vann:preferredNamespacePrefix), such as "bibo": the first one Omeka can
     * use. Only resources for this namespace are read, as a file may declare
     * prefixes for vocabularies it reuses.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @return string|null
     */
    protected function getVocabularyPrefix(RdfGraph $graph, $namespaceUri)
    {
        $subjects = $this->getOntologiesFor($graph, $namespaceUri);
        $subjects[] = $graph->resource($namespaceUri);
        $subjects[] = $graph->resource(rtrim($namespaceUri, '#/'));
        foreach ($subjects as $subject) {
            try {
                $prefix = trim((string) $subject->get('<http://purl.org/vocab/vann/preferredNamespacePrefix>'));
            } catch (\Throwable $e) {
                continue;
            }
            if ('' !== $prefix && preg_match(Vocabulary::PREFIX_REGEX, $prefix)) {
                return $prefix;
            }
        }
        return null;
    }

    /**
     * Get the title a file gives its vocabulary, on one line, such as "The
     * Bibliographic Ontology".
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @param string|null $lang The language to prefer
     * @return string|null
     */
    protected function getVocabularyTitle(RdfGraph $graph, $namespaceUri, $lang)
    {
        return $this->findVocabularyText($graph, $namespaceUri, 'dcterms:title|dc11:title|rdfs:label', $lang, false);
    }

    /**
     * Get the description a file gives its vocabulary, its paragraphs kept.
     * Some element sets describe themselves only in skos:note.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @param string|null $lang The language to prefer
     * @return string|null
     */
    protected function getVocabularyDescription(RdfGraph $graph, $namespaceUri, $lang)
    {
        return $this->findVocabularyText($graph, $namespaceUri, 'dcterms:description|dc11:description|rdfs:comment|skos:definition', $lang, true)
            ?? $this->findVocabularyText($graph, $namespaceUri, 'skos:note', $lang, true);
    }

    /**
     * Get the text of the first resource describing a file's vocabulary that
     * has one of some properties.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @param string $properties Alternatives, as "a|b"
     * @param string|null $lang
     * @param bool $paragraphs True for a description, keeping its paragraphs
     *   and all its values; false for a title, one value on one line
     * @return string|null
     */
    protected function findVocabularyText(RdfGraph $graph, $namespaceUri, $properties, $lang, $paragraphs)
    {
        foreach ($this->getDescribingResources($graph, $namespaceUri) as $resource) {
            try {
                $values = $this->getValuesInPreferredLanguage($resource, $properties, $lang);
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
                $texts[] = $this->getUnindentedText($text);
            }
            if ($texts) {
                return implode("\n\n", $texts);
            }
        }
        return null;
    }

    /**
     * Get a description without the indentation a file adds when it wraps
     * a value over several lines, keeping its line breaks and any
     * indentation of its own.
     *
     * Only the indentation every line after the first shares is removed.
     * "First line,\n    and the next." comes out as "First line,\nand the
     * next.", while "Rules:\n1. A rule,\n   wrapped." keeps its hanging
     * indent, as "1." starts at the margin. Spaces at the ends of lines go,
     * and blank lines come down to one in a row.
     *
     * @param string $text Trimmed, with "\n" line breaks
     * @return string
     */
    protected function getUnindentedText($text)
    {
        // Without the spaces at their ends, so a line of only spaces is
        // blank.
        $lines = preg_split('/[^\S\n]*\n/u', $text);
        $shared = null;
        foreach (array_slice($lines, 1) as $line) {
            if ('' === $line) {
                continue;
            }
            preg_match('/^[ \t]*/', $line, $match);
            $shared ??= $match[0];
            // Shortened to what this line's indentation starts with too.
            while (0 !== strpos($match[0], $shared)) {
                $shared = substr($shared, 0, -1);
            }
        }
        foreach ($lines as $i => $line) {
            if ($i && '' !== $line) {
                $lines[$i] = substr($line, strlen((string) $shared));
            }
        }
        return preg_replace("/\n\n+/", "\n\n", implode("\n", $lines));
    }

    /**
     * Get the resources that may describe a file's vocabulary as a whole, in
     * the order they're read: its owl:Ontology resources for the namespace,
     * else its only owl:Ontology if it has just one; the namespace URI as a
     * resource, with and without its closing # or /; and the resource most of
     * its terms name with rdfs:isDefinedBy.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @return RdfResource[]
     */
    protected function getDescribingResources(RdfGraph $graph, $namespaceUri)
    {
        $resources = $this->getOntologiesFor($graph, $namespaceUri);
        if (!$resources) {
            $all = $graph->allOfType('owl:Ontology');
            $resources = 1 === count($all) ? $all : [];
        }
        $resources[] = $graph->resource($namespaceUri);
        $resources[] = $graph->resource(rtrim($namespaceUri, '#/'));
        $counts = [];
        foreach ($graph->resources() as $resource) {
            if ($resource->isBNode() || 0 !== strpos($resource->getUri(), $namespaceUri)) {
                continue;
            }
            foreach ($resource->allResources('rdfs:isDefinedBy') as $definedBy) {
                if (!$definedBy->isBNode()) {
                    $counts[$definedBy->getUri()] = ($counts[$definedBy->getUri()] ?? 0) + 1;
                }
            }
        }
        if ($counts) {
            arsort($counts);
            $resources[] = $graph->resource(array_key_first($counts));
        }
        return $resources;
    }

    /**
     * Get a file's owl:Ontology resources for a namespace.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @return RdfResource[]
     */
    protected function getOntologiesFor(RdfGraph $graph, $namespaceUri)
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
     * Get a resource's values for the first of some properties that has them,
     * in the preferred language, else English, else the first language one
     * has. Values with no language come along with any.
     *
     * @param RdfResource $resource
     * @param string $properties Alternatives, as "a|b"
     * @param string|null $lang
     * @return \EasyRdf\Literal[]
     */
    protected function getValuesInPreferredLanguage(RdfResource $resource, $properties, $lang)
    {
        foreach (array_unique(array_filter([$lang, 'en'])) as $language) {
            foreach (explode('|', $properties) as $property) {
                $values = array_filter($resource->all($property, 'literal'), function ($value) use ($language) {
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
            $values = $resource->all($property, 'literal');
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
     * passed. EasyRdf checks a typed literal against its datatype only when
     * it's fetched, not while parsing, so an invalid one fails here.
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
