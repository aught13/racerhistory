/* global afterEach, beforeEach, describe, expect, test */

import { Application } from "@hotwired/stimulus";

import SportsFormController from "../controllers/sports_form_controller.js";

describe("sports-form controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = `
            <div data-controller="sports-form">
                <form class="needs-validation" novalidate data-sports-form-target="form">
                    <input id="sport-name" required />
                    <button type="submit">Save</button>
                </form>
            </div>
        `;

        application = Application.start();
        application.register("sports-form", SportsFormController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
    });

    test("prevents submit when form is invalid", () => {
        const form = document.querySelector("form");
        const event = new Event("submit", { bubbles: true, cancelable: true });

        form.dispatchEvent(event);

        expect(event.defaultPrevented).toBe(true);
        expect(form.classList.contains("was-validated")).toBe(true);
    });

    test("allows submit when form is valid", () => {
        const form = document.querySelector("form");
        const input = document.getElementById("sport-name");
        const event = new Event("submit", { bubbles: true, cancelable: true });

        input.value = "Basketball";
        form.dispatchEvent(event);

        expect(event.defaultPrevented).toBe(false);
        expect(form.classList.contains("was-validated")).toBe(true);
    });
});
