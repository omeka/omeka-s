<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Zend\Validator\EmailAddress;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\SitePage;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;

class SitePageAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'site_pages';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\SitePageRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Entity\SitePage';
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();
        if (Request::CREATE === $request->getOperation()
            && isset($data['o:site']['o:id'])
        ) {
            $site = $this->getAdapter('sites')->findEntity($data['o:site']['o:id']);
            $this->authorize($site, 'add-page');
            $entity->setSite($site);
        }
        if ($this->shouldHydrate($request, 'o:slug')) {
            $entity->setSlug($request->getValue('o:slug'));
        }
        if ($this->shouldHydrate($request, 'o:title')) {
            $entity->setTitle($request->getValue('o:title'));
        }

        $appendBlocks = $request->getOperation() === Request::UPDATE
            && $request->isPartial();
        $this->hydrateBlocks($request->getValue('o:block', array()), $entity, $errorStore,
            $appendBlocks);
    }

    /**
     * {@inheritDoc}
     */
    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!$entity->getSite()) {
            $errorStore->addError('o:site', 'A page must belong to a site.');
        }

        $slug = $entity->getSlug();
        if (!is_string($slug) || $slug === '') {
            $errorStore->addError('o:slug', 'The slug cannot be empty.');
        }
        if (preg_match('/[^a-zA-Z0-9\/-]/u', $slug)) {
            $errorStore->addError('o:slug',
                'A slug can only contain letters, numbers, slashes, and hyphens.');
        }
        if ($entity->getSite() && !$this->isUnique($entity, array(
                'slug' => $slug,
                'site' => $entity->getSite()
        ))) {
            $errorStore->addError('o:slug', sprintf(
                'The slug "%s" is already taken.',
                $slug
            ));
        }

        if (!$entity->getTitle()) {
            $errorStore->addError('o:title', 'A page must have a title.');
        }
    }

    /**
     * Hydrate block data for the page.
     *
     * @param array $blockData
     * @param SitePage $page
     * @param bool $append
     */
    private function hydrateBlocks(array $blockData, SitePage $page, ErrorStore $errorStore,
        $append = false)
    {
        $blocks = $page->getBlocks();
        $existingBlocks = $blocks->toArray();
        $newBlocks = array();
        $position = 1;
        $fallbackBlock = array(
            'o:layout' => null,
            'o:data' => array()
        );

        foreach ($blockData as $inputBlock) {
            if (!is_array($inputBlock)) {
                continue;
            }

            $inputBlock = array_merge($fallbackBlock, $inputBlock);

            $block = current($existingBlocks);
            if ($block === false || $append) {
                $block = new SitePageBlock;
                $block->setPage($page);
                $newBlocks[] = $block;
            } else {
                // Null out values as we re-use them
                $existingBlocks[key($existingBlocks)] = null;
                next($existingBlocks);
            }

            if (!is_string($inputBlock['o:layout']) || $inputBlock['o:layout'] === '') {
                $errorStore->addError('o:block', 'All blocks must have a layout.');
                return;
            }
            if (!is_array($inputBlock['o:data'])) {
                $errorStore->addError('o:block', 'Block data must not be a scalar value.');
                return;
            }

            $block->setLayout($inputBlock['o:layout']);
            $block->setData($inputBlock['o:data']);

            // (Re-)order blocks by their order in the input
            $block->setPosition($position++);
        }

        // Remove any blocks that weren't reused
        if (!$append) {
            foreach ($existingBlocks as $key => $existingBlock) {
                if ($existingBlock !== null) {
                    $blocks->remove($key);
                }
            }
        }

        // Add any new blocks that had to be created
        foreach ($newBlocks as $newBlock) {
            $blocks->add($newBlock);
        }
    }
}
