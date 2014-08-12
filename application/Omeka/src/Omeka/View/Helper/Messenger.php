<?php
namespace Omeka\View\Helper;

use Omeka\Mvc\Controller\Plugin\Messenger as MessengerPlugin;
use Zend\View\Helper\AbstractHelper;

/**
 * Helper to proxy the messenger controller plugin.
 */
class Messenger extends AbstractHelper
{
    /**
     * Get all messages and clear them from the session.
     *
     * @return array
     */
    public function get()
    {
        $messengerPlugin = new MessengerPlugin;
        $messages = $messengerPlugin->get();
        $messengerPlugin->clear();
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
            return;
        }

        $output = '<ul class="messages">';
        foreach ($allMessages as $type => $messages) {
            switch ($type) {
                case MessengerPlugin::ERROR;
                    $class = 'error';
                    break;
                case MessengerPlugin::SUCCESS;
                    $class = 'success';
                    break;
                case MessengerPlugin::WARNING;
                    $class = 'warning';
                    break;
                case MessengerPlugin::NOTICE;
                default:
                    $class = 'notice';
            }
            foreach ($messages as $message) {
                $output .= "<li class=\"$class\">";
                $output .= htmlspecialchars($message);
                $output .= '</li>';
            }
        }
        $output .= '</ul>';
        return $output;
    }
}
