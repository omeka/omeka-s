<?php
namespace Omeka\Form\Element;

use Omeka\Stdlib\HtmlPurifier;
use Zend\Form\Element\Textarea;
use Zend\InputFilter\InputProviderInterface;

/**
 * Textarea element for HTML.
 *
 * Purifies the markup after form submission.
 */
class HtmlTextarea extends Textarea implements InputProviderInterface
{
    /**
     * @var HtmlPurifier
     */
    protected $htmlPurifier;

    public function getInputSpecification()
    {
        return [
            'name' => $this->getName(),
            'required' => false,
            'filters' => [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'purifyHtml'],
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
