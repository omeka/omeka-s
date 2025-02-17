import AbstractProvider, { LatLng, ParseArgument, SearchArgument, SearchResult } from './provider';
interface RequestResult {
    hits: RawResult[];
}
interface ValueMatch {
    value: string;
    matchLevel: string;
    matchedWords: string[];
    fullyHighlighted?: boolean;
}
interface RawResult {
    country: {
        [key: string]: string;
    };
    country_code: string;
    city: {
        [key: string]: string[];
    };
    importance: number;
    _tags: string[];
    postcode: string[];
    population: number;
    is_country: boolean;
    is_highway: boolean;
    is_city: boolean;
    is_popular: boolean;
    administrative: string[];
    admin_level: number;
    is_suburb: boolean;
    locale_names: {
        default: string[];
    };
    _geoloc: LatLng;
    objectID: string;
    _highlightResult: {
        country: {
            default: ValueMatch;
            [key: string]: ValueMatch;
        };
        city: {
            default: ValueMatch[];
            [key: string]: ValueMatch[];
        };
        postcode: ValueMatch[];
        administrative: ValueMatch[];
        locale_names: {
            default: ValueMatch[];
        };
    };
}
export default class Provider extends AbstractProvider<RequestResult, RawResult> {
    endpoint(): string;
    /**
     * Find index of value with best match
     * (full, fallback to partial, and then to 0)
     */
    findBestMatchLevelIndex(vms: ValueMatch[]): number;
    /**
     * Algolia not provides labels for hits, so
     * we will implement that builder ourselves
     */
    getLabel(result: RawResult): string;
    parse(response: ParseArgument<RequestResult>): SearchResult<RawResult>[];
    search({ query }: SearchArgument): Promise<SearchResult<RawResult>[]>;
}
export {};
