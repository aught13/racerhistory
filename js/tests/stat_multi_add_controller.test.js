/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import StatMultiAddController from "../controllers/stat_multi_add_controller.js";

describe("stat-multi-add controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = `
            <div data-controller="stat-multi-add">
                <div id="stat-rows" data-stat-multi-add-target="rows">
                    <div class="stat-row" data-row-index="0">
                        <div class="stat-row-label">Player #1</div>
                        <input class="stat-player-select" name="rows[0][player_id]" value="12" />
                        <input type="hidden" name="rows[0][id]" value="55" />
                        <input type="hidden" name="rows[0][GP]" value="1" />
                        <input type="checkbox" name="rows[0][starter]" checked />
                        <input name="rows[0][period]" value="Q1" />
                        <select name="rows[0][stat_type]">
                            <option value="points" selected>Points</option>
                            <option value="assists">Assists</option>
                        </select>
                        <button type="button" class="remove-row-btn">Remove</button>
                    </div>
                </div>
                <button id="add-stat-row" type="button" data-stat-multi-add-target="addButton">Add row</button>
            </div>
        `;

        application = Application.start();
        application.register("stat-multi-add", StatMultiAddController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("disables remove buttons when only one row exists", () => {
        expect(document.querySelector(".remove-row-btn").disabled).toBe(true);
    });

    test("adds a row and resets cloned fields for the new index", () => {
        document.getElementById("add-stat-row").click();

        const rows = document.querySelectorAll(".stat-row");
        const newRow = rows[1];

        expect(rows).toHaveLength(2);
        expect(newRow.dataset.rowIndex).toBe("1");
        expect(newRow.querySelector(".stat-row-label").textContent).toBe(
            "Player #2",
        );
        expect(newRow.querySelector('[name="rows[1][player_id]"]').value).toBe(
            "",
        );
        expect(newRow.querySelector('[name="rows[1][id]"]').value).toBe("");
        expect(newRow.querySelector('[name="rows[1][GP]"]').value).toBe("1");
        expect(newRow.querySelector('[name="rows[1][starter]"]').checked).toBe(
            false,
        );
        expect(newRow.querySelector('[name="rows[1][period]"]').value).toBe(
            "Z",
        );
        expect(newRow.querySelector('[name="rows[1][stat_type]"]').value).toBe(
            "points",
        );
        expect(document.activeElement).toBe(
            newRow.querySelector(".stat-player-select"),
        );
        expect(document.querySelectorAll(".remove-row-btn")[0].disabled).toBe(
            false,
        );
        expect(document.querySelectorAll(".remove-row-btn")[1].disabled).toBe(
            false,
        );
    });

    test("removes rows and reindexes the remaining fields", () => {
        const addButton = document.getElementById("add-stat-row");
        addButton.click();
        addButton.click();

        const middleRemoveButton =
            document.querySelectorAll(".remove-row-btn")[1];
        middleRemoveButton.click();

        const rows = document.querySelectorAll(".stat-row");

        expect(rows).toHaveLength(2);
        expect(rows[0].dataset.rowIndex).toBe("0");
        expect(rows[1].dataset.rowIndex).toBe("1");
        expect(rows[1].querySelector(".stat-row-label").textContent).toBe(
            "Player #2",
        );
        expect(
            rows[1].querySelector('[name="rows[1][player_id]"]'),
        ).not.toBeNull();

        rows[0].querySelector(".remove-row-btn").click();

        expect(document.querySelectorAll(".stat-row")).toHaveLength(1);
        expect(document.querySelector(".stat-row").dataset.rowIndex).toBe("0");
        expect(document.querySelector(".stat-row-label").textContent).toBe(
            "Player #1",
        );
        expect(document.querySelector(".remove-row-btn").disabled).toBe(true);
    });
});
