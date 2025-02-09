<?php
namespace Omeka\Installation\Task;

use Omeka\Api\Manager as ApiManager;
use Omeka\Installation\Installer;

/**
 * Install default resource templates.
 */
class InstallDefaultTemplatesTask implements TaskInterface
{
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
    protected $templates = [
        /*
         * The "Base Resource" resource template, comprising recommended
         * properties for a dpla:SourceResource as described by DPLA's Metadata
         * Application Profile (MAP) version 4.0. These properties are roughly
         * ordered by importance (required, recommended, and optional).
         */
        'Base Resource' => [
            ['prefix' => 'dcterms', 'local_name' => 'title'],
            ['prefix' => 'dcterms', 'local_name' => 'rights'],
            ['prefix' => 'dcterms', 'local_name' => 'type'],
            ['prefix' => 'dcterms', 'local_name' => 'creator'],
            ['prefix' => 'dcterms', 'local_name' => 'date'],
            ['prefix' => 'dcterms', 'local_name' => 'description'],
            ['prefix' => 'dcterms', 'local_name' => 'format'],
            ['prefix' => 'dcterms', 'local_name' => 'language'],
            ['prefix' => 'dcterms', 'local_name' => 'spatial', 'alternate_label' => 'Place'],
            ['prefix' => 'dcterms', 'local_name' => 'publisher'],
            ['prefix' => 'dcterms', 'local_name' => 'alternative'],
            ['prefix' => 'dcterms', 'local_name' => 'contributor'],
            ['prefix' => 'dcterms', 'local_name' => 'extent'],
            ['prefix' => 'dcterms', 'local_name' => 'identifier'],
            ['prefix' => 'dcterms', 'local_name' => 'relation'],
            ['prefix' => 'dcterms', 'local_name' => 'isReplacedBy'],
            ['prefix' => 'dcterms', 'local_name' => 'replaces'],
            ['prefix' => 'dcterms', 'local_name' => 'rightsHolder'],
            ['prefix' => 'dcterms', 'local_name' => 'subject'],
            ['prefix' => 'dcterms', 'local_name' => 'temporal'],
        ],
    ];

    public function perform(Installer $installer)
    {
        $this->setApi($installer->getServiceLocator()->get('Omeka\ApiManager'));
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
            throw new \RuntimeException('The API manager must be set to this task.');
        }
        return $this->api;
    }

    public function setApi(ApiManager $api)
    {
        $this->api = $api;
    }
}
