/** @jest-environment jsdom */

const { jest, describe, test, expect, beforeEach } = require("@jest/globals");

const resetAndMock = async () => {
    await jest.resetModules();
    // attach a global mock so the jest.mock factory can safely reference it
    globalThis.__BLOG_INIT_MOCK__ = jest.fn();
    jest.mock("../modules/blog-interactions.mjs", () => ({
        __esModule: true,
        default: (...args) => globalThis.__BLOG_INIT_MOCK__(...args),
    }));
    return globalThis.__BLOG_INIT_MOCK__;
};

describe("blog view init loader", () => {
    beforeEach(() => {
        document.body.innerHTML = "";
        // restore readyState if tests mutated it earlier
        try {
            Object.defineProperty(document, "readyState", {
                value: "complete",
                configurable: true,
            });
        } catch {
            // ignore
        }
    });

    test("calls initBlogInteractions on import when not loading", async () => {
        const initMock = await resetAndMock();
        await import("../blog-view-init-loader.js");

        expect(initMock).toHaveBeenCalled();
        const callArg = initMock.mock.calls[0][0];
        expect(callArg).toHaveProperty("root");
        expect(callArg.root).toBe(document);
    });

    test("calls initBlogInteractions with frame root on turbo:frame-load", async () => {
        const initMock = await resetAndMock();
        await import("../blog-view-init-loader.js");

        const frame = document.createElement("turbo-frame");
        document.body.appendChild(frame);

        // clear initial import call
        initMock.mockClear();

        frame.dispatchEvent(new Event("turbo:frame-load", { bubbles: true }));
        // microtask flush
        await Promise.resolve();

        expect(initMock).toHaveBeenCalled();
        const callArg = initMock.mock.calls[0][0];
        expect(callArg.root).toBe(frame);
    });

    test("waits for DOMContentLoaded when document.readyState === 'loading'", async () => {
        // force loading state before import
        Object.defineProperty(document, "readyState", {
            value: "loading",
            configurable: true,
        });

        const initMock = await resetAndMock();

        await import("../blog-view-init-loader.js");

        // should not have been called yet
        expect(initMock).not.toHaveBeenCalled();

        document.dispatchEvent(
            new Event("DOMContentLoaded", { bubbles: true }),
        );
        await Promise.resolve();

        expect(initMock).toHaveBeenCalled();
        const callArg = initMock.mock.calls[0][0];
        expect(callArg.root).toBe(document);
    });
});
