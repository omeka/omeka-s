import { createElement, addClassName, removeClassName, cx } from './domUtils';
export default class ResultList {
    constructor({ handleClick, classNames = {}, notFoundMessage, }) {
        this.selected = -1;
        this.results = [];
        this.onClick = (event) => {
            if (typeof this.handleClick !== 'function') {
                return;
            }
            const target = event.target;
            if (!target ||
                !this.container.contains(target) ||
                !target.hasAttribute('data-key')) {
                return;
            }
            const idx = Number(target.getAttribute('data-key'));
            this.clear();
            this.handleClick({ result: this.results[idx] });
        };
        this.handleClick = handleClick;
        this.notFoundMessage = !!notFoundMessage
            ? createElement('div', cx(classNames.notfound), undefined, { html: notFoundMessage })
            : undefined;
        this.container = createElement('div', cx('results', classNames.resultlist));
        this.container.addEventListener('click', this.onClick, true);
        this.resultItem = createElement('div', cx(classNames.item));
    }
    render(results = [], resultFormat) {
        this.clear();
        results.forEach((result, idx) => {
            const child = this.resultItem.cloneNode(true);
            child.setAttribute('data-key', `${idx}`);
            child.innerHTML = resultFormat({ result });
            this.container.appendChild(child);
        });
        if (results.length > 0) {
            addClassName(this.container.parentElement, 'open');
            addClassName(this.container, 'active');
        }
        else if (!!this.notFoundMessage) {
            this.container.appendChild(this.notFoundMessage);
            addClassName(this.container.parentElement, 'open');
        }
        this.results = results;
    }
    select(index) {
        // eslint-disable-next-line no-confusing-arrow
        Array.from(this.container.children).forEach((child, idx) => idx === index
            ? addClassName(child, 'active')
            : removeClassName(child, 'active'));
        this.selected = index;
        return this.results[index];
    }
    count() {
        return this.results ? this.results.length : 0;
    }
    clear() {
        this.selected = -1;
        while (this.container.lastChild) {
            this.container.removeChild(this.container.lastChild);
        }
        removeClassName(this.container.parentElement, 'open');
        removeClassName(this.container, 'active');
    }
}
//# sourceMappingURL=resultList.js.map