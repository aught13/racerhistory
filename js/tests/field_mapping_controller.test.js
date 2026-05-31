/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import FieldMappingController from "../controllers/field_mapping_controller.js";

describe("field-mapping controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = `
            <div data-controller="field-mapping">
                <div id="field-mapping-container" data-field-mapping-target="container">
                    <div class="field-mapping-row">
                        <input name="rows[0][source]" value="first" />
                        <select name="rows[0][type]"><option value="a" selected>A</option></select>
                        <button type="button" class="remove-field">Remove</button>
                    </div>
                </div>
                <button id="add-field" type="button" data-field-mapping-target="addButton">Add</button>
            </div>
        `;

        application = Application.start();
        application.register("field-mapping", FieldMappingController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("disables remove buttons until more than one row exists", () => {
        expect(document.querySelector(".remove-field").disabled).toBe(true);
    });

    test("adds a row and enables removal", () => {
        document.getElementById("add-field").click();

        expect(document.querySelectorAll(".field-mapping-row")).toHaveLength(2);
        expect(document.querySelectorAll(".remove-field")[0].disabled).toBe(
            false,
        );
        expect(document.querySelectorAll(".remove-field")[1].disabled).toBe(
            false,
        );
    });

    test("removes a row when the remove button is clicked", () => {
        const row = document.querySelector(".field-mapping-row");
        const removeButton = row.querySelector(".remove-field");

        document.getElementById("add-field").click();
        removeButton.click();

        expect(document.querySelectorAll(".field-mapping-row")).toHaveLength(1);
        expect(document.querySelector(".remove-field").disabled).toBe(true);
    });
});
