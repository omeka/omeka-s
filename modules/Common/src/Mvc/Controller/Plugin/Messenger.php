<?php declare(strict_types=1);

namespace Common\Mvc\Controller\Plugin;

use Laminas\Form\Fieldset;

class Messenger extends \Omeka\Mvc\Controller\Plugin\Messenger
{
    /**
     * Fixed messenger for forms with any levels of element Collection.
     *
     * @link https://github.com/omeka/omeka-s/pull/1626
     *
     * {@inheritDoc}
     * @see \Omeka\Mvc\Controller\Plugin\Messenger::addFormErrors()
     */
    public function addFormErrors(Fieldset $formOrFieldset): void
    {
        foreach ($formOrFieldset->getIterator() as $elementOrFieldset) {
            if ($elementOrFieldset instanceof Fieldset) {
                $this->addFormErrors($elementOrFieldset);
            } else {
                // Sub-names are not kept here, only main label.
                $label = $this->getController()->translate($elementOrFieldset->getLabel());
                $fieldsetMessages = $elementOrFieldset->getMessages();
                array_walk_recursive($fieldsetMessages, function ($msg) use ($label): void {
                    $this->addError(sprintf('%1$s: %2$s', $label, $msg));
                });
            }
        }
    }

    /**
     * Get all messages flatted by type.
     *
     * To flat is needed because messages aren't checked early and may be array.
     */
    public function get(): array
    {
        if (!isset($this->container->messages)) {
            return [];
        }

        $output = [];
        foreach ($this->container->messages as $type => $messages) {
            array_walk_recursive($messages, function ($msg) use (&$output, $type): void {
                $output[$type][] = $msg;
            });
        }

        return $this->container->messages = $output;
    }
}
