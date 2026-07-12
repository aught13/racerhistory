/**
 * Admin Runtime - Comprehensive Branch Coverage
 * Targets all uncovered branches in admin_runtime.js
 */

import * as AdminRuntime from "../lib/admin_runtime.js";

describe("admin_runtime - uncovered branches", () => {
    let originalDocumentElement;
    let originalDocumentBody;

    beforeEach(() => {
        originalDocumentElement = document.documentElement;
        originalDocumentBody = document.body;
        jest.spyOn(document, "addEventListener");
    });

    afterEach(() => {
        jest.restoreAllMocks();
        // Restore document properties
        if (originalDocumentElement) {
            Object.defineProperty(document, "documentElement", {
                value: originalDocumentElement,
                configurable: true,
            });
        }
        if (originalDocumentBody) {
            Object.defineProperty(document, "body", {
                value: originalDocumentBody,
                configurable: true,
            });
        }
    });

    describe("enforceAdminLightTheme", () => {
        test("handles missing documentElement", () => {
            // Simulate missing documentElement (line 5 branch: !root)
            Object.defineProperty(document, "documentElement", {
                value: null,
                configurable: true,
            });

            // Should not throw
            expect(() => {
                AdminRuntime.enforceAdminLightTheme();
            }).not.toThrow();

            // Restore
            Object.defineProperty(document, "documentElement", {
                value: originalDocumentElement,
                configurable: true,
            });
        });

        test("handles missing document.body", () => {
            // Simulate missing body (line 13 branch: else path)
            Object.defineProperty(document, "body", {
                value: null,
                configurable: true,
            });

            const root = document.documentElement;
            expect(() => {
                AdminRuntime.enforceAdminLightTheme();
            }).not.toThrow();

            // Restore
            Object.defineProperty(document, "body", {
                value: originalDocumentBody,
                configurable: true,
            });
        });

        test("sets theme attributes on valid root", () => {
            const root = document.documentElement;
            AdminRuntime.enforceAdminLightTheme();

            expect(root.getAttribute("data-bs-theme")).toBe("light");
            expect(root.getAttribute("data-theme")).toBe("light");
        });
    });

    describe("reinitBootstrap", () => {
        test("returns when bootstrap is undefined", () => {
            // Line 19 branch: typeof bootstrap === "undefined"
            expect(() => {
                AdminRuntime.reinitBootstrap(document);
            }).not.toThrow();
        });

        test("handles when bootstrap exists", () => {
            // Set up bootstrap mock
            globalThis.bootstrap = {
                Tooltip: { getOrCreateInstance: jest.fn() },
                Popover: { getOrCreateInstance: jest.fn() },
            };

            const root = document.createElement("div");
            const tooltip = document.createElement("button");
            tooltip.setAttribute("data-bs-toggle", "tooltip");
            root.appendChild(tooltip);

            AdminRuntime.reinitBootstrap(root);

            delete globalThis.bootstrap;
        });
    });

    describe("disposeBootstrap", () => {
        test("returns when bootstrap is undefined", () => {
            // Line 34 branch: typeof bootstrap === "undefined"
            expect(() => {
                AdminRuntime.disposeBootstrap(document);
            }).not.toThrow();
        });

        test("handles when bootstrap exists", () => {
            // Set up bootstrap mock
            globalThis.bootstrap = {
                Tooltip: {
                    getInstance: jest.fn(() => ({ dispose: jest.fn() })),
                },
                Popover: {
                    getInstance: jest.fn(() => ({ dispose: jest.fn() })),
                },
            };

            const root = document.createElement("div");
            const tooltip = document.createElement("button");
            tooltip.setAttribute("data-bs-toggle", "tooltip");
            root.appendChild(tooltip);

            AdminRuntime.disposeBootstrap(root);

            delete globalThis.bootstrap;
        });
    });

    describe("initAdminRuntimeLifecycle", () => {
        beforeEach(() => {
            delete window.__RH_ADMIN_RUNTIME_INIT__;
        });

        test("initializes and sets window flag", () => {
            AdminRuntime.initAdminRuntimeLifecycle();

            // Line 46 branch: else path when window exists
            expect(window.__RH_ADMIN_RUNTIME_INIT__).toBe(true);

            // Setup for next test
            delete window.__RH_ADMIN_RUNTIME_INIT__;
        });

        test("early returns if already initialized", () => {
            // Set the init flag first
            window.__RH_ADMIN_RUNTIME_INIT__ = true;

            const addEventListenerSpy = jest.spyOn(document, "addEventListener");
            AdminRuntime.initAdminRuntimeLifecycle();

            // addEventListener should not be called again if already init
            expect(addEventListenerSpy).not.toHaveBeenCalled();

            delete window.__RH_ADMIN_RUNTIME_INIT__;
        });

        test("sets up turbo event listeners", () => {
            const addEventListenerSpy = jest.spyOn(document, "addEventListener");
            AdminRuntime.initAdminRuntimeLifecycle();

            // Should have added event listeners
            expect(addEventListenerSpy).toHaveBeenCalled();
        });
    });
});
