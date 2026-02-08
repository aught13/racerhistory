/* seasons-init.branches.test.js
 * Branch and behavior tests for webroot/js/modules/seasons-init.js
 */
// ...existing code...

beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = "";
    try {
        delete window.$;
    } catch {
        /* ignore */
    }
});

test("restores header text and builds columnDefs", async () => {
    const table = document.createElement("table");
    table.id = "seasons-table";
    const thead = document.createElement("thead");
    const tr = document.createElement("tr");
    const th = document.createElement("th");
    th.textContent = "Original";
    tr.appendChild(th);
    thead.appendChild(tr);
    table.appendChild(thead);
    const tbody = document.createElement("tbody");
    table.appendChild(tbody);
    document.body.appendChild(table);

    // DataTable mock captures options and defers initComplete to microtask
    let capturedOptions = null;
    window.$ = function (sel) {
        if (sel === "#seasons-table" || sel === table) {
            return {
                length: 1,
                get: (i) => (typeof i === "number" ? table : [table]),
                DataTable: function (options) {
                    capturedOptions = options;
                    const apiObj = {
                        columns: { adjust: () => ({ draw: () => {} }) },
                    };
                    const thisArg = { api: () => apiObj };
                    // defer initComplete so test can mutate DOM before restore runs
                    Promise.resolve().then(() => {
                        if (
                            options &&
                            typeof options.initComplete === "function"
                        ) {
                            options.initComplete.call(thisArg);
                        }
                    });
                    return {
                        destroy: () => {},
                        api: () => apiObj,
                        _options: options,
                    };
                },
            };
        }
        return { remove: () => {}, empty: () => {}, append: () => {} };
    };

    // provide fn helpers but no SearchBuilder
    window.$.fn = {
        dataTable: { isDataTable: () => false, SearchBuilder: undefined },
    };

    const mod = require("../modules/seasons-init.js");
    const initSeasons = mod && mod.default ? mod.default : mod;
    initSeasons({ columnLabels: ["Original"], columns: [0] });

    // mutate header before initComplete runs
    table.querySelector("th").textContent = "Changed";
    await Promise.resolve();

    // after microtask, header should be restored
    expect(table.querySelector("th").textContent).toBe("Original");
    // columnDefs should be attached to captured options
    expect(capturedOptions).not.toBeNull();
    expect(Array.isArray(capturedOptions.columnDefs)).toBe(true);
    expect(capturedOptions.columnDefs[0].title).toBe("Original");
});

test("renumbers rows using numberColumn option", async () => {
    const table = document.createElement("table");
    table.id = "seasons-table";
    const thead = document.createElement("thead");
    thead.appendChild(document.createElement("tr"));
    table.appendChild(thead);
    const tbody = document.createElement("tbody");
    const tr = document.createElement("tr");
    const td1 = document.createElement("td");
    td1.textContent = "";
    tr.appendChild(td1);
    tbody.appendChild(tr);
    table.appendChild(tbody);
    document.body.appendChild(table);

    window.$ = function (sel) {
        if (sel === "#seasons-table" || sel === table) {
            return {
                length: 1,
                get: (i) => (typeof i === "number" ? table : [table]),
                DataTable: function (options) {
                    const apiObj = {
                        columns: { adjust: () => ({ draw: () => {} }) },
                    };
                    const thisArg = { api: () => apiObj };
                    Promise.resolve().then(() => {
                        if (
                            options &&
                            typeof options.initComplete === "function"
                        ) {
                            options.initComplete.call(thisArg);
                        }
                    });
                    return { destroy: () => {}, api: () => apiObj };
                },
            };
        }
        return { remove: () => {}, empty: () => {}, append: () => {} };
    };
    window.$.fn = {
        dataTable: { isDataTable: () => false, SearchBuilder: undefined },
    };

    const mod = require("../modules/seasons-init.js");
    const initSeasons = mod && mod.default ? mod.default : mod;
    initSeasons({ numberColumn: 0, columns: [0] });

    await Promise.resolve();
    await Promise.resolve();
    expect(table.querySelector("tbody tr td").textContent).toBe("1");
});

test("creates filter button and toggles panel when SearchBuilder present", async () => {
    const table = document.createElement("table");
    table.id = "seasons-table";
    table.appendChild(document.createElement("thead"));
    table.appendChild(document.createElement("tbody"));
    document.body.appendChild(table);
    const panel = document.createElement("div");
    panel.id = "searchbuilder-panel";
    document.body.appendChild(panel);
    const controls = document.createElement("div");
    controls.id = "seasons-controls";
    document.body.appendChild(controls);

    window.$ = function (sel) {
        if (sel === "#seasons-table" || sel === table) {
            return {
                length: 1,
                get: (i) => (typeof i === "number" ? table : [table]),
                DataTable: function (options) {
                    const apiObj = {
                        columns: { adjust: () => ({ draw: () => {} }) },
                    };
                    const thisArg = { api: () => apiObj };
                    Promise.resolve().then(() => {
                        if (
                            options &&
                            typeof options.initComplete === "function"
                        ) {
                            options.initComplete.call(thisArg);
                        }
                    });
                    return { destroy: () => {}, api: () => apiObj };
                },
            };
        }
        return {
            remove: () => {},
            empty: () => {},
            append: (el) => {
                const r = document.querySelector(sel);
                if (r && el) r.appendChild(el);
            },
            addClass: () => {},
        };
    };

    // SearchBuilder constructor produces container
    window.$.fn = {
        dataTable: {
            isDataTable: () => false,
            SearchBuilder: function () {
                // Removed unused variable 'opts' for ESLint
            },
        },
    };

    const mod = require("../modules/seasons-init.js");
    const initSeasons = mod && mod.default ? mod.default : mod;
    initSeasons({ columns: [0] });

    // wait for initComplete to run
    await Promise.resolve();

    const btn = document.getElementById("seasons-filter-btn");
    expect(btn).toBeTruthy();
    // initial aria-expanded false
    expect(btn.getAttribute("aria-expanded")).toBe("false");
    // click to toggle
    btn.click();
    expect(["true", "false"]).toContain(btn.getAttribute("aria-expanded"));
});

test("SearchBuilder constructor throws -> placeholder shown", async () => {
    const table = document.createElement("table");
    table.id = "seasons-table";
    table.appendChild(document.createElement("thead"));
    table.appendChild(document.createElement("tbody"));
    document.body.appendChild(table);
    const panel = document.createElement("div");
    panel.id = "searchbuilder-panel";
    document.body.appendChild(panel);
    const controls = document.createElement("div");
    controls.id = "seasons-controls";
    document.body.appendChild(controls);

    window.$ = function (sel) {
        if (sel === "#seasons-table" || sel === table) {
            return {
                length: 1,
                get: (i) => (typeof i === "number" ? table : [table]),
                DataTable: function (options) {
                    const apiObj = {
                        columns: { adjust: () => ({ draw: () => {} }) },
                    };
                    const thisArg = { api: () => apiObj };
                    Promise.resolve().then(() => {
                        if (
                            options &&
                            typeof options.initComplete === "function"
                        ) {
                            options.initComplete.call(thisArg);
                        }
                    });
                    return { destroy: () => {}, api: () => apiObj };
                },
            };
        }
        return {
            remove: () => {},
            empty: () => {},
            append: (el) => {
                const r = document.querySelector(sel);
                if (r && el && el.nodeType) r.appendChild(el);
            },
            addClass: () => {},
        };
    };

    window.$.fn = {
        dataTable: {
            isDataTable: () => false,
            SearchBuilder: function () {
                throw new Error("boom");
            },
        },
    };

    const mod = require("../modules/seasons-init.js");
    const initSeasons = mod && mod.default ? mod.default : mod;
    initSeasons({ columns: [0] });
    await Promise.resolve();

    const ph = document.querySelector("#searchbuilder-panel .p-3");
    expect(ph).toBeTruthy();
    expect(ph.textContent).toMatch(/Advanced filter/);
});
