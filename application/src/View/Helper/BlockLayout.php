<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\Form\Element\Hidden;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

class BlockLayout extends AbstractHelper
{
    protected $manager;

    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->manager = $serviceLocator->get('Omeka\BlockLayoutManager');
    }

    /**
     * Get all registered layouts.
     *
     * @return array
     */
    public function getLayouts()
    {
        return array_unique($this->manager->getCanonicalNames());
    }

    /**
     * Get a layout label.
     *
     * @param string $layout
     * @return string
     */
    public function getLayoutLabel($layout)
    {
        return $this->manager->get($layout)->getLabel();
    }

    /**
     * Prepare the view to enable the block layout form.
     */
    public function prepareForm()
    {
        foreach ($this->getLayouts() as $layout) {
            $this->manager->get($layout)->prepareForm($this->getView());
        }
    }

    /**
     * Return the HTML necessary to render a form.
     *
     * @param int $index The block index on the form
     * @param string|SitePageBlockRepresentation $layout The layout for add or
     *   a block representation for edit
     * @return string
     */
    public function form($index, $layout)
    {
        $block = null;
        if ($layout instanceof SitePageBlockRepresentation) {
            $block = $layout;
            $layout = $block->layout();
        }
        $form = '
<span class="sortable-handle"></span>
<div class="input-header">
    <span class="block-type">' . $this->getLayoutLabel($layout) . '</span>
    <ul class="actions">
        <li><a href="#" class="o-icon-delete remove-value"></a></li>
        <li><a href="#" class="o-icon-undo restore-value"></a></li>
    </ul>
    <span class="restore-value">block to be removed</span>
</div>';
        $form .= $this->manager->get($layout)->form($this->getView(), $index, $block);
        $hidden = new Hidden("o:block[$index][o:layout]");
        $hidden->setAttribute('value', $layout);
        $form .= $this->getView()->formField($hidden);
        return $form;
    }

    /**
     * Return the HTML necessary to render the provided block.
     *
     * @param SitePageBlockRepresentation $block
     * @return string
     */
    public function render(SitePageBlockRepresentation $block)
    {
        return $this->manager->get($block->layout())->render($this->getView(), $block);
    }
}
