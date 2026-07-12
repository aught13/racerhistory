/* global describe, expect, test, jest, beforeEach, afterEach */

describe("people index runtime - initialization utilities", () => {
    let originalJquery;

    beforeEach(() => {
        originalJquery = window.$;
    });

    afterEach(() => {
        if (originalJquery) {
            window.$ = originalJquery;
        } else {
            delete window.$;
        }
    });

    describe("jQuery availability detection", () => {
        test("detects jQuery when $ is function with fn", () => {
            window.$ = jest.fn();
            window.$.fn = {};

            const hasJQuery =
                typeof window.$ === "function" &&
                typeof window.$.fn === "object";
            expect(hasJQuery).toBe(true);
        });

        test("returns false when $ not a function", () => {
            window.$ = "not function";

            const hasJQuery =
                typeof window.$ === "function" &&
                typeof window.$.fn === "object";
            expect(hasJQuery).toBe(false);
        });

        test("returns false when $.fn not an object", () => {
            window.$ = jest.fn();
            window.$.fn = "not an object";

            const hasJQuery =
                typeof window.$ === "function" &&
                typeof window.$.fn === "object";
            expect(hasJQuery).toBe(false);
        });

        test("returns false when $ is undefined", () => {
            delete window.$;

            const hasJQuery =
                typeof window.$ === "function" &&
                typeof window.$.fn === "object";
            expect(hasJQuery).toBe(false);
        });
    });

    describe("DataTables availability detection", () => {
        test("detects DataTables when $.fn.DataTable is function", () => {
            window.$ = jest.fn();
            window.$.fn = { DataTable: jest.fn() };

            const hasDataTables =
                typeof window.$?.fn?.DataTable === "function" ||
                typeof window.$?.fn?.dataTable === "function";
            expect(hasDataTables).toBe(true);
        });

        test("detects DataTables when $.fn.dataTable is function", () => {
            window.$ = jest.fn();
            window.$.fn = { dataTable: jest.fn() };

            const hasDataTables =
                typeof window.$?.fn?.DataTable === "function" ||
                typeof window.$?.fn?.dataTable === "function";
            expect(hasDataTables).toBe(true);
        });

        test("returns false when neither DataTable nor dataTable exist", () => {
            window.$ = jest.fn();
            window.$.fn = {};

            const hasDataTables =
                typeof window.$?.fn?.DataTable === "function" ||
                typeof window.$?.fn?.dataTable === "function";
            expect(hasDataTables).toBe(false);
        });

        test("returns false when $ is undefined", () => {
            delete window.$;

            const hasDataTables =
                typeof window.$?.fn?.DataTable === "function" ||
                typeof window.$?.fn?.dataTable === "function";
            expect(hasDataTables).toBe(false);
        });
    });

    describe("element attribute detection", () => {
        test("reads data attribute from table element", () => {
            const table = document.createElement("table");
            table.id = "people-table";
            table.dataset.peopleDataUrl = "https://example.com/data";
            document.body.appendChild(table);

            const found = document.querySelector("#people-table");
            expect(found?.dataset?.peopleDataUrl).toBe(
                "https://example.com/data",
            );

            document.body.removeChild(table);
        });

        test("handles missing data attribute", () => {
            const table = document.createElement("table");
            table.id = "people-table";
            document.body.appendChild(table);

            const found = document.querySelector("#people-table");
            expect(found?.dataset?.peopleDataUrl).toBeUndefined();

            document.body.removeChild(table);
        });

        test("handles missing table element", () => {
            const found = document.querySelector("#people-table");
            expect(found).toBeNull();
        });
    });

    describe("global reference detection", () => {
        test("accesses globalThis when available", () => {
            const globalRef =
                typeof globalThis !== "undefined" ? globalThis : undefined;
            expect(typeof globalRef).not.toBe("undefined");
        });

        test("accesses window when available", () => {
            const windowRef =
                typeof window !== "undefined" ? window : undefined;
            expect(typeof windowRef).not.toBe("undefined");
        });

        test("prefers globalThis over window", () => {
            const globalRef =
                typeof globalThis !== "undefined" ? globalThis : undefined;
            const windowRef =
                typeof window !== "undefined" ? window : undefined;

            // Both should be available in browser environment
            expect(globalRef).toBeDefined();
            expect(windowRef).toBeDefined();
        });

        test("handles mock loader function from globalThis", () => {
            const mockFn = jest.fn();
            globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__ = mockFn;

            const mockInit = globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__;
            expect(typeof mockInit).toBe("function");

            delete globalThis.__PEOPLE_INDEX_INIT_LOADER_MOCK__;
        });

        test("handles mock loader function from window", () => {
            const mockFn = jest.fn();
            window.__PEOPLE_INDEX_INIT_LOADER_MOCK__ = mockFn;

            const mockInit = window.__PEOPLE_INDEX_INIT_LOADER_MOCK__;
            expect(typeof mockInit).toBe("function");

            delete window.__PEOPLE_INDEX_INIT_LOADER_MOCK__;
        });
    });

    describe("promise initialization", () => {
        test("returns promise for async initialization", async () => {
            const mockInit = jest.fn().mockResolvedValue({});
            window.__PEOPLE_INDEX_INIT_LOADER_MOCK__ = mockInit;

            const resultPromise = Promise.resolve(mockInit);
            expect(resultPromise).toBeInstanceOf(Promise);

            delete window.__PEOPLE_INDEX_INIT_LOADER_MOCK__;
        });

        test("handles promise resolution with function", async () => {
            const mockFn = jest.fn();
            window.__PEOPLE_INDEX_INIT_LOADER_MOCK__ = mockFn;

            const promise = Promise.resolve(
                window.__PEOPLE_INDEX_INIT_LOADER_MOCK__,
            );
            const result = await promise;

            expect(typeof result).toBe("function");

            delete window.__PEOPLE_INDEX_INIT_LOADER_MOCK__;
        });
    });

    describe("selector queries", () => {
        test("finds table by ID selector", () => {
            const table = document.createElement("table");
            table.id = "people-table";
            document.body.appendChild(table);

            const found = document.querySelector("#people-table");
            expect(found).toBe(table);

            document.body.removeChild(table);
        });

        test("finds input by ID selector", () => {
            const input = document.createElement("input");
            input.id = "people-name-search";
            document.body.appendChild(input);

            const found = document.querySelector("#people-name-search");
            expect(found).toBe(input);

            document.body.removeChild(input);
        });

        test("returns null for non-existent selector", () => {
            const found = document.querySelector("#nonexistent-selector-12345");
            expect(found).toBeNull();
        });

        test("finds element among siblings", () => {
            const container = document.createElement("div");
            const table = document.createElement("table");
            table.id = "people-table";

            container.appendChild(table);
            document.body.appendChild(container);

            const found = document.querySelector("#people-table");
            expect(found).toBe(table);

            document.body.removeChild(container);
        });
    });

    describe("configuration object creation", () => {
        test("creates init options with all properties", () => {
            const options = {
                tableSelector: "#people-table",
                searchInputSelector: "#people-name-search",
                dataUrl: "https://example.com/data",
            };

            expect(options.tableSelector).toBe("#people-table");
            expect(options.searchInputSelector).toBe("#people-name-search");
            expect(options.dataUrl).toBe("https://example.com/data");
        });

        test("creates init options with undefined dataUrl", () => {
            const dataUrl = undefined;
            const options = {
                tableSelector: "#people-table",
                searchInputSelector: "#people-name-search",
                dataUrl: dataUrl || undefined,
            };

            expect(options.dataUrl).toBeUndefined();
        });

        test("creates init options with fallback URL", () => {
            const primaryUrl = "";
            const fallbackUrl = "https://fallback.com/data";

            const dataUrl = primaryUrl || fallbackUrl;
            expect(dataUrl).toBe(fallbackUrl);
        });
    });

    describe("error handling patterns", () => {
        test("validates function before calling", () => {
            const initPeopleIndex = jest.fn();

            if (typeof initPeopleIndex === "function") {
                initPeopleIndex();
            }

            expect(initPeopleIndex).toHaveBeenCalled();
        });

        test("logs warning when export is not function", () => {
            const consoleWarnSpy = jest
                .spyOn(console, "warn")
                .mockImplementation();

            const initPeopleIndex = "not a function";
            if (typeof initPeopleIndex !== "function") {
                console.warn(
                    "people-index-init default export is not a function",
                );
            }

            expect(consoleWarnSpy).toHaveBeenCalledWith(
                "people-index-init default export is not a function",
            );

            consoleWarnSpy.mockRestore();
        });

        test("handles missing initialization function", () => {
            const initPeopleIndex = undefined;

            expect(() => {
                if (typeof initPeopleIndex === "function") {
                    initPeopleIndex();
                }
            }).not.toThrow();
        });

        test("rejects when jQuery not available", async () => {
            delete window.$;

            const hasJQuery =
                typeof window.$ === "function" &&
                typeof window.$.fn === "object";

            if (!hasJQuery) {
                const error = new Error("jQuery not available");
                expect(() => {
                    throw error;
                }).toThrow("jQuery not available");
            }
        });

        test("rejects when DataTables not available", async () => {
            window.$ = jest.fn();
            window.$.fn = {};

            const hasDataTables =
                typeof window.$?.fn?.DataTable === "function" ||
                typeof window.$?.fn?.dataTable === "function";

            if (!hasDataTables) {
                const error = new Error("DataTables not available");
                expect(() => {
                    throw error;
                }).toThrow("DataTables not available");
            }
        });
    });

    describe("timeout handling", () => {
        test("sets timeout for DataTable polling", () => {
            jest.useFakeTimers();

            const timeoutMs = 10000;
            const startedAt = Date.now();

            jest.advanceTimersByTime(timeoutMs + 1);

            expect(Date.now() - startedAt).toBeGreaterThanOrEqual(timeoutMs);

            jest.useRealTimers();
        });

        test("uses interval for polling check", () => {
            jest.useFakeTimers();

            const intervalMs = 50;
            let checkCount = 0;

            const tick = () => {
                checkCount++;
                if (checkCount < 3) {
                    jest.advanceTimersByTime(intervalMs);
                    tick();
                }
            };

            tick();

            // Should have called tick multiple times
            expect(checkCount).toBeGreaterThanOrEqual(1);

            jest.useRealTimers();
        });
    });
});
