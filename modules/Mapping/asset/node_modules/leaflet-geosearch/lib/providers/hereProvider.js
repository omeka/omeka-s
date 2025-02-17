import AbstractProvider from './provider';
export default class HereProvider extends AbstractProvider {
    constructor() {
        super(...arguments);
        this.searchUrl = 'https://geocode.search.hereapi.com/v1/autosuggest';
    }
    endpoint({ query }) {
        const params = typeof query === 'string' ? { q: query } : query;
        return this.getUrl(this.searchUrl, params);
    }
    parse(response) {
        return response.data.items
            .filter((r) => r.position !== undefined)
            .map((r) => ({
            x: r.position.lng,
            y: r.position.lat,
            label: r.address.label,
            bounds: null,
            raw: r,
        }));
    }
}
//# sourceMappingURL=hereProvider.js.map