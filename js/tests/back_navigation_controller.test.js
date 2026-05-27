/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import BackNavigationController from "../controllers/back_navigation_controller.js";

describe("back-navigation controller", () => {
    let application;

    function setReferrer(value) {
        Object.defineProperty(document, "referrer", {
            configurable: true,
            get() {
                return value;
            },
        });
    }

    beforeEach(() => {
        document.body.innerHTML = `
            <div
                data-controller="back-navigation"
                data-back-navigation-index-url-value="/admin/persons"
                data-back-navigation-index-path-value="/admin/persons"
                data-back-navigation-view-path-value="/admin/persons/view"
            >
                <button
                    id="back-btn"
                    data-back-navigation-target="backButton"
                    data-action="click->back-navigation#goBack"
                >
                    Back
                </button>
            </div>
        `;

        delete window.__RH_NAVIGATE__;
        setReferrer("");

        application = Application.start();
        application.register("back-navigation", BackNavigationController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete window.__RH_NAVIGATE__;
        setReferrer("");
        document.body.innerHTML = "";
    });

    test("hides back button when referrer is persons index", async () => {
        setReferrer("http://localhost/admin/persons");

        document.body.innerHTML = `
            <div
                data-controller="back-navigation"
                data-back-navigation-index-url-value="/admin/persons"
                data-back-navigation-index-path-value="/admin/persons"
                data-back-navigation-view-path-value="/admin/persons/view"
            >
                <button
                    id="back-btn"
                    data-back-navigation-target="backButton"
                    data-action="click->back-navigation#goBack"
                >
                    Back
                </button>
            </div>
        `;

        application.stop();
        application = Application.start();
        application.register("back-navigation", BackNavigationController);
        await Promise.resolve();

        const backButton = document.getElementById("back-btn");
        expect(backButton.style.display).toBe("none");
    });

    test("redirects to index when referrer is persons index", () => {
        const backButton = document.getElementById("back-btn");
        const navigateSpy = jest.fn();

        setReferrer("http://localhost/admin/persons");
        window.__RH_NAVIGATE__ = navigateSpy;

        backButton.click();

        expect(navigateSpy).toHaveBeenCalledWith("/admin/persons");
    });

    test("falls back to index when no history is available", () => {
        const backButton = document.getElementById("back-btn");
        const navigateSpy = jest.fn();

        setReferrer("");
        window.__RH_NAVIGATE__ = navigateSpy;

        backButton.click();

        expect(navigateSpy).toHaveBeenCalledWith("/admin/persons");
    });
});
