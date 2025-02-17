// Control that fits features within bounds.
L.Control.FitBounds = L.Control.extend({
    options: {
        position: 'topleft'
    },

    initialize: function (layerGroup) {
        this._layerGroup = layerGroup;
    },

    onAdd: function (map) {
        this._map = map;

        var container = L.DomUtil.create('div', 'mapping-control-fit leaflet-bar');
        var link = L.DomUtil.create('a', 'mapping-control-fit-bounds', container);

        link.innerHTML = '⊡';
        link.href = '#';
        link.title = 'Fit all features on the map within one view';
        link.style.fontSize = '20px';

        L.DomEvent
            .on(link, 'mousedown', L.DomEvent.stopPropagation)
            .on(link, 'dblclick', L.DomEvent.stopPropagation)
            .on(link, 'click', L.DomEvent.stopPropagation)
            .on(link, 'click', L.DomEvent.preventDefault)
            .on(link, 'click', this._fitBounds, this);
        return container;
    },

    _fitBounds: function(e) {
        var bounds = this._layerGroup.getBounds();
        if (bounds.isValid()) {
            this._map.fitBounds(bounds, {padding: [50, 50]});
        }
    },
});
