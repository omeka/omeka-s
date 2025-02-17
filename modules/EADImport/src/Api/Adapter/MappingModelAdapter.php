<?php
namespace EADImport\Api\Adapter;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use EADImport\Api\Representation\MappingModelRepresentation;
use EADImport\Entity\EADImportMappingModel;

class MappingModelAdapter extends AbstractEntityAdapter
{
    public function getResourceName()
    {
        return 'eadimport_mapping_models';
    }

    public function getRepresentationClass()
    {
        return MappingModelRepresentation::class;
    }

    public function getEntityClass()
    {
        return EADImportMappingModel::class;
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();

        if (isset($data['name'])) {
            $entity->setName($data['name']);
        }

        if (isset($data['mapping'])) {
            $entity->setMapping($data['mapping']);
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!$entity->getName()) {
            $errorStore->addError('o-module-eadimport-mappingmodel:name', 'A model must have a name to save it.'); // @translate
        }

        if (!$entity->getMapping()) {
            $errorStore->addError('o-module-eadimport-mappingmodel:mapping', 'Mapping must exists.'); // @translate
        }
    }
}
