# Leaflet.Deflate


[![Build Status](https://travis-ci.org/oliverroick/Leaflet.Deflate.svg?branch=master)](https://travis-ci.org/oliverroick/Leaflet.Deflate)
[![npm version](https://badge.fury.io/js/Leaflet.Deflate.svg)](https://badge.fury.io/js/Leaflet.Deflate)

Leaflet.Deflate is a plugin for [Leaflet](https://leafletjs.com/) that improves the readability of large-scale web maps. It substitutes polygons and lines with markers when their screen size falls below a defined threshold.

![Example](https://user-images.githubusercontent.com/159510/52898557-a491d700-31df-11e9-86c4-7a585dc50372.gif)

**Note:** The documentation and examples below are for Leaflet.Deflate's latest release. [Documentation of older releases is available](#previous-releases).

## Installation

### Using a hosted version

Include the source into the `head` section of your document.

```html
<script src="https://unpkg.com/Leaflet.Deflate/dist/L.Deflate.js"></script>
```

### Install via NPM

If you use the [npm package manager](https://www.npmjs.com/), you can fetch a local copy by running:

```bash
npm install Leaflet.Deflate
```

You will find a copy of the release files in `node_modules/Leaflet.Deflate/dist`.

## API

### `L.deflate`

`L.deflate` is the main class of `Leaflet.Deflate`. Use it to create a feature group that deflates all layers added to the group.

#### Usage example

Initialize `L.deflate` and add it to your map. Then add layers you want to deflate.

```javascript
const map = L.map("map");
const features = L.deflate({minSize: 10})
features.addTo(map);

// add layers
const polygon = L.polygon([
    [51.509, -0.08],
    [51.503, -0.06],
    [51.51, -0.047]
])
.addTo(features);

// works with GeoJSONLayer too
L.geoJson(json).addTo(features);
```

#### Creation

Factory                       | Description
----------------------------  | -------------
`L.deflate(<Object> options)` | Creates a new deflatable feature group, optionally given an options object.

#### Options

Option          | Type      | Default | Description
--------------- | --------- | ------- | -------------
`minSize`       | `int`     | `20`    | Optional. Defines the minimum width and height in pixels for a path to be displayed in its actual shape. Anything smaller than the defined `minSize` will be deflated.
`markerType`    | `object`  | `L.marker` | Optional. Specifies the marker type to use for deflated features. Must be either `L.marker` or `L.circleMarker`.
`markerOptions` | `object` or `function`  | `{}`    | Optional. Customize the markers of deflated features using [Leaflet marker options](http://leafletjs.com/reference-1.3.0.html#marker). If you specify `L.circleMarker` as `markerType` use [Leaflet circleMarker options](https://leafletjs.com/reference-1.3.0.html#circlemarker) instead.
`markerLayer`   | `L.featureGroup` | `L.featureGroup` | A `L.FeatureGroup` instance used to display deflate markers. Use this to realise special behaviours, such as clustering markers.
`greedyCollapse`| `boolean` | `true` | Specify false if you would like that features would be deflated only if both of their width and height are less than `minSize`.

## Examples

### Basic

To create a basic deflatable layer, you have to

1. Create an `L.deflate` feature group and add it to your map.
2. Add features to the `L.Deflate` feature group.

```javascript
const map = L.map("map").setView([51.505, -0.09], 12);

const deflate_features = L.deflate({minSize: 20});
deflate_features.addTo(map);

const polygon = L.polygon([
    [51.509, -0.08],
    [51.503, -0.06],
    [51.51, -0.047]
]);
polygon.addTo(deflate_features);

const polyline = L.polyline([
    [51.52, -0.05],
    [51.53, -0.10],
], {color: 'red'});
polyline.addTo(deflate_features);
```

### GeoJSON

[`GeoJSON` layers](http://leafletjs.com/reference-1.3.0.html#geojson) can be added in the same way:

```javascript
const map = L.map("map").setView([51.505, -0.09], 12);

const deflate_features = L.deflate({minSize: 20});
deflate_features.addTo(map);

const json = {
    "type": "FeatureCollection",
    "features": [{}]
}

L.geoJson(json, {style: {color: '#0000FF'}}).addTo(deflate_features);
```

### Custom markers

You can change the appearance of markers representing deflated features by providing:

- A [marker-options object](http://leafletjs.com/reference-1.3.0.html#marker-option), or
- A function that returns a marker-options object.

Providing a marker-options object is usually sufficient. You would typically choose to provide a function if you want to base the marker appearance on the feature's properties.

Provide the object or function to the `markerOptions` property when initializing `L.deflate`.

#### Define custom markers using a marker options object

```javascript
const map = L.map("map").setView([51.550406, -0.140765], 16);

const myIcon = L.icon({
  iconUrl: 'img/marker.png',
  iconSize: [24, 24]
});

const features = L.deflate({minSize: 20, markerOptions: {icon: myIcon}});
features.addTo(map);
```

#### Define custom markers using a function

```javascript
const map = L.map("map").setView([51.550406, -0.140765], 16);

function options(f) {
    // Use custom marker only for buildings
    if (f.feature.properties.type === 'building') {
        return {
            icon: L.icon({
                iconUrl: 'img/marker.png',
                iconSize: [24, 24]
            })
        }
    }

    return {};
}

const features = L.deflate({minSize: 20, markerOptions: options});
features.addTo(map);
```

### CircleMarkers

Alternatively to standard markers, you can use [`CircleMarker`](https://leafletjs.com/reference-1.6.0.html#circlemarker) objects to represent deflated features on the map.

To use default circle markers, specify the `markerType` option.

```javascript
const map = L.map("map").setView([51.550406, -0.140765], 16);

const features = L.deflate({
    minSize: 20,
    markerType: L.circleMarker
});
features.addTo(map);
```

#### Customise CircleMarker

Similar to standard markers, you can customise how circle markers are displayed using the `markerOptions` property. There are to options to provide the options for circle markers:

- A [CircleMarker-options object](https://leafletjs.com/reference-1.6.0.html#circlemarker-option), or
- A function that returns a CircleMarker-options object.

##### Define custom circle markers using a CircleMarker options object

```javascript
const map = L.map("map").setView([51.550406, -0.140765], 16);

const features = L.deflate({
    minSize: 20,
    markerType: L.circleMarker,
    markerOptions: {
        radius: 3,
        color: '#ff0000'
    }
});
features.addTo(map);
```

##### Define custom markers using a function

```javascript
const map = L.map("map").setView([51.550406, -0.140765], 16);

function options(f) {
    // Use custom marker only for buildings
    if (f.feature.properties.type === 'building') {
        return {
            radius: 3,
            color: '#ff0000'
        }
    }

    return {};
}

const features = L.deflate({
    minSize: 20,
    markerType: L.circleMarker,
    markerOptions: options
});
features.addTo(map);
```

### Cluster Markers

Using [Leaflet.Markercluster](https://github.com/Leaflet/Leaflet.markercluster>), you can cluster markers. To enable clustered markers on a map:

1. Add the `Leaflet.Markercluster` libraries to the `head` section of your document as [described in the MarkerCluster documentation](https://github.com/Leaflet/Leaflet.markercluster#using-the-plugin>).
2. Inject a `MarkerClusterGroup` instance via the `markerLayer` option when initializing `L.deflate`.

```javascript
const map = L.map("map").setView([51.505, -0.09], 12);

const markerLayer = L.markerClusterGroup();
const deflate_features = L.deflate({minSize: 20, markerLayer: markerLayer});
deflate_features.addTo(map);

const polygon = L.polygon([
    [51.509, -0.08],
    [51.503, -0.06],
    [51.51, -0.047]
]);
polygon.addTo(deflate_features)

const polyline = L.polyline([
    [51.52, -0.05],
    [51.53, -0.10],
], {color: 'red'});
polyline.addTo(deflate_features)
```

### Leaflet.Draw

[`Leaflet.Draw`](https://github.com/Leaflet/Leaflet.draw) is a plugin that adds support for drawing and editing vector features on Leaflet maps. `Leaflet.Deflate` integrates with `Leaflet.Draw`.

Initialize the [`Leaflet.draw` control](https://leaflet.github.io/Leaflet.draw/docs/leaflet-draw-latest#l-draw). Use the `L.deflate` instance to draw and edit features and add it the map.

To ensure that newly added or edited features are deflated at the correct zoom level and show the marker at the correct location, you need to call `prepLayer` with the edited layer on every change. In the example below, we call `prepLayer` inside the handler function for the [`L.Draw.Event.EDITED`](https://leaflet.github.io/Leaflet.draw/docs/leaflet-draw-latest#l-draw-event-draw:editstop) event.

```javascript
const map = L.map("map").setView([51.505, -0.09], 12);

const deflate_features = L.deflate({minSize: 20, markerCluster: true});
deflate_features.addTo(map);

const drawControl = new L.Control.Draw({
    edit: {
        featureGroup: deflate_features
    }
});
map.addControl(drawControl);

map.on(L.Draw.Event.CREATED, function (event) {
    const layer = event.layer;
    deflate_features.addLayer(layer);
});

map.on(L.Draw.Event.EDITED, function(event) {
    const editedLayers = event.layers;
    editedLayers.eachLayer(function(l) {
        deflate_features.prepLayer(l);
    });
});

```

## Previous releases

Documentation for older releases is available:

- [2.0.x](https://github.com/oliverroick/Leaflet.Deflate/tree/v2.0.0#leafletdeflate)
- [1.4.x](https://github.com/oliverroick/Leaflet.Deflate/tree/v1.4.0#leafletdeflate)
- [1.3.x](https://github.com/oliverroick/Leaflet.Deflate/tree/v1.3.0#leafletdeflate)
- [1.2.x](https://github.com/oliverroick/Leaflet.Deflate/tree/v1.2.0#leafletdeflate)
- [1.1.x](https://github.com/oliverroick/Leaflet.Deflate/tree/1.1.0#leafletdeflate)
- [1.0.x](https://github.com/oliverroick/Leaflet.Deflate/tree/1.0.0#leafletdeflate)
- [0.3](https://github.com/oliverroick/Leaflet.Deflate/tree/v0.3#leafletdeflate)
- [0.2](https://github.com/oliverroick/Leaflet.Deflate/tree/v0.2#leafletdeflate)
- [0.1](https://github.com/oliverroick/Leaflet.Deflate/tree/v0.1#leafletdeflate)

## Developing

You'll need to install the dev dependencies to test and write the distribution file.

```
npm install
```

To run tests:

```
npm test
```

To run eslint on source and test code:

```
npm run lint
```

To write a minified JS into dist:

```
npm run dist
```

## Authors

- [Lindsey Jacks](https://github.com/linzjax)
- [Loic Lacroix](https://github.com/loclac)
- [Oliver Roick](http://github.com/oliverroick)

## License

Apache 2.0
