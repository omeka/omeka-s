<?php
namespace Omeka\Controller\Admin;

use Doctrine\ORM\EntityManager;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Omeka\View\Model\ApiJsonModel;

class ValueController extends AbstractRestfulController
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function browseAction()
    {
        $maxResults = 10;

        $query = $this->params()->fromQuery();
        $q = $query['q'] ?? '';
        if (!$q) {
            return new ApiJsonModel([
                'status' => 'success',
                'data' => [
                    'results' => [],
                ],
            ]);
        }

        $qq = isset($query['type']) && $query['type'] === 'in'
             ? '%' . addcslashes($q, '%_') . '%'
             : addcslashes($q, '%_') . '%';

         $property = isset($query['prop']) ? (int) $query['prop'] : null;

        $qb = $this->entityManager->createQueryBuilder();
        $expr = $qb->expr();
        $qb
            ->select('DISTINCT omeka_root.value')
            ->from(\Omeka\Entity\Value::class, 'omeka_root')
            ->where($expr->like('omeka_root.value', ':qq'))
            ->setParameter('qq', $qq)
            ->groupBy('omeka_root.value')
            ->orderBy('omeka_root.value', 'ASC')
            ->setMaxResults($maxResults);
        if ($property) {
            $qb
                ->andWhere($expr->eq('omeka_root.property', ':prop'))
                ->setParameter('prop', $property);
        }
        $result = $qb->getQuery()->getScalarResult();

        // Output for jQuery Autocomplete.
        // @see https://www.devbridge.com/sourcery/components/jquery-autocomplete
        $result = array_map('trim', array_column($result, 'value'));
        return new ApiJsonModel([
            'suggestions' => $result,
        ]);
    }
}
