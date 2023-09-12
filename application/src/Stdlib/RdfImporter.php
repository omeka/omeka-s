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
     *     parser will attempt to guess the format.
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

        // Get the full RDF graph from EasyRdf.
        $graph = new RdfGraph;
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
                try {
                    $graph->parseFile($file, $options['format'], $namespaceUri);
                } catch (\EasyRdf\Exception $e) {
                    throw new ValidationException('Could not parse vocabulary file.');
                }
                break;
            case 'url':
                // Import from a URL.
                if (!isset($options['url'])) {
                    throw new ValidationException('No URL specified for the URL import strategy.');
                }
                try {
                    $graph->load($options['url'], $options['format']);
                } catch (\EasyRdf\Exception $e) {
                    throw new ValidationException('Could not load vocabulary from URL.');
                }
                break;
            default:
                throw new ValidationException('Unsupported import strategy.');
        }

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
            throw new ValidationException('No classes or properties found. Are you sure you used the correct namespace URI?');
        }

        return $this->apiManager->create('vocabularies', array_merge($vocab, $vocabMembers));
    }

    /**
     * Update a vocabulary given a diff.
     *
     * @param int $vocabId
     * @param array $diff
     */
    public function update($vocabId, array $diff)
    {
        $em = $this->entityManager;
        $vocabulary = $em->find('Omeka\Entity\Vocabulary', $vocabId);
        $classRepo = $em->getRepository('Omeka\Entity\ResourceClass');
        $propertyRepo = $em->getRepository('Omeka\Entity\Property');

        foreach ($diff['properties']['new'] as $localName => $info) {
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
            $property->setLabel($change[1]);
        }
        foreach ($diff['properties']['comment'] as $localName => $change) {
            $property = $propertyRepo->findOneBy([
                'vocabulary' => $vocabulary,
                'localName' => $localName,
            ]);
            $property->setComment($change[1]);
        }

        foreach ($diff['classes']['new'] as $localName => $info) {
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
            $class->setLabel($change[1]);
        }
        foreach ($diff['classes']['comment'] as $localName => $change) {
            $class = $classRepo->findOneBy([
                'vocabulary' => $vocabulary,
                'localName' => $localName,
            ]);
            $class->setComment($change[1]);
        }

        $em->flush();
    }

    /**
     * Get the diff between a stored vocab and one represented in an RDF graph.
     *
     * @see self::getMembers()
     * @param string $strategy
     * @param string $namespaceUri
     * @param array $options
     * @return array
     */
    public function getDiff($strategy, $namespaceUri, array $options = [])
    {
        // Get classes and properties from the database.
        $classes = $this->apiManager->search(
            'resource_classes',
            ['vocabulary_namespace_uri' => $namespaceUri]
        )->getContent();
        $dbClasses = [];
        foreach ($classes as $class) {
            $dbClasses[$class->localName()] = [
                'label' => $class->label(),
                'comment' => $class->comment(),
            ];
        }
        $properties = $this->apiManager->search(
            'properties',
            ['vocabulary_namespace_uri' => $namespaceUri]
        )->getContent();
        $dbProperties = [];
        foreach ($properties as $property) {
            $dbProperties[$property->localName()] = [
                'label' => $property->label(),
                'comment' => $property->comment(),
            ];
        }

        // Get classes and properties from the RDF graph.
        $members = $this->getMembers($strategy, $namespaceUri, $options);

        return [
            'classes' => $this->calculateDiff($dbClasses, $members['classes']),
            'properties' => $this->calculateDiff($dbProperties, $members['properties']),
        ];
    }

    /**
     * Calculate the difference between two vocabulary members.
     *
     * Only gets the diffs that we can process safely: add new members and
     * update existing labels and comments. It doesn't get the diffs we cannot
     * process safely: delete existing members or update existing local names.
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
                if ($from[$localName]['label'] !== $info['label']) {
                    // Updated label.
                    $diff['label'][$localName] = [$from[$localName]['label'], $info['label']];
                }
                if ($from[$localName]['comment'] !== $info['comment']) {
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
     * it defaults to the first available label, if any.
     *
     * @param RdfResource $resource
     * @param string $labelProperty
     * @param string $lang
     * @param string $default
     * @return string
     */
    protected function getLabel(RdfResource $resource, $labelProperty, $lang, $default)
    {
        $label = $resource->get($labelProperty, 'literal', $lang) ?: $resource->get($labelProperty, 'literal');
        if ($label) {
            $value = $label->getValue();
            if ('' !== $value) {
                return $value;
            }
        }
        return $default;
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
     * @return string
     */
    protected function getComment(RdfResource $resource, $commentProperty, $lang)
    {
        $comment = $resource->get($commentProperty, 'literal', $lang) ?: $resource->get($commentProperty, 'literal');
        if ($comment) {
            return $comment->getValue();
        }
    }
}
