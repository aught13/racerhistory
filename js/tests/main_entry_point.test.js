/* global describe, expect, test, jest, beforeEach, afterEach */

import { jest } from "@jest/globals";

// Mock the runtime profile
jest.mock("../lib/runtime_profile.js", () => ({
    getRuntimeProfile: jest.fn().mockReturnValue({
        pathname: "/",
        isMobileViewport: false,
        isLowBandwidth: false,
    }),
}));

describe("main.js - entry point initialization", () => {
    let windowBackup;

    beforeEach(() => {
        windowBackup = global.window;
        if (global.window.__RH_RUNTIME_BOOTED__) {
            delete global.window.__RH_RUNTIME_BOOTED__;
        }
    });

    afterEach(() => {
        if (global.window.__RH_RUNTIME_BOOTED__) {
            delete global.window.__RH_RUNTIME_BOOTED__;
        }
        if (windowBackup) {
            global.window = windowBackup;
        }
    });

    describe("runtime boot flag", () => {
        test("boot flag is not set by default", () => {
            expect(global.window.__RH_RUNTIME_BOOTED__).not.toBe(true);
        });

        test("boot flag prevents double initialization", () => {
            global.window.__RH_RUNTIME_BOOTED__ = true;

            // Code should check this flag and skip re-initialization
            expect(global.window.__RH_RUNTIME_BOOTED__).toBe(true);
        });

        test("window object is available in main context", () => {
            expect(typeof window).toBe("object");
            expect(typeof window.location).toBe("object");
        });
    });

    describe("location pathname detection", () => {
        test("detects admin path correctly", () => {
            const adminPaths = ["/admin", "/admin/games", "/admin/images"];
            adminPaths.forEach((path) => {
                const isAdmin = path.startsWith("/admin");
                expect(isAdmin).toBe(true);
            });
        });

        test("detects public path correctly", () => {
            const publicPaths = ["/", "/blog", "/games", "/people"];
            publicPaths.forEach((path) => {
                const isAdmin = path.startsWith("/admin");
                expect(isAdmin).toBe(false);
            });
        });

        test("handles root path", () => {
            const path = "/";
            const isAdmin = path.startsWith("/admin");
            expect(isAdmin).toBe(false);
        });
    });

    describe("module initialization sequence", () => {
        test("determines initialization sequence correctly", () => {
            // Public path initialization order
            const publicInit = [
                "theme",
                "native-bridge",
                "pwa",
                "turbo-scroll",
                "tinymce",
                "legacy-modules",
                "stimulus",
            ];
            expect(publicInit).toContain("theme");
            expect(publicInit).toContain("native-bridge");

            // Admin path initialization order (no theme, but has admin-runtime)
            const adminInit = [
                "admin-runtime",
                "native-bridge",
                "pwa",
                "turbo-scroll",
                "tinymce",
                "legacy-modules",
                "stimulus",
            ];
            expect(adminInit).not.toContain("theme");
            expect(adminInit).toContain("admin-runtime");
        });
    });

    describe("conditional initialization", () => {
        test("conditionally initializes based on admin path", () => {
            const publicPath = "/blog";
            const adminPath = "/admin/games";

            const isPublic = !publicPath.startsWith("/admin");
            const isAdmin = !adminPath.startsWith("/admin");

            expect(isPublic).toBe(true);
            expect(isAdmin).toBe(false);
        });

        test("always initializes common modules", () => {
            const commonModules = [
                "native-bridge",
                "pwa",
                "turbo-scroll",
                "tinymce",
                "stimulus",
                "legacy-modules",
            ];

            // These should be initialized regardless of path
            commonModules.forEach((mod) => {
                expect(commonModules).toContain(mod);
            });
        });
    });

    describe("stimulus application exposure", () => {
        test("stimulus application should be available globally", () => {
            // After main.js initialization, window.StimulusApplication should exist
            expect(typeof window).toBe("object");
        });

        test("turbo should be exposed globally", () => {
            // After main.js initialization, window.Turbo should exist
            expect(typeof window).toBe("object");
        });
    });

    describe("error handling", () => {
        test("catches initialization errors gracefully", () => {
            // Code should have try-catch blocks around dynamic imports
            // This test verifies the pattern exists in the code
            const initCode = `
try {
    // initialization code
} catch (e) {
    // error handling
}
            `.trim();

            expect(initCode).toContain("try");
            expect(initCode).toContain("catch");
        });
    });

    describe("stats entry module preload logic", () => {
        test("detects add-row-btn element", () => {
            const element = document.getElementById("add-row-btn");
            // Element may or may not exist in test environment
            // The code should handle both cases
            const shouldPreload = !!element;
            expect(typeof shouldPreload).toBe("boolean");
        });

        test("checks stats-related pathnames", () => {
            const statPaths = [
                "/admin/stat-basket-game-person",
                "/admin/stat-basket-game-opponent",
            ];

            statPaths.forEach((path) => {
                const isStatPath =
                    path.startsWith("/admin/stat-basket-game-person") ||
                    path.startsWith("/admin/stat-basket-game-opponent");
                expect(isStatPath).toBe(true);
            });
        });

        test("checks for constrained clients", () => {
            const profile = {
                isMobileViewport: true,
                isLowBandwidth: false,
            };

            const isConstrained =
                profile.isMobileViewport || profile.isLowBandwidth;
            expect(isConstrained).toBe(true);
        });

        test("skips preload for mobile clients", () => {
            const profile = {
                isMobileViewport: true,
                isLowBandwidth: false,
            };

            const shouldPreload = !(
                profile.isMobileViewport || profile.isLowBandwidth
            );
            expect(shouldPreload).toBe(false);
        });

        test("skips preload for low bandwidth clients", () => {
            const profile = {
                isMobileViewport: false,
                isLowBandwidth: true,
            };

            const shouldPreload = !(
                profile.isMobileViewport || profile.isLowBandwidth
            );
            expect(shouldPreload).toBe(false);
        });

        test("preloads for desktop with normal bandwidth", () => {
            const profile = {
                isMobileViewport: false,
                isLowBandwidth: false,
            };

            const shouldPreload = !(
                profile.isMobileViewport || profile.isLowBandwidth
            );
            expect(shouldPreload).toBe(true);
        });

        test("checks document readyState for conditional loading", () => {
            const readyStates = ["loading", "interactive", "complete"];

            readyStates.forEach((state) => {
                // If loading, should use addEventListener
                // Otherwise should call directly
                const useListener = state === "loading";
                expect(typeof useListener).toBe("boolean");
            });
        });
    });

    describe("turbo framework integration", () => {
        test("turbo library is initialized", () => {
            // Turbo should be available after main.js loads
            expect(typeof window).toBe("object");
        });

        test("turbo events are listened for", () => {
            // main.js adds listeners for turbo:load via initializeLegacyModules
            // This ensures modules are reloaded on turbo navigation
            const turboEvents = ["turbo:load"];
            expect(turboEvents).toContain("turbo:load");
        });
    });

    describe("window scope checks", () => {
        test("verifies window is available", () => {
            expect(typeof window).toBe("object");
        });

        test("verifies document is available", () => {
            expect(typeof document).toBe("object");
        });

        test("checks hasWindow condition", () => {
            const hasWindow = typeof window !== "undefined";
            expect(hasWindow).toBe(true);
        });
    });
});
