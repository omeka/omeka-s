Repository
==========

When your spatial entity is created, you can add new methods to your repositories. This section will explain you how to
add new methods to a standard repository.

In this example, we assume that a building entity was already created. The building entity owns a spatial property to
store polygon. We assume that the entity is named ``building`` and that the spatial property is name ``plan`` which is a
``polygon`` type.

.. code-block:: php

    <?php

    namespace App\Repository;

    use App\Entity\Building; // This is our spatial entity
    use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
    use Doctrine\Persistence\ManagerRegistry;

    /**
     * Building repository.
     *
     * These methods inherits from ServiceEntityRepository
     *
     * @method Building|null find($id, $lockMode = null, $lockVersion = null)
     * @method Building|null findOneBy(array $criteria, array $orderBy = null)
     * @method Building[]    findAll()
     * @method Building[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
     */
    class BuildingRepository extends ServiceEntityRepository
    {
        /**
         * BuildingRepository constructor.
         *
         * The repository constructor of a spatial entity is strictly identic to other repositories.
         *
         * @param ManagerRegistry $registry injected by dependency injection
         */
        public function __construct(ManagerRegistry $registry)
        {
            parent::__construct($registry, Building::class);
        }

        // ...

        /**
         * Find building that have an area between min and max .
         *
         * @param float $min the minimum accepted area
         * @param float $max the maximum accepted area
         *
         * @return Building[]
         */
        public function findAreaBetween(float $min, float $max): array
        {
            //The query builder is normally retrieved
            $queryBuilder = $this->createQueryBuilder('b');

            //We assume that the ST_AREA has been declared in configuration
            return $queryBuilder->where('ST_AREA(b.plan) BETWEEN :min AND :max')
                ->setParameter('min', $min, 'float')
                ->setParameter('max', $max, 'float')
                ->getQuery()
                ->getResult()
            ;
        }
