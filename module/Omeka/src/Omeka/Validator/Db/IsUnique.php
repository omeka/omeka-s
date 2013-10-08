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
    );

    /**
     * @var array
     */
    protected $messageTemplates = array(
        self::NOT_UNIQUE => 'The "%property%" property is not unique.',
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
        if (null === $entity->getId()) {
            $dql = "SELECT 1 FROM $entityClass entity "
                 . "WHERE entity.{$this->property} = :value ";
            $query = $this->entityManager->createQuery($dql);
            $query->setParameter('value', $value);
        // Check uniqueness on a persistent entity. In this case, a value is
        // unique if no entity, other than the persistent entity itself, has the
        // specified property equal to the assigned value.
        } else {
            $dql = "SELECT 1 FROM $entityClass entity "
                 . "WHERE entity.{$this->property} = :value "
                 . 'AND entity.id != :id';
            $query = $this->entityManager->createQuery($dql);
            $query->setParameter('value', $value);
            $query->setParameter('id', $entity->getId());
        }

        if ($query->getOneOrNullResult()) {
            $this->error(self::NOT_UNIQUE);
            return false;
        }
        return true;
    }
}
