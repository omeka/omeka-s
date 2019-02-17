<?php
namespace Omeka\View\Helper;

use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Stdlib\Message;
use Laminas\View\Helper\AbstractHelper;
use Omeka\Stdlib\PsrMessage;

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
                $escapeHtml = true; // escape HTML by default
                if ($message instanceof Message) {
                    $escapeHtml = $message->escapeHtml();
                    $message = $translate($message);
                } elseif ($message instanceof PsrMessage) {
                    $escapeHtml = $message->escapeHtml();
                    $message = $message->setTranslator($translator)->translate();
                } else {
                    $message = $translate($message);
                }
                if ($escapeHtml) {
                    $message = $escape($message);
                }
                $output .= sprintf('<li class="%s">%s</li>', $class, $message);
            }
        }
        $output .= '</ul>';
        return $output;
    }
}
