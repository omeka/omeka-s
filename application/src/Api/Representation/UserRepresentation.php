<?php
namespace Omeka\Api\Representation;

class UserRepresentation extends AbstractEntityRepresentation
{
    public function getControllerName()
    {
        return 'user';
    }

    public function getJsonLdType()
    {
        return 'o:User';
    }

    public function getJsonLd()
    {
        $entity = $this->resource;
        return [
            'o:name' => $entity->getName(),
            'o:email' => $entity->getEmail(),
            'o:created' => $this->getDateTime($entity->getCreated()),
            'o:role' => $entity->getRole(),
            'o:is_active' => $entity->isActive(),
        ];
    }

    public function name()
    {
        return $this->resource->getName();
    }

    public function email()
    {
        return $this->resource->getEmail();
    }

    public function role()
    {
        return $this->resource->getRole();
    }

    public function isActive()
    {
        return $this->resource->isActive();
    }

    public function created()
    {
        return $this->resource->getCreated();
    }

    public function getEntity()
    {
        return $this->resource;
    }

    public function sites()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $sites = $api
            ->search('sites', ['owner_id' => $this->id()])->getContent();
        return $sites;
    }

    public function sitePermissions()
    {
        $sitePermissions = [];
        $services = $this->getServiceLocator();
        $entityManager = $services->get('Omeka\EntityManager');
        $sitePermissionRepository = $entityManager->getRepository(\Omeka\Entity\SitePermission::class);
        $sitePermissionEntities = $sitePermissionRepository
            ->findBy(['user' => $this->id()]);
        foreach ($sitePermissionEntities as $sitePermissionEntity) {
            $sitePermissions[] = new SitePermissionRepresentation(
                $sitePermissionEntity, $services);
        }
        return $sitePermissions;
    }

    public function displayRole()
    {
        $roleIndex = $this->resource->getRole();
        $roleLabels = $this->getServiceLocator()->get('Omeka\Acl')->getRoleLabels();
        if (isset($roleLabels[$roleIndex])) {
            return $roleLabels[$roleIndex];
        }
        return $roleIndex;
    }

    /**
     * Get the item count for this user.
     *
     * @return int
     */
    public function itemCount()
    {
        $response = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('items', [
                'owner_id' => $this->id(),
                'limit' => 0,
            ]);
        return $response->getTotalResults();
    }

    /**
     * Get the item set count for this user.
     *
     * @return int
     */
    public function itemSetCount()
    {
        $response = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('item_sets', [
                'owner_id' => $this->id(),
                'limit' => 0,
            ]);
        return $response->getTotalResults();
    }
}
