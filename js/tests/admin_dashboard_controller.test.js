/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import AdminDashboardController from "../controllers/admin_dashboard_controller.js";

describe("admin-dashboard controller", () => {
    let application;
    let confirmSpy;

    beforeEach(() => {
        document.body.innerHTML = `
            <form
                id="clear-cache-form"
                data-controller="admin-dashboard"
                data-action="submit->admin-dashboard#confirmAndSubmit"
                data-admin-dashboard-confirm-message-value="Clear all CakePHP cache engines?"
                data-admin-dashboard-loading-label-value="Clearing..."
            >
                <button type="submit" data-admin-dashboard-target="button">Clear</button>
            </form>
        `;

        confirmSpy = jest
            .spyOn(window, "confirm")
            .mockImplementation(() => true);

        application = Application.start();
        application.register("admin-dashboard", AdminDashboardController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("prevents submit when user cancels confirmation", async () => {
        confirmSpy.mockReturnValue(false);
        await Promise.resolve();

        const form = document.getElementById("clear-cache-form");
        const button = form.querySelector("button");
        const submitEvent = new Event("submit", {
            bubbles: true,
            cancelable: true,
        });

        form.dispatchEvent(submitEvent);

        expect(window.confirm).toHaveBeenCalledWith(
            "Clear all CakePHP cache engines?",
        );
        expect(submitEvent.defaultPrevented).toBe(true);
        expect(button.disabled).toBe(false);
    });

    test("disables button and shows loading label when confirmed", async () => {
        confirmSpy.mockReturnValue(true);
        await Promise.resolve();

        const form = document.getElementById("clear-cache-form");
        const button = form.querySelector("button");
        const submitEvent = new Event("submit", {
            bubbles: true,
            cancelable: true,
        });

        form.dispatchEvent(submitEvent);

        expect(submitEvent.defaultPrevented).toBe(false);
        expect(button.disabled).toBe(true);
        expect(button.innerHTML).toContain("spinner-border");
        expect(button.innerHTML).toContain("Clearing...");
    });

    test("handles forms without a button target", async () => {
        document.body.innerHTML = `
            <form
                id="clear-cache-form"
                data-controller="admin-dashboard"
                data-action="submit->admin-dashboard#confirmAndSubmit"
            ></form>
        `;

        application.stop();
        confirmSpy = jest
            .spyOn(window, "confirm")
            .mockImplementation(() => true);

        application = Application.start();
        application.register("admin-dashboard", AdminDashboardController);
        await Promise.resolve();

        const form = document.getElementById("clear-cache-form");
        const submitEvent = new Event("submit", {
            bubbles: true,
            cancelable: true,
        });

        form.dispatchEvent(submitEvent);

        expect(window.confirm).toHaveBeenCalled();
        // Should not throw even though button target is missing
        expect(() => form.dispatchEvent(submitEvent)).not.toThrow();
    });
});
