/** @jest-environment jsdom */

import { jest } from "@jest/globals";
import setupDataTablesMock from "./helpers/datatables.mock.mjs";
import initSeasons from "../modules/seasons-init.js";

describe("seasons-init additional branches", () => {
    let teardown;

    beforeEach(() => {
        document.body.innerHTML = `
            <div id="seasons-controls" class="d-flex"></div>
            <div id="searchbuilder-panel" class="searchbuilder-panel"></div>
            <table id="seasons-table">
                <thead>
                    <tr><th>#</th><th>Team</th><th>Season</th></tr>
                </thead>
                <tbody>
                    <tr><td></td><td>Team</td><td>2025</td></tr>
                </tbody>
            </table>
        `;
        teardown = setupDataTablesMock();
        if (global.$ && !window.$) {
            window.$ = global.$;
        }
    });

    afterEach(() => {
        if (typeof teardown === "function") teardown();
        delete window.$;
    });

    test("adds placeholder when SearchBuilder is unavailable", () => {
        delete window.$.fn.dataTable.SearchBuilder;

        initSeasons({
            tableSelector: "#seasons-table",
            controlsSelector: "#seasons-controls",
            panelSelector: "#searchbuilder-panel",
            filterButtonId: "seasons-filter-btn",
            columns: [1, 2],
        });

        const panel = document.getElementById("searchbuilder-panel");
        expect(panel.textContent).toContain("Advanced filter not available.");
        expect(panel.classList.contains("d-none")).toBe(true);
        expect(document.getElementById("seasons-filter-btn")).toBeTruthy();
    });

    test("reuses existing SearchBuilder instance", () => {
        const searchBuilderMock = jest.fn(function () {
            const container = document.createElement("div");
            container.className = "dtsb-searchBuilder";
            this.dom = { container };
            this.container = () => container;
            return this;
        });
        window.$.fn.dataTable.SearchBuilder = searchBuilderMock;

        initSeasons({
            tableSelector: "#seasons-table",
            controlsSelector: "#seasons-controls",
            panelSelector: "#searchbuilder-panel",
            filterButtonId: "seasons-filter-btn",
            columns: [1, 2],
        });

        initSeasons({
            tableSelector: "#seasons-table",
            controlsSelector: "#seasons-controls",
            panelSelector: "#searchbuilder-panel",
            filterButtonId: "seasons-filter-btn",
            columns: [1, 2],
        });

        const panel = document.getElementById("searchbuilder-panel");
        expect(panel.querySelector(".dtsb-searchBuilder")).toBeTruthy();
    });

    test("filters empty column labels when building columnDefs", () => {
        initSeasons({
            tableSelector: "#seasons-table",
            controlsSelector: "#seasons-controls",
            panelSelector: "#searchbuilder-panel",
            filterButtonId: "seasons-filter-btn",
            columns: [1, 2],
            columnLabels: ["", "Team", null, "Season"],
        });

        const lastCall = global.__datatableCalls.at(-1);
        expect(lastCall).toBeTruthy();
        const defs = lastCall.opts.columnDefs || [];
        expect(defs.length).toBe(2);
    });

    test("renumbers rows when number column is missing", () => {
        initSeasons({
            tableSelector: "#seasons-table",
            controlsSelector: "#seasons-controls",
            panelSelector: "#searchbuilder-panel",
            filterButtonId: "seasons-filter-btn",
            columns: [1, 2],
            numberColumn: 10,
        });

        const cell = document.querySelector("#seasons-table tbody tr td");
        expect(cell.textContent).toBe("1");
    });
});
