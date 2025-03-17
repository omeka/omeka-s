<?php
namespace Collecting\View\Helper;

use Collecting\Entity\CollectingForm;
use Collecting\Entity\CollectingPrompt;
use Collecting\MediaType\Manager as MediaTypeManager;
use Composer\Semver\Comparator;
use Omeka\Api\Exception\BadRequestException;
use Omeka\Module\Manager as ModuleManager;
use Laminas\View\Helper\AbstractHelper;

class Collecting extends AbstractHelper
{
    /**
     * @var MediaTypeManager
     */
    protected $mediaTypeManager;

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var array
     */
    protected $types;

    /**
     * @var array
     */
    protected $inputTypes;

    /**
     * @var array
     */
    protected $mediaTypes;

    /**
     * @var array
     */
    protected $anonTypes;

    /**
     * @var array
     */
    protected $customVocabs;

    public function __construct(MediaTypeManager $mediaTypeManager, ModuleManager $moduleManager)
    {
        $this->mediaTypeManager = $mediaTypeManager;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Get all prompt types.
     *
     * @return array;
     */
    public function types()
    {
        if (null === $this->types) {
            $this->types = CollectingPrompt::getTypes();
        }
        return $this->types;
    }

    /**
     * Get all prompt input types.
     *
     * @return array;
     */
    public function inputTypes()
    {
        if (null === $this->inputTypes) {
            $this->inputTypes = CollectingPrompt::getInputTypes();
        }
        return $this->inputTypes;
    }

    /**
     * Is an input type available?
     *
     * @param string $inputType
     * @return bool
     */
    public function inputTypeIsAvailable($inputType)
    {
        switch ($inputType) {
            case 'custom_vocab':
                // Available when the CustomVocab module is active.
                $module = $this->moduleManager->getModule('CustomVocab');
                return (
                    $module
                    && ModuleManager::STATE_ACTIVE === $module->getState()
                );
            case 'numeric:timestamp':
            case 'numeric:interval':
            case 'numeric:duration':
            case 'numeric:integer':
                // Available when the NumericDataTypes module is active and the
                // version >= 1.2.0 (when it introduced numeric form elements).
                $module = $this->moduleManager->getModule('NumericDataTypes');
                return (
                    $module
                    && ModuleManager::STATE_ACTIVE === $module->getState()
                    && Comparator::greaterThanOrEqualTo($module->getDb('version'), '1.2.0')
                );
            case 'text':
            case 'textarea':
            case 'select':
            case 'item':
            case 'url':
                // Native input types are always available.
                return true;
            default:
                // Unknown input types are always unavailable.
                return false;
        }
    }

    /**
     * Get all prompt media types.
     *
     * @return array;
     */
    public function mediaTypes()
    {
        if (null === $this->mediaTypes) {
            $this->mediaTypes = [];
            $names = $this->mediaTypeManager->getRegisteredNames();
            foreach ($names as $name) {
                $this->mediaTypes[$name] = $this->mediaTypeManager->get($name)->getLabel();
            }
        }
        return $this->mediaTypes;
    }

    /**
     * Get all form anon types.
     *
     * @return array;
     */
    public function anonTypes()
    {
        if (null === $this->anonTypes) {
            $this->anonTypes = CollectingForm::getAnonTypes();
        }
        return $this->anonTypes;
    }

    /**
     * Get all custom vocabs from the CustomVocab module.
     *
     * @return array|false
     */
    public function customVocabs()
    {
        if (null === $this->customVocabs) {
            try {
                $response = $this->getView()->api()->search('custom_vocabs');
                $this->customVocabs = [];
                foreach ($response->getContent() as $customVocab) {
                    if (!$customVocab->terms()) {
                        continue; // URIs and Items vocab types not implemented
                    }
                    $this->customVocabs[$customVocab->id()] = $customVocab->label();
                }
            } catch (BadRequestException $e) {
                // The CustomVocab module is not installed or active.
                $this->customVocabs = false;
            }
        }
        return $this->customVocabs;
    }

    public function typeValue($key)
    {
        return isset($this->types()[$key]) ? $this->types()[$key] : null;
    }

    public function inputTypeValue($key)
    {
        return isset($this->inputTypes()[$key]) ? $this->inputTypes()[$key] : null;
    }

    public function mediaTypeValue($key)
    {
        return $this->mediaTypeManager->get($key)->getLabel();
    }

    public function anonTypeValue($key)
    {
        return isset($this->anonTypes()[$key]) ? $this->anonTypes()[$key] : null;
    }
}
