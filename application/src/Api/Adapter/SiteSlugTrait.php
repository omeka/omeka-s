<?php
namespace Omeka\Api\Adapter;

use PDO;

/**
 * Trait for shared slug behavior for sites and pages
 */
trait SiteSlugTrait
{
    /**
     * Get a valid and unused slug for a given title
     *
     * @param string $title
     * @param Site $site
     * @return string
     */
    protected function getAutomaticSlug($title, $site = null)
    {
        if ($site) {
            $siteId = $site->getId();
            if (!$siteId) {
                return null;
            }
        }

        $slug = $this->slugify($title);

        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $conn = $this->getServiceLocator()->get('Omeka\Connection');
        $table = $em->getClassMetadata($this->getEntityClass())->getTableName();
        $qb = $conn->createQueryBuilder();
        $where = 'slug LIKE ' . $qb->createPositionalParameter($slug . '%');
        if ($site) {
            $where = $qb->expr()->andX(
                $where,
                'site_id = ' . $qb->createPositionalParameter($siteId)
            );
        }
        $qb->select('slug')
            ->from($table)
            ->where($where);
        $stmt = $qb->execute();
        $similarSlugs = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!($similarSlugs && in_array($slug, $similarSlugs))) {
            return $slug;
        }

        $suffix = 1;
        while (in_array($slug . $suffix, $similarSlugs)) {
            $suffix++;
        }
        return $slug . $suffix;
    }

    /**
     * Transform the given string into a valid URL slug
     *
     * @param string $input
     * @return string
     */
    protected function slugify($input)
    {
        if (extension_loaded('intl')) {
            $transliterator = \Transliterator::createFromRules(':: NFD; :: [:Nonspacing Mark:] Remove; :: NFC;');
            $slug = $transliterator->transliterate($input);
        } elseif (extension_loaded('iconv')) {
            $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $input);
        } else {
            $slug = $input;
        }
        $slug = mb_strtolower($slug, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9-]+/u', '-', $slug);
        $slug = preg_replace('/-{2,}/', '-', $slug);
        $slug = preg_replace('/-*$/', '', $slug);
        return $slug;
    }
}
