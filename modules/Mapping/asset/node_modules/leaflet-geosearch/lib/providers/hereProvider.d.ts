import AbstractProvider, { EndpointArgument, LatLng, ParseArgument, SearchResult } from './provider';
export interface RequestResult {
    items: RawResult[];
}
export interface RawResult {
    title: string;
    id: string;
    resultType: string;
    address: {
        label: string;
        countryCode: string;
        countryName: string;
        state: string;
        county: string;
        city: string;
        district: string;
        street: string;
        postalCode: string;
        houseNumber: string;
    };
    position: LatLng;
    access: LatLng[];
    categories: {
        id: string;
    }[];
    contacts: {
        [key: string]: {
            value: string;
        }[];
    }[];
    scoring: {
        queryScore: number;
        fieldScore: {
            placeName: number;
        };
    };
}
export default class HereProvider extends AbstractProvider<RequestResult, RawResult> {
    searchUrl: string;
    endpoint({ query }: EndpointArgument): string;
    parse(response: ParseArgument<RequestResult>): SearchResult<RawResult>[];
}
