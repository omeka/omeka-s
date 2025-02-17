import AbstractProvider, { EndpointArgument, ParseArgument, SearchArgument, SearchResult } from './provider';
export interface RequestResult {
    authenticationResultCode: string;
    brandLogoUri: string;
    copyright: string;
    resourceSets: {
        estimatedTotal: number;
        resources: RawResult[];
    }[];
    statusCode: number;
    statusDescription: string;
    traceId: string;
}
export interface RawResult {
    __type: string;
    bbox: [number, number, number, number];
    name: string;
    point: {
        type: 'Point';
        coordinates: [number, number];
    };
    address: {
        adminDistrict: string;
        adminDistrict2: string;
        countryRegion: string;
        formattedAddress: string;
        locality: string;
    };
    confidence: string;
    entityType: string;
    geocodePoints: [
        {
            type: 'Point';
            coordinates: [number, number];
            calculationMethod: string;
            usageTypes: string[];
        }
    ];
    matchCodes: string[];
}
export default class BingProvider extends AbstractProvider<RequestResult, RawResult> {
    searchUrl: string;
    endpoint({ query, jsonp }: EndpointArgument & {
        jsonp: string;
    }): string;
    parse(response: ParseArgument<RequestResult>): SearchResult<RawResult>[];
    search({ query }: SearchArgument): Promise<SearchResult<RawResult>[]>;
}
