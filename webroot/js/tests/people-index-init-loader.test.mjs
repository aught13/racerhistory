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

        await import("../people-index-init-loader.mjs");

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
