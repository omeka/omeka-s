<?php declare(strict_types=1);

namespace Sparql\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Fieldset;

class SparqlFieldset extends Fieldset
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][interface]',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Interface', // @translate
                    'value_options' => [
                        'default' => 'Simple (internal engine)', // @translate
                        'yasgui' => 'Yasgui', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'sparql_yasgui',
                ],
            ])
        ;
    }
}
