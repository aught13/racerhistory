/* searchbuilder-loader.test.mjs
 * Focused tests for webroot/js/modules/searchbuilder-loader.mjs
 */
import { jest } from "@jest/globals";
import {
    ensureSearchBuilderLoaded,
    resetSearchBuilderLoaderForTests,
    SEARCH_BUILDER_SRC,
} from "../modules/searchbuilder-loader.mjs";

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

test("loads script when constructor is missing", async () => {
    window.$ = { fn: { dataTable: {} } };

    const promise = ensureSearchBuilderLoaded();
    const script = document.head.querySelector(
        `script[src="${SEARCH_BUILDER_SRC}"]`,
    );
    expect(script).toBeTruthy();

    window.$.fn.dataTable.SearchBuilder = function SB() {};
    script.dispatchEvent(new Event("load"));

    await expect(promise).resolves.toBeUndefined();
});

test("waits for DataTables before injecting SearchBuilder", async () => {
    jest.useFakeTimers();
    try {
        window.$ = { fn: {} };

        const promise = ensureSearchBuilderLoaded();
        expect(
            document.head.querySelector(`script[src="${SEARCH_BUILDER_SRC}"]`),
        ).toBeNull();

        window.$.fn.dataTable = {};
        jest.advanceTimersByTime(60);

        const script = document.head.querySelector(
            `script[src="${SEARCH_BUILDER_SRC}"]`,
        );
        expect(script).toBeTruthy();

        window.$.fn.dataTable.SearchBuilder = function SB() {};
        script.dispatchEvent(new Event("load"));

        await expect(promise).resolves.toBeUndefined();
    } finally {
        jest.useRealTimers();
    }
});

test("reuses the same promise while loading", async () => {
    window.$ = { fn: { dataTable: {} } };

    const promise = ensureSearchBuilderLoaded();
    const secondPromise = ensureSearchBuilderLoaded();
    expect(secondPromise).toBe(promise);

    const script = document.head.querySelector(
        `script[src="${SEARCH_BUILDER_SRC}"]`,
    );
    window.$.fn.dataTable.SearchBuilder = function SB() {};
    script.dispatchEvent(new Event("load"));

    await expect(promise).resolves.toBeUndefined();
});

test("rejects when the script fails to load", async () => {
    window.$ = { fn: { dataTable: {} } };

    const promise = ensureSearchBuilderLoaded();
    const script = document.head.querySelector(
        `script[src="${SEARCH_BUILDER_SRC}"]`,
    );
    script.dispatchEvent(new Event("error"));

    await expect(promise).rejects.toThrow(
        "SearchBuilder script failed to load",
    );
});

test("rejects when constructor is still missing after load", async () => {
    window.$ = { fn: { dataTable: {} } };

    const promise = ensureSearchBuilderLoaded();
    const script = document.head.querySelector(
        `script[src="${SEARCH_BUILDER_SRC}"]`,
    );
    script.dispatchEvent(new Event("load"));

    await expect(promise).rejects.toThrow(
        "SearchBuilder script loaded but constructor missing",
    );
});
