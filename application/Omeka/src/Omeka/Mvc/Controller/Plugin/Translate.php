<?php
namespace Omeka\Mvc\Controller\Plugin;

use Zend\I18n\Translator\TranslatorInterface;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Translate extends AbstractPlugin
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * Translate a message
     *
     * @param  string $message
     * @param  string $textDomain
     * @param  string $locale
     * @return string
     */
    public function __invoke($message, $textDomain = 'default', $locale = null)
    {
        return $this->getTranslator()->translate($message, $textDomain, $locale);
    }

    /**
     * Get the translator service
     *
     * return TranslatorInterface
     */
    public function getTranslator()
    {
        if (!$this->translator instanceof TranslatorInterface) {
            $this->translator = $this->getController()
                ->getServiceLocator()->get('MvcTranslator');
        }
        return $this->translator;
    }
}
