<?php
namespace Mapping\Site\BlockLayout;

use Composer\Semver\Comparator;
use Mapping\Module;
use NumericDataTypes\DataType\Timestamp;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Module\Manager as ModuleManager;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\HtmlPurifier;
use Laminas\Form\Element;
use Laminas\View\Renderer\PhpRenderer;

abstract class AbstractMap extends AbstractBlockLayout
{
    /**
     * @var HtmlPurifier
     */
    protected $htmlPurifier;

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    public function __construct(HtmlPurifier $htmlPurifier, ModuleManager $moduleManager)
    {
        $this->htmlPurifier = $htmlPurifier;
        $this->moduleManager = $moduleManager;
    }

    public function prepareForm(PhpRenderer $view)
    {
        $view->headLink()->appendStylesheet($view->assetUrl('node_modules/leaflet/dist/leaflet.css', 'Mapping'));
        $view->headLink()->appendStylesheet($view->assetUrl('node_modules/leaflet.fullscreen/Control.FullScreen.css', 'Mapping'));

        $view->headScript()->appendFile($view->assetUrl('node_modules/leaflet/dist/leaflet.js', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('node_modules/leaflet-providers/leaflet-providers.js', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('node_modules/leaflet.fullscreen/Control.FullScreen.js', 'Mapping'));

        $view->headScript()->appendFile($view->assetUrl('js/mapping-block-form.js', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('js/control.default-view.js', 'Mapping'));
    }

    public function prepareRender(PhpRenderer $view)
    {
        $view->headLink()->appendStylesheet($view->assetUrl('css/mapping.css', 'Mapping'));
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $data = $this->filterBlockData($block ? $block->data() : []);
        $basemapProviderSelect = (new Element\Select('o:block[__blockIndex__][o:data][basemap_provider]'))
            ->setLabel($view->translate('Basemap provider'))
            ->setOption('info', $view->translate('Select the basemap provider. The default is OpenStreetMap.Mapnik. These providers are offered AS-IS. There is no guarantee of service or speed.'))
            ->setValue($data['basemap_provider'])
            ->setValueOptions(Module::BASEMAP_PROVIDERS)
            ->setEmptyOption('[Default provider]') // @translate
            ->setAttribute('class', 'basemap-provider');
        $minZoomInput = (new Element\Number('o:block[__blockIndex__][o:data][min_zoom]'))
            ->setLabel($view->translate('Minimum zoom level'))
            ->setOption('info', $view->translate('The minimum zoom level down to which the map will be displayed. The default is 0.'))
            ->setValue($data['min_zoom'])
            ->setAttribute('class', 'min-zoom')
            ->setAttribute('min', 0)
            ->setAttribute('step', 1)
            ->setAttribute('placeholder', '0');
        $maxZoomInput = (new Element\Number('o:block[__blockIndex__][o:data][max_zoom]'))
            ->setLabel($view->translate('Maximum zoom level'))
            ->setOption('info', $view->translate('Set the maximum zoom level up to which the map will be displayed. The default is 19.'))
            ->setValue($data['max_zoom'])
            ->setAttribute('class', 'max-zoom')
            ->setAttribute('min', 0)
            ->setAttribute('step', 1)
            ->setAttribute('placeholder', '19');
        $scrollWheelZoom = (new Element\Select('o:block[__blockIndex__][o:data][scroll_wheel_zoom]'))
            ->setLabel($view->translate('Scroll wheel zoom'))
            ->setOption('info', $view->translate('Set whether users can zoom with their mouse wheel when hovering over the map, either automatically upon page load or after clicking inside the map.'))
            ->setValue($data['scroll_wheel_zoom'])
            ->setValueOptions([
                '' => $view->translate('Enabled'),
                'disable' => $view->translate('Disabled'),
                'click' => $view->translate('Disabled until map click'),

            ]);
        $form = $view->partial(
            'common/block-layout/mapping-block-form',
            [
                'data' => $data,
                'timelineIsAvailable' => $this->timelineIsAvailable(),
                'basemapProviderSelect' => $basemapProviderSelect,
                'minZoomInput' => $minZoomInput,
                'maxZoomInput' => $maxZoomInput,
                'scrollWheelZoom' => $scrollWheelZoom,
            ]
        );
        return $form;
    }

    /**
     * Filter Map block data.
     *
     * We filter data on input and output to ensure a valid format, regardless
     * of version.
     *
     * @param array $data
     * @return array
     */
    protected function filterBlockData($data)
    {
        // Filter the defualt view data.
        $basemapProvider = null;
        if (isset($data['basemap_provider']) && array_key_exists($data['basemap_provider'], Module::BASEMAP_PROVIDERS)) {
            $basemapProvider = $data['basemap_provider'];
        }
        $minZoom = null;
        if (isset($data['min_zoom']) && is_numeric($data['min_zoom'])) {
            $minZoom = $data['min_zoom'];
        }
        $maxZoom = null;
        if (isset($data['max_zoom']) && is_numeric($data['max_zoom'])) {
            $maxZoom = $data['max_zoom'];
        }
        $scrollWheelZoom = '';
        if (isset($data['scroll_wheel_zoom'])) {
            $scrollWheelZoom = $data['scroll_wheel_zoom'];
        }
        $bounds = null;
        if (isset($data['bounds'])
            && 4 === count(array_filter(explode(',', $data['bounds']), 'is_numeric'))
        ) {
            $bounds = $data['bounds'];
        }

        // Filter the WMS overlay data.
        $wmsOverlays = [];
        if (isset($data['wms']) && is_array($data['wms'])) {
            foreach ($data['wms'] as $wmsOverlay) {
                // WMS data must have label and base URL.
                if (is_array($wmsOverlay)
                    && isset($wmsOverlay['label'])
                    && isset($wmsOverlay['base_url'])
                ) {
                    $layers = '';
                    if (isset($wmsOverlay['layers']) && '' !== trim($wmsOverlay['layers'])) {
                        $layers = $wmsOverlay['layers'];
                    }
                    $wmsOverlay['layers'] = $layers;

                    $styles = '';
                    if (isset($wmsOverlay['styles']) && '' !== trim($wmsOverlay['styles'])) {
                        $styles = $wmsOverlay['styles'];
                    }
                    $wmsOverlay['styles'] = $styles;

                    $open = null;
                    if (isset($wmsOverlay['open']) && $wmsOverlay['open']) {
                        $open = true;
                    }
                    $wmsOverlay['open'] = $open;

                    $wmsOverlays[] = $wmsOverlay;
                }
            }
        }

        // Filter the timeline data.
        $timeline = [
            'title_headline' => null,
            'title_text' => null,
            'fly_to' => null,
            'show_contemporaneous' => null,
            'timenav_position' => null,
            'data_type_properties' => null,
        ];
        if (isset($data['timeline']) && is_array($data['timeline'])) {
            if (isset($data['timeline']['title_headline'])) {
                $timeline['title_headline'] = $this->htmlPurifier->purify($data['timeline']['title_headline']);
            }
            if (isset($data['timeline']['title_text'])) {
                $timeline['title_text'] = $this->htmlPurifier->purify($data['timeline']['title_text']);
            }
            if (isset($data['timeline']['fly_to']) && is_numeric($data['timeline']['fly_to'])) {
                $timeline['fly_to'] = $data['timeline']['fly_to'];
            }
            if (isset($data['timeline']['show_contemporaneous']) && $data['timeline']['show_contemporaneous']) {
                $timeline['show_contemporaneous'] = true;
            }
            if (isset($data['timeline']['timenav_position']) && in_array($data['timeline']['timenav_position'], ['full_width_below', 'full_width_above'])) {
                $timeline['timenav_position'] = $data['timeline']['timenav_position'];
            }
            if (isset($data['timeline']['data_type_properties'])) {
                // Anticipate future use of multiple numeric properties per
                // timeline by saving an array of properties.
                if (is_string($data['timeline']['data_type_properties'])) {
                    $data['timeline']['data_type_properties'] = [$data['timeline']['data_type_properties']];
                }
                if (is_array($data['timeline']['data_type_properties'])) {
                    foreach ($data['timeline']['data_type_properties'] as $dataTypeProperty) {
                        if (is_string($dataTypeProperty)) {
                            $dataTypeProperty = explode(':', $dataTypeProperty);
                            if (3 === count($dataTypeProperty)) {
                                [$namespace, $type, $propertyId] = $dataTypeProperty;
                                if ('numeric' === $namespace
                                    && in_array($type, ['timestamp', 'interval'])
                                    && is_numeric($propertyId)
                                ) {
                                    $timeline['data_type_properties'][] = sprintf('%s:%s:%s', $namespace, $type, $propertyId);
                                }
                            }
                        }
                    }
                }
            }
        }

        return [
            'basemap_provider' => $basemapProvider,
            'max_zoom' => $maxZoom,
            'min_zoom' => $minZoom,
            'scroll_wheel_zoom' => $scrollWheelZoom,
            'bounds' => $bounds,
            'wms' => $wmsOverlays,
            'timeline' => $timeline,
        ];
    }

    /**
     * Is the timeline feature available?
     *
     * @return bool
     */
    public function timelineIsAvailable()
    {
        // Available when the NumericDataTypes module is active and the version
        // >= 1.1.0 (when it introduced interval data type).
        $module = $this->moduleManager->getModule('NumericDataTypes');
        return (
            $module
            && ModuleManager::STATE_ACTIVE === $module->getState()
            && Comparator::greaterThanOrEqualTo($module->getDb('version'), '1.1.0')
        );
    }

    /**
     * Get timeline options.
     *
     * @see https://timeline.knightlab.com/docs/options.html
     * @param srray $data
     * @return array
     */
    public function getTimelineOptions(array $data)
    {
        return [
            'debug' => false,
            'timenav_position' => 'bottom',
        ];
    }

    /**
     * Get timeline data.
     *
     * @see https://timeline.knightlab.com/docs/json-format.html
     * @param array $events
     * @param array $data
     * @param PhpRenderer $view
     * @return array
     */
    public function getTimelineData(array $events, array $data, PhpRenderer $view)
    {
        $timelineData = [
            'title' => null,
            'events' => $events,
        ];
        // Set the timeline title.
        if (isset($data['timeline']['title_headline']) || isset($data['timeline']['title_text'])) {
            $timelineData['title'] = [
                'text' => [
                    'headline' => $data['timeline']['title_headline'],
                    'text' => $data['timeline']['title_text'],
                ],
            ];
        }
        return $timelineData;
    }

    /**
     * Get a timeline event.
     *
     * @see https://timeline.knightlab.com/docs/json-format.html#json-slide
     * @param ItemRepresentation $item
     * @param array $dataTypeProperties
     * @return array
     */
    public function getTimelineEvent($item, array $dataTypeProperties, $view)
    {
        $property = null;
        $dataType = null;
        $value = null;
        foreach ($dataTypeProperties as $dataTypeProperty) {
            $dataTypeProperty = explode(':', $dataTypeProperty);
            try {
                $property = $view->api()->read('properties', $dataTypeProperty[2])->getContent();
            } catch (NotFoundException $e) {
                // Invalid property.
                continue;
            }
            $dataType = sprintf('%s:%s', $dataTypeProperty[0], $dataTypeProperty[1]);
            $value = $item->value($property->term(), ['type' => $dataType]);
            if ($value) {
                // Set only the first matching numeric value.
                break;
            }
        }
        if (!$value) {
            // This item has no numeric values.
            return;
        }

        // Set the unique ID and "text" object.
        $title = $item->value('dcterms:title');
        $description = $item->value('dcterms:description');
        $event = [
            'unique_id' => (string) $item->id(), // must cast to string
            'text' => [
                'headline' => $item->link($item->displayTitle(null, $view->lang()), null, ['target' => '_blank']),
                'text' => $item->displayDescription(),
            ],
        ];

        // Set the "media" object.
        $media = $item->primaryMedia();
        if ($media) {
            $event['media'] = [
                'url' => $media->thumbnailUrl('large'),
                'thumbnail' => $media->thumbnailUrl('medium'),
                'link' => $item->url(),
                'alt' => $media->altTextResolved(),
            ];
        }

        // Set the start and end "date" objects.
        if ('numeric:timestamp' === $dataType) {
            $dateTime = Timestamp::getDateTimeFromValue($value->value());
            $event['start_date'] = [
                'year' => $dateTime['year'],
                'month' => $dateTime['month'],
                'day' => $dateTime['day'],
                'hour' => $dateTime['hour'],
                'minute' => $dateTime['minute'],
                'second' => $dateTime['second'],
            ];
        } elseif ('numeric:interval' === $dataType) {
            [$intervalStart, $intervalEnd] = explode('/', $value->value());
            $dateTimeStart = Timestamp::getDateTimeFromValue($intervalStart);
            $event['start_date'] = [
                'year' => $dateTimeStart['year'],
                'month' => $dateTimeStart['month'],
                'day' => $dateTimeStart['day'],
                'hour' => $dateTimeStart['hour'],
                'minute' => $dateTimeStart['minute'],
                'second' => $dateTimeStart['second'],
            ];
            $dateTimeEnd = Timestamp::getDateTimeFromValue($intervalEnd, false);
            $event['end_date'] = [
                'year' => $dateTimeEnd['year'],
                'month' => $dateTimeEnd['month_normalized'],
                'day' => $dateTimeEnd['day_normalized'],
                'hour' => $dateTimeEnd['hour_normalized'],
                'minute' => $dateTimeEnd['minute_normalized'],
                'second' => $dateTimeEnd['second_normalized'],
            ];
            $event['display_date'] = sprintf(
                '%s — %s',
                $dateTimeStart['date']->format($dateTimeStart['format_render']),
                $dateTimeEnd['date']->format($dateTimeEnd['format_render'])
            );
        }
        return $event;
    }
}
