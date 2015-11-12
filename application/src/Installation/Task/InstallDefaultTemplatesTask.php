<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Installer;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Install default resource templates.
 */
class InstallDefaultTemplatesTask implements TaskInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var \Omeka\Api\Manager
     */
    protected $api;

    /**
     * @var array Cached properties
     */
    protected $cache = [];

    /**
     * Default resource templates.
     *
     * Keys are the (unique) template labels. Values are arrays containing:
     *
     * - prefix: vocabulary prefix (required)
     * - local_name: property local name (required)
     * - alternate_label: template alternate label (optional)
     * - alternate_comment: template alternate comment (optional)
     *
     * @var array
     */
    protected $templates = [];

    public function perform(Installer $installer)
    {
        $this->setServiceLocator($installer->getServiceLocator());
        foreach (array_keys($this->templates) as $label) {
            $this->installTemplate($label);
        }
    }

    /**
     * Install a default resource template.
     *
     * @param string $label A label of a template defined in this class
     * @return null|false Returns false if the label does not exist
     */
    public function installTemplate($label)
    {
        if (!isset($this->templates[$label])) {
            return false;
        }

        $resTemProps = [];
        foreach ($this->templates[$label] as $property) {
            if (!isset($this->cache[$property['prefix']])) {
                // Cache this vocabulary's properties.
                $this->cacheProperties($property['prefix']);
            }
            if (!isset($property['prefix']) || !isset($property['local_name'])) {
                // Must have vocabulary prefix and property local name.
                continue;
            }
            $propertyId = $this->cache[$property['prefix']][$property['local_name']];
            $altLabel = null;
            if (isset($property['alternate_label'])) {
                $altLabel = $property['alternate_label'];
            }
            $altComment = null;
            if (isset($property['alternate_comment'])) {
                $altComment = $property['alternate_comment'];
            }
            $resTemProps[] = [
                'o:property' => ['o:id' => $propertyId],
                'o:alternate_label' => $altLabel,
                'o:alternate_comment' => $altComment,
            ];
        }

        $this->getApi()->create('resource_templates', [
            'o:label' => $label,
            'o:resource_template_property' => $resTemProps,
        ]);
    }

    protected function cacheProperties($prefix)
    {
        $properties = $this->getApi()
            ->search('properties', ['vocabulary_prefix' => $prefix])
            ->getContent();
        foreach ($properties as $property) {
            $this->cache[$prefix][$property->localName()] = $property->id();
        }
    }

    protected function getApi()
    {
        if (!$this->api) {
            $this->api = $this->getServiceLocator()->get('Omeka\ApiManager');
        }
        return $this->api;
    }
}
