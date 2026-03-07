import { jest } from "@jest/globals";

/* people-index-init.modules.test.js
 * Focused tests for webroot/js/modules/people-index-init.js
 */

describe("people-index-init", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
        try {
            delete window.$;
        } catch {
            /* ignore */
        }
    });

    test("returns null when no table present", async () => {
        window.$ = () => ({ length: 0, get: () => [] });
        window.$.fn = { dataTable: { isDataTable: () => false } };

        const mod = await import("../modules/people-index-init.js");
        const initPeople = mod.default || mod;
        const res = initPeople();
        expect(res).toEqual({ sb: null, table: null });
    });

    test("initializes DataTable with scroller defaults and binds search", async () => {
        const table = document.createElement("table");
        table.id = "people-table";
        table.innerHTML =
            "<thead><tr><th>Name</th></tr></thead><tbody><tr><td>John</td></tr></tbody>";
        document.body.appendChild(table);

        const panel = document.createElement("div");
        panel.id = "people-searchbuilder-panel";
        document.body.appendChild(panel);

        const controls = document.createElement("div");
        controls.id = "people-controls";
        document.body.appendChild(controls);

        const input = document.createElement("input");
        input.id = "people-name-search";
        document.body.appendChild(input);

        let capturedOptions = null;

        const searchMock = jest.fn(() => ({ draw: () => {} }));
        window.$ = function (sel) {
            if (sel === "#people-table" || sel === table) {
                return {
                    length: 1,
                    get: (i) => (typeof i === "number" ? table : [table]),
                    DataTable: function (options) {
                        capturedOptions = options;
                        const apiObj = {
                            columns: { adjust: () => ({ draw: () => {} }) },
                            table: () => ({ node: () => table }),
                            search: searchMock,
                        };
                        const thisArg = {
                            api: () => apiObj,
                            table: () => ({ node: () => table }),
                        };
                        if (
                            options &&
                            typeof options.initComplete === "function"
                        ) {
                            options.initComplete.call(thisArg);
                        }
                        return { destroy: () => {}, api: () => apiObj };
                    },
                };
            }
            return {
                remove: () => {},
                empty: () => {},
                append: () => {},
                addClass: () => {},
            };
        };
        window.$.fn = {};
        window.$.fn.dataTable = function () {};
        window.$.fn.dataTable.isDataTable = () => false;
        window.$.fn.dataTable.SearchBuilder = undefined;
        window.$.fn.dataTable.ext = { search: [] };
        window.$.fn.DataTable = function () {};

        const mod = await import("../modules/people-index-init.js");
        const initPeople = mod.default || mod;
        const res = initPeople({ dataUrl: "/people?format=json" });

        expect(res.table).not.toBeNull();
        expect(capturedOptions.pageLength).toBe(50);
        expect(capturedOptions.scroller).toBe(true);
        expect(capturedOptions.scrollY).toBe("60vh");
        expect(capturedOptions.deferRender).toBe(true);
        expect(input.dataset.peopleSearchBound).toBe("true");
        expect(capturedOptions.serverSide).toBe(true);
        expect(capturedOptions.ajax.url).toBe("/people?format=json");
        input.dispatchEvent(new Event("input"));
        expect(searchMock).toHaveBeenCalled();
    });

    test("name filter matches data-person-search", async () => {
        const table = document.createElement("table");
        table.id = "people-table";
        table.innerHTML =
            '<thead><tr><th>Name</th></tr></thead><tbody><tr data-person-search="john doe"><td>John</td></tr></tbody>';
        document.body.appendChild(table);

        const panel = document.createElement("div");
        panel.id = "people-searchbuilder-panel";
        document.body.appendChild(panel);

        const controls = document.createElement("div");
        controls.id = "people-controls";
        document.body.appendChild(controls);

        const input = document.createElement("input");
        input.id = "people-name-search";
        document.body.appendChild(input);

        const filters = [];
        window.$ = function (sel) {
            if (sel === "#people-table" || sel === table) {
                return {
                    length: 1,
                    get: (i) => (typeof i === "number" ? table : [table]),
                    DataTable: function (options) {
                        const apiObj = {
                            columns: { adjust: () => ({ draw: () => {} }) },
                            table: () => ({ node: () => table }),
                        };
                        const thisArg = {
                            api: () => apiObj,
                            table: () => ({ node: () => table }),
                        };
                        if (
                            options &&
                            typeof options.initComplete === "function"
                        ) {
                            options.initComplete.call(thisArg);
                        }
                        return { destroy: () => {}, api: () => apiObj };
                    },
                };
            }
            return {
                remove: () => {},
                empty: () => {},
                append: () => {},
                addClass: () => {},
            };
        };
        window.$.fn = {};
        window.$.fn.dataTable = function () {};
        window.$.fn.dataTable.isDataTable = () => false;
        window.$.fn.dataTable.SearchBuilder = undefined;
        window.$.fn.dataTable.ext = { search: filters };
        window.$.fn.DataTable = function () {};

        const mod = await import("../modules/people-index-init.js");
        const initPeople = mod.default || mod;
        initPeople({ dataUrl: "" });

        expect(filters.length).toBe(1);
        const settings = {
            nTable: table,
            aoData: [{ nTr: table.querySelector("tbody tr") }],
        };
        const filterFn = filters[0];

        input.value = "john";
        expect(filterFn(settings, ["John"], 0)).toBe(true);

        input.value = "sally";
        expect(filterFn(settings, ["John"], 0)).toBe(false);
    });

    test("creates filter button and SearchBuilder when available", async () => {
        const table = document.createElement("table");
        table.id = "people-table";
        table.appendChild(document.createElement("thead"));
        table.appendChild(document.createElement("tbody"));
        document.body.appendChild(table);

        const panel = document.createElement("div");
        panel.id = "people-searchbuilder-panel";
        document.body.appendChild(panel);

        const controls = document.createElement("div");
        controls.id = "people-controls";
        document.body.appendChild(controls);

        const input = document.createElement("input");
        input.id = "people-name-search";
        document.body.appendChild(input);

        window.$ = function (sel) {
            if (sel === "#people-table" || sel === table) {
                return {
                    length: 1,
                    get: (i) => (typeof i === "number" ? table : [table]),
                    DataTable: function (options) {
                        const apiObj = {
                            columns: { adjust: () => ({ draw: () => {} }) },
                            table: () => ({ node: () => table }),
                        };
                        const thisArg = {
                            api: () => apiObj,
                            table: () => ({ node: () => table }),
                        };
                        if (
                            options &&
                            typeof options.initComplete === "function"
                        ) {
                            options.initComplete.call(thisArg);
                        }
                        return { destroy: () => {}, api: () => apiObj };
                    },
                };
            }
            return {
                remove: () => {},
                empty: () => {},
                append: (el) => {
                    const root = document.querySelector(sel);
                    if (root && el) root.appendChild(el);
                },
                addClass: () => {},
            };
        };
        window.$.fn = {};
        window.$.fn.dataTable = function () {};
        window.$.fn.dataTable.isDataTable = () => false;
        window.$.fn.dataTable.SearchBuilder = function () {
            this._container = document.createElement("div");
            this._container.className = "dtsb-searchBuilder";
            this.container = () => this._container;
            this.destroy = () => {};
        };
        window.$.fn.dataTable.ext = { search: [] };
        window.$.fn.DataTable = function () {};

        const mod = await import("../modules/people-index-init.js");
        const initPeople = mod.default || mod;
        initPeople();

        const btn = document.getElementById("people-filter-btn");
        expect(btn).toBeTruthy();
        expect(panel.querySelector(".dtsb-searchBuilder")).toBeTruthy();
    });
});
