/* global afterEach, beforeEach, describe, expect, test */

import { Application } from "@hotwired/stimulus";

import SportsConfigsFormController from "../controllers/sports_configs_form_controller.js";

describe("sports-configs-form controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = `
            <div
                data-controller="sports-configs-form"
                data-sports-configs-form-period-name-index-value="2"
                data-sports-configs-form-setting-index-value="3"
            >
                <button id="add-period" data-action="click->sports-configs-form#addPeriodName">Add Period</button>
                <div data-sports-configs-form-target="periodNamesContainer"></div>

                <button id="add-setting" data-action="click->sports-configs-form#addSetting">Add Setting</button>
                <div data-sports-configs-form-target="settingsContainer"></div>
            </div>
        `;

        application = Application.start();
        application.register("sports-configs-form", SportsConfigsFormController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
    });

    test("adds and removes period rows", async () => {
        const addButton = document.getElementById("add-period");
        const container = document.querySelector(
            '[data-sports-configs-form-target="periodNamesContainer"]',
        );

        addButton.click();

        let rows = container.querySelectorAll(".period-name-row");
        expect(rows).toHaveLength(1);
        expect(rows[0].innerHTML).toContain(
            "configs[period_name_new_2][periods]",
        );

        const removeButton = rows[0].querySelector(
            '[data-action="click->sports-configs-form#removePeriodName"]',
        );
        await Promise.resolve();
        removeButton.click();

        rows = container.querySelectorAll(".period-name-row");
        expect(rows).toHaveLength(0);
    });

    test("adds and removes setting rows", async () => {
        const addButton = document.getElementById("add-setting");
        const container = document.querySelector(
            '[data-sports-configs-form-target="settingsContainer"]',
        );

        addButton.click();

        let rows = container.querySelectorAll(".setting-row");
        expect(rows).toHaveLength(1);
        expect(rows[0].innerHTML).toContain("configs[new_setting_3][key]");

        const removeButton = rows[0].querySelector(
            '[data-action="click->sports-configs-form#removeSetting"]',
        );
        await Promise.resolve();
        removeButton.click();

        rows = container.querySelectorAll(".setting-row");
        expect(rows).toHaveLength(0);
    });
});
