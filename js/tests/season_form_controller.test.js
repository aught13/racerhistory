/* global afterEach, beforeEach, describe, expect, test */

import { Application } from "@hotwired/stimulus";

import SeasonFormController from "../controllers/season_form_controller.js";

describe("season-form controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = `
            <div data-controller="season-form">
                <input id="start-year" data-season-form-target="startYear" value="" />
                <input id="end-year" data-season-form-target="endYear" value="" />
            </div>
        `;

        application = Application.start();
        application.register("season-form", SeasonFormController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }
        document.body.innerHTML = "";
    });

    test("fills end year when start year is blurred", () => {
        const start = document.getElementById("start-year");
        const end = document.getElementById("end-year");

        start.value = "2024";
        start.dispatchEvent(new Event("blur", { bubbles: true }));

        expect(end.value).toBe("2025");
    });

    test("does not override an existing end year", () => {
        const start = document.getElementById("start-year");
        const end = document.getElementById("end-year");

        end.value = "2030";
        start.value = "2024";
        start.dispatchEvent(new Event("blur", { bubbles: true }));

        expect(end.value).toBe("2030");
    });
});
