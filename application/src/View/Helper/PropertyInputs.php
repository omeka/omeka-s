<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\Form\ElementInterface;
use Zend\Form\Element\Hidden;
use Zend\Form\Element\Text;
use Zend\Form\Element\Textarea;

class PropertyInputs extends AbstractHelper
{
    protected $element;
    protected $values;
    protected $resource;
    
    public function __invoke(ElementInterface $element, $resource = null) {
        $this->element = $element;
        $this->resource = $resource;
        //@TODO: how to dig up the values for an element's qName (term) on a resource?
        $this->values = false;
        if (!$element) {
            return $this;
        }
        if ($this->values) {
            foreach ($this->values as $value) {
                switch ($value->type) {
                    case 'literal':
                        return $this->renderLiteral($value);
                    break;

                    case 'resource':
                        return $this->renderResource($value);
                    break;
                }
            }
        }

        return $this->renderNew();
    }
    
    public function renderNew()
    {
        $html = '';
        $html .= $this->renderInputOptions();
        $html .= "<div class='type text input-option active'>";
        $html .= $this->renderValueComponent();
        $html .= $this->renderPropertyIdComponent();
        $html .= $this->renderLanguageComponent();
        $html .= "</div>";
        $html .= $this->renderResourceSelect();
        return $html;
    }
    
    protected function getName($component = '@value')
    {
        $name = $this->element->getOption('term'). "[{$this->getIndex()}][$component]";
        return $name;
    }
    
    protected function renderResourceSelect($value = null) 
    {
        if ($value) {
             $propertyId = $this->element->getAttribute('data-property-id');
             $html = '<div class="items input-option">
                    <p class="selected-resource template">
                        <span class="o-title"></span>
                        <input type="hidden" class="value" />
                        <input type="hidden" class="property" />
                    </p>
                </div>
                ';
        } else {
            $escapeAttr = $this->getView()->plugin('escapeHtmlAttr');
            $url = $escapeAttr($this->getView()->url(null, array("action" => "sidebar-select"), true));
            
            $html = '
                 <div class="items input-option">
                    <span class="default">No item selected</span>
                
                    <p class="selected-resource template">
                        <span class="o-title"></span>
                        <input type="hidden" class="value" />
                        <input type="hidden" class="property" />
                    </p>
            
                    <a href="#resource-select" 
                       class="button resource-select"
                       data-sidebar-content-url="' . $url . '"
                       >Select Omeka Resource</a>
                </div>
            ';
        }
        return $html;
    }
    
    protected function renderValueComponent()
    {
        //the core value gets into a fieldset, and ZF rewrites the name
        //so, I'm re-rewriting the name and other things here to keep
        //consistency in the form 
        $name = $this->getName();
        $valueElement = new Textarea($name);
        $valueElement->setAttributes(array(
                'class' => 'input-value',
                'data-property-qname' => $name,
                'data-property-id'    => $this->element->getAttribute('data-property-id'),
                ));
        
        $html = $this->getView()->formElement($valueElement);
        return $html;
    }
    
    protected function renderPropertyIdComponent()
    {
        $name = $this->getName('property_id');
        $propertyIdElement = new Hidden($name);
        $propertyIdElement->setAttributes(array(
                'value'               => $this->element->getAttribute('data-property-id'),
                'data-property-qname' => $name,
                'class'               => 'input-id'
            ));
        
        return $this->getView()->formHidden($propertyIdElement);
    }
    
    protected function renderInputOptions($type = 'literal')
    {
        //@TODO: eventually this will need to branch around the input type
        // (literal, object, external url) to show which is active
        $html = '
            <div class="input-options">
                    <a link="#" class="tab active o-icon-text"><span class="screen-reader-text">Text</span></a>
                    <a link="#" class="tab o-icon-items"><span class="screen-reader-text">Omeka Item</span></a>
                    <a link="#" class="tab o-icon-link"><span class="screen-reader-text">External Resource</span></a>
            </div>
        ';
        return $html;
    }
    
    protected function renderLanguageComponent()
    {
        $view = $this->getView();
        $name = $this->getName('@language');
        $languageElement = new Text($name, array(
                'label' => 'Language'
                ));
        $languageElement->setName($name);
        $languageElement->setAttributes(array(
                'class' => 'value-language'
                ));
        $html = $view->formLabel($languageElement);
        $html .= $view->formText($languageElement);
        return $html;
    }
    
    protected function getIndex()
    {
        return $this->element->getOption('index');
    }
}
    