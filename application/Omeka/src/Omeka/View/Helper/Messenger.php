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
}
