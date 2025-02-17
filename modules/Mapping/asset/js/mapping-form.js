$(document).ready( function() {

/**
 * Add a feature to the map.
 *
 * @param feature
 * @param featureId
 * @param featureLabel
 * @param featureMediaId
 */
const addFeature = function(feature, featureId, featureLabel, featureMediaId) {

    // Build the feature popup content.
    const popupContent = $('.mapping-feature-popup-content.template').clone()
        .removeClass('template')
        .data('feature', feature)
        .data('selectedMediaId', featureMediaId)
        .show();
    popupContent.find('.mapping-feature-popup-label').val(featureLabel);
    if (featureMediaId) {
        const mediaThumbnail = $('<img>', {
            src: $(`.mapping-feature-image-select[value="${featureMediaId}"]`).data('mediaThumbnailUrl')
        });
        popupContent.find('.mapping-feature-popup-image').html(mediaThumbnail);
    }
    feature.bindPopup(popupContent[0]);

    // Prepare image selector when feature is clicked.
    feature.on('click', function(e) {
        const selectedMediaId = popupContent.data('selectedMediaId');
        if (selectedMediaId) {
            $(`.mapping-feature-image-select[value="${selectedMediaId}"]`).prop('checked', true);
        } else {
            $('.mapping-feature-image-select:first').prop('checked', true);
        }
    });

    // Close image selector when feature closes.
    feature.on('popupclose', function(e) {
        const sidebar = $('#mapping-feature-image-selector');
        if (sidebar.hasClass('active')) {
            Omeka.closeSidebar(sidebar);
        }
    });

    // Wrap marker coordinates that are outside their valid ranges into their
    // valid geographical equivalents. Note that this only applies to markers
    // because other features may extend through the antimeridian.
    if (feature._latlng) {
        feature.setLatLng(feature.getLatLng().wrap());
    }
    // Add the feature layer before adding feature inputs so Leaflet sets an ID.
    drawnFeatures.addLayer(feature);
    const featureGeoJson = feature.toGeoJSON();
    const featureNamePrefix = getFeatureNamePrefix(feature);

    // Add the corresponding feature inputs to the form.
    if (featureId) {
        mappingForm.append($('<input>', {
            type: 'hidden',
            name: featureNamePrefix + '[o:id]',
            value: featureId
        }));
    }
    mappingForm.append($('<input>', {
        type: 'hidden',
        name: featureNamePrefix + '[o:media][o:id]',
        value: featureMediaId
    }));
    mappingForm.append($('<input>', {
        type: 'hidden',
        name: featureNamePrefix + '[o:label]',
        value: featureLabel
    }));
    mappingForm.append($('<input>', {
        type: 'hidden',
        name: featureNamePrefix + '[o-module-mapping:geography-type]',
        value: featureGeoJson.geometry.type
    }));
    mappingForm.append($('<input>', {
        type: 'hidden',
        name: featureNamePrefix + '[o-module-mapping:geography-coordinates]',
        value: JSON.stringify(featureGeoJson.geometry.coordinates)
    }));
};

/**
 * Edit a feature.
 *
 * @param feature
 */
const editFeature = function(feature) {
    const featureGeoJson = feature.toGeoJSON();
    const featureNamePrefix = getFeatureNamePrefix(feature);
    // Edit the corresponding feature inputs
    $(`input[name="${featureNamePrefix}[o-module-mapping:geography-type]"]`)
        .val(featureGeoJson.geometry.type);
    $(`input[name="${featureNamePrefix}[o-module-mapping:geography-coordinates]"]`)
        .val(JSON.stringify(featureGeoJson.geometry.coordinates));
}

/**
 * Delete a feature.
 *
 * @param feature
 */
const deleteFeature = function(feature) {
    // Remove the corresponding feature inputs from the form.
    $(`input[name^="${getFeatureNamePrefix(feature)}"]`).remove();
}

/**
 * Set the map view.
 */
const setView = function() {
    if (mapMoved) {
        return; // The user moved the map. Do not set the view.
    }
    if (defaultBounds) {
        map.fitBounds(defaultBounds);
    } else {
        const bounds = drawnFeatures.getBounds();
        if (bounds.isValid()) {
            map.fitBounds(bounds);
        } else {
            map.setView([20, 0], 2)
        }
    }
}

const getFeatureNamePrefix = function(feature) {
    return `o-module-mapping:feature[${drawnFeatures.getLayerId(feature)}]`;
};

// Get map data.
const mappingMap = $('#mapping-map');
const mappingForm = $('#mapping-form');
const mappingData = mappingMap.data('mapping');
const featuresData = mappingMap.data('features');

// Initialize the map and set default view.
const map = L.map('mapping-map', {
    fullscreenControl: true,
    worldCopyJump:true
});
let mapMoved = false;
let defaultBounds = null;
if (mappingData && mappingData['o-module-mapping:bounds'] !== null) {
    const bounds = mappingData['o-module-mapping:bounds'].split(',');
    const southWest = [bounds[1], bounds[0]];
    const northEast = [bounds[3], bounds[2]];
    defaultBounds = [southWest, northEast];
}

// Add layers and controls to the map.
const baseMaps = {
    'Streets': L.tileLayer.provider('OpenStreetMap.Mapnik'),
    'Grayscale': L.tileLayer.provider('CartoDB.Positron'),
    'Satellite': L.tileLayer.provider('Esri.WorldImagery'),
    'Terrain': L.tileLayer.provider('Esri.WorldShadedRelief')
};
const baseMapsControl = L.control.layers(baseMaps);
const geoSearchControl = new window.GeoSearch.GeoSearchControl({
    provider: new window.GeoSearch.OpenStreetMapProvider,
    showMarker: false,
    retainZoomLevel: false,
});
const drawnFeatures = new L.FeatureGroup();
const drawControl = new L.Control.Draw({
    draw: {
        polyline: true,
        polygon: true,
        // Rectangle is compatible because it is treated as a polygon.
        rectangle: true,
        // Circles are incompatible because they require a separate radius.
        // Ideally we would draw circles as polygons, but there is no function
        // to do this. Note that GeoJSON does not support circles.
        circle: false,
        circlemarker: false
    },
    edit: {
        featureGroup: drawnFeatures
    }
});
// Customize strings.
// @see https://github.com/Leaflet/Leaflet.draw?tab=readme-ov-file#customizing-language-and-text-in-leafletdraw
L.drawLocal.edit.toolbar.buttons = {
    edit: 'Edit feature',
    editDisabled: 'No features to edit',
    remove: 'Delete feature',
    removeDisabled: 'No features to delete'
};
map.addLayer(baseMaps['Streets']);
map.addLayer(drawnFeatures);
map.addControl(baseMapsControl);
map.addControl(drawControl);
map.addControl(geoSearchControl);
map.addControl(new L.Control.DefaultView(
    // Set default view callback
    function(e) {
        defaultBounds = map.getBounds();
        $('input[name="o-module-mapping:mapping[o-module-mapping:bounds]"]').val(defaultBounds.toBBoxString());
    },
    // Go to default view callback
    function(e) {
        map.invalidateSize();
        map.fitBounds(defaultBounds);
    },
    // clear default view callback
    function(e) {
        defaultBounds = null;
        $('input[name="o-module-mapping:mapping[o-module-mapping:bounds]"]').val('');
        map.setView([20, 0], 2);
    },
    {noInitialDefaultView: !defaultBounds}
));

// Add saved features to the map.
$.each(featuresData, function(index, data) {
    const featureMediaId = data['o:media'] ? data['o:media']['o:id'] : null;
    const geoJson = {
        type: data['o-module-mapping:geography-type'],
        coordinates: data['o-module-mapping:geography-coordinates'],
    };
    const feature = L.geoJSON(geoJson, {
        onEachFeature: function(feature, layer) {
            addFeature(layer, data['o:id'], data['o:label'], featureMediaId);
        }
    });
});

// Set saved mapping data to the map (default view).
if (mappingData) {
    $('input[name="o-module-mapping:mapping[o:id]"]').val(mappingData['o:id']);
    $('input[name="o-module-mapping:mapping[o-module-mapping:bounds]"]').val(mappingData['o-module-mapping:bounds']);
}

// Set the initial view.
setView();

// Handle map moved.
map.on('movestart', function(e) {
    mapMoved = true;
});

// Handle adding new features.
map.on('draw:created', function(e) {
    if (['marker', 'polyline', 'polygon', 'rectangle'].includes(e.layerType)) {
        addFeature(e.layer);
    }
});

// Handle editing existing features (saved and unsaved).
map.on('draw:edited', function(e) {
    e.layers.eachLayer(function(layer) {
        editFeature(layer);
    });
});

// Handle deleting existing (saved and unsaved) features.
map.on('draw:deleted', function(e) {
    e.layers.eachLayer(function(layer) {
        deleteFeature(layer);
    });
});

// Handle adding a geocoded marker.
map.on('geosearch/showlocation', function(e) {
    addFeature(new L.Marker([e.location.y, e.location.x]), null, e.location.label);
});

// Switching sections changes map dimensions, so make the necessary adjustments.
$('#mapping-section').on('o:section-opened', function(e) {
    $('#content').one('transitionend', function(e) {
        map.invalidateSize();
        setView();
    });
});

// Handle updating corresponding form input when updating a feature label.
mappingMap.on('keyup', '.mapping-feature-popup-label', function(e) {
    const thisInput = $(this);
    const feature = thisInput.closest('.mapping-feature-popup-content').data('feature');
    const labelInput = $(`input[name="${getFeatureNamePrefix(feature)}[o:label]"]`);
    labelInput.val(thisInput.val());
});

// Handle select popup image button.
$('#mapping-section').on('click', '.mapping-feature-popup-image-select', function(e) {
    e.preventDefault();
    Omeka.openSidebar($('#mapping-feature-image-selector'));
});

// Handle media image selection.
$('input.mapping-feature-image-select').on('change', function(e) {
    const thisInput = $(this);
    const popupContent = $('.mapping-feature-popup-content:visible');
    const popupLabel = popupContent.find('.mapping-feature-popup-label');
    const feature = popupContent.data('feature');
    const featureNamePrefix = getFeatureNamePrefix(feature);
    const mediaThumbnailUrl = thisInput.data('mediaThumbnailUrl');
    const mediaTitle = thisInput.data('mediaTitle');
    let mediaThumbnail = null;

    // Render thumbnail in popup content.
    if (mediaThumbnailUrl) {
        mediaThumbnail = $('<img>', {src: mediaThumbnailUrl});
        popupContent.find('.mapping-feature-popup-image-select').html('Change feature image');
    } else {
        popupContent.find('.mapping-feature-popup-image-select').html('Select feature image');
    }
    popupContent.find('.mapping-feature-popup-image').html(mediaThumbnail);
    popupContent.data('selectedMediaId', thisInput.val());

    // Update corresponding form input when updating an image.
    $(`input[name="${featureNamePrefix}[o:media][o:id]"]`).val(thisInput.val());

    // Set the media title as the popup label if not already set.
    if (!popupLabel.val()) {
        const labelInput = $(`input[name="${featureNamePrefix}[o:label]"]`);
        labelInput.val(mediaTitle);
        popupLabel.val(mediaTitle);
    }
});

});
