<?php declare(strict_types=1);

namespace Common\View\Helper;

use Laminas\I18n\Translator\Translator as LaminasTranslator;
use Laminas\View\Helper\AbstractHelper;

class Translator extends AbstractHelper
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
     * View helper to get the Laminas Translator used in Omeka.
     *
     * In view, it's a quick shortcut to `$this->translate()->getTranslator()`.
     */
    public function __invoke(): LaminasTranslator
    {
        return $this->translator;
    }
}
