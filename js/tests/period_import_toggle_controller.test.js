/* global afterEach, beforeEach, describe, expect, test */

import { Application } from "@hotwired/stimulus";

import PeriodImportToggleController from "../controllers/period_import_toggle_controller.js";

describe("period-import-toggle controller", () => {
    let application;

    const renderGroup = ({
        teamChecked = true,
        opponentChecked = true,
        includeItems = true,
    } = {}) => {
        document.body.innerHTML = `
            <div data-controller="period-import-toggle">
                <input
                    id="period-master"
                    type="checkbox"
                    data-period-import-toggle-target="master"
                    data-action="change->period-import-toggle#togglePeriod"
                />
                ${
                    includeItems
                        ? `
                            <input
                                id="team-stat"
                                type="checkbox"
                                ${teamChecked ? "checked" : ""}
                                data-period-import-toggle-target="item"
                                data-action="change->period-import-toggle#syncMaster"
                            />
                            <input
                                id="opponent-stat"
                                type="checkbox"
                                ${opponentChecked ? "checked" : ""}
                                data-period-import-toggle-target="item"
                                data-action="change->period-import-toggle#syncMaster"
                            />
                        `
                        : ""
                }
            </div>
        `;
    };

    const dispatchChange = (element) => {
        element.dispatchEvent(new Event("change", { bubbles: true }));
    };

    beforeEach(() => {
        renderGroup();
        application = Application.start();
        application.register(
            "period-import-toggle",
            PeriodImportToggleController,
        );
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }
        document.body.innerHTML = "";
    });

    test("master reflects when all team and opponent stats are selected", () => {
        const master = document.getElementById("period-master");

        expect(master.checked).toBe(true);
        expect(master.indeterminate).toBe(false);
    });

    test("master checks and unchecks every stat in the period", () => {
        const master = document.getElementById("period-master");
        const teamStat = document.getElementById("team-stat");
        const opponentStat = document.getElementById("opponent-stat");

        master.checked = false;
        dispatchChange(master);
        expect(teamStat.checked).toBe(false);
        expect(opponentStat.checked).toBe(false);

        master.checked = true;
        dispatchChange(master);
        expect(teamStat.checked).toBe(true);
        expect(opponentStat.checked).toBe(true);
    });

    test("individual changes preserve selections and show a mixed master state", () => {
        const master = document.getElementById("period-master");
        const opponentStat = document.getElementById("opponent-stat");

        opponentStat.checked = false;
        dispatchChange(opponentStat);
        expect(master.checked).toBe(false);
        expect(master.indeterminate).toBe(true);

        opponentStat.checked = true;
        dispatchChange(opponentStat);
        expect(master.checked).toBe(true);
        expect(master.indeterminate).toBe(false);
    });
});
