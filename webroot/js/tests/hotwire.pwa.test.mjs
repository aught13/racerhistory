/* global navigator */
import { jest } from "@jest/globals";
import { registerServiceWorker } from "../hotwire/pwa.js";

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

    test("registers service worker on load", async () => {
        const register = jest.fn().mockResolvedValue({});
        navigator.serviceWorker = { register };

        addListenerSpy.mockImplementation((event, handler) => {
            if (event === "load") {
                handler();
            }
        });

        registerServiceWorker();

        expect(register).toHaveBeenCalledWith("/sw.js", { scope: "/" });
    });
});
