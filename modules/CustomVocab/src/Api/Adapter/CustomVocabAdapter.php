<?php
namespace CustomVocab\Api\Adapter;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class CustomVocabAdapter extends AbstractEntityAdapter
{
    protected $sortFields = [
        'id' => 'id',
        'label' => 'label',
        'owner' => 'owner',
    ];

    protected $scalarFields = [
        'id' => 'id',
        'label' => 'label',
        'owner' => 'owner',
        'lang' => 'lang',
        'terms' => 'terms',
        'uris' => 'uris',
        'item_set' => 'itemSet',
    ];

    public function getResourceName()
    {
        return 'custom_vocabs';
    }

    public function getRepresentationClass()
    {
        return \CustomVocab\Api\Representation\CustomVocabRepresentation::class;
    }

    public function getEntityClass()
    {
        return \CustomVocab\Entity\CustomVocab::class;
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $this->hydrateOwner($request, $entity);
        if ($this->shouldHydrate($request, 'o:label')) {
            $entity->setLabel($request->getValue('o:label'));
        }
        if ($this->shouldHydrate($request, 'o:lang')) {
            $lang = trim($request->getValue('o:lang'));
            if ('' === $lang) {
                $lang = null;
            }
            $entity->setLang($lang);
        }
        if ($this->shouldHydrate($request, 'o:item_set')) {
            $itemSet = $request->getValue('o:item_set');
            if ($itemSet && isset($itemSet['o:id']) && is_numeric($itemSet['o:id'])) {
                $itemSet = $this->getAdapter('item_sets')->findEntity($itemSet['o:id']);
            } else {
                $itemSet = null;
            }
            $entity->setItemSet($itemSet);
        }
        if ($this->shouldHydrate($request, 'o:terms')) {
            $terms = $this->sanitizeTerms($request->getValue('o:terms'));
            $terms = $terms ? array_values($terms) : null;
            $entity->setTerms($terms);
        }
        if ($this->shouldHydrate($request, 'o:uris')) {
            $uris = $this->sanitizeUris($request->getValue('o:uris'));
            $uris = $uris ?: null;
            $entity->setUris($uris);
        }
    }

    public function validateEntity(EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $label = $entity->getLabel();
        if (false == trim($label)) {
            $errorStore->addError('o:label', 'The label cannot be empty.'); // @translate
        }
        if (!$this->isUnique($entity, ['label' => $label])) {
            $errorStore->addError('o:label', 'The label is already taken.'); // @translate
        }

        $itemSet = $entity->getItemSet();
        $terms = $entity->getTerms();
        $uris = $entity->getUris();
        if ((null === $itemSet) && null === $terms && null === $uris) {
            $errorStore->addError('o:terms', 'The item set, terms, and URIs cannot all be empty.'); // @translate
        }
    }

    protected function sanitizeTerms($terms)
    {
        if (null === $terms) {
            return null;
        }
        // The str_replace() allows to fix Apple copy/paste.
        if (!is_array($terms)) {
            $terms = explode("\n", str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], $terms)); // explode at end of line
        }
        $terms = array_map('trim', $terms); // trim all terms
        $terms = array_filter($terms); // remove empty terms
        return array_unique($terms); // remove duplicate terms
    }

    protected function sanitizeUris($uriLabels)
    {
        if (null === $uriLabels) {
            return null;
        }
        // The str_replace() allows to fix Apple copy/paste.
        if (!is_array($uriLabels)) {
            $uriLabels = explode("\n", str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], $uriLabels)); // explode at end of line
        }
        return array_map('trim', $uriLabels); // trim all terms
    }
}
