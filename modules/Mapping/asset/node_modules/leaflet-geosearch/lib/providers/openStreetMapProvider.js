import AbstractProvider, { RequestType, } from './provider';
export default class OpenStreetMapProvider extends AbstractProvider {
    constructor(options = {}) {
        super(options);
        const host = 'https://nominatim.openstreetmap.org';
        this.searchUrl = options.searchUrl || `${host}/search`;
        this.reverseUrl = options.reverseUrl || `${host}/reverse`;
    }
    endpoint({ query, type }) {
        const params = typeof query === 'string' ? { q: query } : query;
        params.format = 'json';
        switch (type) {
            case RequestType.REVERSE:
                return this.getUrl(this.reverseUrl, params);
            default:
                return this.getUrl(this.searchUrl, params);
        }
    }
    parse(response) {
        const records = Array.isArray(response.data)
            ? response.data
            : [response.data];
        return records.map((r) => ({
            x: Number(r.lon),
            y: Number(r.lat),
            label: r.display_name,
            bounds: [
                [parseFloat(r.boundingbox[0]), parseFloat(r.boundingbox[2])],
                [parseFloat(r.boundingbox[1]), parseFloat(r.boundingbox[3])], // n, e
            ],
            raw: r,
        }));
    }
}
//# sourceMappingURL=openStreetMapProvider.js.map