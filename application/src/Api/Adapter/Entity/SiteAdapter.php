<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Zend\Validator\EmailAddress;
use Omeka\Api\Request;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class SiteAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'sites';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\Entity\SiteRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\Site';
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();

        if (isset($data['o:slug'])) {
            $entity->setSlug($data['o:slug']);
        }
        if (isset($data['o:theme'])) {
            $entity->setTheme($data['o:theme']);
        }
        if (isset($data['o:title'])) {
            $entity->setTitle($data['o:title']);
        }
        if (isset($data['o:navigation'])) {
            $entity->setNavigation($data['o:navigation']);
        }
        if (isset($data['o:owner']['o:id'])) {
            $owner = $this->getAdapter('users')
                ->findEntity($data['o:owner']['o:id']);
            $entity->setOwner($owner);
        } else {
            $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
            $currentUser = $auth->getIdentity();
            $entity->setOwner($currentUser);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        $slug = $entity->getSlug();
        if (!is_string($slug) || $slug === '') {
            $errorStore->addError('o:slug', 'The slug cannot be empty.');
        }
        if (preg_match('/[^a-zA-Z0-9\/-]/u', $slug)) {
            $errorStore->addError('o:slug',
                'A slug can only contain letters, numbers, slashes, and hyphens.');
        }
        if (!$this->isUnique($entity, array('slug' => $slug))) {
            $errorStore->addError('o:slug', sprintf(
                'The slug "%s" is already taken.',
                $slug
            ));
        }

        if (empty($entity->getTitle())) {
            $errorStore->addError('o:title', 'A site must have a title.');
        }

        if (empty($entity->getTheme())) {
            $errorStore->addError('o:theme', 'A site must have a theme.');
        }

        if (!is_array($entity->getNavigation())) {
            $errorStore->addError('o:navigation', 'A site must have navigation data.');
        }
    }
}
