<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Form\Element\SelectSortTrait;

class SiteSelector extends AbstractHelper
{
    use SelectSortTrait;

    public function __invoke()
    {
        $view = $this->getView();
        $sites = $view->api()->search('sites', ['sort_by' => 'title'])->getContent();
        $options = [];
        $totalCount = 0;
        foreach ($sites as $site) {
            if ($site->userIsAllowed('can-assign-items')) {
                $owner = $site->owner();
                $email = $owner ? $owner->email() : null;
                $options[$email]['owner'] = $owner;
                $options[$email]['label'] = $owner ? $owner->name() : $view->translate('[No owner]');
                $options[$email]['options'][] = [
                    'label' => $site->title(),
                    'site' => $site,
                ];
                $totalCount++;
            }
        }
        $options = $this->sortSelectOptions($options);

        return $view->partial(
            'common/site-selector',
            [
                'options' => $options,
                'totalCount' => $totalCount,
            ]
        );
    }
}
