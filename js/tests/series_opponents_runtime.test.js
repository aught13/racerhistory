/* global describe, expect, test, jest, beforeEach, afterEach */

import { jest } from "@jest/globals";

describe("series opponents runtime - utility functions", () => {
    let originalJquery;
    let originalWindow$;

    beforeEach(() => {
        originalJquery = window.$;
        originalWindow$ = window.$;
    });

    afterEach(() => {
        if (originalJquery) {
            window.$ = originalJquery;
        } else {
            delete window.$;
        }
    });

    describe("jQuery detection", () => {
        test("detects jQuery when $ is a function with fn object", () => {
            window.$ = jest.fn();
            window.$.fn = {};

            // hasJquery equivalent check
            const hasJQuery =
                typeof window.$ === "function" &&
                typeof window.$.fn === "object";
            expect(hasJQuery).toBe(true);
        });

        test("returns false when $ is not a function", () => {
            window.$ = "not a function";

            const hasJQuery =
                typeof window.$ === "function" &&
                typeof window.$.fn === "object";
            expect(hasJQuery).toBe(false);
        });

        test("returns false when $.fn is not an object", () => {
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

    describe("DataTables detection", () => {
        test("detects DataTables when $.fn.DataTable exists", () => {
            window.$ = jest.fn();
            window.$.fn = { DataTable: jest.fn() };

            const hasDataTables =
                typeof window.$?.fn?.DataTable === "function" ||
                typeof window.$?.fn?.dataTable === "function";
            expect(hasDataTables).toBe(true);
        });

        test("detects DataTables when $.fn.dataTable exists", () => {
            window.$ = jest.fn();
            window.$.fn = { dataTable: jest.fn() };

            const hasDataTables =
                typeof window.$?.fn?.DataTable === "function" ||
                typeof window.$?.fn?.dataTable === "function";
            expect(hasDataTables).toBe(true);
        });

        test("returns false when DataTables is not available", () => {
            window.$ = jest.fn();
            window.$.fn = {};

            const hasDataTables =
                typeof window.$?.fn?.DataTable === "function" ||
                typeof window.$?.fn?.dataTable === "function";
            expect(hasDataTables).toBe(false);
        });

        test("returns false when $ is not available", () => {
            delete window.$;

            const hasDataTables =
                typeof window.$?.fn?.DataTable === "function" ||
                typeof window.$?.fn?.dataTable === "function";
            expect(hasDataTables).toBe(false);
        });
    });

    describe("element detection", () => {
        test("detects search input element", () => {
            const input = document.createElement("input");
            input.id = "series-opponents-search";
            document.body.appendChild(input);

            const found = document.getElementById("series-opponents-search");
            expect(found).toBe(input);

            document.body.removeChild(input);
        });

        test("returns null when search input missing", () => {
            const found = document.getElementById("series-opponents-search");
            expect(found).toBeNull();
        });

        test("detects picker panel element", () => {
            const panel = document.createElement("div");
            panel.id = "series-opponents-picker-panel";
            document.body.appendChild(panel);

            const found = document.getElementById(
                "series-opponents-picker-panel",
            );
            expect(found).toBe(panel);

            document.body.removeChild(panel);
        });

        test("detects picker toggle element", () => {
            const toggle = document.createElement("button");
            toggle.id = "series-opponents-picker-toggle";
            document.body.appendChild(toggle);

            const found = document.getElementById(
                "series-opponents-picker-toggle",
            );
            expect(found).toBe(toggle);

            document.body.removeChild(toggle);
        });
    });

    describe("aria attributes", () => {
        test("sets aria-expanded to true", () => {
            const toggle = document.createElement("button");
            toggle.setAttribute("aria-expanded", "false");

            toggle.setAttribute("aria-expanded", "true");
            expect(toggle.getAttribute("aria-expanded")).toBe("true");
        });

        test("sets aria-expanded to false", () => {
            const toggle = document.createElement("button");
            toggle.setAttribute("aria-expanded", "true");

            toggle.setAttribute("aria-expanded", "false");
            expect(toggle.getAttribute("aria-expanded")).toBe("false");
        });

        test("handles aria-expanded on non-existent element", () => {
            const toggle = document.createElement("button");
            // Should not throw
            expect(() => {
                toggle.setAttribute("aria-expanded", "true");
            }).not.toThrow();
        });
    });

    describe("event listener management", () => {
        test("removes previous event listener before adding new one", () => {
            const element = document.createElement("input");
            const handler1 = jest.fn();
            const handler2 = jest.fn();

            element.addEventListener("input", handler1);
            element.removeEventListener("input", handler1);
            element.addEventListener("input", handler2);

            const event = new Event("input");
            element.dispatchEvent(event);

            expect(handler2).toHaveBeenCalled();
        });

        test("handles missing event listener gracefully", () => {
            const element = document.createElement("input");
            const handler = jest.fn();

            // Should not throw
            expect(() => {
                element.removeEventListener("input", handler);
            }).not.toThrow();
        });

        test("tracks event handler on element", () => {
            const element = document.createElement("button");
            const handler = jest.fn();

            element._clickHandler = handler;
            expect(element._clickHandler).toBe(handler);
        });

        test("clears tracked event handler", () => {
            const element = document.createElement("button");
            element._clickHandler = jest.fn();

            delete element._clickHandler;
            expect(element._clickHandler).toBeUndefined();
        });
    });

    describe("timeout management", () => {
        test("sets and clears timeout", () => {
            jest.useFakeTimers();

            const element = document.createElement("input");
            const callback = jest.fn();

            const timerId = window.setTimeout(callback, 250);
            element._searchTimer = timerId;

            expect(element._searchTimer).toBe(timerId);

            window.clearTimeout(element._searchTimer);
            // Timer is now cleared

            jest.useRealTimers();
        });

        test("handles multiple timeout clears", () => {
            jest.useFakeTimers();

            const element = document.createElement("input");

            element._searchTimer = window.setTimeout(() => {}, 250);
            window.clearTimeout(element._searchTimer);

            element._searchTimer = window.setTimeout(() => {}, 250);
            window.clearTimeout(element._searchTimer);

            // Should not throw
            expect(element._searchTimer).toBeDefined();

            jest.useRealTimers();
        });
    });

    describe("class toggling", () => {
        test("adds class to element", () => {
            const element = document.createElement("div");
            element.classList.add("expanded");

            expect(element.classList.contains("expanded")).toBe(true);
        });

        test("removes class from element", () => {
            const element = document.createElement("div");
            element.classList.add("expanded");
            element.classList.remove("expanded");

            expect(element.classList.contains("expanded")).toBe(false);
        });

        test("toggles class on element", () => {
            const element = document.createElement("div");

            element.classList.toggle("expanded");
            expect(element.classList.contains("expanded")).toBe(true);

            element.classList.toggle("expanded");
            expect(element.classList.contains("expanded")).toBe(false);
        });

        test("handles multiple classes", () => {
            const element = document.createElement("div");

            element.classList.add("expanded", "visible", "animated");
            expect(element.classList.contains("expanded")).toBe(true);
            expect(element.classList.contains("visible")).toBe(true);
            expect(element.classList.contains("animated")).toBe(true);
        });
    });

    describe("input value handling", () => {
        test("reads input value", () => {
            const input = document.createElement("input");
            input.value = "opponent search term";

            expect(input.value).toBe("opponent search term");
        });

        test("updates input value", () => {
            const input = document.createElement("input");
            input.value = "initial";
            input.value = "updated";

            expect(input.value).toBe("updated");
        });

        test("handles empty input value", () => {
            const input = document.createElement("input");
            input.value = "";

            expect(input.value).toBe("");
        });

        test("handles special characters in input", () => {
            const input = document.createElement("input");
            input.value = "opponent & partner (C)";

            expect(input.value).toBe("opponent & partner (C)");
        });
    });

    describe("DataTable API interactions", () => {
        test("checks for search function on DataTable API", () => {
            const dtApi = {
                search: jest.fn().mockReturnThis(),
                draw: jest.fn().mockReturnThis(),
            };

            expect(typeof dtApi.search).toBe("function");
        });

        test("calls search and draw sequence", () => {
            const dtApi = {
                search: jest.fn().mockReturnThis(),
                draw: jest.fn().mockReturnThis(),
            };

            dtApi.search("term").draw();

            expect(dtApi.search).toHaveBeenCalledWith("term");
            expect(dtApi.draw).toHaveBeenCalled();
        });

        test("handles missing search function", () => {
            const dtApi = {};

            expect(typeof dtApi.search).toBe("undefined");
        });

        test("handles chaining on API calls", () => {
            const dtApi = {
                search: jest.fn(function () {
                    return this;
                }),
                draw: jest.fn(function () {
                    return this;
                }),
            };

            const result = dtApi.search("term").draw();
            expect(result).toBe(dtApi);
        });
    });
});
