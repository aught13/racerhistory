import { jest } from "@jest/globals";

/**
 * Comprehensive branch coverage tests for modules/person-game-log-tabs.mjs
 */

beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = "";
    // Set up CSS.escape mock
    if (!globalThis.CSS) {
        globalThis.CSS = { escape: (str) => str };
    }
});

afterEach(() => {
    jest.restoreAllMocks();
});

describe("person-game-log-tabs.mjs", () => {
    test("returns empty tabs when no tabs found", async () => {
        const mod = await import("../modules/person-game-log-tabs.mjs");
        const result = mod.default();
        expect(result.tabs).toHaveLength(0);
    });

    test("binds tabs and hydrates active tab frame", async () => {
        document.body.innerHTML = `
            <div>
                <button data-person-game-log-tab class="active"
                        data-person-game-log-frame="frame-1">Tab 1</button>
                <button data-person-game-log-tab
                        data-person-game-log-frame="frame-2">Tab 2</button>
                <turbo-frame id="frame-1" data-person-game-log-src="/logs/1"></turbo-frame>
                <turbo-frame id="frame-2" data-person-game-log-src="/logs/2"></turbo-frame>
            </div>
        `;
        const mod = await import("../modules/person-game-log-tabs.mjs");
        const result = mod.default();
        expect(result.tabs).toHaveLength(2);

        // Active tab should hydrate its frame
        const frame1 = document.getElementById("frame-1");
        expect(frame1.getAttribute("src")).toBe("/logs/1");
    });

    test("clicking tab hydrates destination frame", async () => {
        document.body.innerHTML = `
            <div>
                <button data-person-game-log-tab
                        data-person-game-log-frame="frame-a">Tab A</button>
                <turbo-frame id="frame-a" data-person-game-log-src="/a"></turbo-frame>
            </div>
        `;
        const mod = await import("../modules/person-game-log-tabs.mjs");
        mod.default();

        const tab = document.querySelector("[data-person-game-log-tab]");
        tab.dispatchEvent(new MouseEvent("click", { bubbles: true }));

        const frame = document.getElementById("frame-a");
        expect(frame.getAttribute("src")).toBe("/a");
    });

    test("does not re-bind already bound tabs", async () => {
        document.body.innerHTML = `
            <button data-person-game-log-tab
                    data-person-game-log-frame="f1">Tab</button>
            <turbo-frame id="f1" data-person-game-log-src="/x"></turbo-frame>
        `;
        const mod = await import("../modules/person-game-log-tabs.mjs");
        mod.default();
        mod.default(); // should not re-bind
    });

    test("does not hydrate frame if src already set", async () => {
        document.body.innerHTML = `
            <button data-person-game-log-tab class="active"
                    data-person-game-log-frame="f2">Tab</button>
            <turbo-frame id="f2" src="/already"
                         data-person-game-log-src="/new"></turbo-frame>
        `;
        const mod = await import("../modules/person-game-log-tabs.mjs");
        mod.default();
        const frame = document.getElementById("f2");
        expect(frame.getAttribute("src")).toBe("/already");
    });

    test("handles missing frame gracefully", async () => {
        document.body.innerHTML = `
            <button data-person-game-log-tab
                    data-person-game-log-frame="nonexistent">Tab</button>
        `;
        const mod = await import("../modules/person-game-log-tabs.mjs");
        const result = mod.default();
        expect(result.tabs).toHaveLength(1);

        const tab = document.querySelector("[data-person-game-log-tab]");
        expect(() =>
            tab.dispatchEvent(new MouseEvent("click", { bubbles: true })),
        ).not.toThrow();
    });

    test("handles frame without src data attribute", async () => {
        document.body.innerHTML = `
            <button data-person-game-log-tab class="active"
                    data-person-game-log-frame="f3">Tab</button>
            <turbo-frame id="f3"></turbo-frame>
        `;
        const mod = await import("../modules/person-game-log-tabs.mjs");
        mod.default();
        const frame = document.getElementById("f3");
        expect(frame.hasAttribute("src")).toBe(false);
    });

    test("handles tab without frame id", async () => {
        document.body.innerHTML = `
            <button data-person-game-log-tab>Tab No Frame</button>
        `;
        const mod = await import("../modules/person-game-log-tabs.mjs");
        const result = mod.default();
        expect(result.tabs).toHaveLength(1);
    });

    test("works with custom root option", async () => {
        document.body.innerHTML = `
            <div id="container">
                <button data-person-game-log-tab
                        data-person-game-log-frame="cf1">Tab</button>
                <turbo-frame id="cf1" data-person-game-log-src="/c"></turbo-frame>
            </div>
        `;
        const mod = await import("../modules/person-game-log-tabs.mjs");
        const result = mod.default({
            root: document.getElementById("container"),
        });
        expect(result.tabs).toHaveLength(1);
    });

    test("handles CSS.escape being undefined", async () => {
        const savedCSS = globalThis.CSS;
        delete globalThis.CSS;

        document.body.innerHTML = `
            <button data-person-game-log-tab
                    data-person-game-log-frame="simple-id">Tab</button>
            <turbo-frame id="simple-id" data-person-game-log-src="/s"></turbo-frame>
        `;
        const mod = await import("../modules/person-game-log-tabs.mjs");
        mod.default();

        const tab = document.querySelector("[data-person-game-log-tab]");
        tab.dispatchEvent(new MouseEvent("click", { bubbles: true }));

        globalThis.CSS = savedCSS;
    });
});
