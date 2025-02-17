<?php
namespace Mapping\CsvMapping;

use CSVImport\Mapping\AbstractMapping;
use Laminas\View\Renderer\PhpRenderer;

class CsvMapping extends AbstractMapping
{
    protected $label = 'Map'; // @translate
    protected $name = 'mapping-module';

    public function getSidebar(PhpRenderer $view)
    {
        return $view->partial('common/csv-import/mapping');
    }

    public function processRow(array $row)
    {
        // Reset the data and the map between rows.
        $this->setHasErr(false);
        $json = [];

        // Set columns.
        $latMap = isset($this->args['column-map-lat']) ? array_keys($this->args['column-map-lat']) : [];
        $lngMap = isset($this->args['column-map-lng']) ? array_keys($this->args['column-map-lng']) : [];
        $latLngMap = isset($this->args['column-map-latlng']) ? array_keys($this->args['column-map-latlng']) : [];
        $boundsMap = isset($this->args['column-map-bounds']) ? array_keys($this->args['column-map-bounds']) : [];

        $multivalueMap = $this->args['column-multivalue'] ?? [];
        $multivalueSeparator = $this->args['multivalue_separator'];

        // Set default values.
        $lat = null;
        $lng = null;
        $mappingJson = [];

        foreach ($row as $index => $value) {
            if (trim($value) === '') {
                continue;
            }
            if (in_array($index, $latMap)) {
                $lat = $value;
            }
            if (in_array($index, $lngMap)) {
                $lng = $value;
            }
            if (in_array($index, $latLngMap)) {
                if (empty($multivalueMap[$index])) {
                    $latLngs = [$value];
                } else {
                    $latLngs = explode($multivalueSeparator, $value);
                }
                foreach ($latLngs as $latLngString) {
                    $latLng = array_map('trim', explode('/', $latLngString));
                    if (count($latLng) !== 2) {
                        continue;
                    }
                    $json['o-module-mapping:feature'][] = [
                        'o-module-mapping:geography-type' => 'point',
                        'o-module-mapping:geography-coordinates' => [$latLng[1], $latLng[0]],
                    ];
                }
            }
            if (in_array($index, $boundsMap)) {
                $mappingJson['o-module-mapping:bounds'] = $value;
            }
        }

        if ($lat && $lng) {
            $json['o-module-mapping:feature'][] = [
                'o-module-mapping:geography-type' => 'point',
                'o-module-mapping:geography-coordinates' => [$lng, $lat],
            ];
        }
        if (isset($mappingJson['o-module-mapping:bounds'])) {
            $json['o-module-mapping:mapping'] = $mappingJson;
        }
        return $json;
    }
}
