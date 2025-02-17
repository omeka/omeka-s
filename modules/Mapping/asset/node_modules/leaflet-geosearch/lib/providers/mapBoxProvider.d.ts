import AbstractProvider, { EndpointArgument, ParseArgument, ProviderOptions, SearchResult } from './provider';
export declare type RequestResult = {
    features: RawResult[];
};
export interface RawResult {
    center: [string, string];
    text: string;
    place_name: string;
    bbox: [string, string, string, string];
}
export declare type MapBoxProviderOptions = {
    searchUrl?: string;
    reverseUrl?: string;
} & ProviderOptions;
export default class MapBoxProvider extends AbstractProvider<RequestResult, RawResult> {
    searchUrl: string;
    constructor(options?: MapBoxProviderOptions);
    endpoint({ query }: EndpointArgument): string;
    parse(response: ParseArgument<RequestResult>): SearchResult<RawResult>[];
}
