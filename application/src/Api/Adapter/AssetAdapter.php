<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\File\Validator;
use Omeka\Stdlib\ErrorStore;

class AssetAdapter extends AbstractEntityAdapter
{
    const ALLOWED_MEDIA_TYPES = ['image/jpeg', 'image/png', 'image/gif'];

    /**
     * {@inheritDoc}
     */
    protected $sortFields = [
        'id' => 'id',
        'media_type' => 'mediaType',
        'name' => 'name',
        'extension' => 'extension',
    ];

    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'assets';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\AssetRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Entity\Asset';
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        $data = $request->getContent();

        if (Request::CREATE === $request->getOperation()) {
            $fileData = $request->getFileData();
            if (!isset($fileData['file'])) {
                $errorStore->addError('file', 'No file was uploaded');
                return;
            }

            $uploader = $this->getServiceLocator()->get('Omeka\File\Uploader');
            $tempFile = $uploader->upload($fileData['file'], $errorStore);
            if (!$tempFile) {
                return;
            }

            $tempFile->setSourceName($fileData['file']['name']);
            $validator = new Validator(self::ALLOWED_MEDIA_TYPES);
            if (!$validator->validate($tempFile, $errorStore)) {
                return;
            }

            $entity->setStorageId($tempFile->getStorageId());
            $entity->setExtension($tempFile->getExtension());
            $entity->setMediaType($tempFile->getMediaType());
            $entity->setName($request->getValue('o:name', $fileData['file']['name']));

            $tempFile->storeAsset();
            $tempFile->delete();
        } else {
            if ($this->shouldHydrate($request, 'o:name')) {
                $entity->setName($request->getValue('o:name'));
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        // Don't add this name error if we have any other errors already
        if ($errorStore->hasErrors()) {
            return;
        }

        $name = $entity->getName();
        if (!is_string($name) || $name === '') {
            $errorStore->addError('o:name', 'An asset must have a name.');
        }
    }
}
