interface SearchElementProps {
    searchLabel?: string;
    handleSubmit: (args: {
        query: string;
    }) => void;
    classNames?: {
        container?: string;
        form?: string;
        input?: string;
    };
}
export default class SearchElement {
    container: HTMLDivElement;
    form: HTMLFormElement;
    input: HTMLInputElement;
    handleSubmit: (args: {
        query: string;
    }) => void;
    hasError: boolean;
    constructor({ handleSubmit, searchLabel, classNames, }: SearchElementProps);
    onFocus(): void;
    onBlur(): void;
    onSubmit(event: Event): Promise<void>;
    onInput(): void;
    onKeyUp(event: KeyboardEvent): void;
    onKeyPress(event: KeyboardEvent): void;
    setQuery(query: string): void;
}
export {};
