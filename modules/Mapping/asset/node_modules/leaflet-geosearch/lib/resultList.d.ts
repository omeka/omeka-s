import { SearchResult } from './providers/provider';
interface ResultListProps {
    handleClick: (args: {
        result: SearchResult;
    }) => void;
    classNames?: {
        resultlist?: string;
        item?: string;
        notfound?: string;
    };
    notFoundMessage?: string;
}
export default class ResultList {
    handleClick?: (args: {
        result: SearchResult;
    }) => void;
    selected: number;
    results: SearchResult[];
    container: HTMLDivElement;
    resultItem: HTMLDivElement;
    notFoundMessage?: HTMLDivElement;
    constructor({ handleClick, classNames, notFoundMessage, }: ResultListProps);
    render(results: SearchResult<any>[] | undefined, resultFormat: (args: {
        result: SearchResult;
    }) => string): void;
    select(index: number): SearchResult;
    count(): number;
    clear(): void;
    onClick: (event: Event) => void;
}
export {};
