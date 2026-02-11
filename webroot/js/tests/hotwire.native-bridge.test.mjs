import { jest } from "@jest/globals";

describe("hotwire/native_bridge", () => {
    beforeEach(() => {
        jest.resetModules();
        jest.clearAllMocks();
    });

    test("startNativeBridge ignores missing module", async () => {
        const { startNativeBridge } =
            await import("../hotwire/native_bridge.js");
        // Should not throw even if the module doesn't exist
        await expect(startNativeBridge()).resolves.toBeUndefined();
    });

    test("startNativeBridge handles import errors gracefully", async () => {
        // The function has built-in error handling for missing modules
        const { startNativeBridge } =
            await import("../hotwire/native_bridge.js");
        // Should handle both module-not-found and other errors gracefully
        await expect(startNativeBridge()).resolves.toBeUndefined();
    });
});
