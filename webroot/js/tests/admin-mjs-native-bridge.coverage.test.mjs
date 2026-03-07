import { jest } from "@jest/globals";

/**
 * Branch coverage tests for admin.mjs and hotwire/native_bridge.js
 */

beforeEach(() => {
    jest.resetModules();
    jest.restoreAllMocks();
    jest.spyOn(console, "debug").mockImplementation(() => {});
    jest.spyOn(console, "warn").mockImplementation(() => {});
    jest.spyOn(console, "error").mockImplementation(() => {});
    jest.spyOn(console, "log").mockImplementation(() => {});
});

afterEach(() => {
    jest.restoreAllMocks();
    delete globalThis.__HOTWIRE_NATIVE_BRIDGE__;
});

describe("admin.js additional branch coverage", () => {
    test("admin.js IIFE exports internals in CJS environment", async () => {
        jest.resetModules();
        document.body.innerHTML = '<div id="confirm-delete-modal"></div>';
        const mod = require("../admin.js");
        expect(mod).toBeDefined();
        expect(mod.AdminToast).toBeDefined();
        expect(mod.showConfirmDelete).toBeDefined();
        expect(mod.__internals).toBeDefined();
    });
});

describe("native_bridge.js startNativeBridge", () => {
    test("uses mock bridge when __HOTWIRE_NATIVE_BRIDGE__ is set", async () => {
        const startFn = jest.fn();
        globalThis.__HOTWIRE_NATIVE_BRIDGE__ = { start: startFn };

        const { startNativeBridge } =
            await import("../hotwire/native_bridge.js");
        await startNativeBridge();

        expect(startFn).toHaveBeenCalled();
    });

    test("mock bridge without start function does not throw", async () => {
        globalThis.__HOTWIRE_NATIVE_BRIDGE__ = { other: true };

        const { startNativeBridge } =
            await import("../hotwire/native_bridge.js");
        await expect(startNativeBridge()).resolves.toBeUndefined();
    });

    test("falls back to dynamic import when no mock bridge", async () => {
        // No mock bridge set - it will try to import @hotwired/hotwire-native-bridge
        // which will fail (not installed) and be caught silently
        const { startNativeBridge } =
            await import("../hotwire/native_bridge.js");
        await expect(startNativeBridge()).resolves.toBeUndefined();
    });

    test("handles null mock bridge", async () => {
        globalThis.__HOTWIRE_NATIVE_BRIDGE__ = null;

        const { startNativeBridge } =
            await import("../hotwire/native_bridge.js");
        // Should try dynamic import path (and catch the error)
        await expect(startNativeBridge()).resolves.toBeUndefined();
    });
});
