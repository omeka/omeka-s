<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;
use Zend\Filter\File\RenameUpload;
use Zend\InputFilter\FileInput;

class AssetAdapter extends AbstractEntityAdapter
{
    const ALLOWED_MEDIA_TYPES = ['image/jpeg', 'image/png', 'image/gif'];

    /**
     * {@inheritDoc}
     */
    protected $sortFields = [
        'id'      => 'id',
        'media_type'  => 'mediaType',
        'name'   => 'name',
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

            $fileManager = $this->getServiceLocator()->get('Omeka\File\Manager');
            $fileStore = $fileManager->getStore();
            $file = $fileManager->getTempFile();

            $fileInput = new FileInput('file');
            $fileInput->getFilterChain()->attach(new RenameUpload([
                'target' => $file->getTempPath(),
                'overwrite' => true
            ]));

            $fileData = $fileData['file'];
            $fileInput->setValue($fileData);
            if (!$fileInput->isValid()) {
                foreach($fileInput->getMessages() as $message) {
                    $errorStore->addError('file', $message);
                }
                return;
            }
            $fileInput->getValue();
            $file->setSourceName($fileData['name']);
            $mediaType = $file->getMediaType();

            if (!in_array($mediaType, self::ALLOWED_MEDIA_TYPES)) {
                $errorStore->addError('file', new Message(
                    'Cannot store assets with the media type "%s".', // @translate
                    $mediaType
                ));
                return;
            }

            $storageId = $file->getStorageId();
            $extension = $fileManager->getExtension($file);
            $entity->setStorageId($storageId);
            $entity->setExtension($extension);
            $entity->setMediaType($file->getMediaType());
            $entity->setName($request->getValue('o:name', $fileData['name']));

            $storagePath = $fileManager->getStoragePath('asset', $storageId, $extension);
            $fileStore->put($file->getTempPath(), $storagePath);
            $file->delete();
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
