<?php declare(strict_types=1);

namespace Common\Service\Form\Element;

use Common\Form\Element\MediaTypeSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MediaTypeSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $services->get('Omeka\Connection');
        $sql = <<<'SQL'
SELECT `media_type`, `media_type`
FROM media
WHERE `media_type` IS NOT NULL
    AND `media_type` != ""
GROUP BY `media_type`
ORDER BY `media_type` ASC;
SQL;
        $list = $connection->executeQuery($sql)->fetchAllKeyValue();

        $element = new MediaTypeSelect(null, $options ?? []);
        return $element
            ->setValueOptions($list)
            ->setEmptyOption('Select media type…'); // @translate
    }
}
