import AbstractProvider from './provider';
export default class MapBoxProvider extends AbstractProvider {
    constructor(options = {}) {
        super(options);
        const host = 'https://a.tiles.mapbox.com';
        this.searchUrl = options.searchUrl || `${host}/v4/geocode/mapbox.places/`;
    }
    endpoint({ query }) {
        return this.getUrl(`${this.searchUrl}${query}.json`);
    }
    parse(response) {
        const records = Array.isArray(response.data.features)
            ? response.data.features
            : [];
        return records.map((r) => {
            let bounds = null;
            if (r.bbox) {
                bounds = [
                    [parseFloat(r.bbox[1]), parseFloat(r.bbox[0])],
                    [parseFloat(r.bbox[3]), parseFloat(r.bbox[2])], // n, e
                ];
            }
            return {
                x: Number(r.center[0]),
                y: Number(r.center[1]),
                label: r.place_name ? r.place_name : r.text,
                bounds,
                raw: r,
            };
        });
    }
}
//# sourceMappingURL=mapBoxProvider.js.map