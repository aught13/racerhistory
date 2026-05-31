import { jest } from "@jest/globals";

/**
 * Coverage tests for seasons-init-loader.mjs
 * Targets: inferActiveTable, countTableColumns, buildSplitsColumnLabels,
 *   buildStandardColumnLabels, getCellDataAttr, buildOptions,
 *   waitForDataTables, isDataTablesAvailable, boot, enhancedBoot
 */

beforeEach(() => {
    jest.resetModules();
    jest.restoreAllMocks();
    jest.useRealTimers();
    document.body.innerHTML = "";
    delete globalThis.__SEASONS_INIT_LOADER_MOCK__;
    delete globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__;
    delete window.__SEASONS_INIT_LOADER_MOCK__;
    delete window.__SEASONS_SEARCHBUILDER_LOADER_MOCK__;
    delete window.$;
});

afterEach(() => {
    jest.restoreAllMocks();
    jest.useRealTimers();
    delete window.$;
});

function flushPromises(n = 5) {
    let p = Promise.resolve();
    for (let i = 0; i < n; i++) {
        p = p.then(() => new Promise((r) => setTimeout(r, 0)));
    }
    return p;
}

function setupDT() {
    const dtInstance = {
        destroy: jest.fn(),
        draw: jest.fn(),
        columns: {
            adjust: jest
                .fn()
                .mockReturnValue({ draw: jest.fn().mockReturnThis() }),
        },
    };
    const DataTableFn = jest.fn().mockReturnValue(dtInstance);
    DataTableFn.isDataTable = jest.fn().mockReturnValue(false);

    const jq = jest.fn((sel) => ({
        length: document.querySelectorAll(sel).length,
        get: (i) => document.querySelectorAll(sel)[i] || null,
        DataTable: DataTableFn,
        remove: jest.fn(),
        append: jest.fn(),
        empty: jest.fn(),
        addClass: jest.fn(),
    }));
    jq.fn = {
        DataTable: DataTableFn,
        dataTable: Object.assign(DataTableFn, {
            isDataTable: DataTableFn.isDataTable,
            ext: { search: [] },
            SearchBuilder: jest.fn(),
        }),
    };
    window.$ = jq;
    return { jq, DataTableFn, dtInstance };
}

async function bootLoader(eventType = "turbo:load", target = null) {
    const mod = await import("../../legacy/seasons-init-loader.mjs");
    const evt = new Event(eventType, { bubbles: true });
    if (target) {
        Object.defineProperty(evt, "target", { value: target });
    }
    document.dispatchEvent(evt);
    return mod;
}

describe("seasons-init-loader.mjs (coverage)", () => {
    describe("inferActiveTable + buildOptions", () => {
        test("detects splits table and builds splits options", async () => {
            document.body.innerHTML = `
                <div id="seasons-table-frame" data-seasons-view="splits" data-splits-has-ties="true">
                    <table id="season-splits-table">
                        <thead><tr><th>A</th><th>B</th><th>C</th></tr></thead>
                    </table>
                </div>
                <div id="seasons-controls"></div>
                <div id="searchbuilder-panel"></div>`;
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            setupDT();

            await bootLoader();
            await flushPromises();

            expect(initFn).toHaveBeenCalled();
            const opts = initFn.mock.calls[0]?.[0];
            expect(opts?.tableSelector).toBe("#season-splits-table");
            const labels = opts?.columnLabels || [];
            expect(labels).toContain("HW");
            expect(labels).toContain("HT");
        });

        test("falls back to standard table", async () => {
            document.body.innerHTML = `
                <div id="seasons-table-frame" data-seasons-view="standard">
                    <table id="seasons-table"><thead><tr><th>A</th></tr></thead></table>
                </div>
                <div id="seasons-controls"></div>
                <div id="searchbuilder-panel"></div>`;
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            setupDT();

            await bootLoader();
            await flushPromises();

            expect(initFn).toHaveBeenCalled();
            const opts = initFn.mock.calls[0]?.[0];
            expect(opts?.tableSelector).toBe("#seasons-table");
            expect(opts?.columns).toEqual([
                1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16,
            ]);
        });

        test("falls back to frame dataset when no table found", async () => {
            document.body.innerHTML = `
                <div id="seasons-table-frame" data-seasons-view="splits"></div>`;
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            setupDT();
            jest.spyOn(console, "debug").mockImplementation(() => {});
            jest.spyOn(console, "warn").mockImplementation(() => {});

            await bootLoader();
            await flushPromises();
        });
    });

    describe("countTableColumns", () => {
        test("counts colspan correctly", async () => {
            document.body.innerHTML = `
                <div id="seasons-table-frame" data-seasons-view="splits" data-splits-has-ties="false">
                    <table id="season-splits-table">
                        <thead><tr><th>A</th><th colspan="3">B</th><th>C</th></tr></thead>
                    </table>
                </div>
                <div id="seasons-controls"></div>
                <div id="searchbuilder-panel"></div>`;
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            setupDT();

            await bootLoader();
            await flushPromises();

            expect(initFn).toHaveBeenCalled();
            const opts = initFn.mock.calls[0]?.[0];
            expect(opts?.columns).toEqual([1, 2, 3, 4]);
        });
    });

    describe("buildSplitsColumnLabels", () => {
        test("excludes tie columns when hasTies=false", async () => {
            document.body.innerHTML = `
                <div id="seasons-table-frame" data-seasons-view="splits" data-splits-has-ties="false">
                    <table id="season-splits-table">
                        <thead><tr>${"<th>X</th>".repeat(18)}</tr></thead>
                    </table>
                </div>
                <div id="seasons-controls"></div>
                <div id="searchbuilder-panel"></div>`;
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            setupDT();

            await bootLoader();
            await flushPromises();

            expect(initFn).toHaveBeenCalled();
            const opts = initFn.mock.calls[0]?.[0];
            const labels = opts?.columnLabels || [];
            expect(labels).toContain("HW");
            expect(labels).toContain("HL");
            expect(labels).not.toContain("HT");
        });
    });

    describe("buildOptions standard view render function", () => {
        test("columnDefs render returns correct values", async () => {
            document.body.innerHTML = `
                <div id="seasons-table-frame" data-seasons-view="standard">
                    <table id="seasons-table"><thead><tr><th>A</th></tr></thead></table>
                </div>
                <div id="seasons-controls"></div>
                <div id="searchbuilder-panel"></div>`;
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            setupDT();

            await bootLoader();
            await flushPromises();

            expect(initFn).toHaveBeenCalled();
            const opts = initFn.mock.calls[0]?.[0];
            const defs = opts?.dataTableOptions?.columnDefs;
            expect(defs).toBeDefined();
            const renderFn = defs[0].render;

            // display type => pass-through
            expect(renderFn("val", "display", {}, {})).toBe("val");

            // filter type with data-search
            const cell = document.createElement("td");
            cell.setAttribute("data-search", "sv");
            const meta = {
                row: 0,
                col: 16,
                settings: {
                    aoData: [{ anCells: new Array(17).fill(null) }],
                },
            };
            meta.settings.aoData[0].anCells[16] = cell;
            expect(renderFn("val", "filter", {}, meta)).toBe("sv");

            // data-filter fallback
            const cell2 = document.createElement("td");
            cell2.setAttribute("data-filter", "fv");
            meta.settings.aoData[0].anCells[16] = cell2;
            expect(renderFn("val", "search", {}, meta)).toBe("fv");

            // no data attrs
            const cell3 = document.createElement("td");
            meta.settings.aoData[0].anCells[16] = cell3;
            expect(renderFn("val", "filter", {}, meta)).toBe("val");

            // null cell
            meta.settings.aoData[0].anCells[16] = null;
            expect(renderFn("val", "filter", {}, meta)).toBe("val");

            // no meta settings
            expect(renderFn("val", "filter", {}, {})).toBe("val");
        });
    });

    describe("ensureDataTablesLoaded", () => {
        test("resolves when already available", async () => {
            document.body.innerHTML = `
                <table id="seasons-table"><thead><tr><th>A</th></tr></thead></table>
                <div id="seasons-controls"></div>
                <div id="searchbuilder-panel"></div>`;
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            setupDT();

            await bootLoader();
            await flushPromises();

            expect(initFn).toHaveBeenCalled();
        });

        test("skips init when no table on page (e.g. blog)", async () => {
            document.body.innerHTML = `<p>No seasons here</p>`;
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            jest.spyOn(console, "debug").mockImplementation(() => {});

            await bootLoader();
            await flushPromises();

            expect(initFn).not.toHaveBeenCalled();
        });

        test("warns when DataTables fails to load", async () => {
            document.body.innerHTML = `
                <table id="seasons-table"><thead><tr><th>A</th></tr></thead></table>
                <div id="seasons-controls"></div>
                <div id="searchbuilder-panel"></div>`;
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            // No jQuery or DataTables set up
            const warnSpy = jest
                .spyOn(console, "warn")
                .mockImplementation(() => {});
            jest.spyOn(console, "debug").mockImplementation(() => {});

            jest.useFakeTimers();
            await bootLoader();
            for (let i = 0; i < 220; i++) {
                jest.advanceTimersByTime(50);
                await Promise.resolve();
            }

            expect(warnSpy).toHaveBeenCalledWith(
                expect.stringContaining("DataTables failed to load"),
                expect.any(Error),
            );
        });
    });

    describe("boot edge cases", () => {
        test("ignores non-seasons frame turbo events", async () => {
            document.body.innerHTML = `<div id="other-frame"></div>`;
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            setupDT();
            jest.spyOn(console, "debug").mockImplementation(() => {});

            const frame = document.getElementById("other-frame");
            await bootLoader("turbo:frame-load", frame);
            await flushPromises();

            expect(initFn).not.toHaveBeenCalled();
        });

        test("retries when DOM elements not found", async () => {
            document.body.innerHTML = `
                <table id="seasons-table"><thead><tr><th>A</th></tr></thead></table>
                <div id="seasons-table-frame"></div>`;
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            setupDT();
            jest.spyOn(console, "debug").mockImplementation(() => {});
            const warnSpy = jest
                .spyOn(console, "warn")
                .mockImplementation(() => {});

            jest.useFakeTimers();
            await bootLoader();
            for (let i = 0; i < 15; i++) {
                jest.advanceTimersByTime(50);
                await Promise.resolve();
            }

            expect(warnSpy).toHaveBeenCalledWith(
                expect.stringContaining("Required DOM elements not found"),
            );
        });

        test("SearchBuilder rejection falls back to runInit", async () => {
            document.body.innerHTML = `
                <table id="seasons-table"><thead><tr><th>A</th></tr></thead></table>
                <div id="seasons-controls"></div>
                <div id="searchbuilder-panel"></div>`;
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.reject(new Error("SB fail"));
            setupDT();
            const warnSpy = jest
                .spyOn(console, "warn")
                .mockImplementation(() => {});
            jest.spyOn(console, "debug").mockImplementation(() => {});

            await bootLoader();
            await flushPromises();

            expect(warnSpy).toHaveBeenCalledWith(
                "SearchBuilder failed to load",
                expect.any(Error),
            );
            expect(initFn).toHaveBeenCalled();
        });

        test("turbo:frame-load on seasons-table-frame runs boot", async () => {
            document.body.innerHTML = `
                <turbo-frame id="seasons-table-frame">
                    <table id="seasons-table"><thead><tr><th>A</th></tr></thead></table>
                    <div id="seasons-controls"></div>
                    <div id="searchbuilder-panel"></div>
                </turbo-frame>`;
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            setupDT();
            jest.spyOn(console, "debug").mockImplementation(() => {});

            const frame = document.getElementById("seasons-table-frame");
            await bootLoader("turbo:frame-load", frame);
            await flushPromises();

            expect(initFn).toHaveBeenCalled();
        });
    });

    describe("isDataTablesAvailable branches", () => {
        test("false when $.fn undefined", async () => {
            document.body.innerHTML = `
                <table id="seasons-table"><thead><tr><th>A</th></tr></thead></table>
                <div id="seasons-controls"></div>
                <div id="searchbuilder-panel"></div>`;
            window.$ = {};
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            const warnSpy = jest
                .spyOn(console, "warn")
                .mockImplementation(() => {});
            jest.spyOn(console, "debug").mockImplementation(() => {});

            jest.useFakeTimers();
            await bootLoader();
            // hasJquery() returns false since $.fn is undefined,
            // so ensureDataTablesLoaded waits then times out.
            for (let i = 0; i < 220; i++) {
                jest.advanceTimersByTime(50);
                await Promise.resolve();
            }
            expect(warnSpy).toHaveBeenCalledWith(
                expect.stringContaining("DataTables failed to load"),
                expect.any(Error),
            );
        });

        test("true when dataTable is object (not function)", async () => {
            document.body.innerHTML = `
                <table id="seasons-table"><thead><tr><th>A</th></tr></thead></table>
                <div id="seasons-controls"></div>
                <div id="searchbuilder-panel"></div>`;
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            const jq = jest.fn(() => ({
                length: 1,
                get: () => null,
                DataTable: jest.fn(),
            }));
            jq.fn = {
                DataTable: undefined,
                dataTable: { ext: { search: [] } },
            };
            window.$ = jq;
            jest.spyOn(console, "debug").mockImplementation(() => {});

            await bootLoader();
            await flushPromises();
        });
    });
});

describe("seasons-init-loader cleanupSeasonsPage – back-button fix", () => {
    beforeEach(() => {
        jest.resetModules();
        jest.restoreAllMocks();
        document.body.innerHTML = "";
        delete globalThis.__SEASONS_INIT_LOADER_MOCK__;
        delete globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__;
        delete window.$;
    });

    afterEach(() => {
        jest.restoreAllMocks();
        delete globalThis.__SEASONS_INIT_LOADER_MOCK__;
        delete globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__;
        delete window.$;
    });

    test("cleanupSeasonsPage calls destroy(false) so table stays in DOM for Turbo cache", async () => {
        document.body.innerHTML = `
            <table id="seasons-table"><thead><tr><th>A</th></tr></thead></table>
            <div id="searchbuilder-panel"></div>`;
        const table = document.querySelector("#seasons-table");

        const destroyFn = jest.fn();
        const DataTableFn = jest.fn().mockReturnValue({ destroy: destroyFn });
        DataTableFn.isDataTable = jest.fn().mockReturnValue(true);
        DataTableFn.ext = { search: [] };

        const jq = jest.fn(() => ({
            length: 1,
            get: () => table,
            DataTable: DataTableFn,
        }));
        jq.fn = {
            DataTable: DataTableFn,
            dataTable: Object.assign(DataTableFn, {
                isDataTable: DataTableFn.isDataTable,
                ext: DataTableFn.ext,
            }),
        };
        window.$ = jq;

        globalThis.__SEASONS_INIT_LOADER_MOCK__ = jest.fn();
        globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
            Promise.resolve();
        const mod = await import("../../legacy/seasons-init-loader.mjs");

        mod.cleanupSeasonsPage();

        // destroy should be called with false (keep table in DOM for Turbo cache snapshot)
        expect(destroyFn).toHaveBeenCalledWith(false);
        // The table element should still be in the DOM after cleanup
        expect(document.querySelector("#seasons-table")).not.toBeNull();
    });

    test("cleanupSeasonsPage via turbo:before-cache leaves seasons table in DOM", async () => {
        document.body.innerHTML = `
            <table id="seasons-table"><thead><tr><th>A</th></tr></thead></table>
            <div id="searchbuilder-panel"></div>`;

        const destroyFn = jest.fn();
        const DataTableFn = jest.fn().mockReturnValue({ destroy: destroyFn });
        DataTableFn.isDataTable = jest.fn().mockReturnValue(true);
        DataTableFn.ext = { search: [] };

        const jq = jest.fn(() => ({
            length: 1,
            get: () => document.querySelector("#seasons-table"),
            DataTable: DataTableFn,
        }));
        jq.fn = {
            DataTable: DataTableFn,
            dataTable: Object.assign(DataTableFn, {
                isDataTable: DataTableFn.isDataTable,
                ext: DataTableFn.ext,
            }),
        };
        window.$ = jq;

        globalThis.__SEASONS_INIT_LOADER_MOCK__ = jest.fn();
        globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
            Promise.resolve();
        await import("../../legacy/seasons-init-loader.mjs");

        // Simulate turbo:before-cache (what Turbo fires before creating page snapshot)
        document.dispatchEvent(new Event("turbo:before-cache"));

        // Table must still be in DOM so the Turbo snapshot includes it for back navigation
        expect(document.querySelector("#seasons-table")).not.toBeNull();
    });
});
