/** @jest-environment jsdom */
import { jest } from "@jest/globals";

/**
 * Tests for admin-turbo.mjs
 *
 * Verifies that the admin Turbo initialisation module:
 * - Loads Turbo and exposes it on window
 * - Registers turbo:load and turbo:frame-load listeners
 * - Registers turbo:before-cache cleanup listener
 * - Reinitialises Bootstrap components after Turbo navigations
 */
describe("admin-turbo.mjs", () => {
    let addEventSpy;
    let originalBootstrap;

    beforeEach(() => {
        // Track addEventListener calls
        addEventSpy = jest.spyOn(document, "addEventListener");

        // Mock bootstrap globally
        originalBootstrap = global.bootstrap;
        global.bootstrap = {
            Tooltip: {
                getOrCreateInstance: jest.fn(),
                getInstance: jest.fn(() => ({ dispose: jest.fn() })),
            },
            Popover: {
                getOrCreateInstance: jest.fn(),
                getInstance: jest.fn(() => ({ dispose: jest.fn() })),
            },
        };

        // Clear any previous window.Turbo
        delete window.Turbo;
    });

    afterEach(() => {
        document.body.innerHTML = "";
        global.bootstrap = originalBootstrap;
        jest.restoreAllMocks();
        jest.resetModules();
    });

    test("exposes Turbo on window after import", async () => {
        await import("../admin-turbo.mjs");
        expect(window.Turbo).toBeDefined();
    });

    test("registers turbo:load listener", async () => {
        await import("../admin-turbo.mjs");
        const turboLoadCalls = addEventSpy.mock.calls.filter(
            ([event]) => event === "turbo:load",
        );
        expect(turboLoadCalls.length).toBeGreaterThanOrEqual(1);
    });

    test("registers turbo:frame-load listener", async () => {
        await import("../admin-turbo.mjs");
        const frameLoadCalls = addEventSpy.mock.calls.filter(
            ([event]) => event === "turbo:frame-load",
        );
        expect(frameLoadCalls.length).toBeGreaterThanOrEqual(1);
    });

    test("registers turbo:before-cache listener", async () => {
        await import("../admin-turbo.mjs");
        const cacheCleanupCalls = addEventSpy.mock.calls.filter(
            ([event]) => event === "turbo:before-cache",
        );
        expect(cacheCleanupCalls.length).toBeGreaterThanOrEqual(1);
    });

    test("reinitBootstrap initialises tooltips and popovers", async () => {
        document.body.innerHTML = `
      <button data-bs-toggle="tooltip" title="Tip">Hover</button>
      <button data-bs-toggle="popover" data-bs-content="Pop">Click</button>
    `;
        const mod = await import("../admin-turbo.mjs");
        mod.reinitBootstrap();
        expect(global.bootstrap.Tooltip.getOrCreateInstance).toHaveBeenCalled();
        expect(global.bootstrap.Popover.getOrCreateInstance).toHaveBeenCalled();
    });

    test("reinitBootstrap is a no-op when bootstrap is undefined", async () => {
        delete global.bootstrap;
        const mod = await import("../admin-turbo.mjs");
        // Should not throw
        expect(() => mod.reinitBootstrap()).not.toThrow();
    });

    test("turbo:before-cache disposes Bootstrap tooltips", async () => {
        const disposeSpy = jest.fn();
        global.bootstrap.Tooltip.getInstance = jest.fn(() => ({
            dispose: disposeSpy,
        }));
        global.bootstrap.Popover.getInstance = jest.fn(() => ({
            dispose: jest.fn(),
        }));

        document.body.innerHTML = `
      <button data-bs-toggle="tooltip" title="Tip">Hover</button>
    `;

        await import("../admin-turbo.mjs");

        // Fire turbo:before-cache
        document.dispatchEvent(new Event("turbo:before-cache"));
        expect(disposeSpy).toHaveBeenCalled();
    });
});
