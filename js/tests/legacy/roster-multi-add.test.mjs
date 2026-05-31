/**
 * @jest-environment jsdom
 */
import { initRosterMultiAdd } from "../../legacy/modules/roster-multi-add.mjs";

/**
 * Build the DOM fixture that mirrors the server-rendered add.php template.
 * Each row uses an AJAX person search (text input + hidden input + results div)
 * instead of a static select.
 */
function buildDom(rowCount = 1) {
    let rows = "";
    for (let i = 0; i < rowCount; i++) {
        rows += `
      <div class="card mb-2 roster-row" data-row-index="${i}">
        <div class="card-body">
          <div class="row g-2 align-items-end">
            <div class="col-md-3" style="position:relative">
              <label class="form-label">Person</label>
              <input type="text" class="form-control roster-person-search" placeholder="Search persons..." autocomplete="off">
              <input type="hidden" name="rows[${i}][person_id]" class="roster-person-id" required>
              <div class="roster-person-selected small mt-1"><span class="text-muted fst-italic">None selected</span></div>
              <div class="roster-person-results"></div>
            </div>
            <div class="col-md-2">
              <input type="text" name="rows[${i}][roster_number]" class="form-control" maxlength="3">
            </div>
            <div class="col-md-2">
              <input type="text" name="rows[${i}][roster_position]" class="form-control" maxlength="30">
            </div>
            <div class="col-md-2">
              <input type="text" name="rows[${i}][roster_height]" class="form-control" maxlength="5">
            </div>
            <div class="col-md-2">
              <input type="text" name="rows[${i}][roster_weight]" class="form-control" maxlength="5">
            </div>
            <div class="col-md-1 text-end">
              <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn" title="Remove row" ${rowCount <= 1 ? "disabled" : ""}>
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
          </div>
        </div>
      </div>`;
    }

    document.body.innerHTML = `
    <div id="roster-rows" data-person-search-url="/admin/persons/ajax-search">${rows}</div>
    <button type="button" id="add-row-btn">Add Another</button>
  `;
}

describe("roster-multi-add (AJAX search)", () => {
    beforeEach(() => {
        // Reset the module's _initialised flag
        document.dispatchEvent(new Event("turbo:before-cache"));
        // Mock fetch globally
        global.fetch = jest.fn();
    });

    afterEach(() => {
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("initRosterMultiAdd does nothing without DOM elements", () => {
        document.body.innerHTML = "";
        expect(() => initRosterMultiAdd()).not.toThrow();
    });

    test("add row button creates a new roster row", () => {
        buildDom();
        initRosterMultiAdd();

        document.getElementById("add-row-btn").click();

        const rows = document.querySelectorAll(".roster-row");
        expect(rows.length).toBe(2);
        expect(rows[1].getAttribute("data-row-index")).toBe("1");
    });

    test("new row has empty hidden person_id and reset fields", () => {
        buildDom();
        initRosterMultiAdd();

        // Set values in the first row
        const firstHidden = document.querySelector(".roster-person-id");
        firstHidden.value = "1";
        const firstNumber = document.querySelector(
            'input[name="rows[0][roster_number]"]',
        );
        firstNumber.value = "22";

        // Add row
        document.getElementById("add-row-btn").click();

        // New row should have empty values
        const newHidden = document.querySelectorAll(".roster-person-id")[1];
        expect(newHidden.value).toBe("");

        const newNumber = document.querySelector(
            'input[name="rows[1][roster_number]"]',
        );
        expect(newNumber.value).toBe("");
    });

    test("new row has correctly indexed field names", () => {
        buildDom();
        initRosterMultiAdd();

        document.getElementById("add-row-btn").click();
        document.getElementById("add-row-btn").click();

        const rows = document.querySelectorAll(".roster-row");
        expect(rows.length).toBe(3);

        // Check hidden person_id name
        expect(rows[2].querySelector(".roster-person-id").name).toBe(
            "rows[2][person_id]",
        );
        expect(rows[2].querySelector('input[name*="roster_number"]').name).toBe(
            "rows[2][roster_number]",
        );
    });

    test("remove button is disabled when only one row", () => {
        buildDom();
        initRosterMultiAdd();

        const removeBtn = document.querySelector(".remove-row-btn");
        expect(removeBtn.disabled).toBe(true);
    });

    test("remove buttons become enabled when multiple rows exist", () => {
        buildDom();
        initRosterMultiAdd();

        document.getElementById("add-row-btn").click();

        const removeBtns = document.querySelectorAll(".remove-row-btn");
        removeBtns.forEach((btn) => {
            expect(btn.disabled).toBe(false);
        });
    });

    test("removing a row re-indexes remaining rows", () => {
        buildDom();
        initRosterMultiAdd();

        document.getElementById("add-row-btn").click();
        document.getElementById("add-row-btn").click();

        // Remove the second row (index 1)
        const rows = document.querySelectorAll(".roster-row");
        rows[1].querySelector(".remove-row-btn").click();

        const remaining = document.querySelectorAll(".roster-row");
        expect(remaining.length).toBe(2);
        expect(remaining[0].getAttribute("data-row-index")).toBe("0");
        expect(remaining[1].getAttribute("data-row-index")).toBe("1");

        // Hidden person_id name should be re-indexed
        expect(remaining[1].querySelector(".roster-person-id").name).toBe(
            "rows[1][person_id]",
        );
    });

    test("removing last extra row disables remaining remove button", () => {
        buildDom();
        initRosterMultiAdd();

        document.getElementById("add-row-btn").click();
        expect(document.querySelectorAll(".roster-row").length).toBe(2);

        const secondRemoveBtn = document.querySelectorAll(".remove-row-btn")[1];
        secondRemoveBtn.click();

        const remainingBtn = document.querySelector(".remove-row-btn");
        expect(remainingBtn.disabled).toBe(true);
    });

    test("search input triggers fetch after debounce", () => {
        jest.useFakeTimers();
        const mockResponse = {
            ok: true,
            json: () =>
                Promise.resolve({
                    success: true,
                    results: [{ value: 1, text: "John Doe" }],
                }),
        };
        global.fetch.mockResolvedValue(mockResponse);

        buildDom();
        initRosterMultiAdd();

        const searchInput = document.querySelector(".roster-person-search");
        searchInput.value = "john";
        searchInput.dispatchEvent(new Event("input"));

        // Before debounce fires, no fetch
        expect(global.fetch).not.toHaveBeenCalled();

        // Advance past debounce
        jest.advanceTimersByTime(350);

        expect(global.fetch).toHaveBeenCalledWith(
            "/admin/persons/ajax-search?q=john",
            expect.objectContaining({
                headers: { "X-Requested-With": "XMLHttpRequest" },
            }),
        );

        jest.useRealTimers();
    });

    test("selecting a search result sets hidden input and shows badge", async () => {
        const mockResponse = {
            ok: true,
            json: () =>
                Promise.resolve({
                    success: true,
                    results: [
                        { value: 5, text: "Jane Smith" },
                        { value: 6, text: "John Doe" },
                    ],
                }),
        };
        global.fetch.mockResolvedValue(mockResponse);

        jest.useFakeTimers();
        buildDom();
        initRosterMultiAdd();

        const searchInput = document.querySelector(".roster-person-search");
        searchInput.value = "ja";
        searchInput.dispatchEvent(new Event("input"));
        jest.advanceTimersByTime(350);

        // Let the fetch promise resolve
        jest.useRealTimers();
        await new Promise((r) => setTimeout(r, 0));

        // Results should be rendered
        const resultBtns = document.querySelectorAll(".roster-search-result");
        expect(resultBtns.length).toBe(2);

        // Click the first result
        resultBtns[0].click();

        // Hidden input should be set
        const hiddenInput = document.querySelector(".roster-person-id");
        expect(hiddenInput.value).toBe("5");

        // Badge should appear
        const selected = document.querySelector(".roster-person-selected");
        expect(selected.textContent).toContain("Jane Smith");
        expect(selected.querySelector(".badge")).not.toBeNull();
    });

    test("clearing selection resets hidden input", async () => {
        const mockResponse = {
            ok: true,
            json: () =>
                Promise.resolve({
                    success: true,
                    results: [{ value: 5, text: "Jane Smith" }],
                }),
        };
        global.fetch.mockResolvedValue(mockResponse);

        jest.useFakeTimers();
        buildDom();
        initRosterMultiAdd();

        const searchInput = document.querySelector(".roster-person-search");
        searchInput.value = "ja";
        searchInput.dispatchEvent(new Event("input"));
        jest.advanceTimersByTime(350);

        jest.useRealTimers();
        await new Promise((r) => setTimeout(r, 0));

        // Select something
        document.querySelector(".roster-search-result").click();
        expect(document.querySelector(".roster-person-id").value).toBe("5");

        // Clear it
        document.querySelector(".roster-clear-person").click();
        expect(document.querySelector(".roster-person-id").value).toBe("");
        expect(
            document.querySelector(".roster-person-selected").textContent,
        ).toContain("None selected");
    });

    test("popupFormSuccess auto-selects in first empty row", () => {
        buildDom();
        initRosterMultiAdd();

        // Dispatch event
        document.dispatchEvent(
            new CustomEvent("popupFormSuccess", {
                detail: { id: 99, label: "New Person" },
            }),
        );

        // First row should have been selected
        const hiddenInput = document.querySelector(".roster-person-id");
        expect(hiddenInput.value).toBe("99");
        expect(
            document.querySelector(".roster-person-selected").textContent,
        ).toContain("New Person");
    });

    test("turbo:before-cache resets initialisation flag", () => {
        buildDom();
        initRosterMultiAdd();

        // Calling again should be idempotent
        initRosterMultiAdd();

        // After turbo:before-cache, re-init should work
        document.dispatchEvent(new Event("turbo:before-cache"));

        // Re-build DOM and init again
        buildDom();
        initRosterMultiAdd();

        document.getElementById("add-row-btn").click();
        expect(document.querySelectorAll(".roster-row").length).toBe(2);
    });

    test("new row search input gets bound for AJAX", () => {
        jest.useFakeTimers();
        const mockResponse = {
            ok: true,
            json: () => Promise.resolve({ success: true, results: [] }),
        };
        global.fetch.mockResolvedValue(mockResponse);

        buildDom();
        initRosterMultiAdd();

        // Add a second row
        document.getElementById("add-row-btn").click();

        // Type in the second row's search
        const searchInputs = document.querySelectorAll(".roster-person-search");
        searchInputs[1].value = "test";
        searchInputs[1].dispatchEvent(new Event("input"));
        jest.advanceTimersByTime(350);

        expect(global.fetch).toHaveBeenCalledWith(
            "/admin/persons/ajax-search?q=test",
            expect.any(Object),
        );

        jest.useRealTimers();
    });

    test("short queries do not trigger fetch", () => {
        jest.useFakeTimers();
        buildDom();
        initRosterMultiAdd();

        const searchInput = document.querySelector(".roster-person-search");
        searchInput.value = "j"; // Only 1 char, MIN is 2
        searchInput.dispatchEvent(new Event("input"));
        jest.advanceTimersByTime(350);

        expect(global.fetch).not.toHaveBeenCalled();
        jest.useRealTimers();
    });
});

// ── Bulk Edit Mode Tests ──────────────────────────────────────────────

/**
 * Build a DOM fixture that mirrors the bulk_edit.php template
 * with pre-populated existing roster entries.
 */
function buildEditDom(entries = []) {
    let rows = "";
    entries.forEach((entry, i) => {
        rows += `
      <div class="card mb-2 roster-row" data-row-index="${i}">
        <div class="card-body">
          <div class="row g-2 align-items-end">
            <input type="hidden" name="rows[${i}][id]" value="${entry.id}">
            <div class="col-md-3" style="position:relative">
              <label class="form-label">Person</label>
              <input type="text" class="form-control roster-person-search" placeholder="Search persons..." autocomplete="off">
              <input type="hidden" name="rows[${i}][person_id]" class="roster-person-id" value="${entry.person_id}" required>
              <div class="roster-person-selected small mt-1">
                <span class="badge bg-primary me-1">${entry.person_name}
                  <button type="button" class="btn-close btn-close-white ms-1 roster-clear-person"
                      aria-label="Clear" style="font-size:.5em;vertical-align:middle"></button>
                </span>
              </div>
              <div class="roster-person-results"></div>
            </div>
            <div class="col-md-1">
              <input type="text" name="rows[${i}][roster_year]" class="form-control" maxlength="10" value="${entry.year || ""}">
            </div>
            <div class="col-md-2">
              <input type="text" name="rows[${i}][roster_number]" class="form-control" maxlength="3" value="${entry.number || ""}">
            </div>
            <div class="col-md-2">
              <input type="text" name="rows[${i}][roster_position]" class="form-control" maxlength="30" value="${entry.position || ""}">
            </div>
            <div class="col-md-1">
              <input type="text" name="rows[${i}][roster_height]" class="form-control" maxlength="5" value="${entry.height || ""}">
            </div>
            <div class="col-md-2">
              <input type="text" name="rows[${i}][roster_weight]" class="form-control" maxlength="5" value="${entry.weight || ""}">
            </div>
            <div class="col-md-1 text-end">
              <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn" title="Remove row">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
          </div>
        </div>
      </div>`;
    });

    document.body.innerHTML = `
    <div id="roster-rows" data-person-search-url="/admin/persons/ajax-search">${rows}</div>
    <button type="button" id="add-row-btn">Add Another</button>
  `;
}

describe("roster-multi-add (bulk edit mode)", () => {
    const sampleEntries = [
        {
            id: 1,
            person_id: 10,
            person_name: "Alice Adams",
            number: "12",
            position: "G",
            year: "Sr.",
            height: "5-9",
            weight: "140",
        },
        {
            id: 2,
            person_id: 20,
            person_name: "Bob Brown",
            number: "22",
            position: "F",
            year: "Jr.",
            height: "6-3",
            weight: "200",
        },
    ];

    beforeEach(() => {
        document.dispatchEvent(new Event("turbo:before-cache"));
        global.fetch = jest.fn();
    });

    afterEach(() => {
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("initialises search on all pre-populated rows", () => {
        buildEditDom(sampleEntries);
        initRosterMultiAdd();

        // Both rows should have searchBound flag set
        const inputs = document.querySelectorAll(".roster-person-search");
        expect(inputs.length).toBe(2);
        inputs.forEach((input) => {
            expect(input.dataset.searchBound).toBe("1");
        });
    });

    test("pre-populated rows have person badges and hidden IDs", () => {
        buildEditDom(sampleEntries);
        initRosterMultiAdd();

        const badges = document.querySelectorAll(
            ".roster-person-selected .badge",
        );
        expect(badges.length).toBe(2);
        expect(badges[0].textContent).toContain("Alice Adams");
        expect(badges[1].textContent).toContain("Bob Brown");

        const hiddenIds = document.querySelectorAll('input[name*="[id]"]');
        expect(hiddenIds.length).toBe(2);
        expect(hiddenIds[0].value).toBe("1");
        expect(hiddenIds[1].value).toBe("2");
    });

    test("remove buttons are enabled when multiple rows exist", () => {
        buildEditDom(sampleEntries);
        initRosterMultiAdd();

        const removeBtns = document.querySelectorAll(".remove-row-btn");
        removeBtns.forEach((btn) => {
            expect(btn.disabled).toBe(false);
        });
    });

    test("removing a row removes its hidden ID from the form", () => {
        buildEditDom(sampleEntries);
        initRosterMultiAdd();

        // Remove Alice (row 0)
        document.querySelectorAll(".remove-row-btn")[0].click();

        const rows = document.querySelectorAll(".roster-row");
        expect(rows.length).toBe(1);
        // Remaining row should be Bob, re-indexed to 0
        const hiddenId = rows[0].querySelector('input[name="rows[0][id]"]');
        expect(hiddenId.value).toBe("2");
    });

    test("removing last row down to one disables remove button", () => {
        buildEditDom(sampleEntries);
        initRosterMultiAdd();

        document.querySelectorAll(".remove-row-btn")[1].click();

        const remaining = document.querySelector(".remove-row-btn");
        expect(remaining.disabled).toBe(true);
    });

    test("adding a new row in edit mode omits the hidden ID field", () => {
        buildEditDom(sampleEntries);
        initRosterMultiAdd();

        document.getElementById("add-row-btn").click();

        const rows = document.querySelectorAll(".roster-row");
        expect(rows.length).toBe(3);

        // New row (index 2) should NOT have a hidden id field
        const newRow = rows[2];
        const idInput = newRow.querySelector('input[name="rows[2][id]"]');
        expect(idInput).toBeNull();

        // But should have a person_id hidden input
        const personIdInput = newRow.querySelector(
            'input[name="rows[2][person_id]"]',
        );
        expect(personIdInput).not.toBeNull();
        expect(personIdInput.value).toBe("");
    });

    test("clearing a pre-populated person resets the hidden person_id", () => {
        buildEditDom(sampleEntries);
        initRosterMultiAdd();

        // Clear Alice's selection
        const clearBtn = document.querySelectorAll(".roster-clear-person")[0];
        clearBtn.click();

        const personId = document.querySelector(
            'input[name="rows[0][person_id]"]',
        );
        expect(personId.value).toBe("");
        expect(
            document.querySelectorAll(".roster-person-selected")[0].textContent,
        ).toContain("None selected");
    });

    test("re-indexing preserves hidden ID values after removal", () => {
        const threeEntries = [
            ...sampleEntries,
            {
                id: 3,
                person_id: 30,
                person_name: "Carol Clark",
                number: "33",
                position: "C",
                year: "So.",
                height: "6-5",
                weight: "220",
            },
        ];
        buildEditDom(threeEntries);
        initRosterMultiAdd();

        // Remove the middle row (Bob)
        document.querySelectorAll(".remove-row-btn")[1].click();

        const rows = document.querySelectorAll(".roster-row");
        expect(rows.length).toBe(2);

        // Row 0 = Alice (id=1), Row 1 = Carol (id=3, re-indexed)
        expect(rows[0].querySelector('input[name="rows[0][id]"]').value).toBe(
            "1",
        );
        expect(rows[1].querySelector('input[name="rows[1][id]"]').value).toBe(
            "3",
        );
        expect(
            rows[1].querySelector('input[name="rows[1][person_id]"]').value,
        ).toBe("30");
    });
});
