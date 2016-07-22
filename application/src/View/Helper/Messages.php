<?php
namespace Omeka\View\Helper;

use Omeka\Mvc\Controller\Plugin\Messenger;
use Zend\View\Helper\AbstractHelper;

/**
 * Helper to proxy the messenger controller plugin.
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
        $output = '<ul class="messages">';
        foreach ($allMessages as $type => $messages) {
            switch ($type) {
                case Messenger::ERROR:
                    $class = 'error';
                    break;
                case Messenger::SUCCESS:
                    $class = 'success';
                    break;
                case Messenger::WARNING:
                    $class = 'warning';
                    break;
                case Messenger::NOTICE:
                default:
                    $class = 'notice';
            }
            foreach ($messages as $message) {
                $args = $message[1];
                $message = $view->translate($message[0]);
                if ($args) {
                    // Only interpolate args into messages that have them.
                    $message = vsprintf($message, $args);
                }
                $output .= sprintf(
                    '<li class="%s">%s</li>',
                    $class,
                    $view->escapeHtml($message)
                );
            }
        }
        $output .= '</ul>';
        return $output;
    }
}
