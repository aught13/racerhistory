/* global afterEach, beforeEach, describe, expect, test */

import { Application } from "@hotwired/stimulus";

import AdminLayoutController from "../controllers/admin_layout_controller.js";

const STORAGE_KEY = "rh_admin_sidebar_collapsed";

/**
 * Mount the controller on a <div> inside <body> so connect() fires correctly
 * via Stimulus's MutationObserver in jsdom.
 * The controller toggles `sidebar-collapse` on `document.body`, so assertions
 * always check `document.body.classList`.
 *
 * Mirrors the pattern used in nav_accordion_controller.test.js: call
 * Application.start() in beforeEach so Stimulus's initial scan completes
 * before the first test assertion runs.
 */

// ─── Expanded sidebar (no localStorage value) ───────────────────────────────
describe("admin-layout controller — no persisted state", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML =
            '<div id="wrapper" data-controller="admin-layout">' +
            '<button id="btn" data-action="click->admin-layout#toggle">&#9776;</button>' +
            "</div>";
        application = Application.start();
        application.register("admin-layout", AdminLayoutController);
    });

    afterEach(() => {
        application.stop();
        application = null;
        document.body.innerHTML = "";
        document.body.classList.remove("sidebar-collapse");
        localStorage.removeItem(STORAGE_KEY);
    });

    test("sidebar is expanded when localStorage is empty", () => {
        expect(document.body.classList.contains("sidebar-collapse")).toBe(
            false,
        );
    });

    test("toggle collapses the sidebar and persists '1' to localStorage", () => {
        document.getElementById("btn").click();
        expect(document.body.classList.contains("sidebar-collapse")).toBe(true);
        expect(localStorage.getItem(STORAGE_KEY)).toBe("1");
    });

    test("second toggle re-expands the sidebar and persists '0'", () => {
        const btn = document.getElementById("btn");
        btn.click();
        btn.click();
        expect(document.body.classList.contains("sidebar-collapse")).toBe(
            false,
        );
        expect(localStorage.getItem(STORAGE_KEY)).toBe("0");
    });

    test("toggle calls event.preventDefault()", () => {
        const event = new MouseEvent("click", {
            bubbles: true,
            cancelable: true,
        });
        document.getElementById("btn").dispatchEvent(event);
        expect(event.defaultPrevented).toBe(true);
    });

    test("toggle uses sidebar-open on mobile without mutating desktop collapse state", () => {
        const originalInnerWidth = window.innerWidth;
        Object.defineProperty(window, "innerWidth", {
            configurable: true,
            writable: true,
            value: 768,
        });

        const btn = document.getElementById("btn");
        btn.click();
        expect(document.body.classList.contains("sidebar-open")).toBe(true);
        expect(document.body.classList.contains("sidebar-collapse")).toBe(
            false,
        );
        expect(localStorage.getItem(STORAGE_KEY)).toBeNull();

        btn.click();
        expect(document.body.classList.contains("sidebar-open")).toBe(false);

        Object.defineProperty(window, "innerWidth", {
            configurable: true,
            writable: true,
            value: originalInnerWidth,
        });
    });

    test("turbo:load closes an open mobile sidebar", () => {
        document.body.classList.add("sidebar-open");
        document.dispatchEvent(new Event("turbo:load"));

        expect(document.body.classList.contains("sidebar-open")).toBe(false);
    });
});

// ─── Pre-collapsed sidebar (localStorage = "1") ─────────────────────────────
describe("admin-layout controller — pre-collapsed state", () => {
    let application;

    beforeEach(() => {
        localStorage.setItem(STORAGE_KEY, "1"); // must be set BEFORE startApp
        document.body.innerHTML =
            '<div id="wrapper" data-controller="admin-layout">' +
            '<button id="btn" data-action="click->admin-layout#toggle">&#9776;</button>' +
            "</div>";
        application = Application.start();
        application.register("admin-layout", AdminLayoutController);
    });

    afterEach(() => {
        application.stop();
        application = null;
        document.body.innerHTML = "";
        document.body.classList.remove("sidebar-collapse");
        localStorage.removeItem(STORAGE_KEY);
    });

    test("sidebar-collapse is applied on connect when localStorage is '1'", () => {
        expect(document.body.classList.contains("sidebar-collapse")).toBe(true);
    });

    test("toggle expands the sidebar from a collapsed state", () => {
        expect(document.body.classList.contains("sidebar-collapse")).toBe(true);
        document.getElementById("btn").click();
        expect(document.body.classList.contains("sidebar-collapse")).toBe(
            false,
        );
        expect(localStorage.getItem(STORAGE_KEY)).toBe("0");
    });
});

// ─── Explicit expanded (localStorage = "0") ─────────────────────────────────
describe("admin-layout controller — explicit expanded state", () => {
    let application;

    beforeEach(() => {
        localStorage.setItem(STORAGE_KEY, "0");
        document.body.innerHTML =
            '<div id="wrapper" data-controller="admin-layout"></div>';
        application = Application.start();
        application.register("admin-layout", AdminLayoutController);
    });

    afterEach(() => {
        application.stop();
        application = null;
        document.body.innerHTML = "";
        document.body.classList.remove("sidebar-collapse");
        localStorage.removeItem(STORAGE_KEY);
    });

    test("sidebar is expanded when localStorage flag is '0'", () => {
        expect(document.body.classList.contains("sidebar-collapse")).toBe(
            false,
        );
    });
});
