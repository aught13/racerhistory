/* global afterEach, beforeEach, describe, expect, jest, test */

describe("main runtime bootstrap", () => {
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

        return globalThis.__MAIN_TEST_MOCKS__;
    }

    beforeEach(() => {
        document.body.innerHTML = "";
        delete window.__RH_RUNTIME_BOOTED__;
        delete globalThis.__MAIN_TEST_MOCKS__;
        window.history.replaceState({}, "", "/");
    });

    afterEach(() => {
        delete window.__RH_RUNTIME_BOOTED__;
        delete globalThis.__MAIN_TEST_MOCKS__;
        jest.resetModules();
        jest.restoreAllMocks();
        window.history.replaceState({}, "", "/");
    });

    test("boots public runtime once and hands off to route loader", async () => {
        const mocks = installMocks();

        await import("../main.js");

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
        expect(mocks.enforceAdminLightTheme).toHaveBeenCalledTimes(1);
        expect(mocks.initThemeFromCookie).not.toHaveBeenCalled();
        expect(mocks.initAdminRuntimeLifecycle).toHaveBeenCalledTimes(1);
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
});
