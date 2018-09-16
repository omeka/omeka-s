<?php
namespace Omeka\Form;

use Omeka\Form\Element\SiteSelect;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\Form\Form;
use Zend\View\Helper\Url;

class UserBatchUpdateForm extends Form
{
    use EventManagerAwareTrait;

    /**
     * @var Url
     */
    protected $urlHelper;

    public function init()
    {
        $this->add([
            'name' => 'role',
            'type' => 'Omeka\Form\Element\RoleSelect',
            'attributes' => [
                'id' => 'role-select',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select role…', // @translate
            ],
            'options' => [
                'label' => 'Set role', // @translate
                'empty_option' => '[No change]', // @translate
            ],
        ]);

        $this->add([
            'name' => 'is_active',
            'type' => 'radio',
            'options' => [
                'label' => 'Set activity', // @translate
                'value_options' => [
                    '1' => 'Active', // @translate
                    '0' => 'Not active', // @translate
                    '' => '[No change]', // @translate
                ],
            ],
        ]);

        $this->add([
            'name' => 'remove_from_site_permission',
            'type' => SiteSelect::class,
            'attributes' => [
                'id' => 'remove-from-site-permission-select',
                'class' => 'chosen-select',
                'multiple' => true,
                'data-placeholder' => 'Select sites…', // @translate
                'data-collection-action' => 'remove',
            ],
            'options' => [
                'label' => 'Remove from site permission', // @translate
                'empty_option' => '[No change]', // @translate
                'prepend_value_options' => ['-1' => '[All sites]'], // @translate
            ],
        ]);

        $this->add([
            'name' => 'add_to_site_permission',
            'type' => SiteSelect::class,
            'attributes' => [
                'id' => 'add-to-site-permission-select',
                'class' => 'chosen-select',
                'multiple' => true,
                'data-placeholder' => 'Select sites…', // @translate
                'data-collection-action' => 'append',
            ],
            'options' => [
                'label' => 'Add to site permission', // @translate
                'empty_option' => '[No change]', // @translate
                'prepend_value_options' => ['-1' => '[All sites]'], // @translate
            ],
        ]);

        $this->add([
            'name' => 'add_to_site_permission_role',
            'type' => 'Select',
            'attributes' => [
                'id' => 'add-to-site-permission-role-select',
                'data-placeholder' => 'Select permission…', // @translate
                'data-collection-action' => 'append',
            ],
            'options' => [
                'label' => 'Add to site permission as', // @translate
                'empty_option' => '[No change]', // @translate
                'value_options' => [
                    'viewer' => 'Viewer', // @translate
                    'editor' => 'Editor', // @translate
                    'admin' => 'Admin', // @translate
                ],
            ],
        ]);

        $addEvent = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($addEvent);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'role',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'is_active',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'remove_from_site_permission',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'add_to_site_permission',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'add_to_site_permission_role',
            'required' => false,
        ]);

        $filterEvent = new Event('form.add_input_filters', $this, ['inputFilter' => $inputFilter]);
        $this->getEventManager()->triggerEvent($filterEvent);
    }

    /**
     * Preprocess data to get data to replace, to remove and to append.
     *
     * Batch update data contains instructions on what to update. It needs to be
     * preprocessed before it's sent to the API. The elements are udpated by
     * entity according to the attribute "data-collection-action", that can be
     * "replace" (default), "remove" or "append".
     *
     * @todo Use standard validationGroup and filters.
     *
     * @return array Associative array of data to replace, to remove and to
     * append.
     */
    public function preprocessData()
    {
        $data = $this->getData();
        $preData = [
            'replace' => null,
            'remove' => null,
            'append' => null,
        ];

        // Set the data to change and data to remove.
        if (!empty($data['role'])) {
            $preData['remove']['o:role'] = $data['role'];
        }
        if (array_key_exists('is_active', $data) && in_array($data['is_active'], ['0', '1'])) {
            $preData['remove']['o:is_active'] = $data['is_active'];
        }

        // Set remaining elements according to attribute data-collection-action.
        $processeds = [
            'role', 'is_active',
            'csrf', 'id', 'o:id',
        ];
        foreach ($data as $key => $value) {
            if (is_numeric($key) || in_array($key, $processeds)
                || is_null($value) || $value === ''
            ) {
                continue;
            }
            $collectionAction = $this->has($key)
                ? $this->get($key)->getAttribute('data-collection-action')
                : 'replace';
            $preData[$collectionAction][$key] = $value;
        }

        return array_filter($preData);
    }
}
