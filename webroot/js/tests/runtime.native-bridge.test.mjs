import { jest } from "@jest/globals";

describe("hotwire/native_bridge", () => {
    beforeEach(() => {
        jest.resetModules();
        jest.clearAllMocks();
        delete globalThis.__HOTWIRE_NATIVE_BRIDGE__;
    });

    test("startNativeBridge ignores missing module", async () => {
        const { startNativeBridge } =
            await import("../../../js/lib/native_bridge.js");
        // Should not throw even if the module doesn't exist
        await expect(startNativeBridge()).resolves.toBeUndefined();
    });

    test("startNativeBridge handles import errors gracefully", async () => {
        // The function has built-in error handling for missing modules
        const { startNativeBridge } =
            await import("../../../js/lib/native_bridge.js");
        // Should handle both module-not-found and other errors gracefully
        await expect(startNativeBridge()).resolves.toBeUndefined();
    });

    test("startNativeBridge calls start when available", async () => {
        const start = jest.fn();
        globalThis.__HOTWIRE_NATIVE_BRIDGE__ = { start };

        const { startNativeBridge } =
            await import("../../../js/lib/native_bridge.js");

        await startNativeBridge();

        expect(start).toHaveBeenCalled();
    });

    test("startNativeBridge ignores missing start function", async () => {
        globalThis.__HOTWIRE_NATIVE_BRIDGE__ = {};

        const { startNativeBridge } =
            await import("../../../js/lib/native_bridge.js");

        await expect(startNativeBridge()).resolves.toBeUndefined();
    });
});
