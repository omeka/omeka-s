import AbstractProvider, { RequestType, } from './provider';
export default class PeliasProvider extends AbstractProvider {
    constructor(options = {}) {
        super(options);
        this.host = options.host || 'http://localhost:4000';
    }
    /**
     * note: Pelias has four different query modes:
     * /v1/autocomplete: for partially completed inputs (such as when a user types)
     * /v1/search: for completed inputs (such as when geocoding a CSV file)
     * /v1/search/structured: for completed inputs with fields already separated
     * /v1/reverse: for finding places nearby/enveloping a point
     */
    endpoint({ query, type }) {
        switch (type) {
            // case RequestType.AUTOCOMPLETE:
            //   const autocompleteParams = (typeof query === 'string') ? { text: query } : query;
            //   return this.getUrl(`${this.host}/v1/autocomplete`, autocompleteParams);
            // case RequestType.FULLTEXT:
            //   const searchParams = (typeof query === 'string') ? { text: query } : query;
            //   return this.getUrl(`${this.host}/v1/search`, searchParams);
            // case RequestType.STRUCTURED:
            //   const structuredParams = (typeof query === 'string') ? { address: query } : query;
            //   return this.getUrl(`${this.host}/v1/search/structured`, structuredParams);
            case RequestType.REVERSE:
                const reverseParams = typeof query === 'string' ? {} : query;
                return this.getUrl(`${this.host}/v1/reverse`, reverseParams);
            // note: the default query mode is set to 'autocomplete'
            default:
                const autocompleteParams = typeof query === 'string' ? { text: query } : query;
                return this.getUrl(`${this.host}/v1/autocomplete`, autocompleteParams);
        }
    }
    parse(response) {
        return response.data.features.map((feature) => {
            const res = {
                x: feature.geometry.coordinates[0],
                y: feature.geometry.coordinates[1],
                label: feature.properties.label,
                bounds: null,
                raw: feature,
            };
            // bbox values are only available for features derived from non-point geometries
            // geojson bbox format: [minX, minY, maxX, maxY]
            if (Array.isArray(feature.bbox) && feature.bbox.length === 4) {
                res.bounds = [
                    [feature.bbox[1], feature.bbox[0]],
                    [feature.bbox[3], feature.bbox[2]], // n, e
                ];
            }
            return res;
        });
    }
}
//# sourceMappingURL=peliasProvider.js.map