import { jest } from "@jest/globals";

describe("people-index-init-loader", () => {
    const flushPromises = async (times = 1) => {
        for (let i = 0; i < times; i += 1) {
            await Promise.resolve();
        }
    };

    const setupLoader = async ({ rejectLoader } = {}) => {
        await jest.resetModules();
        const initPeopleMock = jest.fn();
        const ensureSearchBuilderLoaded = rejectLoader
            ? jest.fn(() => Promise.reject(new Error("nope")))
            : jest.fn(() => Promise.resolve());

        window.$ = function () {
            return {};
        };
        window.$.fn = {
            DataTable: function () {},
            dataTable: function () {},
        };

        globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__ = initPeopleMock;
        window.__PEOPLE_INDEX_INIT_LOADER_MOCK__ = initPeopleMock;
        globalThis.__PEOPLE_SEARCHBUILDER_LOADER_MOCK__ =
            ensureSearchBuilderLoaded;
        window.__PEOPLE_SEARCHBUILDER_LOADER_MOCK__ = ensureSearchBuilderLoaded;

        await import("../people-index-init-loader.mjs");

        return { initPeopleMock, ensureSearchBuilderLoaded };
    };

    beforeEach(() => {
        document.body.innerHTML = "";
    });

    afterEach(() => {
        delete globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__;
        delete window.__PEOPLE_INDEX_INIT_LOADER_MOCK__;
        delete globalThis.__PEOPLE_SEARCHBUILDER_LOADER_MOCK__;
        delete window.__PEOPLE_SEARCHBUILDER_LOADER_MOCK__;
        delete window.$;
    });

    test("boots on import and calls init", async () => {
        const { initPeopleMock, ensureSearchBuilderLoaded } =
            await setupLoader();
        await flushPromises(2);

        expect(ensureSearchBuilderLoaded).toHaveBeenCalled();
        expect(initPeopleMock).toHaveBeenCalled();
    });

    test("falls back to init when SearchBuilder loader rejects", async () => {
        const { initPeopleMock } = await setupLoader({ rejectLoader: true });
        await flushPromises(2);

        expect(initPeopleMock).toHaveBeenCalled();
    });
});
