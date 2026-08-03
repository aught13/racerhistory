/* global afterEach, beforeEach, describe, expect, jest, test */

describe("main runtime bootstrap", () => {
    const flush = async () => {
        await Promise.resolve();
        await Promise.resolve();
    };

    function installMocks() {
        jest.resetModules();

        globalThis.__MAIN_TEST_MOCKS__ = {
            applicationStart: jest.fn(() => ({
                register: jest.fn(),
            })),
            initThemeFromCookie: jest.fn(),
            enforceAdminLightTheme: jest.fn(),
            initAdminRuntimeLifecycle: jest.fn(),
            startNativeBridge: jest.fn(),
            registerServiceWorker: jest.fn(),
            initTurboScrollBehavior: jest.fn(),
            initializeLegacyModules: jest.fn(),
            getRuntimeProfile: jest.fn(() => ({
                isMobileViewport: false,
                isLowBandwidth: false,
            })),
            registerAdminCoreControllers: jest.fn(),
            registerAdminStatsEntryControllers: jest.fn(),
        };

        jest.mock("@hotwired/stimulus", () => {
            const actual = jest.requireActual("@hotwired/stimulus");

            return {
                __esModule: true,
                Controller: actual.Controller,
                Application: {
                    start: globalThis.__MAIN_TEST_MOCKS__.applicationStart,
                },
            };
        });

        jest.mock("../lib/theme.js", () => ({
            __esModule: true,
            initThemeFromCookie:
                globalThis.__MAIN_TEST_MOCKS__.initThemeFromCookie,
        }));

        jest.mock("../lib/admin_runtime.js", () => ({
            __esModule: true,
            enforceAdminLightTheme:
                globalThis.__MAIN_TEST_MOCKS__.enforceAdminLightTheme,
            initAdminRuntimeLifecycle:
                globalThis.__MAIN_TEST_MOCKS__.initAdminRuntimeLifecycle,
        }));

        jest.mock("../lib/native_bridge.js", () => ({
            __esModule: true,
            startNativeBridge: globalThis.__MAIN_TEST_MOCKS__.startNativeBridge,
        }));

        jest.mock("../lib/pwa.js", () => ({
            __esModule: true,
            registerServiceWorker:
                globalThis.__MAIN_TEST_MOCKS__.registerServiceWorker,
        }));

        jest.mock("../lib/turbo_scroll.js", () => ({
            __esModule: true,
            initTurboScrollBehavior:
                globalThis.__MAIN_TEST_MOCKS__.initTurboScrollBehavior,
        }));

        jest.mock("../lib/legacy_loader_registry.js", () => ({
            __esModule: true,
            initializeLegacyModules:
                globalThis.__MAIN_TEST_MOCKS__.initializeLegacyModules,
        }));

        jest.mock("../lib/runtime_profile.js", () => ({
            __esModule: true,
            getRuntimeProfile: globalThis.__MAIN_TEST_MOCKS__.getRuntimeProfile,
        }));

        jest.mock("../route_modules/admin_core.js", () => ({
            __esModule: true,
            registerAdminCoreControllers: (...args) =>
                globalThis.__MAIN_TEST_MOCKS__.registerAdminCoreControllers(
                    ...args,
                ),
        }));

        jest.mock("../route_modules/admin_stats_entry.js", () => ({
            __esModule: true,
            registerAdminStatsEntryControllers: (...args) =>
                globalThis.__MAIN_TEST_MOCKS__.registerAdminStatsEntryControllers(
                    ...args,
                ),
        }));

        return globalThis.__MAIN_TEST_MOCKS__;
    }

    beforeEach(() => {
        document.body.innerHTML = "";
        delete window.__RH_RUNTIME_BOOTED__;
        delete window.__RH_ADMIN_PATH_THEME_WATCHER_INIT__;
        delete window.StimulusApplication;
        delete window.Turbo;
        delete globalThis.__MAIN_TEST_MOCKS__;
        window.history.replaceState({}, "", "/");
    });

    afterEach(() => {
        delete window.__RH_RUNTIME_BOOTED__;
        delete window.__RH_ADMIN_PATH_THEME_WATCHER_INIT__;
        delete window.StimulusApplication;
        delete window.Turbo;
        delete globalThis.__MAIN_TEST_MOCKS__;
        jest.resetModules();
        jest.restoreAllMocks();
        jest.useRealTimers();
        window.history.replaceState({}, "", "/");
    });

    test("boots public runtime once and hands off to route loader", async () => {
        const mocks = installMocks();

        await import("../main.js");
        await flush();

        expect(window.__RH_RUNTIME_BOOTED__).toBe(true);
        expect(window.Turbo).toBeTruthy();
        expect(mocks.initThemeFromCookie).toHaveBeenCalledTimes(1);
        expect(mocks.initAdminRuntimeLifecycle).not.toHaveBeenCalled();
        expect(mocks.startNativeBridge).toHaveBeenCalledTimes(1);
        expect(mocks.registerServiceWorker).toHaveBeenCalledTimes(1);
        expect(mocks.initTurboScrollBehavior).toHaveBeenCalledTimes(1);

        expect(mocks.applicationStart).toHaveBeenCalledTimes(1);
        const stimulus = mocks.applicationStart.mock.results[0].value;
        expect(mocks.initializeLegacyModules).toHaveBeenCalledTimes(1);
        expect(mocks.initializeLegacyModules).toHaveBeenCalledWith(stimulus);
        expect(stimulus.register).not.toHaveBeenCalled();

        await import("../main.js");
        expect(mocks.applicationStart).toHaveBeenCalledTimes(1);
    });

    test("boots admin runtime with theme bootstrap first", async () => {
        window.history.replaceState({}, "", "/admin/dashboard");

        const mocks = installMocks();

        await import("../main.js");

        // On admin paths, enforce light theme first (before cookie preference)
        // to prevent dark mode from bleeding through during page transitions.
        expect(
            mocks.enforceAdminLightTheme.mock.calls.length,
        ).toBeGreaterThanOrEqual(1);
        expect(mocks.initThemeFromCookie).not.toHaveBeenCalled();
        expect(
            mocks.initAdminRuntimeLifecycle.mock.calls.length,
        ).toBeGreaterThanOrEqual(1);
        expect(mocks.registerServiceWorker).toHaveBeenCalledTimes(1);
        expect(mocks.initializeLegacyModules).toHaveBeenCalledTimes(1);
        expect(mocks.initializeLegacyModules).toHaveBeenCalledWith(
            mocks.applicationStart.mock.results[0].value,
        );
    });

    test("does nothing when runtime boot flag is already set", async () => {
        window.__RH_RUNTIME_BOOTED__ = true;

        const mocks = installMocks();

        await import("../main.js");

        expect(mocks.applicationStart).not.toHaveBeenCalled();
        expect(mocks.initThemeFromCookie).not.toHaveBeenCalled();
        expect(mocks.initAdminRuntimeLifecycle).not.toHaveBeenCalled();
        expect(mocks.startNativeBridge).not.toHaveBeenCalled();
        expect(mocks.registerServiceWorker).not.toHaveBeenCalled();
        expect(mocks.initTurboScrollBehavior).not.toHaveBeenCalled();
        expect(mocks.initializeLegacyModules).not.toHaveBeenCalled();
    });

    test("theme watchers react to turbo and page lifecycle events", async () => {
        jest.useFakeTimers();
        window.history.replaceState({}, "", "/admin/dashboard");

        const mocks = installMocks();
        await import("../main.js");
        await flush();

        const beforeEnforce = mocks.enforceAdminLightTheme.mock.calls.length;
        const beforeLifecycle =
            mocks.initAdminRuntimeLifecycle.mock.calls.length;

        document.dispatchEvent(
            new CustomEvent("turbo:before-visit", {
                detail: { url: "/admin/images" },
            }),
        );
        document.dispatchEvent(new Event("turbo:before-visit"));

        const adminBody = document.createElement("body");
        adminBody.classList.add("sidebar-mini");
        document.dispatchEvent(
            new CustomEvent("turbo:before-render", {
                detail: { newBody: adminBody },
            }),
        );

        document.dispatchEvent(new Event("turbo:load"));
        document.dispatchEvent(new Event("turbo:render"));
        window.dispatchEvent(new Event("pageshow"));
        jest.runOnlyPendingTimers();

        expect(mocks.enforceAdminLightTheme.mock.calls.length).toBeGreaterThan(
            beforeEnforce,
        );
        expect(
            mocks.initAdminRuntimeLifecycle.mock.calls.length,
        ).toBeGreaterThan(beforeLifecycle);
    });

    test("logs debug when eager admin core registration throws", async () => {
        window.history.replaceState({}, "", "/admin/dashboard");
        const debugSpy = jest
            .spyOn(console, "debug")
            .mockImplementation(() => {});

        const mocks = installMocks();
        mocks.registerAdminCoreControllers.mockImplementation(() => {
            throw new Error("core registration failed");
        });

        await import("../main.js");
        await flush();

        expect(debugSpy).toHaveBeenCalledWith(
            "main: failed to eagerly register admin_core",
            expect.any(Error),
        );
    });

    test("eager-loads stats entry module for unconstrained clients", async () => {
        window.history.replaceState({}, "", "/admin/stat-basket-game-person");
        const mocks = installMocks();
        mocks.getRuntimeProfile.mockReturnValue({
            isMobileViewport: false,
            isLowBandwidth: false,
        });

        await import("../main.js");
        await flush();

        expect(mocks.registerAdminStatsEntryControllers).toHaveBeenCalledWith(
            window.StimulusApplication,
        );
    });

    test("does not eager-load stats entry for constrained clients", async () => {
        window.history.replaceState({}, "", "/admin/stat-basket-game-opponent");
        const mocks = installMocks();
        mocks.getRuntimeProfile.mockReturnValue({
            isMobileViewport: true,
            isLowBandwidth: false,
        });

        await import("../main.js");
        await flush();

        expect(mocks.registerAdminStatsEntryControllers).not.toHaveBeenCalled();
    });

    test("defers eager stats import until DOMContentLoaded when readyState is loading", async () => {
        const originalReadyState = Object.getOwnPropertyDescriptor(
            Object.getPrototypeOf(document),
            "readyState",
        );

        Object.defineProperty(document, "readyState", {
            configurable: true,
            get: () => "loading",
        });

        window.history.replaceState({}, "", "/admin/stat-basket-game-person");

        const mocks = installMocks();
        await import("../main.js");
        await flush();

        expect(mocks.registerAdminStatsEntryControllers).not.toHaveBeenCalled();

        document.dispatchEvent(new Event("DOMContentLoaded"));
        await flush();

        expect(mocks.registerAdminStatsEntryControllers).toHaveBeenCalledWith(
            window.StimulusApplication,
        );

        if (originalReadyState) {
            Object.defineProperty(document, "readyState", originalReadyState);
        }
    });
});
