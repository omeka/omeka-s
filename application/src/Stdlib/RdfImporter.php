<?php
namespace Omeka\Stdlib;

use Doctrine\ORM\EntityManager;
use EasyRdf\Graph as RdfGraph;
use EasyRdf\Resource as RdfResource;
use Omeka\Api\Exception\ValidationException;
use Omeka\Api\Manager as ApiManager;
use Omeka\Entity\Property;
use Omeka\Entity\ResourceClass;

class RdfImporter
{
    /**
     * The longest local name a property or resource class can have, in
     * characters, as its database column allows.
     */
    const LOCAL_NAME_MAX_LENGTH = 190;

    /**
     * The longest label a property or resource class can have, in
     * characters, as its database column allows.
     */
    const LABEL_MAX_LENGTH = 255;

    /**
     * The options that say how a vocabulary file is read, which a vocabulary
     * stores as its import options: see getMembers().
     */
    const READING_OPTIONS = ['format', 'lang', 'label_property', 'comment_property'];

    /**
     * PHP's memory limit while reading a vocabulary file, where it's lower:
     * a large file can take more than PHP's default.
     */
    const MEMORY_LIMIT = '256M';

    /**
     * @var \Omeka\Api\Manager
     */
    protected $apiManager;

    /**
     * @var EntityManager
     */
    protected $entityManager;

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
     *     parser will attempt to guess the format, and a guess that fails or
     *     reads nothing gives way to each format in turn.
     *   - file: (required for "file" strategy) The RDF file path
     *   - url: (required for "url" strategy) The URL of the RDF file.
     *   - label_property: (optional) The RDF property containing the preferred
     *     property label (defaults to "skos:prefLabel|rdfs:label|foaf:name|rss:title|dc:title|dc11:title")
     *   - comment_property: (optional) The RDF property containing the preferred
     *     property comment (defaults to "rdfs:comment")
     *   - lang: (optional) The preferred language of labels and comments
     * @return array
     */
    public function getMembers($strategy, $namespaceUri, array $options = [])
    {
        if (!isset($options['format'])) {
            // EasyRDF should guess the format if none given.
            $options['format'] = 'guess';
        }
        if (!isset($options['label_property'])) {
            $options['label_property'] = 'skos:prefLabel|rdfs:label|foaf:name|rss:title|dc:title|dc11:title';
        }
        if (!isset($options['comment_property'])) {
            $options['comment_property'] = 'rdfs:comment';
        }
        if (!isset($options['lang'])) {
            $options['lang'] = null;
        }

        $graph = $this->getGraph($strategy, $namespaceUri, $options);

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
     * @see self::getMembers()
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
                    throw new ValidationException('No file specified for the file import strategy.');
                }
                $file = $options['file'];
                if (!is_readable($file)) {
                    throw new ValidationException('Could not read vocabulary file.');
                }
                return $this->readGraph(function (RdfGraph $graph, $format) use ($file, $namespaceUri) {
                    $graph->parseFile($file, $format, $namespaceUri);
                }, $format, 'Could not parse vocabulary file.');
            case 'url':
                // Import from a URL.
                if (!isset($options['url'])) {
                    throw new ValidationException('No URL specified for the URL import strategy.');
                }
                $url = $options['url'];
                return $this->readGraph(function (RdfGraph $graph, $format) use ($url) {
                    $graph->load($url, $format);
                }, $format, 'Could not load vocabulary from URL.');
            default:
                throw new ValidationException('Unsupported import strategy.');
        }
    }

    /**
     * Import an RDF vocabulary, including its classes and properties.
     *
     * The vocabulary stores the reading options its file was read with.
     *
     * @see self::getMembers()
     * @param string $strategy
     * @param array $vocab Vocab info supported by the vocabulary entity adapter
     * @param array $options
     * @return \Omeka\Api\Response
     */
    public function import($strategy, array $vocab, array $options = [])
    {
        $members = $this->getMembers($strategy, $vocab['o:namespace_uri'], $options);

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
            $exception = new ValidationException('No classes or properties found. Are you sure you used the correct namespace URI?');
            // Read the file again, which only an import that fails needs,
            // for the namespace its terms use.
            $graph = $this->getGraph($strategy, $vocab['o:namespace_uri'], $options);
            $suggestion = $this->suggestNamespaceUri($graph, $vocab['o:namespace_uri']);
            if (null !== $suggestion) {
                $exception->getErrorStore()->addError('o:namespace_uri', new Message(
                    'The file\'s classes and properties use the namespace URI "%s".', // @translate
                    $suggestion
                ));
            }
            throw $exception;
        }
        $this->checkTermLengths($members);

        return $this->apiManager->create('vocabularies', array_merge($vocab, $vocabMembers, [
            // How the file was read, to read a later version the same way.
            'o:import_options' => array_intersect_key($options, array_flip(self::READING_OPTIONS)),
        ]));
    }

    /**
     * Suggest the namespace URI of a vocabulary file's classes and
     * properties, for when they don't use the one given.
     *
     * Tries, in order: the other forms of the given namespace URI; the
     * namespace URI the file declares with vann:preferredNamespaceUri, and
     * its forms; the URI of its owl:Ontology, and its forms; and then the
     * namespace URI most of its terms use. A form ends in "#", "/", and uses
     * http or https.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri The namespace URI given
     * @return string|null The first that has classes or properties, if any
     */
    public function suggestNamespaceUri(RdfGraph $graph, $namespaceUri)
    {
        $vann = 'http://purl.org/vocab/vann/preferredNamespaceUri';
        $bases = [$namespaceUri];
        foreach ($graph->resourcesMatching($vann) as $resource) {
            foreach ($resource->all("<$vann>") as $declared) {
                $bases[] = (string) $declared;
            }
        }
        foreach ($graph->allOfType('owl:Ontology') as $ontology) {
            if (!$ontology->isBNode()) {
                $bases[] = $ontology->getUri();
            }
        }
        foreach ($bases as $base) {
            foreach ($this->getNamespaceUriForms($base) as $candidate) {
                if ($candidate !== $namespaceUri && $this->hasMembers($graph, $candidate)) {
                    return $candidate;
                }
            }
        }
        $mostUsed = $this->getMostUsedNamespaceUri($graph);
        if (null !== $mostUsed && $mostUsed !== $namespaceUri && $this->hasMembers($graph, $mostUsed)) {
            return $mostUsed;
        }
        return null;
    }

    /**
     * Update a vocabulary given a diff.
     *
     * A change to a term that no longer exists, or a new term that already
     * exists, is skipped.
     *
     * @param int $vocabId
     * @param array $diff
     * @throws ValidationException When a local name or label is longer than
     *   the database can store, before anything is written
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
        $classRepo = $em->getRepository('Omeka\Entity\ResourceClass');
        $propertyRepo = $em->getRepository('Omeka\Entity\Property');

        foreach ($diff['properties']['new'] as $localName => $info) {
            if ($propertyRepo->findOneBy(['vocabulary' => $vocabulary, 'localName' => $localName])) {
                continue;
            }
            $property = new Property;
            $property->setVocabulary($vocabulary);
            $property->setLocalName($localName);
            $property->setLabel($info[0]);
            $property->setComment($info[1]);
            $em->persist($property);
        }
        foreach ($diff['properties']['label'] as $localName => $change) {
            $property = $propertyRepo->findOneBy([
                'vocabulary' => $vocabulary,
                'localName' => $localName,
            ]);
            if ($property) {
                $property->setLabel($change[1]);
            }
        }
        foreach ($diff['properties']['comment'] as $localName => $change) {
            $property = $propertyRepo->findOneBy([
                'vocabulary' => $vocabulary,
                'localName' => $localName,
            ]);
            if ($property) {
                $property->setComment($change[1]);
            }
        }

        foreach ($diff['classes']['new'] as $localName => $info) {
            if ($classRepo->findOneBy(['vocabulary' => $vocabulary, 'localName' => $localName])) {
                continue;
            }
            $class = new ResourceClass;
            $class->setVocabulary($vocabulary);
            $class->setLocalName($localName);
            $class->setLabel($info[0]);
            $class->setComment($info[1]);
            $em->persist($class);
        }
        foreach ($diff['classes']['label'] as $localName => $change) {
            $class = $classRepo->findOneBy([
                'vocabulary' => $vocabulary,
                'localName' => $localName,
            ]);
            if ($class) {
                $class->setLabel($change[1]);
            }
        }
        foreach ($diff['classes']['comment'] as $localName => $change) {
            $class = $classRepo->findOneBy([
                'vocabulary' => $vocabulary,
                'localName' => $localName,
            ]);
            if ($class) {
                $class->setComment($change[1]);
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
     * @param array $options As for getMembers(), and:
     *   - vocabulary_id: (optional) The stored vocabulary to compare with,
     *     whatever its namespace URI. By default it's the one with the
     *     namespace URI given, so a diff can't show a change of namespace.
     *   A reading option not given is the stored vocabulary's, if it has one.
     * @return array
     */
    public function getDiff($strategy, $namespaceUri, array $options = [])
    {
        // Read the file as the stored vocabulary's was, where not told how.
        $vocabulary = $this->apiManager->search('vocabularies', isset($options['vocabulary_id'])
            ? ['id' => (int) $options['vocabulary_id']]
            : ['namespace_uri' => $namespaceUri])->getContent()[0] ?? null;
        if ($vocabulary) {
            $options += $vocabulary->importOptions() ?? [];
        }

        // Get classes and properties from the database. An id that isn't a
        // number would be ignored, and match every vocabulary's terms.
        $stored = $this->getStoredMembers(isset($options['vocabulary_id'])
            ? ['vocabulary_id' => (int) $options['vocabulary_id']]
            : ['vocabulary_namespace_uri' => $namespaceUri]);

        // Get classes and properties from the RDF graph.
        $members = $this->getMembers($strategy, $namespaceUri, $options);
        // A term too long for the database can only be new, or have a new
        // label, so refuse it now rather than on accepting the changes.
        $this->checkTermLengths($members);

        return [
            'classes' => $this->calculateDiff($stored['classes'], $members['classes']),
            'properties' => $this->calculateDiff($stored['properties'], $members['properties']),
        ];
    }

    /**
     * Get the classes and properties a vocabulary has in Omeka that a file
     * doesn't have.
     *
     * An update keeps them, so they're for showing what a file has dropped,
     * never for applying.
     *
     * @see self::getMembers()
     * @param int $vocabularyId
     * @param string $strategy
     * @param array $options As for getMembers(), and:
     *   - namespace_uri: (optional) The namespace URI to read the file with,
     *     if not the vocabulary's own
     * @return array ['classes' => [local name => label, …], 'properties' =>
     *   [local name => label, …]]
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
     * Get the classes and properties of a vocabulary in Omeka, as
     * getMembers() gets a file's.
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
     * Nor does it empty an existing value: a comment the file doesn't have,
     * or a label it doesn't have, which reads as the local name, keeps the
     * value Omeka has.
     *
     * Values that differ only in spacing, or are empty and missing, are the
     * same: see self::getComparableText().
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
                if ((string) $localName !== $info['label']
                    && $this->getComparableText($from[$localName]['label']) !== $this->getComparableText($info['label'])
                ) {
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
     * Get a label or comment as it's compared: each run of whitespace as one
     * space, none at either end, and a missing value as an empty one. Omeka's
     * pages show values that differ only so the same.
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
     * So when a guess fails or reads nothing, each format a user can choose
     * is tried, and the first that reads something is used. A format the
     * user chose is used alone.
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
        } catch (\EasyRdf\Http\Exception $e) {
            // The server refused the request, which another format won't
            // change.
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
                    // A format that can't read the file, however it fails.
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
     * Raise PHP's memory limit to self::MEMORY_LIMIT where it's lower. A
     * host that doesn't allow it keeps its limit.
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
     * Refuse classes and properties whose local names or labels are longer
     * than the database can store, naming them.
     *
     * @param array $members Classes and properties, as getMembers() returns
     *   them: local names keyed to their labels and comments
     * @throws ValidationException
     */
    protected function checkTermLengths(array $members)
    {
        $longLocalNames = [];
        $longLabels = [];
        foreach (['classes', 'properties'] as $type) {
            foreach ($members[$type] ?? [] as $localName => $member) {
                $localName = (string) $localName;
                // A local name too long to store is too long to read whole.
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
     * Get a namespace URI's forms: itself, then ending in "#" and in "/",
     * each with http and https.
     *
     * @param string $namespaceUri
     * @return string[]
     */
    protected function getNamespaceUriForms($namespaceUri)
    {
        $forms = [$namespaceUri];
        $base = rtrim($namespaceUri, '#/');
        foreach (['#', '/'] as $end) {
            $forms[] = $base . $end;
        }
        foreach ($forms as $form) {
            if (0 === strpos($form, 'http://')) {
                $forms[] = 'https://' . substr($form, 7);
            } elseif (0 === strpos($form, 'https://')) {
                $forms[] = 'http://' . substr($form, 8);
            }
        }
        return array_values(array_unique($forms));
    }

    /**
     * Check whether a graph has a class or property in a namespace, as
     * self::getMembersOfTypes() finds them.
     *
     * @param RdfGraph $graph
     * @param string $namespaceUri
     * @return bool
     */
    protected function hasMembers(RdfGraph $graph, $namespaceUri)
    {
        foreach (array_merge($this->classTypes, $this->propertyTypes) as $type) {
            foreach ($graph->allOfType($type) as $resource) {
                if ($resource->getUri() === $namespaceUri . $resource->localName()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get the namespace URI most of a graph's classes and properties use:
     * each one's URI without its local name.
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
                // Only http and https URIs: without a base, a file's
                // relative URIs resolve against its path on the server.
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
     * it defaults to the first available label, if any. Blank labels are
     * skipped.
     *
     * @param RdfResource $resource
     * @param string $labelProperty
     * @param string $lang
     * @param string $default
     * @return string
     */
    protected function getLabel(RdfResource $resource, $labelProperty, $lang, $default)
    {
        return $this->getText($resource, $labelProperty, $lang) ?? $default;
    }

    /**
     * Get the comment from an RDF resource.
     *
     * Attempts to get the comment of the passed language. If one does not exist
     * it defaults to the first available comment, if any. Blank comments are
     * skipped.
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
     * Get the text of an RDF resource's first literal for a property that
     * isn't blank, in the passed language if there is one, else in any.
     *
     * Files often leave a blank literal as a placeholder for a translation,
     * so a blank in the passed language gives way to another language's
     * text.
     *
     * @param RdfResource $resource
     * @param string $property A property, or alternatives as "a|b"
     * @param string|null $lang
     * @return string|null
     */
    protected function getText(RdfResource $resource, $property, $lang)
    {
        $literals = $lang ? $resource->all($property, 'literal', $lang) : [];
        foreach (array_merge($literals, $resource->all($property, 'literal')) as $literal) {
            // The literal's text: a typed literal's value is a number, a
            // boolean, or a date.
            $text = (string) $literal;
            if ('' !== trim($text)) {
                return $text;
            }
        }
        return null;
    }
}
