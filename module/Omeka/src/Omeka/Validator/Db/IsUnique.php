<?php
namespace Omeka\Validator\Db;

use Doctrine\ORM\EntityManager;
use Omeka\Model\Entity\EntityInterface;
use Zend\Validator\AbstractValidator;

/**
 * Check whether a value of a property is unique.
 */
class IsUnique extends AbstractValidator
{
    const NOT_UNIQUE = 'notUnique';
    const INVALID_ENTITY = 'invalidEntity';

    /**
     * @var string
     */
    protected $property;

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
        'property' => 'property',
        'value' => 'value',
    );

    /**
     * @var array
     */
    protected $messageTemplates = array(
        self::NOT_UNIQUE => 'The value "%value%" is not unique for the "%property%" property.',
        self::INVALID_ENTITY => 'Invalid entity passed to IsUnique validator.',
    );

    /**
     * @param string $property The property to check for uniqueness
     * @param EntityManager $entityManager
     */
    public function __construct($property, EntityManager $entityManager)
    {
        $this->property = $property;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    /**
     * Check whether a value of a property is unique.
     *
     * @param EntityInterface $entity
     * @return bool
     */
    public function isValid($entity)
    {
        if (!$entity instanceof EntityInterface) {
            $this->error(self::INVALID_ENTITY);
            return false;
        }

        // Set the value as the value of the specified property of the entity.
        $getProperty = 'get' . ucfirst($this->property);
        $value = $entity->$getProperty();
        $this->setValue($value);

        // Get the fully qualified class name of the entity.
        $entityClass = $this->entityManager
            ->getClassMetadata(get_class($entity))
            ->name;

        // Check uniqueness on an entity that is not yet persistent. In this
        // case, a value is unique if no entity has the specified property equal
        // to the assigned value.
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('1')
           ->from($entityClass, 'entity')
           ->where($qb->expr()->eq("entity.{$this->property}", ':value'))
           ->setParameter('value', $value);

        // Check uniqueness on a persistent entity. In this case, a value is
        // unique if no entity, other than the persistent entity itself, has the
        // specified property equal to the assigned value.
        if (null !== $entity->getId()) {
            $qb->andWhere($qb->expr()->neq('entity.id', ':id'))
               ->setParameter('id', $entity->getId());
        }

        if ($qb->getQuery()->getOneOrNullResult()) {
            $this->error(self::NOT_UNIQUE);
            return false;
        }
        return true;
    }
}
