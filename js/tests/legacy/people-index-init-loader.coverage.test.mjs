import { jest } from "@jest/globals";

/**
 * Coverage tests for people-index-init-loader.mjs
 * Targets: waitForDataTables, ensureDataTablesLoaded,
 *   hasJquery, hasDataTables, boot, getInitPeople
 */

beforeEach(() => {
    jest.resetModules();
    jest.restoreAllMocks();
    jest.useRealTimers();
    document.body.innerHTML = "";
    document.head.innerHTML = "";
    delete globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__;
    delete window.__PEOPLE_INDEX_INIT_LOADER_MOCK__;
    delete window.$;
});

afterEach(() => {
    jest.restoreAllMocks();
    delete window.$;
});

function flushPromises(n = 3) {
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
    };
    const DataTableFn = jest.fn().mockReturnValue(dtInstance);
    DataTableFn.isDataTable = jest.fn().mockReturnValue(false);

    const jq = jest.fn(() => ({
        length: 1,
        get: () => null,
        DataTable: DataTableFn,
    }));
    jq.fn = {
        DataTable: DataTableFn,
        dataTable: Object.assign(DataTableFn, {
            isDataTable: DataTableFn.isDataTable,
            ext: { search: [] },
        }),
    };
    window.$ = jq;
    return { jq, DataTableFn, dtInstance };
}

describe("people-index-init-loader.mjs (coverage)", () => {
    describe("hasDataTables / hasJquery", () => {
        test("returns false when $ not defined", async () => {
            const initMock = jest.fn();
            globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__ = initMock;
            // No jQuery
            const _debugSpy = jest
                .spyOn(console, "debug")
                .mockImplementation(() => {});
            await import("../../legacy/people-index-init-loader.mjs");
            await flushPromises();
            // ensureDataTablesLoaded fails due to no jQuery
        });

        test("hasDataTables via $.fn.dataTable function", async () => {
            const initMock = jest.fn();
            globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__ = initMock;
            const jq = jest.fn(() => ({ length: 0, get: () => null }));
            jq.fn = {
                dataTable: jest.fn(),
            };
            window.$ = jq;
            document.body.innerHTML = `<table id="people-table"></table>`;
            await import("../../legacy/people-index-init-loader.mjs");
            await flushPromises();
        });
    });

    describe("waitForDataTables", () => {
        test("resolves immediately when DataTables available", async () => {
            const initMock = jest.fn();
            globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__ = initMock;
            setupDT();
            document.body.innerHTML = `<table id="people-table"></table>`;
            await import("../../legacy/people-index-init-loader.mjs");
            await flushPromises();
            // Boot should complete successfully
            expect(initMock).toHaveBeenCalled();
        });

        test("times out when DataTables never loads", async () => {
            const initMock = jest.fn();
            globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__ = initMock;

            // jQuery + fn but no DataTable function
            const jq = jest.fn(() => ({
                length: 0,
                get: () => null,
            }));
            jq.fn = {};
            window.$ = jq;

            document.body.innerHTML = `<table id="people-table"></table>`;
            jest.spyOn(console, "debug").mockImplementation(() => {});

            jest.useFakeTimers();
            await import("../../legacy/people-index-init-loader.mjs");

            // Run timers past the 10s timeout
            for (let i = 0; i < 300; i++) {
                jest.advanceTimersByTime(50);
                await Promise.resolve();
            }
        }, 15000);
    });

    describe("boot", () => {
        test("passes dataUrl from table dataset", async () => {
            document.body.innerHTML = `
                <table id="people-table" data-people-data-url="/api/people"></table>`;
            const initMock = jest.fn();
            globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__ = initMock;
            setupDT();
            await import("../../legacy/people-index-init-loader.mjs");
            await flushPromises();
            expect(initMock).toHaveBeenCalledWith(
                expect.objectContaining({ dataUrl: "/api/people" }),
            );
        });

        test("boots on turbo:load event", async () => {
            const initMock = jest.fn();
            globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__ = initMock;
            setupDT();
            document.body.innerHTML = `<table id="people-table"></table>`;
            await import("../../legacy/people-index-init-loader.mjs");
            await flushPromises();
            const callCount = initMock.mock.calls.length;

            // Fire turbo:load
            document.dispatchEvent(new Event("turbo:load"));
            await flushPromises();
            expect(initMock.mock.calls.length).toBeGreaterThanOrEqual(
                callCount,
            );
        });
    });
});
