/**
 * @jest-environment jsdom
 */

import { describe, test, expect, beforeEach, jest } from "@jest/globals";

describe("pwa.js", () => {
    beforeEach(() => {
        jest.resetModules();
        jest.spyOn(console, "warn").mockImplementation(() => {});
    });

    afterEach(() => {
        jest.restoreAllMocks();
    });

    test("registerServiceWorker returns early if serviceWorker not supported", async () => {
        const mod = await import("../../lib/pwa.js");

        // Temporarily remove serviceWorker support
        const original = navigator.serviceWorker;
        Object.defineProperty(navigator, "serviceWorker", {
            value: undefined,
            writable: true,
            configurable: true,
        });

        const result = await mod.registerServiceWorker();
        expect(result).toBeUndefined();

        // Restore
        Object.defineProperty(navigator, "serviceWorker", {
            value: original,
            configurable: true,
        });
    });

    test("registerServiceWorker returns early on admin pages", async () => {
        // Mock location.pathname to be admin
        delete window.location;
        window.location = { pathname: "/admin/dashboard" };

        const mockServiceWorker = {
            register: jest.fn(),
            ready: Promise.resolve(),
        };
        Object.defineProperty(navigator, "serviceWorker", {
            value: mockServiceWorker,
            configurable: true,
        });

        const mod = await import("../../lib/pwa.js");
        const result = await mod.registerServiceWorker();

        expect(result).toBeUndefined();
        expect(mockServiceWorker.register).not.toHaveBeenCalled();
    });

    test("registerServiceWorker registers on public pages", async () => {
        // Mock location.pathname to be public
        delete window.location;
        window.location = { pathname: "/blog" };

        const mockRegistration = { scope: "/" };
        const mockServiceWorker = {
            register: jest.fn().mockResolvedValue(mockRegistration),
            ready: Promise.resolve(),
        };
        Object.defineProperty(navigator, "serviceWorker", {
            value: mockServiceWorker,
            configurable: true,
        });

        const mod = await import("../../lib/pwa.js");
        const result = await mod.registerServiceWorker();

        expect(mockServiceWorker.register).toHaveBeenCalledWith("/sw.js", {
            scope: "/",
        });
        expect(result).toEqual(mockRegistration);
    });

    test("registerServiceWorker handles registration errors", async () => {
        // Mock location.pathname to be public
        delete window.location;
        window.location = { pathname: "/blog" };

        const error = new Error("Registration failed");
        const mockServiceWorker = {
            register: jest.fn().mockRejectedValue(error),
        };
        Object.defineProperty(navigator, "serviceWorker", {
            value: mockServiceWorker,
            configurable: true,
        });

        const mod = await import("../../lib/pwa.js");
        const result = await mod.registerServiceWorker();

        expect(result).toBeUndefined();
        expect(console.warn).toHaveBeenCalledWith(
            "Service worker registration failed:",
            error,
        );
    });
});
