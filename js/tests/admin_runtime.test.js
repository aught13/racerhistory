/* global afterEach, beforeEach, describe, expect, global, jest, test */

import {
    disposeBootstrap,
    enforceAdminLightTheme,
    initAdminRuntimeLifecycle,
    reinitBootstrap,
} from "../lib/admin_runtime.js";

describe("admin runtime lifecycle", () => {
    let originalBootstrap;

    beforeEach(() => {
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

        delete window.__RH_ADMIN_RUNTIME_INIT__;
        document.documentElement.setAttribute("data-bs-theme", "dark");
        document.documentElement.setAttribute("data-theme", "dark");
        document.documentElement.classList.add("dark-mode", "theme-dark");
        document.body.className = "dark-mode theme-dark";
    });

    afterEach(() => {
        global.bootstrap = originalBootstrap;
        delete window.__RH_ADMIN_RUNTIME_INIT__;
        jest.restoreAllMocks();
        document.body.innerHTML = "";
    });

    test("enforceAdminLightTheme normalizes html and body theme classes", () => {
        enforceAdminLightTheme();

        expect(document.documentElement.getAttribute("data-bs-theme")).toBe(
            "light",
        );
        expect(document.documentElement.getAttribute("data-theme")).toBe(
            "light",
        );
        expect(document.documentElement.classList.contains("dark-mode")).toBe(
            false,
        );
        expect(document.body.classList.contains("theme-dark")).toBe(false);
    });

    test("reinitBootstrap initializes tooltip and popover instances", () => {
        document.body.innerHTML = `
            <button data-bs-toggle="tooltip"></button>
            <button data-bs-toggle="popover"></button>
        `;

        reinitBootstrap();

        expect(global.bootstrap.Tooltip.getOrCreateInstance).toHaveBeenCalled();
        expect(global.bootstrap.Popover.getOrCreateInstance).toHaveBeenCalled();
    });

    test("disposeBootstrap disposes tooltip and popover instances", () => {
        const tooltipDispose = jest.fn();
        const popoverDispose = jest.fn();

        global.bootstrap.Tooltip.getInstance = jest.fn(() => ({
            dispose: tooltipDispose,
        }));
        global.bootstrap.Popover.getInstance = jest.fn(() => ({
            dispose: popoverDispose,
        }));

        document.body.innerHTML = `
            <button data-bs-toggle="tooltip"></button>
            <button data-bs-toggle="popover"></button>
        `;

        disposeBootstrap();

        expect(tooltipDispose).toHaveBeenCalled();
        expect(popoverDispose).toHaveBeenCalled();
    });

    test("initAdminRuntimeLifecycle registers once and enforces light theme", () => {
        const addEventSpy = jest.spyOn(document, "addEventListener");

        initAdminRuntimeLifecycle();
        expect(window.__RH_ADMIN_RUNTIME_INIT__).toBe(true);

        const countAfterFirstInit = addEventSpy.mock.calls.length;
        initAdminRuntimeLifecycle();
        expect(addEventSpy.mock.calls.length).toBe(countAfterFirstInit);

        expect(document.documentElement.getAttribute("data-bs-theme")).toBe(
            "light",
        );
    });
});
