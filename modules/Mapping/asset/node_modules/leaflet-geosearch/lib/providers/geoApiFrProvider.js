import AbstractProvider, { RequestType, } from './provider';
export default class GeoApiFrProvider extends AbstractProvider {
    constructor(options = {}) {
        super(options);
        const host = 'https://api-adresse.data.gouv.fr';
        this.searchUrl = options.searchUrl || `${host}/search`;
        this.reverseUrl = options.reverseUrl || `${host}/reverse`;
    }
    endpoint({ query, type }) {
        const params = typeof query === 'string' ? { q: query } : query;
        switch (type) {
            case RequestType.REVERSE:
                return this.getUrl(this.reverseUrl, params);
            default:
                return this.getUrl(this.searchUrl, params);
        }
    }
    parse(result) {
        return result.data.features.map((r) => ({
            x: r.geometry.coordinates[0],
            y: r.geometry.coordinates[1],
            label: r.properties.label,
            bounds: null,
            raw: r,
        }));
    }
}
//# sourceMappingURL=geoApiFrProvider.js.map