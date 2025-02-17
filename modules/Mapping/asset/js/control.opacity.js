// Control that increases and decreases overlay opacity
L.Control.Opacity = L.Control.extend({
    options: {
        position: 'topleft',
        opacityIncText: '▲',
        opacityDecText: '▼',
    },

    initialize: function (overlay, label) {
        this._overlay = overlay;
        this._label = label;
        this._opacity = 1.0;
    },

    onAdd: function (map) {
        this._opacity = this._overlay.options.opacity;

        var opacityName = 'mapping-control-opacity',
            container = L.DomUtil.create('div', opacityName + ' leaflet-bar');
        var opacityIncTitle = 'Increase opacity of "' + this._label + '" overlay';
        var opacityDecTitle = 'Decrease opacity of "' + this._label + '" overlay';

        this._opacityIncButton  = this._createButton(
            this.options.opacityIncText, opacityIncTitle,
            opacityName + '-inc',  container, this._opacityInc,  this);
        this._opacityDecButton = this._createButton(
            this.options.opacityDecText, opacityDecTitle,
            opacityName + '-dec', container, this._opacityDec, this);

        return container;
    },

    _opacityInc: function (e) {
        if (this._opacity < 1.0) {
            this._opacity = this._opacity + 0.1;
        }
        this._overlay.setOpacity(this._opacity);
    },

    _opacityDec: function (e) {
        if (this._opacity > 0.1) {
            this._opacity = this._opacity - 0.1;
        }
        this._overlay.setOpacity(this._opacity);
    },

    _createButton: function (html, title, className, container, fn, context) {
        var link = L.DomUtil.create('a', className, container);
        link.innerHTML = html;
        link.href = '#';
        link.title = title;

        var stop = L.DomEvent.stopPropagation;

        L.DomEvent
            .on(link, 'click', stop)
            .on(link, 'mousedown', stop)
            .on(link, 'dblclick', stop)
            .on(link, 'click', L.DomEvent.preventDefault)
            .on(link, 'click', fn, context);

        return link;
    },
});
L.control.opacity = function (overlay, label) {
    return new L.Control.Opacity(overlay, label);
};
