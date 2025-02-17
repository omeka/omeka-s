import AbstractProvider from './provider';
export default class EsriProvider extends AbstractProvider {
    constructor() {
        super(...arguments);
        this.searchUrl = 'https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/find';
    }
    endpoint({ query }) {
        const params = typeof query === 'string' ? { text: query } : query;
        params.f = 'json';
        return this.getUrl(this.searchUrl, params);
    }
    parse(result) {
        return result.data.locations.map((r) => ({
            x: r.feature.geometry.x,
            y: r.feature.geometry.y,
            label: r.name,
            bounds: [
                [r.extent.ymin, r.extent.xmin],
                [r.extent.ymax, r.extent.xmax], // n, e
            ],
            raw: r,
        }));
    }
}
//# sourceMappingURL=esriProvider.js.map