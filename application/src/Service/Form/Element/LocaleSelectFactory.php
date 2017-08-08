<?php
namespace Omeka\Service\Form\Element;

use Interop\Container\ContainerInterface;
use Zend\Form\Element\Select;
use Zend\ServiceManager\Factory\FactoryInterface;

class LocaleSelectFactory implements FactoryInterface
{
    protected $services;
    protected $intlLoaded;

    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $this->services = $services;
        $this->intlLoaded = extension_loaded('intl');

        $locales = [];
        $dir = sprintf('%s/application/language', OMEKA_PATH);
        foreach (new \DirectoryIterator($dir) as $fileinfo) {
            if ($fileinfo->isFile() && 'mo' === $fileinfo->getExtension()) {
                $localeId = $fileinfo->getBasename('.mo');
                $locales[$localeId] = $this->getValueOption($localeId);
            }
        }
        natcasesort($locales);

        $element = new Select;
        $element->setValueOptions($locales);
        $emptyOption = sprintf(
            'Default—%s', // @translate
            $this->getValueOption($this->getDefaultLocaleId())
        );
        $element->setEmptyOption($emptyOption);
        return $element;
    }

    public function getValueOption($localeId)
    {
        $localeName = $this->intlLoaded
            ? \Locale::getDisplayName($localeId)
            : $localeId;
        if ($localeId !== $localeName) {
            $localeName = sprintf('%s [%s]', $localeName, $localeId);
        }
        return $localeName;
    }

    public function getDefaultLocaleId()
    {
        $config = $this->services->get('Config');
        $localeId = null;
        if (isset($config['translator']['locale'])) {
            $localeId = $config['translator']['locale'];
        } elseif ($this->intlLoaded) {
            $localeId = \Locale::getDefault();
        }
        return $localeId;
    }
}
