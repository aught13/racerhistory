/* eslint-env jest, browser */
/** @jest-environment jsdom */


describe("Seasons SearchBuilder integration (module)", () => {
    let teardown;

    beforeEach(() => {
        document.body.innerHTML = `
      <div id="seasons-controls" class="d-flex"></div>
      <div id="searchbuilder-panel" class="searchbuilder-panel"></div>
      <table id="seasons-table"><thead></thead><tbody></tbody></table>
    `;
        teardown = setupDataTablesMock();
    });

const setupDataTablesMock = require("./helpers/datatables.mock");
const initSeasons = require("../../js/modules/seasons-init.cjs");

    afterEach(() => {
        if (typeof teardown === 'function') teardown();
    });

    test('module initializes DataTable and appends SearchBuilder to panel and toggles', () => {
        const result = initSeasons();
        expect(result).toBeTruthy();
        // button should be created
        const btn = document.getElementById("seasons-filter-btn");
        expect(btn).toBeTruthy();
        const panel = document.getElementById("searchbuilder-panel");
        expect(panel).toBeTruthy();
        // initially hidden
        expect(panel.classList.contains("d-none")).toBe(true);
        // panel contains SearchBuilder
        const sbEl = panel.querySelector(".dtsb-searchBuilder");
        expect(sbEl).toBeTruthy();

        // toggle open
        btn.click();
        expect(panel.classList.contains("d-none")).toBe(false);
        expect(panel.classList.contains("sb-open")).toBe(true);
        expect(btn.getAttribute("aria-expanded")).toBe("true");

        // toggle closed
        btn.click();
        expect(panel.classList.contains("d-none")).toBe(true);
        expect(panel.classList.contains("sb-open")).toBe(false);
        expect(btn.getAttribute("aria-expanded")).toBe("false");
    });
});
