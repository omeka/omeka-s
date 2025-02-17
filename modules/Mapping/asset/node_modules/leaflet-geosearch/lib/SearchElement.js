var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
import { createElement, addClassName, removeClassName, cx, stopPropagation, replaceClassName, } from './domUtils';
import { ESCAPE_KEY, ENTER_KEY } from './constants';
export default class SearchElement {
    constructor({ handleSubmit, searchLabel, classNames = {}, }) {
        this.hasError = false;
        this.container = createElement('div', cx('geosearch', classNames.container));
        this.form = createElement('form', ['', classNames.form].join(' '), this.container, {
            autocomplete: 'none',
            onClick: stopPropagation,
            onDblClick: stopPropagation,
            touchStart: stopPropagation,
            touchEnd: stopPropagation,
        });
        this.input = createElement('input', ['glass', classNames.input].join(' '), this.form, {
            type: 'text',
            placeholder: searchLabel || 'search',
            onInput: this.onInput,
            onKeyUp: (e) => this.onKeyUp(e),
            onKeyPress: (e) => this.onKeyPress(e),
            onFocus: this.onFocus,
            onBlur: this.onBlur,
            // For some reason, leaflet is blocking the 'touchstart', manually give
            // focus to the input onClick
            // > Ignored attempt to cancel a touchstart event with cancelable=false,
            // > for example because scrolling is in progress and cannot be interrupted.
            onClick: () => {
                this.input.focus();
                this.input.dispatchEvent(new Event('focus'));
            },
        });
        this.handleSubmit = handleSubmit;
    }
    onFocus() {
        addClassName(this.form, 'active');
    }
    onBlur() {
        removeClassName(this.form, 'active');
    }
    onSubmit(event) {
        return __awaiter(this, void 0, void 0, function* () {
            stopPropagation(event);
            replaceClassName(this.container, 'error', 'pending');
            yield this.handleSubmit({ query: this.input.value });
            removeClassName(this.container, 'pending');
        });
    }
    onInput() {
        if (!this.hasError) {
            return;
        }
        removeClassName(this.container, 'error');
        this.hasError = false;
    }
    onKeyUp(event) {
        if (event.keyCode !== ESCAPE_KEY) {
            return;
        }
        removeClassName(this.container, ['pending', 'active']);
        this.input.value = '';
        document.body.focus();
        document.body.blur();
    }
    onKeyPress(event) {
        if (event.keyCode !== ENTER_KEY) {
            return;
        }
        this.onSubmit(event);
    }
    setQuery(query) {
        this.input.value = query;
    }
}
//# sourceMappingURL=SearchElement.js.map