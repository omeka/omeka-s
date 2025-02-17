var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
import AbstractProvider from './provider';
export default class OpenCageProvider extends AbstractProvider {
    constructor() {
        super(...arguments);
        this.searchUrl = 'https://api.opencagedata.com/geocode/v1/json';
    }
    endpoint({ query }) {
        const params = typeof query === 'string' ? { q: query } : query;
        params.format = 'json';
        return this.getUrl(this.searchUrl, params);
    }
    parse(response) {
        return response.data.results.map((r) => ({
            x: r.geometry.lng,
            y: r.geometry.lat,
            label: r.formatted,
            bounds: [
                [r.bounds.southwest.lat, r.bounds.southwest.lng],
                [r.bounds.northeast.lat, r.bounds.northeast.lng], // n, e
            ],
            raw: r,
        }));
    }
    search(options) {
        const _super = Object.create(null, {
            search: { get: () => super.search }
        });
        return __awaiter(this, void 0, void 0, function* () {
            // opencage returns a 400 error when query length < 2
            if (options.query.length < 2) {
                return [];
            }
            return _super.search.call(this, options);
        });
    }
}
//# sourceMappingURL=openCageProvider.js.map