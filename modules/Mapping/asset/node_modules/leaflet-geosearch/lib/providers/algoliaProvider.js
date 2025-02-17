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
export default class Provider extends AbstractProvider {
    endpoint() {
        // No query, Algolia requires POST request
        return 'https://places-dsn.algolia.net/1/places/query';
    }
    /**
     * Find index of value with best match
     * (full, fallback to partial, and then to 0)
     */
    findBestMatchLevelIndex(vms) {
        const match = vms.find((vm) => vm.matchLevel === 'full') ||
            vms.find((vm) => vm.matchLevel === 'partial');
        return match ? vms.indexOf(match) : 0;
    }
    /**
     * Algolia not provides labels for hits, so
     * we will implement that builder ourselves
     */
    getLabel(result) {
        var _a, _b, _c, _d;
        return [
            // Building + Street
            (_a = result.locale_names) === null || _a === void 0 ? void 0 : _a.default[this.findBestMatchLevelIndex(result._highlightResult.locale_names.default)],
            // City
            (_b = result.city) === null || _b === void 0 ? void 0 : _b.default[this.findBestMatchLevelIndex(result._highlightResult.city.default)],
            // Administrative (State / Province)
            result.administrative[this.findBestMatchLevelIndex(result._highlightResult.administrative)],
            // Zip code / Postal code
            (_c = result.postcode) === null || _c === void 0 ? void 0 : _c[this.findBestMatchLevelIndex(result._highlightResult.postcode)],
            // Country
            (_d = result.country) === null || _d === void 0 ? void 0 : _d.default,
        ]
            .filter(Boolean)
            .join(', ');
    }
    parse(response) {
        return response.data.hits.map((r) => ({
            x: r._geoloc.lng,
            y: r._geoloc.lat,
            label: this.getLabel(r),
            bounds: null,
            raw: r,
        }));
    }
    search({ query }) {
        return __awaiter(this, void 0, void 0, function* () {
            const params = typeof query === 'string' ? { query } : query;
            const request = yield fetch(this.endpoint(), {
                method: 'POST',
                body: JSON.stringify(Object.assign(Object.assign({}, this.options.params), params)),
            });
            const json = yield request.json();
            return this.parse({ data: json });
        });
    }
}
//# sourceMappingURL=algoliaProvider.js.map