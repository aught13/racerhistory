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

describe("seasons-init-loader.mjs additional branch coverage", () => {
    describe("getInitSeasons mock resolution paths", () => {
        test("uses window.__SEASONS_INIT_LOADER_MOCK__ when set", async () => {
            const initFn = jest.fn();
            window.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            document.body.innerHTML = `
                <table id="seasons-table"><thead><tr><th>A</th></tr></thead></table>
                <div id="seasons-controls"></div>
                <div id="searchbuilder-panel"></div>`;
            setupDT();
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            await bootLoader();
            await flushPromises();
            expect(initFn).toHaveBeenCalled();
        });

        test("boot calls initSeasons mock as a function", async () => {
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            document.body.innerHTML = `
                <table id="seasons-table"><thead><tr><th>A</th></tr></thead></table>
                <div id="seasons-controls"></div>
                <div id="searchbuilder-panel"></div>`;
            setupDT();
            await bootLoader();
            await flushPromises();
            expect(initFn).toHaveBeenCalled();
        });
    });

    describe("getSearchBuilderLoader mock resolution paths", () => {
        test("uses window.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ when set", async () => {
            const sbLoader = jest.fn().mockResolvedValue(undefined);
            window.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = sbLoader;
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = jest.fn();
            document.body.innerHTML = `
                <table id="seasons-table"><thead><tr><th>A</th></tr></thead></table>
                <div id="seasons-controls"></div>
                <div id="searchbuilder-panel"></div>`;
            setupDT();
            await bootLoader();
            await flushPromises();
            expect(sbLoader).toHaveBeenCalled();
        });

        test("falls back to runInit when SearchBuilder loader rejects", async () => {
            jest.spyOn(console, "warn").mockImplementation(() => {});
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.reject(new Error("sb load failed"));
            document.body.innerHTML = `
                <table id="seasons-table"><thead><tr><th>A</th></tr></thead></table>
                <div id="seasons-controls"></div>
                <div id="searchbuilder-panel"></div>`;
            setupDT();
            await bootLoader();
            await flushPromises();
            expect(console.warn).toHaveBeenCalledWith(
                "SearchBuilder failed to load",
                expect.any(Error),
            );
            expect(initFn).toHaveBeenCalled();
        });
    });

    describe("inferActiveTable edge cases", () => {
        test("returns splits table when #season-splits-table present", async () => {
            document.body.innerHTML = `
                <table id="season-splits-table"><thead><tr><th>A</th></tr></thead></table>`;
            const { inferActiveTable } =
                await import("../../legacy/seasons-init-loader.mjs");
            const result = inferActiveTable();
            expect(result.view).toBe("splits");
            expect(result.table).not.toBeNull();
        });

        test("returns standard table when #seasons-table present", async () => {
            document.body.innerHTML = `
                <table id="seasons-table"><thead><tr><th>A</th></tr></thead></table>`;
            const { inferActiveTable } =
                await import("../../legacy/seasons-init-loader.mjs");
            const result = inferActiveTable();
            expect(result.view).toBe("standard");
            expect(result.table).not.toBeNull();
        });

        test("returns frame dataset view when neither table present and frame has data attribute", async () => {
            document.body.innerHTML = `
                <div id="seasons-table-frame" data-seasons-view="splits"></div>`;
            const { inferActiveTable } =
                await import("../../legacy/seasons-init-loader.mjs");
            const result = inferActiveTable();
            expect(result.view).toBe("splits");
            expect(result.table).toBeNull();
        });

        test("defaults to standard view when no frame data attribute", async () => {
            document.body.innerHTML = `
                <div id="seasons-table-frame"></div>`;
            const { inferActiveTable } =
                await import("../../legacy/seasons-init-loader.mjs");
            const result = inferActiveTable();
            expect(result.view).toBe("standard");
            expect(result.table).toBeNull();
        });

        test("returns null table with standard view when no elements found", async () => {
            document.body.innerHTML = "";
            const { inferActiveTable } =
                await import("../../legacy/seasons-init-loader.mjs");
            const result = inferActiveTable();
            expect(result.view).toBe("standard");
            expect(result.table).toBeNull();
        });
    });

    describe("countTableColumns edge cases", () => {
        test("returns 0 when tableEl is null", async () => {
            const { countTableColumns } =
                await import("../../legacy/seasons-init-loader.mjs");
            expect(countTableColumns(null)).toBe(0);
        });

        test("returns 0 when no header row", async () => {
            const table = document.createElement("table");
            const { countTableColumns } =
                await import("../../legacy/seasons-init-loader.mjs");
            expect(countTableColumns(table)).toBe(0);
        });

        test("sums colspan values", async () => {
            document.body.innerHTML = `
                <table id="test-table">
                    <thead>
                        <tr>
                            <th colspan="3">A</th>
                            <th>B</th>
                            <th colspan="2">C</th>
                        </tr>
                    </thead>
                </table>`;
            const { countTableColumns } =
                await import("../../legacy/seasons-init-loader.mjs");
            expect(
                countTableColumns(document.getElementById("test-table")),
            ).toBe(6);
        });

        test("treats cells with no colspan as 1", async () => {
            document.body.innerHTML = `
                <table id="test-table2">
                    <thead><tr><th>A</th><th>B</th><th>C</th></tr></thead>
                </table>`;
            const { countTableColumns } =
                await import("../../legacy/seasons-init-loader.mjs");
            expect(
                countTableColumns(document.getElementById("test-table2")),
            ).toBe(3);
        });
    });

    describe("buildSplitsColumnLabels with hasTies flag", () => {
        test("includes tie columns when hasTies is true", async () => {
            const { buildSplitsColumnLabels } =
                await import("../../legacy/seasons-init-loader.mjs");
            const labels = buildSplitsColumnLabels(true);
            // Should include T columns for each group (HT, RT, NT, CHT, CRT, CTT, PT)
            const tieLabels = Object.values(labels).filter((l) =>
                l.endsWith("T"),
            );
            expect(tieLabels.length).toBeGreaterThan(0);
        });

        test("excludes tie columns when hasTies is false", async () => {
            const { buildSplitsColumnLabels } =
                await import("../../legacy/seasons-init-loader.mjs");
            const labels = buildSplitsColumnLabels(false);
            const tieLabels = Object.values(labels).filter(
                (l) => l.endsWith("T") && l !== "CT",
            );
            // Should have no T-suffix columns (except CT which is a group code)
            expect(tieLabels.length).toBe(0);
        });
    });

    describe("getCellDataAttr edge cases", () => {
        test("returns null when meta is null", async () => {
            const { getCellDataAttr } =
                await import("../../legacy/seasons-init-loader.mjs");
            expect(getCellDataAttr(null, "search")).toBeNull();
        });

        test("returns null when cell not in aoData", async () => {
            const { getCellDataAttr } =
                await import("../../legacy/seasons-init-loader.mjs");
            const meta = { settings: { aoData: [] }, row: 0, col: 0 };
            expect(getCellDataAttr(meta, "search")).toBeNull();
        });

        test("returns attribute value when cell has data attribute", async () => {
            const { getCellDataAttr } =
                await import("../../legacy/seasons-init-loader.mjs");
            const td = document.createElement("td");
            td.setAttribute("data-search", "filter-value");
            const meta = {
                settings: { aoData: [{ anCells: [td] }] },
                row: 0,
                col: 0,
            };
            expect(getCellDataAttr(meta, "search")).toBe("filter-value");
        });

        test("returns null when attribute not present", async () => {
            const { getCellDataAttr } =
                await import("../../legacy/seasons-init-loader.mjs");
            const td = document.createElement("td");
            const meta = {
                settings: { aoData: [{ anCells: [td] }] },
                row: 0,
                col: 0,
            };
            expect(getCellDataAttr(meta, "search")).toBeNull();
        });
    });

    describe("buildOptions splits render callback", () => {
        test("standard buildOptions render returns filter value from data-search attr", async () => {
            document.body.innerHTML = `
                <table id="seasons-table">
                    <thead><tr><th>A</th><th>B</th><th>C</th><th>D</th><th>E</th><th>F</th><th>G</th>
                    <th>H</th><th>I</th><th>J</th><th>K</th><th>L</th><th>M</th><th>N</th><th>O</th><th>P</th><th>Type</th></tr></thead>
                </table>`;
            const { buildOptions } =
                await import("../../legacy/seasons-init-loader.mjs");
            const opts = buildOptions();
            const renderFn = opts.dataTableOptions.columnDefs?.[0]?.render;
            expect(typeof renderFn).toBe("function");

            const td = document.createElement("td");
            td.setAttribute("data-search", "Basketball");
            const meta = {
                settings: {
                    aoData: [
                        {
                            anCells: Array(17)
                                .fill(null)
                                .map(() => document.createElement("td")),
                        },
                    ],
                },
                row: 0,
                col: 16,
            };
            meta.settings.aoData[0].anCells[16] = td;

            // filter type → returns data-search value
            expect(renderFn("rawData", "filter", {}, meta)).toBe("Basketball");
            // search type → same
            expect(renderFn("rawData", "search", {}, meta)).toBe("Basketball");
            // display type → returns raw data
            expect(renderFn("rawData", "display", {}, meta)).toBe("rawData");
        });

        test("standard buildOptions render falls back to data when cell has no data-search", async () => {
            document.body.innerHTML = `
                <table id="seasons-table">
                    <thead><tr>
                        ${Array(17).fill("<th>X</th>").join("")}
                    </tr></thead>
                </table>`;
            const { buildOptions } =
                await import("../../legacy/seasons-init-loader.mjs");
            const opts = buildOptions();
            const renderFn = opts.dataTableOptions.columnDefs?.[0]?.render;
            const td = document.createElement("td");
            // no data attribute
            const meta = {
                settings: {
                    aoData: [
                        {
                            anCells: Array(17)
                                .fill(null)
                                .map(() => document.createElement("td")),
                        },
                    ],
                },
                row: 0,
                col: 16,
            };
            meta.settings.aoData[0].anCells[16] = td;

            expect(renderFn("rawData", "filter", {}, meta)).toBe("rawData");
        });
    });

    describe("enhancedBoot skips when no table on non-frame events", () => {
        test("enhancedBoot returns early when no table in DOM on DOMContentLoaded", async () => {
            document.body.innerHTML = ""; // no #seasons-table
            setupDT();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = jest.fn();
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            const mod = await import("../../legacy/seasons-init-loader.mjs");
            // Should not throw and mock should not be called
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            await mod.enhancedBoot({ type: "DOMContentLoaded" });
            await flushPromises();
            expect(initFn).not.toHaveBeenCalled();
        });

        test("enhancedBoot processes turbo:frame-load for correct frame id", async () => {
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ = () =>
                Promise.resolve();
            document.body.innerHTML = `
                <div id="seasons-table-frame" data-seasons-view="standard">
                    <table id="seasons-table"><thead><tr><th>A</th></tr></thead></table>
                </div>
                <div id="seasons-controls"></div>
                <div id="searchbuilder-panel"></div>`;
            setupDT();
            const mod = await import("../../legacy/seasons-init-loader.mjs");
            const frame = document.getElementById("seasons-table-frame");
            const evt = { type: "turbo:frame-load", target: frame };
            await mod.enhancedBoot(evt);
            await flushPromises();
            expect(initFn).toHaveBeenCalled();
        });

        test("enhancedBoot skips turbo:frame-load with wrong frame id", async () => {
            const initFn = jest.fn();
            globalThis.__SEASONS_INIT_LOADER_MOCK__ = initFn;
            document.body.innerHTML = `
                <div id="other-frame"></div>`;
            const mod = await import("../../legacy/seasons-init-loader.mjs");
            const frame = document.getElementById("other-frame");
            const evt = { type: "turbo:frame-load", target: frame };
            await mod.enhancedBoot(evt);
            await flushPromises();
            expect(initFn).not.toHaveBeenCalled();
        });
    });

    describe("isDataTablesAvailable paths", () => {
        test("returns false when window.$ is undefined", async () => {
            delete window.$;
            const { isDataTablesAvailable } =
                await import("../../legacy/seasons-init-loader.mjs");
            expect(isDataTablesAvailable()).toBe(false);
        });

        test("returns true when $.fn.DataTable is a function", async () => {
            const jq = jest.fn();
            jq.fn = { DataTable: jest.fn(), dataTable: {} };
            window.$ = jq;
            const { isDataTablesAvailable } =
                await import("../../legacy/seasons-init-loader.mjs");
            expect(isDataTablesAvailable()).toBe(true);
        });

        test("returns true when $.fn.dataTable is an object", async () => {
            const jq = jest.fn();
            jq.fn = { dataTable: { isDataTable: jest.fn() } };
            window.$ = jq;
            const { isDataTablesAvailable } =
                await import("../../legacy/seasons-init-loader.mjs");
            expect(isDataTablesAvailable()).toBe(true);
        });
    });

    describe("cleanupSeasonsPage edge cases", () => {
        test("skips table when jQuery not available", async () => {
            delete window.$;
            document.body.innerHTML = `<table id="seasons-table"></table>`;
            const { cleanupSeasonsPage } =
                await import("../../legacy/seasons-init-loader.mjs");
            expect(() => cleanupSeasonsPage()).not.toThrow();
        });

        test("skips table that is not a DataTable", async () => {
            document.body.innerHTML = `<table id="seasons-table"></table>`;
            const { DataTableFn } = setupDT();
            DataTableFn.isDataTable = jest.fn().mockReturnValue(false);
            const { cleanupSeasonsPage } =
                await import("../../legacy/seasons-init-loader.mjs");
            expect(() => cleanupSeasonsPage()).not.toThrow();
        });

        test("destroys DataTable and clears panel", async () => {
            document.body.innerHTML = `
                <table id="seasons-table"></table>
                <div id="searchbuilder-panel"><div>content</div></div>`;
            const { DataTableFn } = setupDT();
            DataTableFn.isDataTable = jest.fn().mockReturnValue(true);
            const { cleanupSeasonsPage } =
                await import("../../legacy/seasons-init-loader.mjs");
            cleanupSeasonsPage();
            expect(
                document.querySelector("#searchbuilder-panel").innerHTML,
            ).toBe("");
        });

        test("warns when destroy throws", async () => {
            jest.spyOn(console, "warn").mockImplementation(() => {});
            document.body.innerHTML = `<table id="seasons-table"></table>`;
            const { dtInstance, DataTableFn } = setupDT();
            DataTableFn.isDataTable = jest.fn().mockReturnValue(true);
            dtInstance.destroy = jest.fn().mockImplementation(() => {
                throw new Error("destroy failed");
            });
            const { cleanupSeasonsPage } =
                await import("../../legacy/seasons-init-loader.mjs");
            cleanupSeasonsPage();
            expect(console.warn).toHaveBeenCalledWith(
                expect.stringContaining("Failed to clean up seasons DataTable"),
                expect.any(Error),
            );
        });
    });
});
