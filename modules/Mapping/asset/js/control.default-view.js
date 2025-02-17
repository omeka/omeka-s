// Control that sets the default view.
L.Control.DefaultView = L.Control.extend({
    options: {
        position: 'topleft',
        noInitialDefaultView: true // Flag whether to disable the goto and clear links
    },

    /**
     * @param setCallback Callback that handles setting the default view
     * @param gotoCallback Callback that handles going to the default view
     * @param clearCallback Callback that handles clearing default view
     * @param options
     */
    initialize: function (setCallback, gotoCallback, clearCallback, options) {
        this._setCallback = setCallback;
        this._gotoCallback = gotoCallback;
        this._clearCallback = clearCallback;
        L.setOptions(this, options);
    },

    onAdd: function (map) {
        this._map = map;

        var container = L.DomUtil.create('div', 'mapping-control-default leaflet-bar');
        var setLink = L.DomUtil.create('a', 'mapping-control-default-view-set', container);
        var gotoLink = L.DomUtil.create('a', 'mapping-control-default-view-goto', container);
        var clearLink = L.DomUtil.create('a', 'mapping-control-default-view-clear', container);

        setLink.innerHTML = '⊹';
        setLink.href = '#';
        setLink.title = 'Set the current view as the default view';
        setLink.style.fontSize = '18px';

        gotoLink.innerHTML = '⊡';
        gotoLink.href = '#';
        gotoLink.title = 'Go to the current default view';
        gotoLink.style.fontSize = '18px';

        clearLink.innerHTML = '✕';
        clearLink.href = '#';
        clearLink.title = 'Clear the default view';
        clearLink.style.fontSize = '18px';

        if (this.options.noInitialDefaultView) {
            L.DomUtil.addClass(clearLink, 'leaflet-disabled');
            L.DomUtil.addClass(gotoLink, 'leaflet-disabled');
        }

        L.DomEvent
            .on(setLink, 'mousedown', L.DomEvent.stopPropagation)
            .on(setLink, 'dblclick', L.DomEvent.stopPropagation)
            .on(setLink, 'click', L.DomEvent.stopPropagation)
            .on(setLink, 'click', L.DomEvent.preventDefault)
            .on(setLink, 'click', this._setCallback, this)
            .on(setLink, 'click', function(e) {
                L.DomUtil.removeClass(clearLink, 'leaflet-disabled')
                L.DomUtil.removeClass(gotoLink, 'leaflet-disabled')
            });
        L.DomEvent
            .on(gotoLink, 'mousedown', L.DomEvent.stopPropagation)
            .on(gotoLink, 'dblclick', L.DomEvent.stopPropagation)
            .on(gotoLink, 'click', L.DomEvent.stopPropagation)
            .on(gotoLink, 'click', L.DomEvent.preventDefault)
            .on(gotoLink, 'click', this._gotoCallback, this);
        L.DomEvent
            .on(clearLink, 'mousedown', L.DomEvent.stopPropagation)
            .on(clearLink, 'dblclick', L.DomEvent.stopPropagation)
            .on(clearLink, 'click', L.DomEvent.stopPropagation)
            .on(clearLink, 'click', L.DomEvent.preventDefault)
            .on(clearLink, 'click', this._clearCallback, this)
            .on(clearLink, 'click', function(e) {
                L.DomUtil.addClass(clearLink, 'leaflet-disabled');
                L.DomUtil.addClass(gotoLink, 'leaflet-disabled');
            });

        return container;
    },
});
