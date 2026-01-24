/* eslint-env node, jest, browser */
/* global module, HTMLElement */
// Reusable DataTables + SearchBuilder mock helper for Jest tests (jsdom)
module.exports = function setupDataTablesMock() {
    // minimal jQuery-like wrapper
    global.$ = function (selectorOrEl) {
        let el = null;
        if (typeof selectorOrEl === "string")
            el = document.querySelector(selectorOrEl);
        else if (selectorOrEl instanceof HTMLElement) el = selectorOrEl;
        const wrapper = {
            el,
            empty() {
                if (el) while (el.firstChild) el.removeChild(el.firstChild);
                return wrapper;
            },
            append(node) {
                if (!el) return wrapper;
                if (node && node.el) el.appendChild(node.el);
                else if (node instanceof HTMLElement) el.appendChild(node);
                else if (Array.isArray(node) && node[0] instanceof HTMLElement)
                    el.appendChild(node[0]);
                else if (
                    node &&
                    node.jquery &&
                    node.length &&
                    node[0] instanceof HTMLElement
                )
                    el.appendChild(node[0]);
                return wrapper;
            },
            remove() {
                if (el && el.parentNode) el.parentNode.removeChild(el);
                return wrapper;
            },
            addClass(cls) {
                if (el) el.classList.add(cls);
                return wrapper;
            },
            removeClass(cls) {
                if (el) el.classList.remove(cls);
                return wrapper;
            },
            hasClass(cls) {
                return el ? el.classList.contains(cls) : false;
            },
            length: el ? 1 : 0,
            get(i) {
                return i === 0 ? el : null;
            },
            toArray() {
                return el ? [el] : [];
            },
        };

        if (selectorOrEl === "#seasons-table") {
            wrapper.DataTable = function (opts) {
                const api = {
                    settings: opts,
                    columns: { adjust: jest.fn() },
                    destroy: jest.fn(),
                };
                if (opts && typeof opts.initComplete === "function") {
                    opts.initComplete.call(api);
                }
                return api;
            };
        }

        return wrapper;
    };

    global.$.fn = global.$.fn || {};
    global.$.fn.dataTable = global.$.fn.dataTable || {};

    // Mock SearchBuilder implementation
    global.$.fn.dataTable.SearchBuilder = function (dt, options) {
        this.dt = dt;
        this.options = options;
        this.dom = {};
        const container = document.createElement("div");
        container.className = "dtsb-searchBuilder";
        container.innerHTML =
            '<div class="dtsb-titleRow"><div class="dtsb-title">Build advanced filters</div></div><div class="dtsb-group"><button class="dtsb-add dtsb-button" type="button">Add condition</button></div>';
        this.dom.container = container;
        this.container = function () {
            return container;
        };
        this.destroy = function () {
            if (container.parentNode)
                container.parentNode.removeChild(container);
        };
        return this;
    };

    return function teardown() {
        delete global.$;
    };
};
