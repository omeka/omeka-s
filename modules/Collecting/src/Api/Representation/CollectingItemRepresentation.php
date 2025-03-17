<?php
namespace Collecting\Api\Representation;

use Collecting\Entity\CollectingPrompt;
use Omeka\Api\Representation\AbstractEntityRepresentation;

class CollectingItemRepresentation extends AbstractEntityRepresentation
{
    /**
     * @var array Cache of all inputs
     */
    protected $inputs;

    /**
     * @var array Cache of all inputs keyed by prompt type
     */
    protected $inputsByType;

    public function getJsonLdType()
    {
        return 'o-module-collecting:Item';
    }

    public function getJsonLd()
    {
        if ($item = $this->item()) {
            $item = $item->getReference();
        }
        if ($form = $this->form()) {
            $form = $form->getReference();
        }
        return [
            'o:item' => $item,
            'o-module-collecting:form' => $form,
            // Must use self::displayUserName() since it's responsible for redaction
            'o-module-collecting:user_name' => $this->displayUserName(),
            // Must use self::displayUserEmail() since it's responsible for redaction
            'o-module-collecting:user_email' => $this->displayUserEmail(),
            'o-module-collecting:anon' => $this->anon(),
            'o-module-collecting:reviewed' => $this->reviewed(),
            'o-module-collecting:input' => $this->inputs(),
        ];
    }

    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/site/slug/collecting/item/id',
            [
                'site-slug' => $this->form()->site()->slug(),
                'controller' => $this->getControllerName(),
                'action' => $action,
                'form-id' => $this->form()->id(),
                'item-id' => $this->id(),
            ],
            ['force_canonical' => $canonical]
        );
    }

    public function item()
    {
        return $this->getAdapter('items')
            ->getRepresentation($this->resource->getItem());
    }

    public function form()
    {
        return $this->getAdapter('collecting_forms')
            ->getRepresentation($this->resource->getForm());
    }

    public function collectingUser()
    {
        return $this->getAdapter('collecting_users')
            ->getRepresentation($this->resource->getCollectingUser());
    }

    public function reviewer()
    {
        return $this->getAdapter('users')
            ->getRepresentation($this->resource->getReviewer());
    }

    public function userName()
    {
        return $this->resource->getUserName();
    }

    public function userEmail()
    {
        return $this->resource->getUserEmail();
    }

    public function anon()
    {
        return $this->resource->getAnon();
    }

    public function reviewed()
    {
        return $this->resource->getReviewed();
    }

    public function created()
    {
        return $this->resource->getCreated();
    }

    public function modified()
    {
        return $this->resource->getModified();
    }

    /**
     * Get this item's user name, ready for display.
     *
     * @return string
     */
    public function displayUserName()
    {
        $name = $this->userName();
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        if (!$acl->userIsAllowed($this->resource, 'view-collecting-user-name')) {
            $name = $this->getTranslator()->translate('[private]');
        }
        return $name;
    }

    /**
     * Get this item's user email, ready for display.
     *
     * @return string
     */
    public function displayUserEmail()
    {
        $email = $this->userEmail();
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        if (!$acl->userIsAllowed($this->resource, 'view-collecting-user-email')) {
            $email = $this->getTranslator()->translate('[private]');
        }
        return $email;
    }

    /**
     * Get all inputs.
     *
     * @return array
     */
    public function inputs()
    {
        $this->cacheInputs();
        return $this->inputs;
    }

    /**
     * Get inputs by prompt type.
     *
     * @param string $type
     * @return array
     */
    public function inputsByType($type)
    {
        $this->cacheInputs();
        return $this->inputsByType[$type] ?? [];
    }

    /**
     * Cache inputs if not already cached.
     */
    protected function cacheInputs()
    {
        if (is_array($this->inputs)) {
            return; // already cached
        }

        $inputs = [];
        $inputsByType = array_map(function () {
            return [];
        }, CollectingPrompt::getTypes());
        $services = $this->getServiceLocator();
        foreach ($this->resource->getInputs() as $input) {
            $inputRep = new CollectingInputRepresentation($input, $services);
            $inputs[] = $inputRep;
            $inputsByType[$input->getPrompt()->getType()][] = $inputRep;
        }

        $this->inputs = $inputs;
        $this->inputsByType = $inputsByType;
    }

    /**
     * Get the inputs markup, ready for display.
     *
     * @return string
     */
    public function displayInputs()
    {
        $partial = $this->getViewHelper('partial');
        return $partial('common/collecting-item-inputs.phtml', ['cItem' => $this]);
    }

    public function displayCitation()
    {
        $partial = $this->getViewHelper('partial');
        return $partial('common/collecting-item-citation.phtml', ['cItem' => $this]);
    }

    public function statusSelect()
    {
        $select = new \Laminas\Form\Element\Select(sprintf('statuses[%s]', $this->id()));
        $select->setValueOptions([
            'needs_review' => 'Needs review', // @translate
            'public' => 'Public', // @translate
            'private' => 'Private', // @translate
        ]);
        if ($this->reviewed()) {
            $value = $this->item()->isPublic() ? 'public' : 'private';
        } else {
            $value = 'needs_review';
        }
        $select->setValue($value);
        return $select;
    }
}
