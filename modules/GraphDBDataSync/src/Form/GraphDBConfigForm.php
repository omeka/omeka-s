<?php
namespace GraphDBDataSync\Form;

use Laminas\Form\Form;

class GraphDBConfigForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'graphdb_endpoint',
            'type' => 'text',
            'options' => [
                'label' => 'GraphDB Endpoint',
                'info' => 'The full URL to your GraphDB repository',
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        // Add other fields similarly
        $this->add([
            'name' => 'graphdb_username',
            'type' => 'text',
            'options' => [
                'label' => 'Username',
            ],
        ]);

        $this->add([
            'name' => 'graphdb_password',
            'type' => 'password',
            'options' => [
                'label' => 'Password',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Save',
            ],
        ]);
    }
}