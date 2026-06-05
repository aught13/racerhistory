import { jest } from "@jest/globals";

/**
 * Comprehensive branch coverage tests for modules/person-blog-popover.mjs
 */

beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = "";
    jest.spyOn(console, "debug").mockImplementation(() => {});
    jest.spyOn(console, "warn").mockImplementation(() => {});
});

afterEach(() => {
    jest.restoreAllMocks();
});

describe("person-blog-popover.mjs", () => {
    test("returns empty links when none found", async () => {
        const mod =
            await import("../../legacy/modules/person-blog-popover.mjs");
        const result = mod.default();
        expect(result.links).toHaveLength(0);
    });

    test("binds popover links and opens on click", async () => {
        document.body.innerHTML = `
            <div data-person-blog-popovers>
                <a data-person-blog-popover
                   data-person-blog-popover-url="/popover/1"
                   href="/blog/1">Post 1</a>
            </div>
        `;
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            text: jest.fn().mockResolvedValue("<p>Preview content</p>"),
        });

        const mod =
            await import("../../legacy/modules/person-blog-popover.mjs");
        const result = mod.default();
        expect(result.links).toHaveLength(1);

        const link = document.querySelector("[data-person-blog-popover]");
        link.dispatchEvent(
            new MouseEvent("click", { bubbles: true, button: 0 }),
        );

        // Wait for async fetch
        await new Promise((r) => setTimeout(r, 50));

        const popover = document.querySelector(".person-blog-popover");
        expect(popover).toBeTruthy();
        expect(popover.innerHTML).toContain("Preview content");
    });

    test("shows loading state then error on fetch failure", async () => {
        document.body.innerHTML = `
            <div data-person-blog-popovers>
                <a data-person-blog-popover
                   data-person-blog-popover-url="/fail"
                   href="/blog/err">Post</a>
            </div>
        `;
        global.fetch = jest.fn().mockRejectedValue(new Error("Network error"));

        const mod =
            await import("../../legacy/modules/person-blog-popover.mjs");
        mod.default();

        const link = document.querySelector("[data-person-blog-popover]");
        link.dispatchEvent(
            new MouseEvent("click", { bubbles: true, button: 0 }),
        );

        await new Promise((r) => setTimeout(r, 50));

        const popover = document.querySelector(".person-blog-popover");
        expect(popover).toBeTruthy();
        expect(popover.textContent).toContain("Unable to load");
    });

    test("shows error on non-ok response", async () => {
        document.body.innerHTML = `
            <div data-person-blog-popovers>
                <a data-person-blog-popover
                   data-person-blog-popover-url="/404"
                   href="/blog/x">Post</a>
            </div>
        `;
        global.fetch = jest.fn().mockResolvedValue({ ok: false });

        const mod =
            await import("../../legacy/modules/person-blog-popover.mjs");
        mod.default();

        const link = document.querySelector("[data-person-blog-popover]");
        link.dispatchEvent(
            new MouseEvent("click", { bubbles: true, button: 0 }),
        );

        await new Promise((r) => setTimeout(r, 50));

        const popover = document.querySelector(".person-blog-popover");
        expect(popover).toBeTruthy();
        expect(popover.textContent).toContain("Unable to load");
    });

    test("closes popover on Escape key", async () => {
        document.body.innerHTML = `
            <div data-person-blog-popovers>
                <a data-person-blog-popover
                   data-person-blog-popover-url="/esc"
                   href="/blog/e">Post</a>
            </div>
        `;
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            text: jest.fn().mockResolvedValue("<p>Content</p>"),
        });

        const mod =
            await import("../../legacy/modules/person-blog-popover.mjs");
        mod.default();

        const link = document.querySelector("[data-person-blog-popover]");
        link.dispatchEvent(
            new MouseEvent("click", { bubbles: true, button: 0 }),
        );

        await new Promise((r) => setTimeout(r, 50));
        expect(document.querySelector(".person-blog-popover")).toBeTruthy();

        document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape" }));
        expect(document.querySelector(".person-blog-popover")).toBeFalsy();
    });

    test("closes popover on click outside", async () => {
        document.body.innerHTML = `
            <div data-person-blog-popovers>
                <a data-person-blog-popover
                   data-person-blog-popover-url="/out"
                   href="/blog/o">Post</a>
            </div>
            <div id="outside">Outside</div>
        `;
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            text: jest.fn().mockResolvedValue("<p>X</p>"),
        });

        const mod =
            await import("../../legacy/modules/person-blog-popover.mjs");
        mod.default();

        const link = document.querySelector("[data-person-blog-popover]");
        link.dispatchEvent(
            new MouseEvent("click", { bubbles: true, button: 0 }),
        );

        await new Promise((r) => setTimeout(r, 50));

        const outside = document.getElementById("outside");
        outside.dispatchEvent(
            new MouseEvent("click", { bubbles: true, button: 0 }),
        );

        expect(document.querySelector(".person-blog-popover")).toBeFalsy();
    });

    test("does not close when clicking inside popover", async () => {
        document.body.innerHTML = `
            <div data-person-blog-popovers>
                <a data-person-blog-popover
                   data-person-blog-popover-url="/in"
                   href="/blog/i">Post</a>
            </div>
        `;
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            text: jest.fn().mockResolvedValue("<p>Inner</p>"),
        });

        const mod =
            await import("../../legacy/modules/person-blog-popover.mjs");
        mod.default();

        const link = document.querySelector("[data-person-blog-popover]");
        link.dispatchEvent(
            new MouseEvent("click", { bubbles: true, button: 0 }),
        );

        await new Promise((r) => setTimeout(r, 50));

        const popover = document.querySelector(".person-blog-popover");
        popover.dispatchEvent(
            new MouseEvent("click", { bubbles: true, button: 0 }),
        );
        expect(document.querySelector(".person-blog-popover")).toBeTruthy();
    });

    test("ignores modified clicks (ctrl/meta/shift)", async () => {
        document.body.innerHTML = `
            <div data-person-blog-popovers>
                <a data-person-blog-popover
                   data-person-blog-popover-url="/mod"
                   href="/blog/m">Post</a>
            </div>
        `;
        const mod =
            await import("../../legacy/modules/person-blog-popover.mjs");
        mod.default();

        const link = document.querySelector("[data-person-blog-popover]");
        link.dispatchEvent(
            new MouseEvent("click", {
                bubbles: true,
                button: 0,
                ctrlKey: true,
            }),
        );

        expect(document.querySelector(".person-blog-popover")).toBeFalsy();
    });

    test("navigates when no popover URL but has href", async () => {
        document.body.innerHTML = `
            <div data-person-blog-popovers>
                <a data-person-blog-popover href="/blog/direct">Post</a>
            </div>
        `;
        const navigateSpy = jest.fn();
        window.__RH_NAVIGATE__ = navigateSpy;

        const mod =
            await import("../../legacy/modules/person-blog-popover.mjs");
        mod.default();

        const link = document.querySelector("[data-person-blog-popover]");
        link.dispatchEvent(
            new MouseEvent("click", { bubbles: true, button: 0 }),
        );

        expect(navigateSpy).toHaveBeenCalledWith(
            "http://localhost/blog/direct",
        );
        delete window.__RH_NAVIGATE__;
    });

    test("does not re-bind already bound links", async () => {
        document.body.innerHTML = `
            <div data-person-blog-popovers>
                <a data-person-blog-popover
                   data-person-blog-popover-url="/t"
                   href="/b">Post</a>
            </div>
        `;
        const mod =
            await import("../../legacy/modules/person-blog-popover.mjs");
        mod.default();
        mod.default(); // second call
    });

    test("handles empty HTML response", async () => {
        document.body.innerHTML = `
            <div data-person-blog-popovers>
                <a data-person-blog-popover
                   data-person-blog-popover-url="/empty"
                   href="/b">Post</a>
            </div>
        `;
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            text: jest.fn().mockResolvedValue(""),
        });

        const mod =
            await import("../../legacy/modules/person-blog-popover.mjs");
        mod.default();

        const link = document.querySelector("[data-person-blog-popover]");
        link.dispatchEvent(
            new MouseEvent("click", { bubbles: true, button: 0 }),
        );

        await new Promise((r) => setTimeout(r, 50));

        const popover = document.querySelector(".person-blog-popover");
        expect(popover).toBeTruthy();
        expect(popover.textContent).toContain("No preview available");
    });

    test("works with custom root", async () => {
        document.body.innerHTML = `
            <div id="root">
                <div data-person-blog-popovers>
                    <a data-person-blog-popover
                       data-person-blog-popover-url="/r"
                       href="/b">Post</a>
                </div>
            </div>
        `;
        const mod =
            await import("../../legacy/modules/person-blog-popover.mjs");
        const result = mod.default({
            root: document.getElementById("root"),
        });
        expect(result.links).toHaveLength(1);
    });
});
