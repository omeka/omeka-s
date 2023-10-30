<?php
namespace Omeka\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Site\Theme\Theme;

class PageController extends AbstractActionController
{
    protected $currentTheme;

    public function __construct(Theme $currentTheme)
    {
        $this->currentTheme = $currentTheme;
    }

    public function browseAction()
    {
        $this->setBrowseDefaults('created');
        $query = $this->params()->fromQuery();
        $query['site_id'] = $this->currentSite()->id();

        $response = $this->api()->search('site_pages', $query);
        $this->paginator($response->getTotalResults());
        $pages = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('pages', $pages);
        return $view;
    }

    public function showAction()
    {
        $slug = $this->params('page-slug');
        $site = $this->currentSite();
        $page = $this->api()->read('site_pages', [
            'slug' => $slug,
            'site' => $site->id(),
        ])->getContent();

        $pageBodyClass = 'page site-page-' . preg_replace('([^a-zA-Z0-9\-])', '-', $slug);

        $this->viewHelpers()->get('sitePagePagination')->setPage($page);

        $view = new ViewModel;

        $view->setVariable('site', $site);
        $view->setVariable('page', $page);
        $view->setVariable('pageBodyClass', $pageBodyClass);
        $view->setVariable('displayNavigation', true);

        // Set the configured page template, if any.
        $templateName = $page->layoutDataValue('template_name');
        if ($templateName) {
            // Verify that the current theme provides this template.
            $config = $this->currentTheme->getConfigSpec();
            if (isset($config['page_templates'][$templateName])) {
                $view->setTemplate(sprintf('common/page-template/%s', $templateName));
            }
        }

        $contentView = clone $view;
        $contentView->setTemplate('omeka/site/page/content');
        $contentView->setVariable('pageViewModel', $view);

        $view->addChild($contentView, 'content');
        return $view;
    }
}
