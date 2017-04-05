<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;

class VocabularyAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = [
        'id' => 'id',
        'namespace_uri' => 'namespaceUri',
        'prefix' => 'prefix',
        'label' => 'label',
        'comment' => 'comment',
    ];

    /**
     * @var array Reserved vocabulary prefixes
     */
    protected $reservedPrefixes = [
        // Omeka and module prefixes
        '^o$', '^o-',
        // Prefixes introduced in core code
        '^time$', '^cnt$',
    ];

    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'vocabularies';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\VocabularyRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Entity\Vocabulary';
    }

    /**
     * {@inheritDoc}
     */
    public function sortQuery(QueryBuilder $qb, array $query)
    {
        if (is_string($query['sort_by'])) {
            if ('property_count' == $query['sort_by']) {
                $this->sortByCount($qb, $query, 'properties');
            } elseif ('resource_class_count' == $query['sort_by']) {
                $this->sortByCount($qb, $query, 'resourceClasses');
            } else {
                parent::sortQuery($qb, $query);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (array_key_exists('o:class', $data)
            && !is_array($data['o:class'])
        ) {
            $errorStore->addError('o:item_set', 'Classes must be an array'); // @translate
        }

        if (array_key_exists('o:property', $data)
            && !is_array($data['o:property'])
        ) {
            $errorStore->addError('o:item_set', 'Properties must be an array'); // @translate
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $this->hydrateOwner($request, $entity);

        if ($this->shouldHydrate($request, 'o:namespace_uri')) {
            $entity->setNamespaceUri($request->getValue('o:namespace_uri'));
        }
        if ($this->shouldHydrate($request, 'o:prefix')) {
            $entity->setPrefix($request->getValue('o:prefix'));
        }
        if ($this->shouldHydrate($request, 'o:label')) {
            $entity->setLabel($request->getValue('o:label'));
        }
        if ($this->shouldHydrate($request, 'o:comment')) {
            $entity->setComment($request->getValue('o:comment'));
        }

        if ($this->shouldHydrate($request, 'o:class')) {
            $classesData = $request->getValue('o:class', []);
            $adapter = $this->getAdapter('resource_classes');
            $class = $adapter->getEntityClass();
            $retainResourceClasses = [];
            $retainResourceClassIds = [];
            foreach ($classesData as $classData) {
                if (isset($classData['o:id'])) {
                    // Do not update existing resource classes.
                    $retainResourceClassIds[] = $classData['o:id'];
                } else {
                    // Create a new resource class.
                    $resourceClass = new $class;
                    $resourceClass->setVocabulary($entity);
                    $subrequest = new Request(Request::CREATE, 'resource_classes');
                    $subrequest->setContent($classData);
                    $adapter->hydrateEntity($subrequest, $resourceClass, $errorStore);
                    $entity->getResourceClasses()->add($resourceClass);
                    $retainResourceClasses[] = $resourceClass;
                }
            }
            // Remove resource classes not included in request.
            foreach ($entity->getResourceClasses() as $resourceClass) {
                if (!in_array($resourceClass, $retainResourceClasses, true)
                    && !in_array($resourceClass->getId(), $retainResourceClassIds)
                ) {
                    $entity->getResourceClasses()->removeElement($resourceClass);
                }
            }
        }

        if ($this->shouldHydrate($request, 'o:property')) {
            $propertiesData = $request->getValue('o:property', []);
            $adapter = $this->getAdapter('properties');
            $class = $adapter->getEntityClass();
            $retainProperties = [];
            $retainPropertyIds = [];
            foreach ($propertiesData as $propertyData) {
                if (isset($propertyData['o:id'])) {
                    // Do not update existing properties.
                    $retainPropertyIds[] = $propertyData['o:id'];
                } else {
                    // Create a new property.
                    $property = new $class;
                    $property->setVocabulary($entity);
                    $subrequest = new Request(Request::CREATE, 'properties');
                    $subrequest->setContent($propertyData);
                    $adapter->hydrateEntity($subrequest, $property, $errorStore);
                    $entity->getProperties()->add($property);
                    $retainProperties[] = $property;
                }
            }
            // Remove resource classes not included in request.
            foreach ($entity->getProperties() as $property) {
                if (!in_array($property, $retainProperties, true)
                    && !in_array($property->getId(), $retainPropertyIds)
                ) {
                    $entity->getProperties()->removeElement($property);
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['owner_id'])) {
            $userAlias = $this->createAlias();
            $qb->innerJoin(
                'Omeka\Entity\Vocabulary.owner',
                $userAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$userAlias.id",
                $this->createNamedParameter($qb, $query['owner_id']))
            );
        }
        if (isset($query['namespace_uri'])) {
            $qb->andWhere($qb->expr()->eq(
                "Omeka\Entity\Vocabulary.namespaceUri",
                $this->createNamedParameter($qb, $query['namespace_uri']))
            );
        }
        if (isset($query['prefix'])) {
            $qb->andWhere($qb->expr()->eq(
                "Omeka\Entity\Vocabulary.prefix",
                $this->createNamedParameter($qb, $query['prefix']))
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        // Validate namespace URI
        $namespaceUri = $entity->getNamespaceUri();
        if (false == $entity->getNamespaceUri()) {
            $errorStore->addError('o:namespace_uri', 'The namespace URI cannot be empty.'); // @translate
        }
        if (!$this->isUnique($entity, ['namespaceUri' => $namespaceUri])) {
            $errorStore->addError('o:namespace_uri', new Message(
                'The namespace URI "%s" is already taken.', // @translate
                $namespaceUri
            ));
        }

        // Validate prefix
        $prefix = $entity->getPrefix();
        if (false == $entity->getPrefix()) {
            $errorStore->addError('o:prefix', 'The prefix cannot be empty.'); // @translate
        }
        if (!$this->isUnique($entity, ['prefix' => $prefix])) {
            $errorStore->addError('o:prefix', new Message(
                'The prefix "%s" is already taken.', // @translate
                $prefix
            ));
        }
        foreach ($this->reservedPrefixes as $reservedPrefix) {
            if (preg_match("/$reservedPrefix/", $prefix)) {
                $errorStore->addError('o:prefix', new Message(
                    'The prefix "%s" is reserved.', // @translate
                    $prefix
                ));
                break;
            }
        }

        // Validate label
        if (false == $entity->getLabel()) {
            $errorStore->addError('o:label', 'The label cannot be empty.'); // @translate
        }

        // Check for uniqueness of resource class local names.
        $uniqueLocalNames = [];
        foreach ($entity->getResourceClasses() as $resourceClass) {
            if (in_array($resourceClass->getLocalName(), $uniqueLocalNames)) {
                $errorStore->addError('o:resource_class', new Message(
                    'The local name "%s" is already taken.', // @translate
                    $resourceClass->getLocalName()
                ));
            } else {
                $uniqueLocalNames[] = $resourceClass->getLocalName();
            }
        }

        // Check for uniqueness of property local names.
        $uniqueLocalNames = [];
        foreach ($entity->getProperties() as $property) {
            if (in_array($property->getLocalName(), $uniqueLocalNames)) {
                $errorStore->addError('o:resource_class', new Message(
                    'The local name "%s" is already taken.', // @translate
                    $property->getLocalName()
                ));
            } else {
                $uniqueLocalNames[] = $property->getLocalName();
            }
        }
    }
}
