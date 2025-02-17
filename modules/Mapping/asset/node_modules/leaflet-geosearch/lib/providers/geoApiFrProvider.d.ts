import AbstractProvider, { EndpointArgument, ParseArgument, ProviderOptions, SearchResult } from './provider';
export interface RequestResult {
    features: RawResult[];
    type: string;
    version: string;
    attribution: string;
    licence: string;
    query: string;
    limit: string;
}
export interface RawResult {
    properties: {
        label: string;
        score: number;
        importance: number;
        x: number;
        y: number;
        housenumber: string;
        id: string;
        type: string;
        name: string;
        postcode: string;
        citycode: string;
        city: string;
        context: string;
        street: string;
    };
    type: string;
    geometry: {
        coordinates: number[];
        type: string;
    };
}
export declare type GeoApiFrProviderOptions = {
    searchUrl?: string;
    reverseUrl?: string;
} & ProviderOptions;
export default class GeoApiFrProvider extends AbstractProvider<RequestResult, RawResult> {
    searchUrl: string;
    reverseUrl: string;
    constructor(options?: GeoApiFrProviderOptions);
    endpoint({ query, type }: EndpointArgument): string;
    parse(result: ParseArgument<RequestResult>): SearchResult<RawResult>[];
}
