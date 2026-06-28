/* searchbuilder-loader.test.mjs
 * Focused tests for webroot/js/modules/searchbuilder-loader.mjs
 */
import { jest } from "@jest/globals";
import {
    ensureSearchBuilderLoaded,
    resetSearchBuilderLoaderForTests,
} from "../../legacy/modules/searchbuilder-loader.mjs";

beforeEach(() => {
    resetSearchBuilderLoaderForTests();
    document.head.innerHTML = "";
    delete window.$;
});

afterEach(() => {
    resetSearchBuilderLoaderForTests();
    delete window.$;
});

test("resolves immediately when constructor exists", async () => {
    window.$ = { fn: { dataTable: { SearchBuilder: function SB() {} } } };
    await expect(ensureSearchBuilderLoaded()).resolves.toBeUndefined();
});

test("resolves when constructor appears after polling", async () => {
    jest.useFakeTimers();
    try {
        window.$ = { fn: { dataTable: {} } };

        const promise = ensureSearchBuilderLoaded();
        window.$.fn.dataTable.SearchBuilder = function SB() {};
        jest.advanceTimersByTime(60);

        await expect(promise).resolves.toBeUndefined();
    } finally {
        jest.useRealTimers();
    }
});

test("rejects when DataTables is missing", async () => {
    window.$ = { fn: {} };

    await expect(ensureSearchBuilderLoaded()).rejects.toThrow(
        "DataTables not available for SearchBuilder",
    );
});

test("reuses the same promise while loading", async () => {
    window.$ = { fn: { dataTable: {} } };

    const promise = ensureSearchBuilderLoaded();
    const secondPromise = ensureSearchBuilderLoaded();
    expect(secondPromise).toBe(promise);

    window.$.fn.dataTable.SearchBuilder = function SB() {};

    await expect(promise).resolves.toBeUndefined();
});

test("rejects when constructor never appears", async () => {
    jest.useFakeTimers();
    try {
        window.$ = { fn: { dataTable: {} } };

        const promise = ensureSearchBuilderLoaded();
        jest.advanceTimersByTime(10050);

        await expect(promise).rejects.toThrow("SearchBuilder not available");
    } finally {
        jest.useRealTimers();
    }
});
