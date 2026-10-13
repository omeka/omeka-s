<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Exception;
use Omeka\Api\Representation\ValueAnnotationRepresentation;
use Omeka\Api\Request;
use Omeka\Entity\ValueAnnotation;

class ValueAnnotationAdapter extends AbstractResourceEntityAdapter
{
    protected $sortFields = [
        'id' => 'id',
        'is_public' => 'isPublic',
        'created' => 'created',
        'modified' => 'modified',
    ];

    public function getResourceName()
    {
        return 'value_annotations';
    }

    public function getRepresentationClass()
    {
        return ValueAnnotationRepresentation::class;
    }

    public function getEntityClass()
    {
        return ValueAnnotation::class;
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        parent::buildQuery($qb, $query);

        // The visibility filter only checks the annotation's own public flag, not
        // the parent resource's. Join through Value to check both.
        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');
        if ($acl->userIsAllowed('Omeka\Entity\Resource', 'view-all')) {
            return;
        }

        $identity = $services->get('Omeka\AuthenticationService')->getIdentity();
        $valueAlias = $qb->createAlias();
        $parentResourceAlias = $qb->createAlias();

        $qb->join(
            'Omeka\Entity\Value',
            $valueAlias,
            'WITH',
            $qb->expr()->eq("$valueAlias.valueAnnotation", 'omeka_root')
        );
        $qb->join("$valueAlias.resource", $parentResourceAlias);

        $parentVisibilityExpr = $qb->expr()->eq(
            "$parentResourceAlias.isPublic",
            $qb->createNamedParameter(true)
        );
        if ($identity) {
            $parentVisibilityExpr = $qb->expr()->orX(
                $parentVisibilityExpr,
                $qb->expr()->eq(
                    "$parentResourceAlias.owner",
                    $qb->createNamedParameter($identity)
                )
            );
        }
        $qb->andWhere($parentVisibilityExpr);
    }

    public function read(Request $request)
    {
        // The visibility filter doesn't catch a public annotation on a private
        // resource, so verify the parent explicitly. Return 404 rather than 403
        // to avoid revealing that the resource exists.
        $response = parent::read($request);

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        if ($acl->userIsAllowed('Omeka\Entity\Resource', 'view-all')) {
            return $response;
        }

        // The ResourceVisibilityFilter applies to lazy-loaded associations, so
        // if the parent resource is not visible to this user, getResource() returns null.
        $entity = $response->getContent();
        if (!$entity->getValue()->getResource()) {
            throw new Exception\NotFoundException(sprintf(
                $this->getTranslator()->translate('%1$s entity with criteria %2$s not found'),
                $this->getEntityClass(),
                json_encode(['id' => $entity->getId()])
            ));
        }

        return $response;
    }

    public function create(Request $request)
    {
        return AbstractAdapter::create($request);
    }

    public function update(Request $request)
    {
        return AbstractAdapter::update($request);
    }

    public function delete(Request $request)
    {
        return AbstractAdapter::delete($request);
    }
}
