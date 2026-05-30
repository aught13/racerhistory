/* global afterEach, beforeEach, describe, expect, test */

import { Application } from "@hotwired/stimulus";

import GameBoxTotalsToggleController from "../controllers/game_box_totals_toggle_controller.js";

describe("game-box-totals-toggle controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = `
            <div data-controller="game-box-totals-toggle">
                <input
                    type="checkbox"
                    id="add-to-totals-check"
                    data-game-box-totals-toggle-target="checkbox"
                    data-action="change->game-box-totals-toggle#toggle"
                />
                <div
                    id="season-totals-options"
                    style="display:none;"
                    data-game-box-totals-toggle-target="optionsPanel"
                ></div>
            </div>
        `;

        application = Application.start();
        application.register(
            "game-box-totals-toggle",
            GameBoxTotalsToggleController,
        );
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
    });

    test("keeps options hidden when checkbox is not checked", () => {
        const optionsPanel = document.getElementById("season-totals-options");

        expect(optionsPanel.style.display).toBe("none");
    });

    test("shows options when checkbox is checked", () => {
        const checkbox = document.getElementById("add-to-totals-check");
        const optionsPanel = document.getElementById("season-totals-options");

        checkbox.checked = true;
        checkbox.dispatchEvent(new Event("change", { bubbles: true }));

        expect(optionsPanel.style.display).toBe("block");
    });
});
