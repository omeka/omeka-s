<?php
namespace Omeka\Form;

use Omeka\Form\Element\ResourceSelect;

class SettingForm extends AbstractForm
{
    public function buildForm()
    {
        $this->add([
            'name' => 'administrator_email',
            'type' => 'Email',
            'options' => [
                'label' => 'Administrator Email', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'installation_title',
            'type' => 'Text',
            'options' => [
                'label' => 'Installation Title', // @translate
            ],
            'attributes' => [
                'id' => 'installation-title',
                'required' => true,
            ],
        ]);

        $timeZones = \DateTimeZone::listIdentifiers();
        $timeZones = array_combine($timeZones, $timeZones);
        $this->add([
            'name' => 'time_zone',
            'type' => 'Select',
            'options' => [
                'label' => 'Time Zone', // @translate
                'value_options' => $timeZones,
            ],
            'attributes' => [
                'id' => 'time-zone',
                'required' => true,
                'value' => $this->getServiceLocator()->get('Omeka\Settings')->get('time_zone', 'UTC'),
            ],
        ]);

        $this->add([
            'name' => 'pagination_per_page',
            'type' => 'Text',
            'options' => [
                'label' => 'Results per page', // @translate
                'info' => 'The maximum number of results per page on browse pages.', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'property_label_information',
            'type' => 'Select',
            'options' => [
                'label' => 'Property Label Information', // @translate
                'info' => 'The additional information that accompanies labels on resource pages.', // @translate
                'value_options' =>  [
                    'none' => 'None',
                    'vocab' => 'Show Vocabulary',
                    'term' => 'Show Term'
                ],
            ]
        ]);

        $siteSelect = new ResourceSelect(
            $this->getServiceLocator(), 'default_site', [
                'label' => 'Default Site', // @translate
                'info' => 'Select which site should appear when users go to the front page of the installation.', // @translate
                'empty_option' => 'No default (Show index of sites)', // @translate
        ]);
        $siteSelect->setResourceValueOptions(
            'sites',
            [],
            function ($site, $serviceLocator) {
                return $site->title();
            }
        );
        $this->add($siteSelect);

        $this->add([
            'name'    => 'use_htmlpurifier',
            'type'    => 'Checkbox',
            'options' => [
                'label' => 'Use HTMLPurifier', // @translate
                'info'  => 'Clean up user-entered HTML.' // @translate
            ]

        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'pagination_per_page',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                ['name' => 'Digits']
            ],
        ]);
        $inputFilter->add([
            'name' => 'default_site',
            'allow_empty' => true,
        ]);
    }
}
