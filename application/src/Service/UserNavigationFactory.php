<?php
namespace Omeka\Service;

use Zend\Navigation\Service\AbstractNavigationFactory;


/**
 * User navigation factory.
 */
class UserNavigationFactory extends AbstractNavigationFactory
{
    /**
     * @return string
     */
    protected function getName()
    {
        return 'user';
    }
}
