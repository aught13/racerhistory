import { jest } from "@jest/globals";
import { registerServiceWorker } from "../../lib/pwa.js";

describe("hotwire/pwa", () => {
    let originalServiceWorker;
    let addListenerSpy;

    beforeEach(() => {
        originalServiceWorker = navigator.serviceWorker;
        addListenerSpy = jest.spyOn(window, "addEventListener");
        window.history.pushState({}, "", "/");
    });

    afterEach(() => {
        if (originalServiceWorker === undefined) {
            delete navigator.serviceWorker;
        } else {
            navigator.serviceWorker = originalServiceWorker;
        }

        addListenerSpy.mockRestore();
        jest.clearAllMocks();
    });

    test("returns early when service worker is unavailable", async () => {
        delete navigator.serviceWorker;

        const result = await registerServiceWorker();

        expect(result).toBeUndefined();
    });

    test("skips registration on admin routes", async () => {
        navigator.serviceWorker = { register: jest.fn() };
        window.history.pushState({}, "", "/admin/dashboard");

        const result = await registerServiceWorker();

        expect(navigator.serviceWorker.register).not.toHaveBeenCalled();
        expect(result).toBeUndefined();
    });

    test("registers service worker and waits for ready", async () => {
        const mockRegistration = { scope: "/" };
        const register = jest.fn().mockResolvedValue(mockRegistration);
        const ready = Promise.resolve();

        navigator.serviceWorker = { register, ready };

        const result = await registerServiceWorker();

        expect(register).toHaveBeenCalledWith("/sw.js", { scope: "/" });
        expect(result).toBe(mockRegistration);
    });

    test("handles registration error gracefully", async () => {
        const error = new Error("Registration failed");
        const register = jest.fn().mockRejectedValue(error);
        const consoleWarnSpy = jest
            .spyOn(console, "warn")
            .mockImplementation(() => {});

        navigator.serviceWorker = { register };

        const result = await registerServiceWorker();

        expect(result).toBeUndefined();
        expect(consoleWarnSpy).toHaveBeenCalledWith(
            "Service worker registration failed:",
            error,
        );

        consoleWarnSpy.mockRestore();
    });

    test("handles ready promise rejection gracefully", async () => {
        const mockRegistration = { scope: "/" };
        const readyError = new Error("Activation failed");
        const register = jest.fn().mockResolvedValue(mockRegistration);

        navigator.serviceWorker = {
            register,
            get ready() {
                return Promise.reject(readyError);
            },
        };

        const consoleWarnSpy = jest
            .spyOn(console, "warn")
            .mockImplementation(() => {});

        const result = await registerServiceWorker();

        expect(result).toBeUndefined();
        expect(consoleWarnSpy).toHaveBeenCalled();

        consoleWarnSpy.mockRestore();
    });
});
