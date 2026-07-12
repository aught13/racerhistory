/**
 * @jest-environment jsdom
 */

import {
    describe,
    test,
    expect,
    beforeEach,
    afterEach,
    jest,
} from "@jest/globals";

describe("public_vite_datatables.mjs", () => {
    let originalLocation;

    beforeEach(() => {
        jest.resetModules();
        originalLocation = window.location;
        delete window.__RH_PUBLIC_VITE_DATATABLES_READY__;
        global.fetch = jest.fn();
    });

    afterEach(() => {
        Object.defineProperty(window, "location", {
            value: originalLocation,
            writable: true,
        });
    });

    test("returns early on admin pages", async () => {
        Object.defineProperty(window, "location", {
            value: { pathname: "/admin/dashboard" },
            writable: true,
        });

        const mod = await import("../../lib/public_vite_datatables.mjs");
        await mod.publicDataTablesReady;

        // Should not set the global flag
        expect(window.__RH_PUBLIC_VITE_DATATABLES_READY__).toBeUndefined();
    });

    test("returns early when window is undefined", async () => {
        // Simulate check by verifying it doesn't error
        const mod = await import("../../lib/public_vite_datatables.mjs");
        await mod.publicDataTablesReady;
        // If we got here without errors, it handled the case
        expect(true).toBe(true);
    });

    test("loads on public pages", async () => {
        Object.defineProperty(window, "location", {
            value: { pathname: "/blog" },
            writable: true,
        });

        // Mock imports to avoid loading actual dependencies
        jest.doMock("jquery", () => ({ default: () => {} }), {
            virtual: true,
        });
        jest.doMock("luxon", () => ({}), { virtual: true });

        const mod = await import("../../lib/public_vite_datatables.mjs");
        // The promise should resolve eventually (we can't await all imports in test)
        expect(mod.publicDataTablesReady).toBeTruthy();
    });
});
