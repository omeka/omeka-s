<?php
namespace Collecting\Form;

use Collecting\Entity\CollectingForm as CollectingFormEntity;
use Omeka\Form\Element\CkeditorInline;
use Omeka\Form\Element\ItemSetSelect;
use Laminas\Form\Form;

class CollectingForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'o-module-collecting:label',
            'type' => 'Text',
            'options' => [
                'label' => 'Label', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
        $this->add([
            'name' => 'item_set_id',
            'type' => ItemSetSelect::class,
            'options' => [
                'label' => 'Item set', // @translate
                'info' => 'Assign all items created by this form to this item set.', // @translate
                'empty_option' => 'Select Item Set...', // @translate
            ],
            'attributes' => [
                'required' => false,
            ],
        ]);
        $this->add([
            'name' => 'o-module-collecting:anon_type',
            'type' => 'select',
            'options' => [
                'label' => 'Anonymity type', // @translate
                'info' => 'Control how to treat “User Public” and “User Name” input on public pages.', // @translate
                'value_options' => CollectingFormEntity::getAnonTypes(),
            ],
        ]);
        $this->add([
            'name' => 'o-module-collecting:success_text',
            'type' => CkeditorInline::class,
            'options' => [
                'label' => 'Success text', // @translate
                'info' => 'Display this text after a user successfully submits a form.', // @translate
            ],
            'attributes' => [
                'required' => false,
                'id' => 'form-success-text',
            ],
        ]);
        $this->add([
            'name' => 'o-module-collecting:email_text',
            'type' => CkeditorInline::class,
            'options' => [
                'label' => 'Email text', // @translate
                'info' => 'Begin all submission emails with this text.', // @translate
            ],
            'attributes' => [
                'required' => false,
                'id' => 'form-email-text',
            ],
        ]);
        $this->add([
            'name' => 'o-module-collecting:default_site_assign',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Use default site assignments', // @translate
                'info' => 'Check this to use default site assignments for collected items. If not checked, all items created by this form are assigned to this site only.', // @translate
            ],
            'attributes' => [
                'required' => false,
            ],
        ]);

        $filter = $this->getInputFilter();
        $filter->add([
            'name' => 'o-module-collecting:label',
            'required' => true,
        ]);
        $filter->add([
            'name' => 'item_set_id',
            'required' => false,
        ]);
    }
}
