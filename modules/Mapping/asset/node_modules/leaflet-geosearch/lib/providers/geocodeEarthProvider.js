import PeliasProvider from './peliasProvider';
export default class GeocodeEarthProvider extends PeliasProvider {
    constructor(options = {}) {
        options.host = 'https://api.geocode.earth';
        super(options);
    }
}
//# sourceMappingURL=geocodeEarthProvider.js.map