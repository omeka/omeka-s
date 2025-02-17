import AbstractProvider from './provider';
export default class LegacyGoogleProvider extends AbstractProvider {
    constructor() {
        super(...arguments);
        this.searchUrl = 'https://maps.googleapis.com/maps/api/geocode/json';
    }
    endpoint({ query }) {
        const params = typeof query === 'string' ? { address: query } : query;
        return this.getUrl(this.searchUrl, params);
    }
    parse(result) {
        return result.data.results.map((r) => ({
            x: r.geometry.location.lng,
            y: r.geometry.location.lat,
            label: r.formatted_address,
            bounds: [
                [r.geometry.viewport.southwest.lat, r.geometry.viewport.southwest.lng],
                [r.geometry.viewport.northeast.lat, r.geometry.viewport.northeast.lng], // n, e
            ],
            raw: r,
        }));
    }
}
//# sourceMappingURL=legacyGoogleProvider.js.map