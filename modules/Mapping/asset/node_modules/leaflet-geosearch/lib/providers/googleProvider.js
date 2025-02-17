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
import { Loader } from '@googlemaps/js-api-loader';
export default class GoogleProvider extends AbstractProvider {
    constructor(options) {
        super(options);
        this.loader = null;
        this.geocoder = null;
        if (typeof window !== 'undefined') {
            this.loader = new Loader(options).load().then((google) => {
                const geocoder = new google.maps.Geocoder();
                this.geocoder = geocoder;
                return geocoder;
            });
        }
    }
    endpoint({ query }) {
        throw new Error('Method not implemented.');
    }
    parse(response) {
        return response.data.results.map((r) => {
            const { lat, lng } = r.geometry.location.toJSON();
            const { east, north, south, west } = r.geometry.viewport.toJSON();
            return {
                x: lng,
                y: lat,
                label: r.formatted_address,
                bounds: [
                    [south, west],
                    [north, east],
                ],
                raw: r,
            };
        });
    }
    search(options) {
        return __awaiter(this, void 0, void 0, function* () {
            const geocoder = this.geocoder || (yield this.loader);
            if (!geocoder) {
                throw new Error('GoogleMaps GeoCoder is not loaded. Are you trying to run this server side?');
            }
            const response = yield geocoder
                .geocode({ address: options.query }, (response) => ({
                results: response,
            }))
                .catch((e) => {
                if (e.code !== 'ZERO_RESULTS') {
                    console.error(`${e.code}: ${e.message}`);
                }
                return { results: [] };
            });
            return this.parse({ data: response });
        });
    }
}
//# sourceMappingURL=googleProvider.js.map