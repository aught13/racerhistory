/** @jest-environment jsdom */

import { describe, test, expect, beforeEach, jest } from "@jest/globals";

const flush = () => new Promise((resolve) => globalThis.setTimeout(resolve, 0));

async function loadModule() {
    jest.resetModules();
    const mod = await import("../../legacy/modules/person-blog-popover.mjs");
    return mod.default;
}

describe("person-blog-popover", () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <div data-person-blog-popovers>
                <a
                    href="/blog/example"
                    data-person-blog-popover
                    data-person-blog-popover-url="/blog/popover/example"
                    data-person-blog-popover-title="Example"
                >Story</a>
            </div>
        `;
        global.fetch = jest.fn(() =>
            Promise.resolve({
                ok: true,
                text: () => Promise.resolve("<p>Preview</p>"),
            }),
        );
    });

    test("fetches popover HTML on click and renders it", async () => {
        const init = await loadModule();
        init({ root: document });

        const link = document.querySelector("[data-person-blog-popover]");
        const clickEvent = new MouseEvent("click", {
            bubbles: true,
            cancelable: true,
            button: 0,
        });

        link.dispatchEvent(clickEvent);
        expect(clickEvent.defaultPrevented).toBe(true);

        await flush();
        await flush();
        await flush();

        expect(global.fetch).toHaveBeenCalledWith(
            "/blog/popover/example",
            expect.objectContaining({ headers: expect.any(Object) }),
        );

        const popover = document.body.querySelector(".person-blog-popover");
        expect(popover).not.toBeNull();
        expect(popover.innerHTML).toContain("Preview");
    });

    test("allows ctrl/meta clicks to fall through to default behavior", async () => {
        const init = await loadModule();
        init({ root: document });
        const link = document.querySelector("[data-person-blog-popover]");

        const clickEvent = new MouseEvent("click", {
            bubbles: true,
            cancelable: true,
            ctrlKey: true,
        });

        link.dispatchEvent(clickEvent);

        expect(clickEvent.defaultPrevented).toBe(false);
        expect(global.fetch).not.toHaveBeenCalled();
    });
});
