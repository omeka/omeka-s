<?php
namespace OmekaTheme\Helper;

use Zend\View\Helper\AbstractHelper;

class ThemeHelperOne extends AbstractHelper
{
    public function __invoke()
    {
        return 'Invoked ThemeHelperOne';
    }
}
