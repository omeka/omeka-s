import * as L from 'leaflet';

type MarkerFactory = (latlng: L.LatLngExpression, options?: L.MarkerOptions) => L.Marker;
type CircleMarkerFactory = (latlng: L.LatLngExpression, options?: L.CircleMarkerOptions) => L.CircleMarker;
type MarkerOptionsFunction = (layer: L.Layer) => L.MarkerOptions | L.CircleMarkerOptions;

declare module 'leaflet' {
    interface DeflateOptions extends LayerOptions {
        minSize?: number;
        markerOptions?: MarkerOptions | CircleMarkerOptions | MarkerOptionsFunction;
        markerType?: MarkerFactory | CircleMarkerFactory;
        markerLayer?: FeatureGroup;
    }

    class DeflateLayer extends FeatureGroup {
        constructor(options: DeflateOptions);
        prepLayer(layer: Layer): void;
    }

    function deflate(options: DeflateOptions): DeflateLayer;
}
export {};
