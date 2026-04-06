/**
 * @jest-environment jsdom
 */
import { initStatMultiAdd } from "../modules/stat-multi-add.mjs";

/**
 * Build the DOM fixture for a player (person) stat multi-row form.
 * Mirrors the server-rendered StatBasketGamePerson/add.php template.
 */
function buildPersonDom(rowCount = 1) {
    let rows = "";
    for (let i = 0; i < rowCount; i++) {
        rows += `
      <div class="card mb-3 stat-row" data-row-index="${i}">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="stat-row-label">Player #${i + 1}</span>
          <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn" title="Remove row" ${rowCount <= 1 ? "disabled" : ""}>
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="card-body">
          <div class="row g-2 mb-2">
            <div class="col-md-4">
              <select name="rows[${i}][team_season_roster_id]" class="form-select stat-player-select" required>
                <option value="">-- Select Player --</option>
                <option value="1">#12 John Smith</option>
                <option value="2">#22 Jane Doe</option>
              </select>
            </div>
            <div class="col-md-2">
              <input type="text" name="rows[${i}][period]" class="form-control" value="Z">
            </div>
            <div class="col-md-1">
              <div class="form-check mt-1">
                <input type="checkbox" name="rows[${i}][GS]" value="1" class="form-check-input">
              </div>
            </div>
            <div class="col-md-1">
              <input type="text" name="rows[${i}][MIN]" class="form-control">
            </div>
          </div>
          <div class="row g-2 mb-2">
            <div class="col-md-1"><input type="text" name="rows[${i}][FGM]" class="form-control"></div>
            <div class="col-md-1"><input type="text" name="rows[${i}][FGA]" class="form-control"></div>
            <div class="col-md-1"><input type="text" name="rows[${i}][TPM]" class="form-control"></div>
            <div class="col-md-1"><input type="text" name="rows[${i}][TPA]" class="form-control"></div>
            <div class="col-md-1"><input type="text" name="rows[${i}][FTM]" class="form-control"></div>
            <div class="col-md-1"><input type="text" name="rows[${i}][FTA]" class="form-control"></div>
            <div class="col-md-1"><input type="text" name="rows[${i}][ORB]" class="form-control"></div>
            <div class="col-md-1"><input type="text" name="rows[${i}][DRB]" class="form-control"></div>
            <div class="col-md-1"><input type="text" name="rows[${i}][RB]" class="form-control"></div>
            <div class="col-md-1"><input type="text" name="rows[${i}][AST]" class="form-control"></div>
          </div>
          <div class="row g-2 mb-2">
            <div class="col-md-1"><input type="text" name="rows[${i}][STL]" class="form-control"></div>
            <div class="col-md-1"><input type="text" name="rows[${i}][BS]" class="form-control"></div>
            <div class="col-md-1"><input type="text" name="rows[${i}][BD]" class="form-control"></div>
            <div class="col-md-1"><input type="text" name="rows[${i}][TRN]" class="form-control"></div>
            <div class="col-md-1"><input type="text" name="rows[${i}][PF]" class="form-control"></div>
            <div class="col-md-1"><input type="text" name="rows[${i}][TF]" class="form-control"></div>
            <div class="col-md-1"><input type="text" name="rows[${i}][FD]" class="form-control"></div>
            <div class="col-md-1"><input type="text" name="rows[${i}][PTS]" class="form-control" required></div>
          </div>
          <input type="hidden" name="rows[${i}][GP]" value="1">
        </div>
      </div>`;
    }

    document.body.innerHTML = `
    <div id="stat-rows" data-stat-type="person">${rows}</div>
    <button type="button" id="add-row-btn">Add Another</button>
  `;
}

/**
 * Build the DOM fixture for an opponent stat multi-row form.
 * Mirrors the server-rendered StatBasketGameOpponent/add.php template.
 */
function buildOpponentDom(rowCount = 1) {
    let rows = "";
    for (let i = 0; i < rowCount; i++) {
        rows += `
      <div class="card mb-3 stat-row" data-row-index="${i}">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="stat-row-label">Player #${i + 1}</span>
          <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn" title="Remove row" ${rowCount <= 1 ? "disabled" : ""}>
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="card-body">
          <div class="row g-2 mb-2">
            <div class="col-md-3">
              <input type="text" name="rows[${i}][name]" class="form-control stat-opp-name" placeholder="e.g., John Smith" required>
            </div>
            <div class="col-md-1">
              <input type="text" name="rows[${i}][jersey]" class="form-control" placeholder="23">
            </div>
            <div class="col-md-1">
              <input type="text" name="rows[${i}][position]" class="form-control" placeholder="G">
            </div>
            <div class="col-md-2">
              <input type="text" name="rows[${i}][period]" class="form-control" value="Z">
            </div>
            <div class="col-md-1">
              <div class="form-check mt-1">
                <input type="checkbox" name="rows[${i}][GS]" value="1" class="form-check-input">
              </div>
            </div>
            <div class="col-md-1">
              <input type="text" name="rows[${i}][MIN]" class="form-control">
            </div>
          </div>
          <div class="row g-2 mb-2">
            <div class="col-md-1"><input type="text" name="rows[${i}][PTS]" class="form-control" required></div>
          </div>
          <input type="hidden" name="rows[${i}][GP]" value="1">
        </div>
      </div>`;
    }

    document.body.innerHTML = `
    <div id="stat-rows" data-stat-type="opponent">${rows}</div>
    <button type="button" id="add-row-btn">Add Another</button>
  `;
}

describe("stat-multi-add (person mode)", () => {
    beforeEach(() => {
        document.dispatchEvent(new Event("turbo:before-cache"));
    });

    afterEach(() => {
        document.body.innerHTML = "";
    });

    test("initStatMultiAdd does nothing without DOM elements", () => {
        document.body.innerHTML = "";
        expect(() => initStatMultiAdd()).not.toThrow();
    });

    test("add row button creates a new stat row", () => {
        buildPersonDom();
        initStatMultiAdd();

        document.getElementById("add-row-btn").click();

        const rows = document.querySelectorAll(".stat-row");
        expect(rows.length).toBe(2);
        expect(rows[1].getAttribute("data-row-index")).toBe("1");
    });

    test("new row has reset player select and empty fields", () => {
        buildPersonDom();
        initStatMultiAdd();

        // Set values in the first row
        const firstSelect = document.querySelector(".stat-player-select");
        firstSelect.value = "1";
        const firstPTS = document.querySelector('input[name="rows[0][PTS]"]');
        firstPTS.value = "20";

        document.getElementById("add-row-btn").click();

        // New row should have reset select and empty PTS
        const selects = document.querySelectorAll(".stat-player-select");
        expect(selects[1].selectedIndex).toBe(0);
        const newPTS = document.querySelector('input[name="rows[1][PTS]"]');
        expect(newPTS.value).toBe("");
    });

    test("new row preserves default period value of Z", () => {
        buildPersonDom();
        initStatMultiAdd();

        document.getElementById("add-row-btn").click();

        const periodInput = document.querySelector(
            'input[name="rows[1][period]"]',
        );
        expect(periodInput.value).toBe("Z");
    });

    test("new row preserves GP hidden value of 1", () => {
        buildPersonDom();
        initStatMultiAdd();

        document.getElementById("add-row-btn").click();

        const gpInput = document.querySelector('input[name="rows[1][GP]"]');
        expect(gpInput.value).toBe("1");
    });

    test("new row checkbox is unchecked", () => {
        buildPersonDom();
        initStatMultiAdd();

        // Check GS in first row
        const firstGS = document.querySelector('input[name="rows[0][GS]"]');
        firstGS.checked = true;

        document.getElementById("add-row-btn").click();

        const newGS = document.querySelector('input[name="rows[1][GS]"]');
        expect(newGS.checked).toBe(false);
    });

    test("new row label is updated correctly", () => {
        buildPersonDom();
        initStatMultiAdd();

        document.getElementById("add-row-btn").click();

        const labels = document.querySelectorAll(".stat-row-label");
        expect(labels[0].textContent).toBe("Player #1");
        expect(labels[1].textContent).toBe("Player #2");
    });

    test("remove button is disabled when only one row exists", () => {
        buildPersonDom();
        initStatMultiAdd();

        const removeBtn = document.querySelector(".remove-row-btn");
        expect(removeBtn.disabled).toBe(true);
    });

    test("remove button becomes enabled when multiple rows exist", () => {
        buildPersonDom();
        initStatMultiAdd();

        document.getElementById("add-row-btn").click();

        const removeBtns = document.querySelectorAll(".remove-row-btn");
        removeBtns.forEach((btn) => expect(btn.disabled).toBe(false));
    });

    test("removing a row re-indexes remaining rows", () => {
        buildPersonDom();
        initStatMultiAdd();

        // Add two rows (total 3)
        document.getElementById("add-row-btn").click();
        document.getElementById("add-row-btn").click();
        expect(document.querySelectorAll(".stat-row").length).toBe(3);

        // Remove the second row (index 1)
        const rows = document.querySelectorAll(".stat-row");
        rows[1].querySelector(".remove-row-btn").click();

        // Should be 2 rows with indexes 0, 1
        const remaining = document.querySelectorAll(".stat-row");
        expect(remaining.length).toBe(2);
        expect(remaining[0].getAttribute("data-row-index")).toBe("0");
        expect(remaining[1].getAttribute("data-row-index")).toBe("1");

        // Check field names are re-indexed
        const lastPTS = remaining[1].querySelector(
            'input[name="rows[1][PTS]"]',
        );
        expect(lastPTS).not.toBeNull();
    });

    test("removing a row updates labels", () => {
        buildPersonDom();
        initStatMultiAdd();

        document.getElementById("add-row-btn").click();
        document.getElementById("add-row-btn").click();

        // Remove the first row
        document
            .querySelectorAll(".stat-row")[0]
            .querySelector(".remove-row-btn")
            .click();

        const labels = document.querySelectorAll(".stat-row-label");
        expect(labels[0].textContent).toBe("Player #1");
        expect(labels[1].textContent).toBe("Player #2");
    });

    test("remove button disabled again when down to one row", () => {
        buildPersonDom();
        initStatMultiAdd();

        document.getElementById("add-row-btn").click();
        expect(document.querySelectorAll(".stat-row").length).toBe(2);

        // Remove second row
        document
            .querySelectorAll(".stat-row")[1]
            .querySelector(".remove-row-btn")
            .click();

        const removeBtn = document.querySelector(".remove-row-btn");
        expect(removeBtn.disabled).toBe(true);
    });

    test("adding multiple rows creates correct count", () => {
        buildPersonDom();
        initStatMultiAdd();

        document.getElementById("add-row-btn").click();
        document.getElementById("add-row-btn").click();
        document.getElementById("add-row-btn").click();

        expect(document.querySelectorAll(".stat-row").length).toBe(4);
    });

    test("turbo:before-cache resets initialization flag", () => {
        buildPersonDom();
        initStatMultiAdd();

        // Should be initialized, add row works
        document.getElementById("add-row-btn").click();
        expect(document.querySelectorAll(".stat-row").length).toBe(2);

        // Reset
        document.dispatchEvent(new Event("turbo:before-cache"));

        // Rebuild DOM and re-init should work
        buildPersonDom();
        initStatMultiAdd();

        document.getElementById("add-row-btn").click();
        expect(document.querySelectorAll(".stat-row").length).toBe(2);
    });

    test("row select names are properly indexed", () => {
        buildPersonDom();
        initStatMultiAdd();

        document.getElementById("add-row-btn").click();
        document.getElementById("add-row-btn").click();

        const selects = document.querySelectorAll(".stat-player-select");
        expect(selects[0].name).toBe("rows[0][team_season_roster_id]");
        expect(selects[1].name).toBe("rows[1][team_season_roster_id]");
        expect(selects[2].name).toBe("rows[2][team_season_roster_id]");
    });
});

describe("stat-multi-add (opponent mode)", () => {
    beforeEach(() => {
        document.dispatchEvent(new Event("turbo:before-cache"));
    });

    afterEach(() => {
        document.body.innerHTML = "";
    });

    test("add row button creates a new opponent stat row", () => {
        buildOpponentDom();
        initStatMultiAdd();

        document.getElementById("add-row-btn").click();

        const rows = document.querySelectorAll(".stat-row");
        expect(rows.length).toBe(2);
    });

    test("new opponent row has empty name field", () => {
        buildOpponentDom();
        initStatMultiAdd();

        // Fill first row
        const nameInput = document.querySelector(".stat-opp-name");
        nameInput.value = "Jane Smith";

        document.getElementById("add-row-btn").click();

        const names = document.querySelectorAll(".stat-opp-name");
        expect(names[1].value).toBe("");
    });

    test("opponent row name fields are properly indexed", () => {
        buildOpponentDom();
        initStatMultiAdd();

        document.getElementById("add-row-btn").click();

        const names = document.querySelectorAll(".stat-opp-name");
        expect(names[0].name).toBe("rows[0][name]");
        expect(names[1].name).toBe("rows[1][name]");
    });

    test("removing an opponent row re-indexes", () => {
        buildOpponentDom();
        initStatMultiAdd();

        document.getElementById("add-row-btn").click();
        document.getElementById("add-row-btn").click();

        // Remove first row
        document
            .querySelectorAll(".stat-row")[0]
            .querySelector(".remove-row-btn")
            .click();

        const rows = document.querySelectorAll(".stat-row");
        expect(rows.length).toBe(2);
        const firstName = rows[0].querySelector(".stat-opp-name");
        expect(firstName.name).toBe("rows[0][name]");
    });

    test("opponent row preserves period default Z in new rows", () => {
        buildOpponentDom();
        initStatMultiAdd();

        document.getElementById("add-row-btn").click();

        const period = document.querySelector('input[name="rows[1][period]"]');
        expect(period.value).toBe("Z");
    });
});
