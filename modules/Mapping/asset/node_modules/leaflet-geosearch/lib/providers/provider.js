var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
export var RequestType;
(function (RequestType) {
    RequestType[RequestType["SEARCH"] = 0] = "SEARCH";
    RequestType[RequestType["REVERSE"] = 1] = "REVERSE";
})(RequestType || (RequestType = {}));
export default class AbstractProvider {
    constructor(options = {}) {
        this.options = options;
    }
    getParamString(params = {}) {
        const set = Object.assign(Object.assign({}, this.options.params), params);
        return Object.keys(set)
            .map((key) => `${encodeURIComponent(key)}=${encodeURIComponent(set[key])}`)
            .join('&');
    }
    getUrl(url, params) {
        return `${url}?${this.getParamString(params)}`;
    }
    search(options) {
        return __awaiter(this, void 0, void 0, function* () {
            const url = this.endpoint({
                query: options.query,
                type: RequestType.SEARCH,
            });
            const request = yield fetch(url);
            const json = yield request.json();
            return this.parse({ data: json });
        });
    }
}
//# sourceMappingURL=provider.js.map