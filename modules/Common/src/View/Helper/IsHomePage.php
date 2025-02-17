<?php declare(strict_types=1);

namespace Common\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;

class IsHomePage extends AbstractHelper
{
    /**
     * Check if a page or the current one is the home page.
     *
     * The main page is the one set in the config of the navigation of the site
     * or the first site page in the menu.
     *
     * Only a site page can be a home page. Nevertheless, when there is no
     * navigation the home page may be a resource page.
     */
    public function __invoke(?SitePageRepresentation $page = null): bool
    {
        $view = $this->getView();

        $hasPage = $page !== null;

        $site = $hasPage ? $page->site() : $view->currentSite();
        if (empty($site)) {
            return false;
        }

        if (!$page) {
            $page = $this->getCurrentPage($site);
        }

        if ($page) {
            $homePage = $this->getHomePage($page->site());
            if ($homePage) {
                return $page->id() === $homePage->id();
            }
            if ($hasPage) {
                return false;
            }
        }

        // Check the alias of the root of Omeka S with rerouting.
        if ($this->isCurrentUrl($view->basePath())) {
            return true;
        }

        // Check the root of the site.
        $url = $view->url('site', ['site-slug' => $site->slug()]);
        return $this->isCurrentUrl($url);
    }

    protected function getCurrentPage(SiteRepresentation $site): ?SitePageRepresentation
    {
        $view = $this->getView();
        $params = $view->params()->fromRoute();

        if (!isset($params['__CONTROLLER__'])
            || $params['__CONTROLLER__'] !== 'Page'
            || !isset($params['page-slug'])
            || !strlen((string) $params['page-slug'])
        ) {
            return null;
        }

        try {
            return $view->api()
                ->read('site_pages', ['site' => $site->id(), 'slug' => $params['page-slug']])
                ->getContent();
        } catch (\Omeka\Api\Exception\NotFoundException $e) {
            return null;
        }
    }

    protected function getHomePage(SiteRepresentation $site): ?SitePageRepresentation
    {
        // Since Omeka S v1.4, there is a site setting for home page.
        $homepage = $site->homepage();
        if ($homepage) {
            return $homepage;
        }

        // Check the first normal page.
        $linkedPages = $site->linkedPages();
        return $linkedPages
            ? current($linkedPages)
            : null;
    }

    /**
     * Check if the given URL matches the current request URL.
     *
     * Upgrade of a method of Omeka Classic / globals.php.
     *
     * @param string $url Relative or absolute
     */
    protected function isCurrentUrl($url): bool
    {
        $view = $this->getView();
        $currentUrl = $this->currentUrl();
        $serverUrl = $view->serverUrl();
        $baseUrl = $view->basePath();

        // Strip out the protocol, host, base URL, and rightmost slash before
        // comparing the URL to the current one
        $stripOut = [$serverUrl . $baseUrl, @$_SERVER['HTTP_HOST'], $baseUrl];
        $currentUrl = rtrim(str_replace($stripOut, '', $currentUrl), '/');
        $url = rtrim(str_replace($stripOut, '', $url), '/');

        if (strlen($url) === 0) {
            return strlen($currentUrl) === 0;
        }
        // Don't check if the url is part of the current url.
        return $url === $currentUrl;
    }

    /**
     * Get the current URL.
     */
    protected function currentUrl(bool $absolute = false): string
    {
        return $absolute
             ? $this->getView()->serverUrl(true)
             : $this->getView()->url(null, [], true);
    }
}
