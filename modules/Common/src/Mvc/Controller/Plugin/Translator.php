<?php declare(strict_types=1);

namespace Common\Mvc\Controller\Plugin;

use Laminas\I18n\Translator\Translator as LaminasTranslator;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class Translator extends AbstractPlugin
{
    /**
     * @var \Laminas\I18n\Translator\Translator
     */
    protected $translator;

    public function __construct(LaminasTranslator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Get the Laminas Translator used in Omeka.
     *
     * In controller, it's a quick shortcut to `$this->getEvent()->getApplication()->getServiceManager()->get('MvcTranslator')`
     * or `$this->viewHelpers()->get('translate')->getTranslator()`.
     */
    public function __invoke(): LaminasTranslator
    {
        return $this->translator;
    }
}
