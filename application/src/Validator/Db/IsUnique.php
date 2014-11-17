<?php
namespace Omeka\Validator\Db;

use Doctrine\ORM\EntityManager;
use Omeka\Model\Entity\EntityInterface;
use Zend\Validator\AbstractValidator;

/**
 * Check whether a value of an entity field is unique.
 */
class IsUnique extends AbstractValidator
{
    const NOT_UNIQUE = 'notUnique';
    const INVALID_ENTITY = 'invalidEntity';
    const INVALID_FIELD = 'invalidField';

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var string
     */
    protected $values;

    /**
     * @var EntityInterface
     */
    protected $entity;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $messageVariables = array(
        'values' => 'values',
    );

    /**
     * @var array
     */
    protected $messageTemplates = array(
        self::NOT_UNIQUE => 'The value "%values%" is not unique for the "%value%" field.',
        self::INVALID_ENTITY => 'Invalid entity "%value%" passed to IsUnique validator.',
        self::INVALID_FIELD => 'Invalid field "%value%" passed to IsUnique validator.',
    );

    /**
     * @param array $fields The entity fields to check for uniqueness
     * @param EntityManager $entityManager
     */
    public function __construct(array $fields, EntityManager $entityManager)
    {
        $this->fields = array_values($fields);
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    /**
     * Check whether a value of an entity field is unique.
     *
     * @param EntityInterface $entity
     * @return bool
     */
    public function isValid($entity)
    {
        if (!$entity instanceof EntityInterface) {
            $this->error(self::INVALID_ENTITY, get_class($entity));
            return false;
        }

        $classMetadata = $this->entityManager->getClassMetadata(get_class($entity));

        // Check the passed fields exist as entity field names or association
        // mapping names.
        foreach ($this->fields as $field) {
            if (!in_array($field, $classMetadata->fieldNames)
                && !array_key_exists($field, $classMetadata->associationMappings)) {
                $this->error(self::INVALID_FIELD, $field);
                return false;
            }
        }

        // Check uniqueness on an entity that is not yet persistent.
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('1')->from($classMetadata->name, 'entity');
        $values = array();
        foreach ($this->fields as $alias => $field) {

            $getField = 'get' . ucfirst($field);
            $qb->andWhere($qb->expr()->eq("entity.$field", "?$alias"));

            // Build a condition for a field.
            if (in_array($field, $classMetadata->fieldNames)) {
                if (is_null($entity->$getField())) {
                    // A null field always indicates uniqueness because null is
                    // never equal to anything, not even another null value.
                    return true;
                } else {
                    $qb->setParameter($alias, $entity->$getField());
                    $values[$alias] = $entity->$getField();
                }

            // Build a condition for an association mapping.
            } else {
                if ($entity->$getField() instanceof EntityInterface) {
                    $qb->setParameter($alias, $entity->$getField()->getId());
                    $values[$alias] = $entity->$getField()->getId();
                } else {
                    // An association that does not resolve to an entity
                    // is null and always indicates uniqueness.
                    return true;
                }
            }
        }
        $this->values = implode(', ', $values);

        // Check uniqueness on a persistent entity.
        if (null !== $entity->getId()) {
            $qb->andWhere($qb->expr()->neq('entity.id', ':id'))
               ->setParameter('id', $entity->getId());
        }

        if ($qb->getQuery()->getOneOrNullResult()) {
            $this->error(self::NOT_UNIQUE, implode(', ', $this->fields));
            return false;
        }
        return true;
    }
}
