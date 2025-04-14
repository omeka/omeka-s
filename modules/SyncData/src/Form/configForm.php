<?php

namespace SyncData\Form; // Corrected namespace

use Laminas\Form\Form;
use Laminas\Form\Element;

class ConfigForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'graphdb_url',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'GraphDB URL',
                'info' => 'The URL of your GraphDB server (e.g., http://localhost:7200).',
            ],
            'attributes' => [
                'id' => 'graphdb_url',
                'required' => 'required',
            ],
        ]);

        $this->add([
            'name' => 'graphdb_repository',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'GraphDB Repository',
                'info' => 'The name of the GraphDB repository to use.',
            ],
            'attributes' => [
                'id' => 'graphdb_repository',
                'required' => 'required',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Element\Submit::class,
            'attributes' => [
                'value' => 'Save Settings',
            ],
        ]);
    }
}