<?php
namespace Omeka\Form;

use Laminas\Form\Form;

/**
 * @deprecated Since 4.3.0, the changes a vocabulary file would make are kept
 *   on the server until they're accepted: see \Omeka\Stdlib\VocabularyReview.
 *   Core no longer uses this form.
 */
class VocabularyUpdateForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'diff',
            'type' => 'hidden',
            'attributes' => [
                'required' => true,
            ],
        ]);
    }
}
