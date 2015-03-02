<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
use Omeka\Media\Ingester\IngesterInterface;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Model\Entity\Item;
use Omeka\Model\Entity\ResourceClass;
use Omeka\Stdlib\ErrorStore;

class MediaAdapter extends AbstractResourceEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = array(
        'id'        => 'id',
        'type'      => 'type',
        'is_public' => 'isPublic',
        'created'   => 'created',
        'modified'  => 'modified',
    );

    /**
     * @var IngesterInterface
     */
    protected $ingester;

    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'media';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\Entity\MediaRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\Media';
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        parent::hydrate($request, $entity, $errorStore);

        // Don't allow mutation of basic properties
        if ($request->getOperation() !== Request::CREATE) {
            return;
        }

        $data = $request->getContent();
        $config = $this->getServiceLocator()->get('Config');
        $mediaTypes = $config['media_types'];

        if (isset($data['o:item']['o:id'])) {
            $item = $this->getAdapter('items')
                ->findEntity($data['o:item']['o:id']);
            $entity->setItem($item);
        }

        // If we've gotten here we're guaranteed to have a set, valid
        // media type thanks to validateRequest
        $type = $data['o:type'];
        $entity->setType($type);
        $ingester = $this->getIngester();
        $ingester->ingest($entity, $request, $errorStore);

        if (isset($data['o:data'])) {
            $entity->setData($data['o:data']);
        }

        if (array_key_exists('o:source', $data)) {
            $entity->setSource($data['o:source']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $config = $this->getServiceLocator()->get('Config');
        $mediaTypes = $config['media_types'];

        $data = $request->getContent();
        if (!isset($data['o:type'])) {
            $errorStore->addError('o:type', 'Media must have a type.');
            return;
        }

        $type = $data['o:type'];
        if (!isset($mediaTypes[$type]['ingester'])) {
            $errorStore->addError('o:type', 'Unrecognized media type.');
            return;
        }

        $ingesterClass = $mediaTypes[$type]['ingester'];
        if (!is_subclass_of($ingesterClass, 'Omeka\Media\Ingester\IngesterInterface')) {
            $errorStore->addError('o:type', 'Invalid media ingester.');
        }

        $ingester = new $ingesterClass;
        $ingester->setServiceLocator($this->getServiceLocator());
        $this->setIngester($ingester);

        $ingester->validateRequest($request, $errorStore);
    }

    /**
     * {@inheritDoc}
     */
    public function validateEntity(EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        if (!($entity->getItem() instanceof Item)) {
            $errorStore->addError('o:item', 'Media must belong to an item.');
        }
    }

    protected function setIngester(IngesterInterface $ingester)
    {
        $this->ingester = $ingester;
    }

    protected function getIngester()
    {
        return $this->ingester;
    }
}
