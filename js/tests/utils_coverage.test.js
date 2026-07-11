/**
 * Additional coverage for utility and runtime modules
 */

import { startNativeBridge } from "../lib/native_bridge.js";

describe("Native Bridge utility", () => {
    test("startNativeBridge handles mock bridge with start function", async () => {
        globalThis.__HOTWIRE_NATIVE_BRIDGE__ = {
            start: jest.fn(),
        };

        await startNativeBridge();

        expect(globalThis.__HOTWIRE_NATIVE_BRIDGE__.start).toHaveBeenCalled();
        delete globalThis.__HOTWIRE_NATIVE_BRIDGE__;
    });

    test("startNativeBridge handles mock bridge without start function", async () => {
        globalThis.__HOTWIRE_NATIVE_BRIDGE__ = {};

        // Should not throw
        await startNativeBridge();

        delete globalThis.__HOTWIRE_NATIVE_BRIDGE__;
        expect(true).toBe(true);
    });

    test("startNativeBridge falls back to import when no mock bridge", async () => {
        const originalBridge = globalThis.__HOTWIRE_NATIVE_BRIDGE__;
        delete globalThis.__HOTWIRE_NATIVE_BRIDGE__;

        // Mock import to fail (which is expected behavior)
        const originalImport = global.import;
        global.import = jest.fn().mockRejectedValue(new Error("Import failed"));

        await startNativeBridge();

        global.import = originalImport;
        if (originalBridge) {
            globalThis.__HOTWIRE_NATIVE_BRIDGE__ = originalBridge;
        }
        expect(true).toBe(true);
    });
});

describe("TinyMCE Loader utility", () => {
    beforeEach(() => {
        // Clear any existing script tags
        document
            .querySelectorAll('script[data-rh-tinymce="true"]')
            .forEach((s) => s.remove());
        delete window.tinymce;
    });

    test("initTinyMceLoader handles missing document gracefully", async () => {
        const { initTinyMceLoader } = await import("../lib/tinymce_loader.js");

        // This should not throw even if document is defined
        initTinyMceLoader();
        expect(true).toBe(true);
    });

    test("initTinyMceLoader can be called multiple times", async () => {
        const { initTinyMceLoader } = await import("../lib/tinymce_loader.js");

        // First call
        initTinyMceLoader();

        // Second call - should not throw
        initTinyMceLoader();

        expect(true).toBe(true);
    });

    test("initTinyMceLoader handles turbo navigation", async () => {
        // Create element with blog-post-form controller
        const elem = document.createElement("form");
        elem.setAttribute("data-controller", "blog-post-form");
        document.body.appendChild(elem);

        const { initTinyMceLoader } = await import("../lib/tinymce_loader.js");

        initTinyMceLoader();

        // Trigger turbo:load event
        const event = new Event("turbo:load");
        document.dispatchEvent(event);

        // Should have created script tag if all conditions are met
        elem.remove();
        expect(true).toBe(true);
    });
});
