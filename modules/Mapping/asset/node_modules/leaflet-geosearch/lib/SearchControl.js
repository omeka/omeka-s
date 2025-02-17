var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
import * as L from 'leaflet';
import SearchElement from './SearchElement';
import ResultList from './resultList';
import debounce from './lib/debounce';
import { createElement, addClassName, removeClassName, } from './domUtils';
import { ENTER_KEY, SPECIAL_KEYS, ARROW_UP_KEY, ARROW_DOWN_KEY, ESCAPE_KEY, } from './constants';
const defaultOptions = {
    position: 'topleft',
    style: 'button',
    showMarker: true,
    showPopup: false,
    popupFormat: ({ result }) => `${result.label}`,
    resultFormat: ({ result }) => `${result.label}`,
    marker: {
        icon: L && L.Icon ? new L.Icon.Default() : undefined,
        draggable: false,
    },
    maxMarkers: 1,
    maxSuggestions: 5,
    retainZoomLevel: false,
    animateZoom: true,
    searchLabel: 'Enter address',
    clearSearchLabel: 'Clear search',
    notFoundMessage: '',
    messageHideDelay: 3000,
    zoomLevel: 18,
    classNames: {
        container: 'leaflet-bar leaflet-control leaflet-control-geosearch',
        button: 'leaflet-bar-part leaflet-bar-part-single',
        resetButton: 'reset',
        msgbox: 'leaflet-bar message',
        form: '',
        input: '',
        resultlist: '',
        item: '',
        notfound: 'leaflet-bar-notfound',
    },
    autoComplete: true,
    autoCompleteDelay: 250,
    autoClose: false,
    keepResult: false,
    updateMap: true,
};
const UNINITIALIZED_ERR = 'Leaflet must be loaded before instantiating the GeoSearch control';
// @ts-ignore
const Control = {
    options: Object.assign({}, defaultOptions),
    classNames: Object.assign({}, defaultOptions.classNames),
    initialize(options) {
        if (!L) {
            throw new Error(UNINITIALIZED_ERR);
        }
        if (!options.provider) {
            throw new Error('Provider is missing from options');
        }
        // merge given options with control defaults
        this.options = Object.assign(Object.assign({}, defaultOptions), options);
        this.classNames = Object.assign(Object.assign({}, this.classNames), options.classNames);
        this.markers = new L.FeatureGroup();
        this.classNames.container += ` leaflet-geosearch-${this.options.style}`;
        this.searchElement = new SearchElement({
            searchLabel: this.options.searchLabel,
            classNames: {
                container: this.classNames.container,
                form: this.classNames.form,
                input: this.classNames.input,
            },
            handleSubmit: (result) => this.onSubmit(result),
        });
        this.button = createElement('a', this.classNames.button, this.searchElement.container, {
            title: this.options.searchLabel,
            href: '#',
            onClick: (e) => this.onClick(e),
        });
        L.DomEvent.disableClickPropagation(this.button);
        this.resetButton = createElement('button', this.classNames.resetButton, this.searchElement.form, {
            text: '×',
            'aria-label': this.options.clearSearchLabel,
            onClick: () => {
                if (this.searchElement.input.value === '') {
                    this.close();
                }
                else {
                    this.clearResults(null, true);
                }
            },
        });
        L.DomEvent.disableClickPropagation(this.resetButton);
        if (this.options.autoComplete) {
            this.resultList = new ResultList({
                handleClick: ({ result }) => {
                    this.searchElement.input.value = result.label;
                    this.onSubmit({ query: result.label, data: result });
                },
                classNames: {
                    resultlist: this.classNames.resultlist,
                    item: this.classNames.item,
                    notfound: this.classNames.notfound,
                },
                notFoundMessage: this.options.notFoundMessage,
            });
            this.searchElement.form.appendChild(this.resultList.container);
            this.searchElement.input.addEventListener('keyup', debounce((e) => this.autoSearch(e), this.options.autoCompleteDelay), true);
            this.searchElement.input.addEventListener('keydown', (e) => this.selectResult(e), true);
            this.searchElement.input.addEventListener('keydown', (e) => this.clearResults(e, true), true);
        }
        this.searchElement.form.addEventListener('click', (e) => {
            e.preventDefault();
        }, false);
    },
    onAdd(map) {
        const { showMarker, style } = this.options;
        this.map = map;
        if (showMarker) {
            this.markers.addTo(map);
        }
        if (style === 'bar') {
            const root = map
                .getContainer()
                .querySelector('.leaflet-control-container');
            this.container = createElement('div', 'leaflet-control-geosearch leaflet-geosearch-bar');
            this.container.appendChild(this.searchElement.form);
            root.appendChild(this.container);
        }
        L.DomEvent.disableClickPropagation(this.searchElement.form);
        return this.searchElement.container;
    },
    onRemove() {
        var _a;
        (_a = this.container) === null || _a === void 0 ? void 0 : _a.remove();
        return this;
    },
    open() {
        const { container, input } = this.searchElement;
        addClassName(container, 'active');
        input.focus();
    },
    close() {
        const { container } = this.searchElement;
        removeClassName(container, 'active');
        this.clearResults();
    },
    onClick(event) {
        event.preventDefault();
        event.stopPropagation();
        const { container } = this.searchElement;
        if (container.classList.contains('active')) {
            this.close();
        }
        else {
            this.open();
        }
    },
    selectResult(event) {
        if ([ENTER_KEY, ARROW_DOWN_KEY, ARROW_UP_KEY].indexOf(event.keyCode) === -1) {
            return;
        }
        event.preventDefault();
        if (event.keyCode === ENTER_KEY) {
            const item = this.resultList.select(this.resultList.selected);
            this.onSubmit({ query: this.searchElement.input.value, data: item });
            return;
        }
        const max = this.resultList.count() - 1;
        if (max < 0) {
            return;
        }
        const { selected } = this.resultList;
        const next = event.keyCode === ARROW_DOWN_KEY ? selected + 1 : selected - 1;
        const idx = next < 0 ? max : next > max ? 0 : next;
        const item = this.resultList.select(idx);
        this.searchElement.input.value = item.label;
    },
    clearResults(event, force = false) {
        if (event && event.keyCode !== ESCAPE_KEY) {
            return;
        }
        const { keepResult, autoComplete } = this.options;
        if (force || !keepResult) {
            this.searchElement.input.value = '';
            this.markers.clearLayers();
        }
        if (autoComplete) {
            this.resultList.clear();
        }
    },
    autoSearch(event) {
        return __awaiter(this, void 0, void 0, function* () {
            if (SPECIAL_KEYS.indexOf(event.keyCode) > -1) {
                return;
            }
            const query = event.target.value;
            const { provider } = this.options;
            if (query.length) {
                let results = yield provider.search({ query });
                results = results.slice(0, this.options.maxSuggestions);
                this.resultList.render(results, this.options.resultFormat);
            }
            else {
                this.resultList.clear();
            }
        });
    },
    onSubmit(query) {
        return __awaiter(this, void 0, void 0, function* () {
            const { provider } = this.options;
            const results = yield provider.search(query);
            if (results && results.length > 0) {
                this.showResult(results[0], query);
            }
        });
    },
    showResult(result, query) {
        const { autoClose, updateMap } = this.options;
        const markers = this.markers.getLayers();
        if (markers.length >= this.options.maxMarkers) {
            this.markers.removeLayer(markers[0]);
        }
        const marker = this.addMarker(result, query);
        if (updateMap) {
            this.centerMap(result);
        }
        this.map.fireEvent('geosearch/showlocation', {
            location: result,
            marker,
        });
        if (autoClose) {
            this.closeResults();
        }
    },
    closeResults() {
        const { container } = this.searchElement;
        if (container.classList.contains('active')) {
            removeClassName(container, 'active');
        }
        this.clearResults();
    },
    addMarker(result, query) {
        const { marker: options, showPopup, popupFormat } = this.options;
        const marker = new L.Marker([result.y, result.x], options);
        let popupLabel = result.label;
        if (typeof popupFormat === 'function') {
            popupLabel = popupFormat({ query, result });
        }
        marker.bindPopup(popupLabel);
        this.markers.addLayer(marker);
        if (showPopup) {
            marker.openPopup();
        }
        if (options.draggable) {
            marker.on('dragend', (args) => {
                this.map.fireEvent('geosearch/marker/dragend', {
                    location: marker.getLatLng(),
                    event: args,
                });
            });
        }
        return marker;
    },
    centerMap(result) {
        const { retainZoomLevel, animateZoom } = this.options;
        const resultBounds = result.bounds
            ? new L.LatLngBounds(result.bounds)
            : new L.LatLng(result.y, result.x).toBounds(10);
        const bounds = resultBounds.isValid()
            ? resultBounds
            : this.markers.getBounds();
        if (!retainZoomLevel && resultBounds.isValid() && !result.bounds) {
            this.map.setView(bounds.getCenter(), this.getZoom(), {
                animate: animateZoom,
            });
        }
        else if (!retainZoomLevel && resultBounds.isValid()) {
            this.map.fitBounds(bounds, { animate: animateZoom });
        }
        else {
            this.map.setView(bounds.getCenter(), this.getZoom(), {
                animate: animateZoom,
            });
        }
    },
    getZoom() {
        const { retainZoomLevel, zoomLevel } = this.options;
        return retainZoomLevel ? this.map.getZoom() : zoomLevel;
    },
};
export default function SearchControl(...options) {
    if (!L) {
        throw new Error(UNINITIALIZED_ERR);
    }
    const LControl = L.Control.extend(Control);
    return new LControl(...options);
}
//# sourceMappingURL=SearchControl.js.map