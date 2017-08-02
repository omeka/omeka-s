<?php
namespace Omeka\Service\Form\Element;

use Interop\Container\ContainerInterface;
use Zend\Form\Element\Select;
use Zend\ServiceManager\Factory\FactoryInterface;

class LocaleSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $locales = [];
        $dir = sprintf('%s/application/language', OMEKA_PATH);
        foreach (new \DirectoryIterator($dir) as $fileinfo) {
            if ($fileinfo->isFile() && 'mo' === $fileinfo->getExtension()) {
                $localeId = $fileinfo->getBasename('.mo');
                $localeName = extension_loaded('intl')
                    ? \Locale::getDisplayName($localeId)
                    : $localeId;
                if ($localeId !== $localeName) {
                    $localeName = sprintf('%s (%s)', $localeName, $localeId);
                }
                $locales[$localeId] = $localeName;
            }
        }

        $element = new Select;
        $element->setValueOptions($locales);
        return $element;
    }
}
