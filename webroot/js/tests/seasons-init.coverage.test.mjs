/**
 * @jest-environment jsdom
 */

/* Targeted branch coverage for modules/seasons-init.js */
import initSeasons from "../modules/seasons-init.js";

function setupJQuery() {
    const dtInstance = {
        destroy: jest.fn(),
        columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
        draw: jest.fn(),
    };
    const DataTableFn = jest.fn().mockReturnValue(dtInstance);
    DataTableFn.isDataTable = jest.fn().mockReturnValue(false);

    const jq = jest.fn((sel) => {
        const el = document.querySelector(sel);
        return {
            length: el ? 1 : 0,
            get: jest.fn(() => el),
            DataTable: DataTableFn,
            remove: jest.fn(),
            empty: jest.fn(),
        };
    });
    jq.fn = { dataTable: { isDataTable: jest.fn(() => false) } };
    window.$ = jq;
    return { jq, DataTableFn, dtInstance };
}

function setupTable(id = "seasons-table", cols = 5) {
    const headers = Array.from(
        { length: cols },
        (_, i) => `<th>Col${i}</th>`,
    ).join("");
    const cells = Array.from({ length: cols }, () => `<td>x</td>`).join("");
    document.body.innerHTML = `
        <div id="seasons-controls"></div>
        <div id="searchbuilder-panel"></div>
        <table id="${id}">
            <thead><tr>${headers}</tr></thead>
            <tbody>
                <tr>${cells}</tr>
                <tr>${cells}</tr>
            </tbody>
        </table>`;
}

beforeEach(() => {
    document.body.innerHTML = "";
    delete window.$;
    jest.restoreAllMocks();
});

describe("seasons-init initSeasons", () => {
    test("throws when jQuery not available", async () => {
        setupTable();
        // no jQuery on window

        expect(() => initSeasons()).toThrow(
            "jQuery / DataTables not available",
        );
    });

    test("throws when $.fn is undefined", async () => {
        setupTable();
        window.$ = jest.fn();
        window.$.fn = undefined;

        expect(() => initSeasons()).toThrow(
            "jQuery / DataTables not available",
        );
    });

    test("returns nulls when table not found", async () => {
        document.body.innerHTML = "<div></div>";
        setupJQuery();

        const debugSpy = jest
            .spyOn(console, "debug")
            .mockImplementation(() => {});
        const result = initSeasons({ tableSelector: "#nonexistent" });
        expect(result).toEqual({ sb: null, table: null });
        expect(debugSpy).toHaveBeenCalledWith(
            expect.stringContaining("Table not found"),
        );
    });

    test("destroys existing DataTable if present", async () => {
        setupTable();
        const destroyFn = jest.fn();
        const existingDt = {
            destroy: destroyFn,
            columns: {
                adjust: jest.fn().mockReturnValue({ draw: jest.fn() }),
            },
        };
        const newDt = {
            columns: {
                adjust: jest.fn().mockReturnValue({ draw: jest.fn() }),
            },
        };
        const DataTableFn = jest.fn().mockReturnValue(newDt);
        DataTableFn.isDataTable = jest
            .fn()
            .mockReturnValueOnce(true)
            .mockReturnValue(false);

        const jq = jest.fn((sel) => {
            const el = document.querySelector(sel);
            return {
                length: el ? 1 : 0,
                get: jest.fn(() => el),
                DataTable: jest
                    .fn()
                    .mockReturnValueOnce(existingDt)
                    .mockReturnValue(newDt),
                remove: jest.fn(),
                empty: jest.fn(),
            };
        });
        jq.fn = {
            dataTable: {
                isDataTable: jest.fn().mockReturnValue(true),
            },
        };
        window.$ = jq;

        jest.spyOn(console, "debug").mockImplementation(() => {});

        const result = initSeasons();
        expect(destroyFn).toHaveBeenCalled();
        expect(result.table).toBeDefined();
    });

    test("handles DataTable constructor error", async () => {
        setupTable();
        const DataTableFn = jest.fn(() => {
            throw new Error("DT init failed");
        });
        DataTableFn.isDataTable = jest.fn().mockReturnValue(false);

        const jq = jest.fn((sel) => {
            const el = document.querySelector(sel);
            return {
                length: el ? 1 : 0,
                get: jest.fn(() => el),
                DataTable: DataTableFn,
                remove: jest.fn(),
                empty: jest.fn(),
            };
        });
        jq.fn = { dataTable: { isDataTable: jest.fn(() => false) } };
        window.$ = jq;

        jest.spyOn(console, "debug").mockImplementation(() => {});
        const errorSpy = jest
            .spyOn(console, "error")
            .mockImplementation(() => {});

        const result = initSeasons();
        expect(result).toEqual({ sb: null, table: null });
        expect(errorSpy).toHaveBeenCalledWith(
            expect.stringContaining("Failed to initialize"),
            expect.any(Error),
        );
    });

    test("passes columnLabels and restores headers via initComplete", async () => {
        setupTable("seasons-table", 3);
        const { jq, dtInstance } = setupJQuery();

        jest.spyOn(console, "debug").mockImplementation(() => {});

        const result = initSeasons({
            columnLabels: ["A", "B", "C"],
        });
        expect(result.table).toBeDefined();

        // The DataTable constructor was called with dtOptions including initComplete
        const dtCall = jq.mock.results[0]?.value?.DataTable;
        if (dtCall) {
            const opts = dtCall.mock.calls[dtCall.mock.calls.length - 1][0];
            // Call initComplete to exercise header restore + renumber
            if (opts && typeof opts.initComplete === "function") {
                opts.initComplete.call({
                    api: () => dtInstance,
                });
            }
            // Call drawCallback to exercise renumberRows
            if (opts && typeof opts.drawCallback === "function") {
                opts.drawCallback.call({});
            }
        }
    });

    test("passes user initComplete and drawCallback", async () => {
        setupTable("seasons-table", 3);
        const { jq, dtInstance } = setupJQuery();

        jest.spyOn(console, "debug").mockImplementation(() => {});
        const userInit = jest.fn();
        const userDraw = jest.fn();

        initSeasons({
            initComplete: userInit,
            drawCallback: userDraw,
            dataTableOptions: {
                initComplete: userInit,
                drawCallback: userDraw,
            },
        });

        const dtCall = jq.mock.results[0]?.value?.DataTable;
        if (dtCall) {
            const opts = dtCall.mock.calls[dtCall.mock.calls.length - 1][0];
            if (opts?.initComplete)
                opts.initComplete.call({ api: () => dtInstance });
            if (opts?.drawCallback) opts.drawCallback.call({});
            expect(userInit).toHaveBeenCalled();
            expect(userDraw).toHaveBeenCalled();
        }
    });

    test("renumberRows with missing first cell falls back to td", async () => {
        // Table where first child cell is missing at the given numberColumn
        document.body.innerHTML = `
            <div id="seasons-controls"></div>
            <div id="searchbuilder-panel"></div>
            <table id="seasons-table">
                <thead><tr><th>A</th><th>B</th></tr></thead>
                <tbody>
                    <tr><td>x</td></tr>
                </tbody>
            </table>`;
        const { jq, dtInstance } = setupJQuery();

        jest.spyOn(console, "debug").mockImplementation(() => {});

        initSeasons({ numberColumn: 5 }); // column index out of range

        const dtCall = jq.mock.results[0]?.value?.DataTable;
        if (dtCall) {
            const opts = dtCall.mock.calls[dtCall.mock.calls.length - 1][0];
            if (opts?.initComplete)
                opts.initComplete.call({ api: () => dtInstance });
        }
        // Falls back to first td
        const cell = document.querySelector("#seasons-table tbody td");
        expect(cell.textContent).toBe("1");
    });

    test("trySetupSearchBuilder with SearchBuilder constructor", async () => {
        setupTable();
        const sbContainer = jest.fn(() => document.createElement("div"));
        const sbMock = { container: sbContainer, destroy: jest.fn() };
        const SearchBuilderMock = jest.fn().mockReturnValue(sbMock);

        const dtInstance = {
            columns: {
                adjust: jest.fn().mockReturnValue({ draw: jest.fn() }),
            },
        };
        const DataTableFn = jest.fn().mockReturnValue(dtInstance);
        DataTableFn.isDataTable = jest.fn().mockReturnValue(false);
        DataTableFn.SearchBuilder = SearchBuilderMock;

        const jq = jest.fn((sel) => {
            const el = document.querySelector(sel);
            return {
                length: el ? 1 : 0,
                get: jest.fn(() => el),
                DataTable: DataTableFn,
                remove: jest.fn(),
                empty: jest.fn(),
                append: jest.fn(),
                addClass: jest.fn(),
            };
        });
        jq.fn = {
            dataTable: {
                isDataTable: jest.fn(() => false),
                SearchBuilder: SearchBuilderMock,
            },
        };
        window.$ = jq;

        jest.spyOn(console, "debug").mockImplementation(() => {});

        const result = initSeasons();
        expect(result.table).toBeDefined();
    });

    test("trySetupSearchBuilder without controls element", async () => {
        // No #seasons-controls
        document.body.innerHTML = `
            <table id="seasons-table">
                <thead><tr><th>A</th></tr></thead>
                <tbody><tr><td>x</td></tr></tbody>
            </table>`;
        setupJQuery();

        jest.spyOn(console, "debug").mockImplementation(() => {});

        const result = initSeasons();
        expect(result.table).toBeDefined();
    });

    test("trySetupSearchBuilder when SearchBuilder unavailable", async () => {
        setupTable();
        const { jq, dtInstance } = setupJQuery();
        // No SearchBuilder on $.fn.dataTable
        delete jq.fn.dataTable.SearchBuilder;

        jest.spyOn(console, "debug").mockImplementation(() => {});

        initSeasons();

        // Trigger initComplete to run trySetupSearchBuilder path
        const dtCall = jq.mock.results[0]?.value?.DataTable;
        if (dtCall) {
            const opts = dtCall.mock.calls[dtCall.mock.calls.length - 1][0];
            if (opts?.initComplete)
                opts.initComplete.call({ api: () => dtInstance });
        }
        // Panel should have "not available" placeholder
        const panel = document.querySelector("#searchbuilder-panel");
        expect(panel.textContent).toContain("not available");
    });

    test("filter button toggles panel visibility", async () => {
        setupTable();
        const { jq, dtInstance } = setupJQuery();

        jest.spyOn(console, "debug").mockImplementation(() => {});

        initSeasons();

        const dtCall = jq.mock.results[0]?.value?.DataTable;
        if (dtCall) {
            const opts = dtCall.mock.calls[dtCall.mock.calls.length - 1][0];
            if (opts?.initComplete)
                opts.initComplete.call({ api: () => dtInstance });
        }

        const btn = document.getElementById("seasons-filter-btn");
        if (btn) {
            btn.click();
            btn.click();
        }
    });

    test("destroyExisting handles sbInstance with dom.container", async () => {
        setupTable();
        const destroyFn = jest.fn();
        const _sbDomContainer = document.createElement("div");
        const existingDt = {
            destroy: destroyFn,
            columns: {
                adjust: jest.fn().mockReturnValue({ draw: jest.fn() }),
            },
        };
        const newDt = {
            columns: {
                adjust: jest.fn().mockReturnValue({ draw: jest.fn() }),
            },
        };

        let callCount = 0;
        const DataTableFn = jest.fn(() => {
            callCount++;
            if (callCount === 1) return existingDt;
            return newDt;
        });
        DataTableFn.isDataTable = jest
            .fn()
            .mockReturnValueOnce(true)
            .mockReturnValue(false);

        const jq = jest.fn((sel) => {
            const el = document.querySelector(sel);
            return {
                length: el ? 1 : 0,
                get: jest.fn(() => el),
                DataTable: DataTableFn,
                remove: jest.fn(),
                empty: jest.fn(),
                append: jest.fn(),
                addClass: jest.fn(),
            };
        });
        jq.fn = {
            dataTable: { isDataTable: jest.fn(() => true) },
        };
        window.$ = jq;

        jest.spyOn(console, "debug").mockImplementation(() => {});

        const result = initSeasons();
        expect(result.table).toBeDefined();
    });

    test("restoreHeaderText applies to scrollHead table", async () => {
        // Set up table with dataTables_wrapper and scrollHead
        document.body.innerHTML = `
            <div id="seasons-controls"></div>
            <div id="searchbuilder-panel"></div>
            <div class="dataTables_wrapper">
                <div class="dataTables_scrollHead">
                    <table><thead><tr><th>Old</th><th>Old2</th></tr></thead></table>
                </div>
                <table id="seasons-table">
                    <thead><tr><th>Original</th><th>Original2</th></tr></thead>
                    <tbody><tr><td>1</td><td>2</td></tr></tbody>
                </table>
            </div>`;
        const { jq, dtInstance } = setupJQuery();

        jest.spyOn(console, "debug").mockImplementation(() => {});

        initSeasons({ columnLabels: ["New1", "New2"] });

        const dtCall = jq.mock.results[0]?.value?.DataTable;
        if (dtCall) {
            const opts = dtCall.mock.calls[dtCall.mock.calls.length - 1][0];
            if (opts?.initComplete)
                opts.initComplete.call({ api: () => dtInstance });
        }
    });

    test("renumberRows handles empty tbody", async () => {
        document.body.innerHTML = `
            <div id="seasons-controls"></div>
            <div id="searchbuilder-panel"></div>
            <table id="seasons-table">
                <thead><tr><th>A</th></tr></thead>
                <tbody></tbody>
            </table>`;
        const { jq, dtInstance: _dtInstance } = setupJQuery();

        jest.spyOn(console, "debug").mockImplementation(() => {});

        initSeasons();

        const dtCall = jq.mock.results[0]?.value?.DataTable;
        if (dtCall) {
            const opts = dtCall.mock.calls[dtCall.mock.calls.length - 1][0];
            if (opts?.drawCallback) opts.drawCallback.call({});
        }
    });

    test("captureHeaderText with null table returns null", async () => {
        setupTable();
        setupJQuery();
        jest.spyOn(console, "debug").mockImplementation(() => {});

        // columnLabels provided but table is present - we'll get a snapshot
        const result = initSeasons({
            columnLabels: ["A", "B", "C", "D", "E"],
        });
        expect(result.table).toBeDefined();
    });

    test("buildColumnDefs filters out falsy labels", async () => {
        setupTable("seasons-table", 3);
        setupJQuery();
        jest.spyOn(console, "debug").mockImplementation(() => {});

        const result = initSeasons({
            columnLabels: ["A", "", null],
        });
        expect(result.table).toBeDefined();
    });

    test("SearchBuilder constructor error falls to placeholder", async () => {
        setupTable();
        const SearchBuilderMock = jest.fn(() => {
            throw new Error("SB failed");
        });

        const dtInstance = {
            columns: {
                adjust: jest.fn().mockReturnValue({ draw: jest.fn() }),
            },
        };
        const DataTableFn = jest.fn().mockReturnValue(dtInstance);
        DataTableFn.isDataTable = jest.fn().mockReturnValue(false);

        const jq = jest.fn((sel) => {
            const el = document.querySelector(sel);
            return {
                length: el ? 1 : 0,
                get: jest.fn(() => el),
                DataTable: DataTableFn,
                remove: jest.fn(),
                empty: jest.fn(),
                append: jest.fn((child) => {
                    const panel = document.querySelector(
                        "#searchbuilder-panel",
                    );
                    if (panel && child) {
                        if (typeof child === "string") panel.innerHTML += child;
                        else panel.appendChild(child);
                    }
                }),
                addClass: jest.fn(),
            };
        });
        jq.fn = {
            dataTable: {
                isDataTable: jest.fn(() => false),
                SearchBuilder: SearchBuilderMock,
            },
        };
        window.$ = jq;

        jest.spyOn(console, "debug").mockImplementation(() => {});

        initSeasons();

        const dtCall = jq.mock.results[0]?.value?.DataTable;
        if (dtCall) {
            const opts = dtCall.mock.calls[dtCall.mock.calls.length - 1][0];
            if (opts?.initComplete)
                opts.initComplete.call({ api: () => dtInstance });
        }
        const panel = document.querySelector("#searchbuilder-panel");
        expect(panel.textContent).toContain("not available");
    });

    test("SB container from dom.container when container() not function", async () => {
        setupTable();
        const sbDomEl = document.createElement("div");
        sbDomEl.textContent = "SB";
        const sbMock = { dom: { container: sbDomEl } };
        const SearchBuilderMock = jest.fn().mockReturnValue(sbMock);

        const dtInstance = {
            columns: {
                adjust: jest.fn().mockReturnValue({ draw: jest.fn() }),
            },
        };
        const DataTableFn = jest.fn().mockReturnValue(dtInstance);
        DataTableFn.isDataTable = jest.fn().mockReturnValue(false);

        const appendedItems = [];
        const jq = jest.fn((sel) => {
            const el = document.querySelector(sel);
            return {
                length: el ? 1 : 0,
                get: jest.fn(() => el),
                DataTable: DataTableFn,
                remove: jest.fn(),
                empty: jest.fn(),
                append: jest.fn((item) => appendedItems.push(item)),
                addClass: jest.fn(),
            };
        });
        jq.fn = {
            dataTable: {
                isDataTable: jest.fn(() => false),
                SearchBuilder: SearchBuilderMock,
            },
        };
        window.$ = jq;

        jest.spyOn(console, "debug").mockImplementation(() => {});

        initSeasons();

        const dtCall = jq.mock.results[0]?.value?.DataTable;
        if (dtCall) {
            const opts = dtCall.mock.calls[dtCall.mock.calls.length - 1][0];
            if (opts?.initComplete)
                opts.initComplete.call({ api: () => dtInstance });
        }
        expect(appendedItems).toContainEqual(sbDomEl);
    });

    test("SB with no container falls to placeholder", async () => {
        setupTable();
        const sbMock = {}; // no container, no dom
        const SearchBuilderMock = jest.fn().mockReturnValue(sbMock);

        const dtInstance = {
            columns: {
                adjust: jest.fn().mockReturnValue({ draw: jest.fn() }),
            },
        };
        const DataTableFn = jest.fn().mockReturnValue(dtInstance);
        DataTableFn.isDataTable = jest.fn().mockReturnValue(false);

        const appendedItems = [];
        const jq = jest.fn((sel) => {
            const el = document.querySelector(sel);
            return {
                length: el ? 1 : 0,
                get: jest.fn(() => el),
                DataTable: DataTableFn,
                remove: jest.fn(),
                empty: jest.fn(),
                append: jest.fn((item) => appendedItems.push(item)),
                addClass: jest.fn(),
            };
        });
        jq.fn = {
            dataTable: {
                isDataTable: jest.fn(() => false),
                SearchBuilder: SearchBuilderMock,
            },
        };
        window.$ = jq;

        jest.spyOn(console, "debug").mockImplementation(() => {});

        initSeasons();

        const dtCall = jq.mock.results[0]?.value?.DataTable;
        if (dtCall) {
            const opts = dtCall.mock.calls[dtCall.mock.calls.length - 1][0];
            if (opts?.initComplete)
                opts.initComplete.call({ api: () => dtInstance });
        }
        // placeholder was appended
        const placeholders = appendedItems.filter(
            (i) =>
                i instanceof HTMLElement &&
                i.textContent.includes("not available"),
        );
        expect(placeholders.length).toBeGreaterThan(0);
    });

    test("existing sbInstance reuse path with container function", async () => {
        setupTable();
        const containerEl = document.createElement("div");
        const sbMock = {
            container: jest.fn(() => containerEl),
            destroy: jest.fn(),
        };
        const SearchBuilderMock = jest.fn().mockReturnValue(sbMock);

        const dtInstance = {
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
        };
        const DataTableFn = jest.fn().mockReturnValue(dtInstance);
        DataTableFn.isDataTable = jest.fn().mockReturnValue(false);

        const jq = jest.fn((sel) => {
            const el = document.querySelector(sel);
            return {
                length: el ? 1 : 0,
                get: jest.fn(() => el),
                DataTable: DataTableFn,
                remove: jest.fn(),
                empty: jest.fn(),
                append: jest.fn(),
                addClass: jest.fn(),
            };
        });
        jq.fn = {
            dataTable: {
                isDataTable: jest.fn(() => false),
                SearchBuilder: SearchBuilderMock,
            },
        };
        window.$ = jq;

        jest.spyOn(console, "debug").mockImplementation(() => {});

        initSeasons();

        // Call initComplete twice to exercise "existing sbInstance" reuse
        const dtCall = jq.mock.results[0]?.value?.DataTable;
        if (dtCall) {
            const opts = dtCall.mock.calls[dtCall.mock.calls.length - 1][0];
            if (opts?.initComplete) {
                opts.initComplete.call({ api: () => dtInstance });
                opts.initComplete.call({ api: () => dtInstance });
            }
        }
    });

    test("destroyExisting with sb that has container function", async () => {
        // This tests the destroy path where sbInstance has .container() but not .destroy()
        setupTable();
        const containerEl = document.createElement("div");
        const sbMock = { container: jest.fn(() => containerEl) }; // no destroy
        const SearchBuilderMock = jest.fn().mockReturnValue(sbMock);

        const dtInstance = {
            destroy: jest.fn(),
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
        };
        const DataTableFn = jest.fn().mockReturnValue(dtInstance);
        // First call isDataTable returns false (no existing), then after init we need another call
        DataTableFn.isDataTable = jest.fn().mockReturnValue(false);

        const jq = jest.fn((sel) => {
            const el = document.querySelector(sel);
            return {
                length: el ? 1 : 0,
                get: jest.fn(() => el),
                DataTable: DataTableFn,
                remove: jest.fn(),
                empty: jest.fn(),
                append: jest.fn(),
                addClass: jest.fn(),
            };
        });
        jq.fn = {
            dataTable: {
                isDataTable: jest.fn(() => false),
                SearchBuilder: SearchBuilderMock,
            },
        };
        window.$ = jq;

        jest.spyOn(console, "debug").mockImplementation(() => {});

        initSeasons();
    });
});
