<?php
namespace Collecting\Api\Adapter;

use Collecting\Entity\CollectingInput;
use Collecting\Entity\CollectingUser;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class CollectingItemAdapter extends AbstractEntityAdapter
{
    protected $sortFields = [
        'id' => 'id',
        'reviewed' => 'reviewed',
        'created' => 'created',
        'modified' => 'modified',
    ];

    public function getResourceName()
    {
        return 'collecting_items';
    }

    public function getRepresentationClass()
    {
        return 'Collecting\Api\Representation\CollectingItemRepresentation';
    }

    public function getEntityClass()
    {
        return 'Collecting\Entity\CollectingItem';
    }

    public function batchCreate(Request $request)
    {
        throw new Exception\OperationNotImplementedException(
            'CollectingItemAdapter does not implement the batchCreate operation.'
        );
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        if (Request::CREATE == $request->getOperation()) {
            $this->hydrateCollectingUser($entity);
            $data = $request->getContent();
            if (isset($data['o:item']['o:id'])) {
                $entity->setItem($this->getEntityManager()->getReference(
                    'Omeka\Entity\Item',
                    $data['o:item']['o:id']
                ));
            }
            if (isset($data['o-module-collecting:form']['o:id'])) {
                $entity->setForm($this->getEntityManager()->getReference(
                    'Collecting\Entity\CollectingForm',
                    $data['o-module-collecting:form']['o:id']
                ));
            }
            foreach ($data['o-module-collecting:input'] as $inputData) {
                $input = new CollectingInput;
                $input->setCollectingItem($entity);
                if (isset($inputData['o-module-collecting:prompt'])) {
                    $input->setPrompt($this->getEntityManager()->getReference(
                        'Collecting\Entity\CollectingPrompt',
                        $inputData['o-module-collecting:prompt']
                    ));
                }
                if (isset($inputData['o-module-collecting:text'])
                    && '' !== trim($inputData['o-module-collecting:text'])
                ) {
                    $input->setText($inputData['o-module-collecting:text']);
                }
                $entity->getInputs()->add($input);
            }
            if (isset($data['o-module-collecting:anon'])) {
                $entity->setAnon($data['o-module-collecting:anon']);
            }
            if (isset($data['o-module-collecting:user_name'])
                && '' !== trim($data['o-module-collecting:user_name'])
            ) {
                $entity->setUserName($data['o-module-collecting:user_name']);
            }
            if (isset($data['o-module-collecting:user_email'])
                && '' !== trim($data['o-module-collecting:user_email'])
            ) {
                $entity->setUserEmail($data['o-module-collecting:user_email']);
            }
        }

        if (Request::UPDATE == $request->getOperation()) {
            if ($this->shouldHydrate($request, 'o-module-collecting:reviewed')) {
                $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
                $entity->setReviewer($auth->getIdentity());
                $entity->setReviewed($request->getValue('o-module-collecting:reviewed'));
            }
        }
    }

    /**
     * Hydrate collecting user for this collecting item.
     *
     * Sets the currently logged in user as the collecting user. If no user is
     * logged in, it sets a new, anonymous collecting user.
     *
     * @param EntityInterface $entity
     */
    protected function hydrateCollectingUser(EntityInterface $entity)
    {
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $user = $auth->getIdentity(); // returns a User entity or null
        $cUser = null;
        if ($user) {
            // User has identity. Check if collecting user already exists.
            $cUser = $this->getEntityManager()
                ->getRepository('Collecting\Entity\CollectingUser')
                ->findOneBy(['user' => $user]);
        }
        if (!$cUser) {
            // Collecting user does not exist. Create a new, anonymous one.
            $cUser = new CollectingUser;
        }
        // CollectingItem::$user has cascade="persist" for persisting new users.
        $cUser->setUser($user);
        $entity->setCollectingUser($cUser);
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!$entity->getItem()) {
            $errorStore->addError('o:item', 'A collecting item must be assigned an item on creation.'); // @translate
        }
        if (!$entity->getForm()) {
            $errorStore->addError('o-module-collecting:form', 'A collecting item must be assigned a form on creation.'); // @translate
        }
        foreach ($entity->getInputs() as $input) {
            if (!$input->getPrompt()) {
                $errorStore->addError('o-module-collecting:prompt', 'A collecting input must be assigned a prompt on creation.'); // @translate
            }
        }
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['form_id'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.form',
                $this->createNamedParameter($qb, $query['form_id']))
            );
        }
        if (isset($query['item_id'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.item',
                $this->createNamedParameter($qb, $query['item_id']))
            );
        }
        if (isset($query['status'])) {
            if ('needs_review' === $query['status']) {
                $qb->andWhere($qb->expr()->eq(
                    'omeka_root.reviewed',
                    $qb->expr()->literal(false)
                ));
            } elseif ('public' === $query['status']) {
                $qb->andWhere($qb->expr()->eq(
                    'omeka_root.reviewed',
                    $qb->expr()->literal(true)
                ));
                $itemAlias = $this->createAlias();
                $qb->innerJoin('omeka_root.item', $itemAlias);
                $qb->andWhere($qb->expr()->eq("$itemAlias.isPublic", $qb->expr()->literal(true)));
            } elseif ('private' === $query['status']) {
                $qb->andWhere($qb->expr()->eq(
                    'omeka_root.reviewed',
                    $qb->expr()->literal(true)
                ));
                $itemAlias = $this->createAlias();
                $qb->innerJoin('omeka_root.item', $itemAlias);
                $qb->andWhere($qb->expr()->eq("$itemAlias.isPublic", $qb->expr()->literal(false)));
            }
        }
    }
}
