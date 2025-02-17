<?php
namespace CustomVocab\Api\Representation;

use CustomVocab\Form\Element\CustomVocabSelect;
use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\Api\Representation\ItemSetRepresentation;
use Omeka\Api\Representation\UserRepresentation;

class CustomVocabRepresentation extends AbstractEntityRepresentation
{
    public function getControllerName()
    {
        return 'custom-vocab';
    }

    public function getJsonLdType()
    {
        return 'o:CustomVocab';
    }

    public function getJsonLd()
    {
        $itemSet = null;
        $owner = null;
        if ($this->itemSet()) {
            $itemSet = $this->itemSet()->getReference();
        }
        if ($this->owner()) {
            $owner = $this->owner()->getReference();
        }
        return [
            'o:label' => $this->label(),
            'o:lang' => $this->lang(),
            'o:terms' => $this->terms(),
            'o:uris' => $this->uris(),
            'o:item_set' => $itemSet,
            'o:owner' => $owner,
        ];
    }

    public function label(): string
    {
        return $this->resource->getLabel();
    }

    public function lang(): ?string
    {
        return $this->resource->getLang();
    }

    public function itemSet(): ?ItemSetRepresentation
    {
        return $this->getAdapter('item_sets')
            ->getRepresentation($this->resource->getItemSet());
    }

    public function terms()
    {
        return $this->resource->getTerms();
    }

    public function uris()
    {
        return $this->resource->getUris();
    }

    /**
     * Get the vocab type.
     *
     * The types are:
     *   - literal: a vocab containing terms
     *   - uri: a vocab containing URIs
     *   - resource: a vocab containing items in an item set
     */
    public function type(): ?string
    {
        // Normally, values are checked in adapter on save, so no more check.
        if ($this->resource->getItemSet()) {
            return 'resource';
        } elseif ($this->resource->getUris()) {
            return 'uri';
        } elseif ($this->resource->getTerms()) {
            return 'literal';
        } else {
            return null;
        }
    }

    /**
     * Get the vocab type's label.
     */
    public function typeLabel(): ?string
    {
        switch ($this->type()) {
            case 'literal':
                return 'Terms'; // @translate
            case 'uri':
                return 'URIs'; // @translate
            case 'resource':
                return 'Items'; // @translate
            default:
                return null;
        }
    }

    /**
     * List values as value/label, whatever the type.
     */
    public function listValues(array $options = []): array
    {
        switch ($this->type()) {
            case 'resource':
                return $this->listItemTitles($options) ?? [];
            case 'uri':
                return $this->listUriLabels($options) ?? [];
            case 'literal':
                return $this->listTerms() ?? [];
            default:
                return [];
        }
    }

    /**
     * List item titles by id when the vocab is based on an item set.
     */
    public function listItemTitles(array $options = []): ?array
    {
        $itemSet = $this->resource->getItemSet();
        if (!$itemSet) {
            return null;
        }
        $result = [];
        /** @var \Omeka\Api\Representation\ItemRepresentation[] $items */
        $items = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('items', ['item_set_id' => $itemSet->getId()])
            ->getContent();
        $lang = $this->lang();
        if (!empty($options['append_id_to_title'])) {
            $label = $this->getTranslator()->translate('%s (#%s)'); // @translate
            foreach ($items as $item) {
                $itemId = $item->id();
                $result[$itemId] = sprintf($label, $item->displayTitle(null, $lang), $itemId);
            }
        } else {
            foreach ($items as $item) {
                $result[$item->id()] = $item->displayTitle(null, $lang);
            }
        }
        natcasesort($result);
        return $result;
    }

    /**
     * List of terms by term when the vocab is a simple list.
     */
    public function listTerms(): ?array
    {
        $terms = $this->resource->getTerms();
        return $terms
            ? array_combine($terms, $terms)
            : null;
    }

    /**
     * List of uris (as key) and labels when the vocab is a list of uris.
     */
    public function listUriLabels(array $options = []): ?array
    {
        $uris = $this->resource->getUris();
        if (!$uris) {
            return null;
        }
        $result = [];
        if (!empty($options['append_uri_to_label'])) {
            $sLabel = $this->getTranslator()->translate('%1$s <%2$s>'); // @translate
            foreach ($uris as $uri => $label) {
                $result[$uri] = strlen($label) ? sprintf($sLabel, $label, $uri) : $uri;
            }
        } else {
            foreach ($uris as $uri => $label) {
                $result[$uri] = strlen($label) ? $label : $uri;
            }
        }
        return $result;
    }

    public function owner(): ?UserRepresentation
    {
        return $this->getAdapter('users')
            ->getRepresentation($this->resource->getOwner());
    }

    /**
     * Get a select element for this custom vocab.
     */
    public function select(array $options = []): CustomVocabSelect
    {
        $options['custom_vocab_id'] = $this->id();
        return $this->getServiceLocator()->get('FormElementManager')
            ->get(CustomVocabSelect::class)
            ->setOptions($options);
    }
}
