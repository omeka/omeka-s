$(document).ready( function() {

/**
 * Set the map with the default view to a block.
 *
 * @param block The page block (div) jQuery object
 */
var setMap = function(block) {
    var mapDiv = block.find('.mapping-map');
    var basemapProviderSelect = block.find('select.basemap-provider');
    var currentZoomLevelSpan = block.find('span.current-zoom');

    var map = L.map(mapDiv[0], {
        fullscreenControl: true,
        worldCopyJump:true
    });
    var defaultBounds = null;
    var defaultBoundsData = mapDiv.find('input[name$="[bounds]"]').val();
    if (defaultBoundsData) {
        var bounds = defaultBoundsData.split(',');
        var southWest = [bounds[1], bounds[0]];
        var northEast = [bounds[3], bounds[2]];
        defaultBounds = [southWest, northEast];
    }

    var layer;
    try {
        layer = L.tileLayer.provider(basemapProviderSelect.val());
    } catch (error) {
        layer = L.tileLayer.provider('OpenStreetMap.Mapnik');
    }
    map.addLayer(layer);

    map.addControl(new L.Control.DefaultView(
        function(e) {
            defaultBounds = map.getBounds();
            mapDiv.find('input[name$="[bounds]"]').val(defaultBounds.toBBoxString());
        },
        function(e) {
            map.invalidateSize();
            map.fitBounds(defaultBounds);
        },
        function(e) {
            defaultBounds = null;
            mapDiv.find('input[name$="[bounds]"]').val('');
            map.setView([20, 0], 2);
        },
        {noInitialDefaultView: !defaultBounds}
    ));

    // Expanding changes map dimensions, so make the necessary adjustments.
    block.on('o:expanded', '.mapping-map-expander', function(e) {
        map.invalidateSize();
        defaultBounds ? map.fitBounds(defaultBounds) : map.setView([20, 0], 2);
    })

    basemapProviderSelect.on('change', function(e) {
        map.removeLayer(layer);
        try {
            layer = L.tileLayer.provider(basemapProviderSelect.val());
        } catch (error) {
            layer = L.tileLayer.provider('OpenStreetMap.Mapnik');
        }
        map.addLayer(layer);
    });

    map.on('zoom', function(e) {
        currentZoomLevelSpan.text(this.getZoom());
    });
};

/**
 * Set WMS data to page form.
 *
 * @param block The page block (div) jQuery object
 * @param wmsOverlay The WMS overlay (li) jQuery object
 * @return bool Whether the WMS data is valid
 */
var setWmsData = function(block, wmsOverlay) {
    var wmsLabel = block.find('input.mapping-wms-label').val();
    var wmsBaseUrl = block.find('input.mapping-wms-base-url').val();
    var wmsLayers = block.find('input.mapping-wms-layers').val();
    var wmsStyles = block.find('input.mapping-wms-styles').val();

    // Label and base URL are required for WMS overlays.
    if (!wmsLabel || !wmsBaseUrl) {
        return false;
    }

    wmsOverlay.find('.mapping-wms-overlay-title').html(wmsLabel);
    wmsOverlay.find('input[name$="[label]"]').val(wmsLabel);
    wmsOverlay.find('input[name$="[base_url]"]').val(wmsBaseUrl);
    wmsOverlay.find('input[name$="[layers]"]').val(wmsLayers);
    wmsOverlay.find('input[name$="[styles]"]').val(wmsStyles);

    block.find('.mapping-wms-fields :input').val('');
    return true;
}

// Handle setting the map for added blocks.
$('#blocks').on('o:block-added', '.block[data-block-layout^="mappingMap"]', function(e) {
    setMap($(this));
});

// Handle setting the map for existing blocks.
$('.block[data-block-layout^="mappingMap"]').each(function() {
    setMap($(this));
});

// Handle preparing the WMS data for submission.
$('form').submit(function(e) {
    $('.mapping-wms-overlay').each(function(index) {
        $(this).find('input[type="hidden"]').each(function() {
            var thisInput = $(this);
            var name = thisInput.attr('name').replace('[__mappingWmsIndex__]', '[' + index + ']');
            thisInput.attr('name', name);
        });
    });
    // We need to replace blockIndex here. Otherwise, dynamically created WMS
    // inputs are ignored.
    $('.block[data-block-layout^="mappingMap"]').each(function() {
        var thisBlock = $(this);
        thisBlock.find('.mapping-wms-overlay').find('input[type="hidden"]').each(function() {
            var thisInput = $(this);
            var name = thisInput.attr('name').replace('[__blockIndex__]', '[' + thisBlock.data('blockIndex') + ']');
            thisInput.attr('name', name);
        });
    });
});

// Handle adding a new WMS overlay.
$('#blocks').on('click', '.mapping-wms-add', function(e) {
    e.preventDefault();

    var block = $(this).closest('.block');
    block.find('.mapping-wms-add').show();
    block.find('.mapping-wms-edit').hide();
    var wmsOverlays = block.find('.mapping-wms-overlays');
    var wmsOverlay = $($.parseHTML(wmsOverlays.data('wmsOverlayTemplate')));

    if (setWmsData(block, wmsOverlay)) {
        wmsOverlays.append(wmsOverlay);
    } else {
        alert('A label and base URL are required for WMS overlays.');
    }
});

// Handle editing an existing WMS overlay.
$('#blocks').on('click', '.mapping-wms-edit', function(e) {
    e.preventDefault();

    var block = $(this).closest('.block');
    block.find('.mapping-wms-add').show();
    block.find('.mapping-wms-edit').hide();
    var wmsOverlay = block.find('.mapping-wms-overlay-editing');
    wmsOverlay.removeClass('mapping-wms-overlay-editing');

    if (!setWmsData(block, wmsOverlay)) {
        alert('A label and base URL are required for WMS overlays.');
    }
});

// Handle clearing the WMS input form.
$('#blocks').on('click', '.mapping-wms-clear', function(e) {
    e.preventDefault();

    var block = $(this).closest('.block');
    block.find('.mapping-wms-add').show();
    block.find('.mapping-wms-edit').hide();
    block.find('.mapping-wms-fields :input').val('');
    block.find('li.mapping-wms-overlay').removeClass('mapping-wms-overlay-editing');
});

// Handle populating existing WMS data to the WMS input form.
$('#blocks').on('click', '.mapping-wms-overlay-edit', function(e) {
    e.preventDefault();

    var block = $(this).closest('.block');
    block.find('.mapping-wms-add').hide();
    block.find('.mapping-wms-edit').show();
    var wmsOverlay = $(this).closest('.mapping-wms-overlay');
    $('.mapping-wms-overlay-editing').removeClass('mapping-wms-overlay-editing');
    wmsOverlay.addClass('mapping-wms-overlay-editing');

    var wmsLabel = wmsOverlay.find('input[name$="[label]"]').val();
    var wmsBaseUrl = wmsOverlay.find('input[name$="[base_url]"]').val();
    var wmsLayers = wmsOverlay.find('input[name$="[layers]"]').val();
    var wmsStyles = wmsOverlay.find('input[name$="[styles]"]').val();

    block.find('input.mapping-wms-label').val(wmsLabel);
    block.find('input.mapping-wms-base-url').val(wmsBaseUrl);
    block.find('input.mapping-wms-layers').val(wmsLayers);
    block.find('input.mapping-wms-styles').val(wmsStyles);
});

// Handle WMS overlay deletion.
$('#blocks').on('click', '.mapping-wms-overlay-delete', function(e) {
    e.preventDefault();

    var wmsOverlay = $(this).closest('.mapping-wms-overlay');
    if (wmsOverlay.hasClass('mapping-wms-overlay-editing')) {
        var block = $(this).closest('.block');
        block.find('.mapping-wms-add').show();
        block.find('.mapping-wms-edit').hide();
        block.find('.mapping-wms-fields :input').val('');
    }
    wmsOverlay.remove();
});

// Handle WMS overlay open/closed checkboxes.
$('#blocks').on('change', '.mapping-wms-open', function(e) {
    var thisCheckbox = $(this);
    var isChecked = thisCheckbox.prop('checked');
    var wmsOverlay = thisCheckbox.closest('.mapping-wms-overlay');
    var wmsOverlays = thisCheckbox.closest('.mapping-wms-overlays');

    wmsOverlays.find('.mapping-wms-open').prop('checked', false);
    thisCheckbox.prop('checked', isChecked);

    wmsOverlays.find('input[name$="[open]"]').val(0);
    wmsOverlay.find('input[name$="[open]"]').val(isChecked ? 1 : 0);
});

});
