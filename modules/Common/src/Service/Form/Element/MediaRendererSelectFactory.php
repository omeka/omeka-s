<?php declare(strict_types=1);

namespace Common\Service\Form\Element;

use Common\Form\Element\MediaRendererSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MediaRendererSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        // Unlike ingesters, there is no labels for renderers, so get the list
        // of used renderers like media-type.

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $services->get('Omeka\Connection');
        $sql = <<<'SQL'
SELECT `renderer`, `renderer`
FROM media
WHERE `renderer` IS NOT NULL
    AND `renderer` != ""
GROUP BY `renderer`
ORDER BY `renderer` ASC;
SQL;
        $renderers = $connection->executeQuery($sql)->fetchAllKeyValue();

        $element = new MediaRendererSelect(null, $options ?? []);
        return $element
            ->setValueOptions($renderers)
            ->setEmptyOption('Select media renderers…'); // @translate
    }
}
