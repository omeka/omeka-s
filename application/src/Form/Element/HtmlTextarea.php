<?php
namespace Omeka\Form\Element;

use Omeka\Service\HtmlPurifier;
use Zend\Form\Element\Textarea;
use Zend\InputFilter\InputProviderInterface;

/**
 * Textarea element for HTML.
 *
 * Purifies the markup after form submission. To automatically enable an inline
 * HTML editor on this element:
 * 
 *   - set the "enable _htmleditor" option to true
 *   - set a unique "id" attribute
 *   - call the prepareHtmlTextarea() view helper in the template
 */
class HtmlTextarea extends Textarea implements InputProviderInterface
{
    /**
     * @var HtmlPurifier
     */
    protected $htmlPurifier;

    /**
     * @var bool Enable an HTML editor for this element?
     */
    protected $enableHtmlEditor;

    public function setOptions($options)
    {
        parent::setOptions($options);

        if (isset($this->options['enable_htmleditor'])) {
            $this->enableHtmlEditor($this->options['enable_htmleditor']);
        }

        return $this;
    }

    /**
     * Enable an HTML editor for this 
     */
    public function enableHtmlEditor($enableHtmlEditor)
    {
        $this->enableHtmlEditor = (bool) $enableHtmlEditor;
        $this->setAttribute('type', $this->enableHtmlEditor ? 'htmltextarea' : 'textarea');
        return $this;
    }

    public function getInputSpecification()
    {
        return [
            'name' => $this->getName(),
            'filters' => [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'purifyHtml']
                    ],
                ],
            ],
        ];
    }

    /**
     * Purify the HTML.
     *
     * @param string $html
     * @return string
     */
    public function purifyHtml($html)
    {
        return $this->htmlPurifier->purify($html);
    }

    /**
     * Set the HTML purifier service.
     *
     * @param HtmlPurifier $htmlPurifier
     */
    public function setHtmlPurifier(HtmlPurifier $htmlPurifier)
    {
        $this->htmlPurifier = $htmlPurifier;
    }
}
