<?php
namespace Omeka\View\Helper;

use Laminas\I18n\Translator\TranslatorAwareInterface;
use Laminas\View\Helper\AbstractHelper;
use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Stdlib\MessageInterface;

/**
 * View helper for proxing the messenger controller plugin.
 */
class Messages extends AbstractHelper
{
    /**
     * Get all messages and clear them from the session.
     *
     * @return array
     */
    public function get()
    {
        $messenger = new Messenger;
        $messages = $messenger->get();
        $messenger->clear();
        return $messages;
    }

    /**
     * Render the messages.
     *
     * @return string
     */
    public function __invoke()
    {
        $allMessages = $this->get();
        if (!$allMessages) {
            return '';
        }

        $view = $this->getView();
        $escape = $view->plugin('escapeHtml');
        $translate = $view->plugin('translate');
        $translator = $translate->getTranslator();
        $output = '<ul class="messages">';
        $typeToClass = [
            Messenger::ERROR => 'error',
            Messenger::SUCCESS => 'success',
            Messenger::WARNING => 'warning',
            Messenger::NOTICE => 'notice',
        ];
        foreach ($allMessages as $type => $messages) {
            $class = isset($typeToClass[$type]) ? $typeToClass[$type] : 'notice';
            foreach ($messages as $message) {
                $translation = $message instanceof TranslatorAwareInterface
                    ? $message->setTranslator($translator)->translate()
                    : $translate($message);
                $escapeHtml = $message instanceof MessageInterface
                    ? $message->escapeHtml()
                    : true; // escape HTML by default
                if ($escapeHtml) {
                    $translation = $escape($translation);
                }
                $output .= sprintf('<li class="%s">%s</li>', $class, $translation);
            }
        }
        $output .= '</ul>';
        return $output;
    }
}
