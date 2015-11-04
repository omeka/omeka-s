<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\View\Renderer\PhpRenderer;

class Navigation extends AbstractBlockLayout
{
	public function getLabel()
	{
		$translator = $this->getServiceLocator()->get('MvcTranslator');
		return $translator->translate('Navigation');
	}

	public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageBlockRepresentation $block = null
    ) {
        return 'A list of child pages';
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
    	$html = '';
    	$html .= '<div class="navigation-block">';
    	$html .= $view->navigation('Zend\Navigation\Site')->menu()->renderSubMenu();
    	$html .= '</div>';

    	return $html;
    }
}