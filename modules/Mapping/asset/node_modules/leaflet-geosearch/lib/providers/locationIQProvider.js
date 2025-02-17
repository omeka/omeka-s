import OpenStreetMapProvider from './openStreetMapProvider';
export default class LocationIQProvider extends OpenStreetMapProvider {
    constructor(options) {
        super(Object.assign(Object.assign({}, options), { searchUrl: `https://locationiq.org/v1/search.php`, reverseUrl: `https://locationiq.org/v1/reverse.php` }));
    }
}
//# sourceMappingURL=locationIQProvider.js.map