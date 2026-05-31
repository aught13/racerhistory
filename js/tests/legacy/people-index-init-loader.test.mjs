import { jest } from "@jest/globals";

describe("people-index-init-loader", () => {
    const flushPromises = async (times = 1) => {
        for (let i = 0; i < times; i += 1) {
            await Promise.resolve();
        }
    };

    const setupLoader = async () => {
        await jest.resetModules();
        const initPeopleMock = jest.fn();

        window.$ = function () {
            return {};
        };
        window.$.fn = {
            DataTable: function () {},
            dataTable: function () {},
        };

        globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__ = initPeopleMock;
        window.__PEOPLE_INDEX_INIT_LOADER_MOCK__ = initPeopleMock;

        await import("../../legacy/people-index-init-loader.mjs");

        return { initPeopleMock };
    };

    beforeEach(() => {
        document.body.innerHTML = "";
    });

    afterEach(() => {
        delete globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__;
        delete window.__PEOPLE_INDEX_INIT_LOADER_MOCK__;
        delete window.$;
    });

    test("boots on import and calls init", async () => {
        const { initPeopleMock } = await setupLoader();
        await flushPromises(2);

        expect(initPeopleMock).toHaveBeenCalled();
    });
});

describe("people-index-init-loader cleanupPeoplePage – back-button fix", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
        delete globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__;
        delete window.__PEOPLE_INDEX_INIT_LOADER_MOCK__;
        delete window.$;
    });

    afterEach(() => {
        jest.restoreAllMocks();
        delete globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__;
        delete window.__PEOPLE_INDEX_INIT_LOADER_MOCK__;
        delete window.$;
    });

    test("cleanupPeoplePage calls destroy(false) so table stays in DOM for Turbo cache", async () => {
        document.body.innerHTML = `<table id="people-table"></table>`;
        const table = document.querySelector("#people-table");

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

        globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__ = jest.fn();
        const mod = await import("../../legacy/people-index-init-loader.mjs");

        mod.cleanupPeoplePage();

        // destroy should be called with false (keep table in DOM for Turbo cache snapshot)
        expect(destroyFn).toHaveBeenCalledWith(false);
        // The table element should still be in the DOM after cleanup
        expect(document.querySelector("#people-table")).not.toBeNull();
    });

    test("cleanupPeoplePage via turbo:before-cache leaves table in DOM", async () => {
        document.body.innerHTML = `<table id="people-table"></table>`;
        const table = document.querySelector("#people-table");

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

        globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__ = jest.fn();
        await import("../../legacy/people-index-init-loader.mjs");

        // Simulate turbo:before-cache (what Turbo fires before creating snapshot)
        document.dispatchEvent(new Event("turbo:before-cache"));

        // Table must still be in DOM so the Turbo snapshot includes it
        expect(document.querySelector("#people-table")).not.toBeNull();
    });
});
