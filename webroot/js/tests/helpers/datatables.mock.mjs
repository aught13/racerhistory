/* eslint-env jest, browser */
/* global HTMLElement */

import { jest } from "@jest/globals";

export default function setupDataTablesMock() {
    global.__datatableCalls = [];

    global.$ = function (selectorOrEl) {
        let el = null;
        if (typeof selectorOrEl === "string") {
            el = document.querySelector(selectorOrEl);
        } else if (selectorOrEl instanceof HTMLElement) {
            el = selectorOrEl;
        }

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

        wrapper.DataTable = jest.fn((opts) => {
            const api = {
                settings: opts,
                columns: { adjust: jest.fn() },
                destroy: jest.fn(),
            };
            global.__datatableCalls.push({ el, opts, api });
            if (opts && typeof opts.initComplete === "function") {
                opts.initComplete.call(api);
            }
            return api;
        });

        return wrapper;
    };

    global.$.fn = global.$.fn || {};
    global.$.fn.dataTable = global.$.fn.dataTable || {};
    global.$.fn.dataTable.isDataTable = jest.fn(() => false);

    return function teardown() {
        delete global.$;
        delete global.__datatableCalls;
    };
}
