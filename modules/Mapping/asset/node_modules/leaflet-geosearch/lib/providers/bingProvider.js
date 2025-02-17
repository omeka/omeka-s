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
import { createScriptElement } from '../domUtils';
export default class BingProvider extends AbstractProvider {
    constructor() {
        super(...arguments);
        this.searchUrl = 'https://dev.virtualearth.net/REST/v1/Locations';
    }
    endpoint({ query, jsonp }) {
        const params = typeof query === 'string' ? { q: query } : query;
        params.jsonp = jsonp;
        return this.getUrl(this.searchUrl, params);
    }
    parse(response) {
        if (response.data.resourceSets.length === 0) {
            return [];
        }
        return response.data.resourceSets[0].resources.map((r) => ({
            x: r.point.coordinates[1],
            y: r.point.coordinates[0],
            label: r.address.formattedAddress,
            bounds: [
                [r.bbox[0], r.bbox[1]],
                [r.bbox[2], r.bbox[3]], // n, e
            ],
            raw: r,
        }));
    }
    search({ query }) {
        return __awaiter(this, void 0, void 0, function* () {
            const jsonp = `BING_JSONP_CB_${Date.now()}`;
            const json = yield createScriptElement(this.endpoint({ query, jsonp }), jsonp);
            return this.parse({ data: json });
        });
    }
}
//# sourceMappingURL=bingProvider.js.map