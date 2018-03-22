<?php
namespace Omeka\Stdlib;

use Doctrine\ORM\EntityManager;
use EasyRdf_Graph;
use EasyRdf_Literal;
use EasyRdf_Resource;
use Omeka\Api\Exception\ValidationException;
use Omeka\Api\Manager as ApiManager;
use Omeka\Entity\Property;
use Omeka\Entity\ResourceClass;
use Omeka\Entity\Vocabulary;

class RdfImporter
{
    /**
     * @var Omeka\Api\Manager
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
     *   - comment_property: (optional) The RDF property containing the preferred
     *     property comment (defaults to "rdfs:comment")
     * @return array
     */
    public function getMembers($strategy, $namespaceUri, array $options = [])
    {
        if (!isset($options['format'])) {
            // EasyRDF should guess the format if none given.
            $options['format'] = 'guess';
        }
        if (!isset($options['comment_property'])) {
            $options['comment_property'] = 'rdfs:comment';
        }

        // Get the full RDF graph from EasyRdf.
        $graph = new EasyRdf_Graph;
        switch ($strategy) {
            case 'file':
                // Import from a file
                if (!isset($options['file'])) {
                    throw new ValidationException('No file specified for the file import strategy.');
                }
                $file = $options['file'];
                if (!is_readable($file)) {
                    throw new ValidationException('File not readable.');
                }
                $graph->parseFile($file, $options['format'], $namespaceUri);
                break;
            case 'url':
                // Import from a URL.
                if (!isset($options['url'])) {
                    throw new ValidationException('No URL specified for the URL import strategy.');
                }
                $graph->load($options['url'], $options['format']);
                break;
            default:
                throw new ValidationException('Unsupported import strategy.');
        }

        $members = ['classes' => [], 'properties' => []];

        // Iterate through all resources of the graph instead of selectively by
        // rdf:type becuase a resource may have more than one type, causing
        // illegal attempts to duplicate classes and properties.
        foreach ($graph->resources() as $resource) {
            // The resource must not be a blank node.
            if ($resource->isBnode()) {
                continue;
            }
            // The resource must be a local member of the vocabulary.
            if (!$this->isMember($resource, $namespaceUri)) {
                continue;
            }
            // Get the vocabulary's classes.
            if (in_array($resource->type(), $this->classTypes)) {
                $members['classes'][$resource->localName()] = [
                    'label' => $this->getLabel($resource, $resource->localName()),
                    'comment' => $this->getComment($resource, $options['comment_property']),
                ];
            }
            // Get the vocabulary's properties.
            if (in_array($resource->type(), $this->propertyTypes)) {
                $members['properties'][$resource->localName()] = [
                    'label' => $this->getLabel($resource, $resource->localName()),
                    'comment' => $this->getComment($resource, $options['comment_property']),
                ];
            }
        }
        return $members;
    }

    /**
     * Import an RDF vocabulary, including its classes and properties.
     *
     * @see self::getMembers()
     * @param string $strategy
     * @param array $vocab Vocab info supported by the vocabulary entity adapter
     * @param array $options
     * @return Omeka\Api\Response
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
     * Determine whether a resource is a local member of the vocabulary.
     *
     * @param EasyRdf_Resource $resource
     * @param string $namespaceUri
     */
    protected function isMember(EasyRdf_Resource $resource, $namespaceUri)
    {
        $output = strncmp($resource->getUri(), $namespaceUri, strlen($namespaceUri));
        return $output === 0;
    }

    /**
     * Get the label from an RDF resource.
     *
     * @param EasyRdf_Resource $resource
     * @param string $default
     * @return string
     */
    protected function getLabel(EasyRdf_Resource $resource, $default)
    {
        $label = $resource->label();
        if ($label instanceof EasyRdf_Literal) {
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
     * @param EasyRdf_Resource $resource
     * @param array $data
     * @return string
     */
    protected function getComment(EasyRdf_Resource $resource, $commentProperty)
    {
        $comment = $resource->get($commentProperty);
        if ($comment instanceof EasyRdf_Literal) {
            return $comment->getValue();
        }
    }
}
