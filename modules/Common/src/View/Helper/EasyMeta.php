<?php declare(strict_types=1);

namespace Common\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class EasyMeta extends AbstractHelper
{
    /**
     * @var \Common\Stdlib\EasyMeta
     */
    protected $easyMeta;

    public function __construct(\Common\Stdlib\EasyMeta $easyMeta)
    {
        $this->easyMeta = $easyMeta;
    }

    /**
     * Get EasyMeta.
     */
    public function __invoke(): \Common\Stdlib\EasyMeta
    {
        return $this->easyMeta;
    }

    /**
     * Proxy to EasyMeta.
     *
     * @see \Common\Stdlib\EasyMeta
     *
     * @method string|null entityClass($name)
     * @method string|null resourceName($name)
     * @method string|null resourceLabel($name)
     * @method string|null resourceLabelPlural($name)
     * @method string|null dataTypeName(?string $dataType)
     * @method array dataTypeNames($dataTypes = null)
     * @method array dataTypeNamesUsed($dataTypes = null)
     * @method array dataTypeLabels($dataTypes = null)
     * @method string|null dataTypeMain(?string $dataType)
     * @method array dataTypeMains($dataType = null)
     * @method string|null dataTypeMainCustomVocab(?string $dataType)
     * @method array dataTypeMainCustomVocabs($dataTypes = null)
     * @method int|null propertyId($termOrId)
     * @method array propertyIds($termsOrIds = null)
     * @method array propertyIdsUsed($termsOrIds = null)
     * @method string|null propertyTerm($termOrId)
     * @method array propertyTerms($termsOrIds = null)
     * @method string|null propertyLabel($termOrId)
     * @method array propertyLabels($termsOrIds = null)
     * @method int|null resourceClassId($termOrId)
     * @method array resourceClassIds($termsOrIds = null)
     * @method array resourceClassIdsUsed($termsOrIds = null)
     * @method string|null resourceClassTerm($termOrId)
     * @method array resourceClassTerms($termsOrIds = null)
     * @method string|null resourceClassLabel($termOrId)
     * @method array resourceClassLabels($termsOrIds = null)
     * @method int|null resourceTemplateId($labelOrId)
     * @method array resourceTemplateIds($labelsOrIds = null)
     * @method array resourceTemplateIdsUsed($labelsOrIds = null)
     * @method string|null resourceTemplateLabel($labelOrId)
     * @method array resourceTemplateLabels($labelsOrIds = null)
     * @method int|null resourceTemplateClassId($labelOrId)
     * @method array resourceTemplateClassIds($labelsOrIds = null)
     * @method int|null vocabularyId($prefixOrUriOrId)
     * @method array vocabularyIds($prefixesOrUrisOrIds = null)
     * @method string|null vocabularyPrefix($prefixOrUriOrId)
     * @method array vocabularyPrefixes($prefixesOrUrisOrIds = null)
     * @method string|null vocabularyUri($prefixOrUriOrId)
     * @method array vocabularyUrisByPrefixes($prefixesOrUrisOrIds = null)
     * @method string|null vocabularyLabel($prefixesOrUrisOrIds)
     * @method array vocabularyLabels($prefixesOrUrisOrIds = null)
     */
    public function __call(string $name, array $arguments)
    {
        return $this->easyMeta->$name(...$arguments);
    }
}
