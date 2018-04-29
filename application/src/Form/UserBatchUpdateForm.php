<?php
namespace Omeka\Form;

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
                'data-placeholder' => 'Select role...', // @translate
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
                    '' => '[No change]', // @translate
                    '1' => 'Active', // @translate
                    '0' => 'Not active', // @translate
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
     * Note:
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
