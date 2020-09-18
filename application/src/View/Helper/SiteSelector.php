<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class SiteSelector extends AbstractHelper
{
    public function __invoke()
    {
        $view = $this->getView();
        $sites = $view->api()->search('sites', ['sort_by' => 'title'])->getContent();
        $sitesByOwner = [];
        $totalCount = 0;
        foreach ($sites as $site) {
            if ($site->userIsAllowed('can-assign-items')) {
                $owner = $site->owner();
                $email = $owner ? $owner->email() : null;
                $sitesByOwner[$email]['owner'] = $owner;
                $sitesByOwner[$email]['sites'][] = $site;
                $totalCount++;
            }
        }
        ksort($sitesByOwner);
        return $view->partial(
            'common/site-selector',
            [
                'sitesByOwner' => $sitesByOwner,
                'totalCount' => $totalCount,
            ]
        );
    }
}
