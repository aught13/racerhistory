import { jest } from "@jest/globals";
import { registerServiceWorker } from "../../lib/pwa.js";

describe("hotwire/pwa", () => {
    let originalServiceWorker;
    let originalReadyState;
    let addListenerSpy;

    beforeEach(() => {
        originalServiceWorker = navigator.serviceWorker;
        originalReadyState = Object.getOwnPropertyDescriptor(document, "readyState");
        addListenerSpy = jest.spyOn(window, "addEventListener");
        window.history.pushState({}, "", "/");
    });

    afterEach(() => {
        if (originalServiceWorker === undefined) {
            delete navigator.serviceWorker;
        } else {
            navigator.serviceWorker = originalServiceWorker;
        }

        if (originalReadyState) {
            Object.defineProperty(document, "readyState", originalReadyState);
        }

        addListenerSpy.mockRestore();
    });

    test("returns early when service worker is unavailable", () => {
        delete navigator.serviceWorker;

        registerServiceWorker();

        expect(addListenerSpy).not.toHaveBeenCalled();
    });

    test("skips registration on admin routes", () => {
        navigator.serviceWorker = { register: jest.fn() };
        window.history.pushState({}, "", "/admin/dashboard");

        registerServiceWorker();

        expect(addListenerSpy).not.toHaveBeenCalled();
    });

    test("registers service worker on load event when document is still loading", async () => {
        const register = jest.fn().mockResolvedValue({});
        navigator.serviceWorker = { register };

        Object.defineProperty(document, "readyState", {
            value: "loading",
            configurable: true,
        });

        addListenerSpy.mockImplementation((event, handler) => {
            if (event === "load") {
                handler();
            }
        });

        registerServiceWorker();

        expect(register).toHaveBeenCalledWith("/sw.js", { scope: "/" });
    });

    test("registers service worker immediately when document is already loaded", async () => {
        const register = jest.fn().mockResolvedValue({});
        navigator.serviceWorker = { register };

        Object.defineProperty(document, "readyState", {
            value: "complete",
            configurable: true,
        });

        registerServiceWorker();

        // Wait for async registration to complete
        await new Promise((resolve) => setTimeout(resolve, 10));

        expect(register).toHaveBeenCalledWith("/sw.js", { scope: "/" });
    });

    test("registers service worker immediately when document is interactive", async () => {
        const register = jest.fn().mockResolvedValue({});
        navigator.serviceWorker = { register };

        Object.defineProperty(document, "readyState", {
            value: "interactive",
            configurable: true,
        });

        registerServiceWorker();

        // Wait for async registration to complete
        await new Promise((resolve) => setTimeout(resolve, 10));

        expect(register).toHaveBeenCalledWith("/sw.js", { scope: "/" });
    });
});
