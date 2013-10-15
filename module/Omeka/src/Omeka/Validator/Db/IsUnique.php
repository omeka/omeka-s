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
     * @var string
     */
    protected $field;

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
        'field' => 'field',
        'value' => 'value',
    );

    /**
     * @var array
     */
    protected $messageTemplates = array(
        self::NOT_UNIQUE => 'The value "%value%" is not unique for the "%field%" field.',
        self::INVALID_ENTITY => 'Invalid entity "%value%" passed to IsUnique validator.',
        self::INVALID_FIELD => 'Invalid field "%value%" passed to IsUnique validator.',
    );

    /**
     * @param string $field The entity field to check for uniqueness
     * @param EntityManager $entityManager
     */
    public function __construct($field, EntityManager $entityManager)
    {
        $this->field = $field;
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
        // Check whether the passed field belongs to the passed entity. This
        // prevents SQL injection of malicious user data.
        if (!in_array($this->field, $classMetadata->fieldNames)) {
            $this->error(self::INVALID_FIELD, $this->field);
            return false;
        }

        // Set the value as the value of the specified field of the entity. The
        // field must have a corresponding get*() method.
        $getField = 'get' . ucfirst($this->field);
        $value = $entity->$getField();
        $this->setValue($value);

        $qb = $this->entityManager->createQueryBuilder();
        // Check uniqueness on an entity that is not yet persistent. In this
        // case, a value is unique if no entity has the specified field equal
        // to the assigned value.
        $qb->select('1')
           ->from($classMetadata->name, 'entity')
           ->where($qb->expr()->eq("entity.{$this->field}", ':value'))
           ->setParameter('value', $value);

        // Check uniqueness on a persistent entity. In this case, a value is
        // unique if no entity, other than the persistent entity itself, has the
        // specified field equal to the assigned value.
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
