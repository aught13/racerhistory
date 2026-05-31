/* global MouseEvent, afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import RosterMultiAddController from "../controllers/roster_multi_add_controller.js";

const flushPromises = async () => {
    await Promise.resolve();
    await Promise.resolve();
};

describe("roster-multi-add controller", () => {
    let application;

    beforeEach(() => {
        jest.useFakeTimers();
        globalThis.fetch = jest.fn();

        document.body.innerHTML = `
            <div data-controller="roster-multi-add">
                <div
                    id="roster-rows"
                    data-roster-multi-add-target="rows"
                    data-person-search-url="/admin/people/search"
                >
                    <div class="roster-row" data-row-index="0">
                        <input class="roster-person-search" name="rows[0][person_search]" />
                        <input class="roster-person-id" name="rows[0][person_id]" value="12" />
                        <div class="roster-person-selected">
                            <span class="badge bg-primary me-1">
                                Existing Person
                                <button
                                    type="button"
                                    class="btn-close btn-close-white ms-1 roster-clear-person"
                                ></button>
                            </span>
                        </div>
                        <div class="roster-person-results"></div>
                        <input type="hidden" name="rows[0][id]" value="44" />
                        <button type="button" class="remove-row-btn">Remove</button>
                    </div>
                </div>
                <button
                    id="add-row-btn"
                    type="button"
                    data-roster-multi-add-target="addButton"
                >
                    Add row
                </button>
            </div>
        `;

        application = Application.start();
        application.register("roster-multi-add", RosterMultiAddController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete globalThis.fetch;
        delete window.onRosterPersonAdded;
        document.body.innerHTML = "";
        jest.useRealTimers();
        jest.restoreAllMocks();
    });

    test("adds/removes rows, reindexes inputs, and resets cloned fields", () => {
        const rowsContainer = document.getElementById("roster-rows");
        const addButton = document.getElementById("add-row-btn");

        expect(rowsContainer.querySelector(".remove-row-btn").disabled).toBe(
            true,
        );

        addButton.click();

        const rows = rowsContainer.querySelectorAll(".roster-row");
        const newRow = rows[1];

        expect(rows).toHaveLength(2);
        expect(rows[0].querySelector(".remove-row-btn").disabled).toBe(false);
        expect(rows[1].querySelector(".remove-row-btn").disabled).toBe(false);
        expect(newRow.dataset.rowIndex).toBe("1");
        expect(
            newRow.querySelector('[name="rows[1][person_search]"]'),
        ).not.toBeNull();
        expect(newRow.querySelector('[name="rows[1][person_id]"]').value).toBe(
            "",
        );
        expect(newRow.querySelector('[name="rows[1][id]"]')).toBeNull();
        expect(
            newRow.querySelector(".roster-person-search").dataset.searchBound,
        ).toBe("1");
        expect(document.activeElement).toBe(
            newRow.querySelector(".roster-person-search"),
        );

        rowsContainer.querySelector(".remove-row-btn").click();

        const remainingRows = rowsContainer.querySelectorAll(".roster-row");
        expect(remainingRows).toHaveLength(1);
        expect(remainingRows[0].dataset.rowIndex).toBe("0");
        expect(
            remainingRows[0].querySelector('[name="rows[0][person_search]"]'),
        ).not.toBeNull();
        expect(remainingRows[0].querySelector(".remove-row-btn").disabled).toBe(
            true,
        );
    });

    test("searches and selects a person, then handles empty and network responses", async () => {
        await flushPromises();

        const searchInput = document.querySelector(".roster-person-search");
        const hiddenInput = document.querySelector(".roster-person-id");
        const results = document.querySelector(".roster-person-results");

        searchInput.value = "A";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        expect(results.innerHTML).toBe("");
        expect(fetch).not.toHaveBeenCalled();

        fetch
            .mockResolvedValueOnce({
                json: async () => ({
                    success: true,
                    results: [{ value: 99, text: "Alice" }],
                }),
            })
            .mockResolvedValueOnce({
                json: async () => ({
                    success: true,
                    results: [],
                }),
            })
            .mockRejectedValueOnce(new Error("network"));

        searchInput.value = "Al";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        await flushPromises();
        await flushPromises();

        expect(fetch).toHaveBeenCalledWith("/admin/people/search?q=Al", {
            signal: expect.anything(),
            headers: { "X-Requested-With": "XMLHttpRequest" },
        });
        expect(results.innerHTML).toContain("Alice");

        results.querySelector("button").click();

        expect(hiddenInput.value).toBe("99");
        expect(
            document.querySelector(".roster-person-selected").innerHTML,
        ).toContain("Alice");
        expect(results.innerHTML).toBe("");
        expect(searchInput.value).toBe("");

        searchInput.value = "No";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        await flushPromises();
        await flushPromises();

        expect(results.innerHTML).toContain("No results found");

        searchInput.value = "Er";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        await flushPromises();
        await flushPromises();

        expect(results.innerHTML).toContain("Network error");
    });

    test("aborts stale searches, supports global person add, and cleans up listeners", async () => {
        await flushPromises();

        const searchInput = document.querySelector(".roster-person-search");
        const hiddenInput = document.querySelector(".roster-person-id");
        const selected = document.querySelector(".roster-person-selected");
        const addButton = document.getElementById("add-row-btn");

        fetch
            .mockImplementationOnce(() => new Promise(() => {}))
            .mockResolvedValueOnce({
                json: async () => ({
                    success: true,
                    results: [{ value: 100, text: "Abe" }],
                }),
            });

        searchInput.value = "Ab";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);

        const firstSignal = fetch.mock.calls[0][1].signal;
        expect(firstSignal.aborted).toBe(false);

        searchInput.value = "Abe";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        await flushPromises();

        expect(firstSignal.aborted).toBe(true);

        document.querySelector(".roster-clear-person").click();
        expect(hiddenInput.value).toBe("");
        expect(selected.textContent).toContain("None selected");

        addButton.click();
        window.onRosterPersonAdded({
            newOption: { value: "321", text: "Injected Player" },
        });

        const rows = document.querySelectorAll(".roster-row");
        expect(rows[0].querySelector(".roster-person-id").value).toBe("321");
        expect(rows[1].querySelector(".roster-person-id").value).toBe("321");

        const results = document.querySelectorAll(".roster-person-results");
        results[0].innerHTML = '<button type="button">Visible</button>';
        document.body.dispatchEvent(new MouseEvent("click", { bubbles: true }));
        expect(results[0].innerHTML).toBe("");

        document.querySelector('[data-controller="roster-multi-add"]').remove();
        await flushPromises();

        expect(window.onRosterPersonAdded).toBeUndefined();
    });
});

describe("roster-multi-add controller branch coverage", () => {
    let application;

    const renderFixture = ({
        includeRowsTarget = true,
        includeAddButtonTarget = true,
        includeAddButton = true,
        includeSearchUrl = true,
        includeRow = true,
    } = {}) => {
        document.body.innerHTML = `
            <div data-controller="roster-multi-add">
                <div
                    id="roster-rows"
                    ${includeRowsTarget ? 'data-roster-multi-add-target="rows"' : ""}
                    ${includeSearchUrl ? 'data-person-search-url="/admin/people/search"' : ""}
                >
                    ${
                        includeRow
                            ? `<div class="roster-row" data-row-index="0">
                                <input class="roster-person-search" name="rows[0][person_search]" />
                                <input class="roster-person-id" name="rows[0][person_id]" value="" />
                                <div class="roster-person-selected"></div>
                                <div class="roster-person-results"></div>
                                <button type="button" class="remove-row-btn">Remove</button>
                            </div>`
                            : ""
                    }
                </div>
                ${
                    includeAddButton
                        ? `<button id="add-row-btn" type="button" ${includeAddButtonTarget ? 'data-roster-multi-add-target="addButton"' : ""}>Add</button>`
                        : ""
                }
            </div>
        `;
    };

    const startController = async (options = {}) => {
        renderFixture(options);

        application = Application.start();
        application.register("roster-multi-add", RosterMultiAddController);

        const root = document.querySelector(
            '[data-controller="roster-multi-add"]',
        );
        for (let i = 0; i < 4; i += 1) {
            const controller =
                application.getControllerForElementAndIdentifier(
                    root,
                    "roster-multi-add",
                ) ||
                application.controllers.find(
                    (item) => item.identifier === "roster-multi-add",
                );

            if (controller) {
                return controller;
            }

            await Promise.resolve();
        }

        return undefined;
    };

    beforeEach(() => {
        jest.useFakeTimers();
        globalThis.fetch = jest.fn();
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete globalThis.fetch;
        delete window.onRosterPersonAdded;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
        jest.useRealTimers();
    });

    test("connect/disconnect fallback and guard branches", async () => {
        const controller = await startController({
            includeRowsTarget: false,
            includeAddButtonTarget: false,
            includeAddButton: false,
            includeSearchUrl: false,
        });

        expect(controller.rowsElement().id).toBe("roster-rows");
        expect(controller.addButtonElement()).toBeNull();

        const keepHandler = () => {};
        window.onRosterPersonAdded = keepHandler;
        controller.disconnect();
        expect(window.onRosterPersonAdded).toBe(keepHandler);

        expect(() =>
            RosterMultiAddController.prototype.disconnect.call({
                rowsElement: () => null,
                addButtonElement: () => null,
                boundRowsClick: () => {},
                boundAddClick: () => {},
                boundDocumentClick: () => {},
                boundPersonAdded: () => {},
            }),
        ).not.toThrow();
    });

    test("click handlers cover remove guards and outside/inside row behavior", async () => {
        const controller = await startController();

        controller.handleRowsClick({ target: document.createElement("div") });

        const orphanRemove = document.createElement("button");
        orphanRemove.className = "remove-row-btn";
        controller.handleRowsClick({ target: orphanRemove });

        const rows = document.querySelectorAll(".roster-row");
        rows[0].querySelector(".roster-person-results").innerHTML = "x";
        controller.handleDocumentClick({ target: rows[0] });
        expect(rows[0].querySelector(".roster-person-results").innerHTML).toBe(
            "x",
        );

        const noResultsRow = document.createElement("div");
        noResultsRow.className = "roster-row";
        document.getElementById("roster-rows").appendChild(noResultsRow);
        controller.handleDocumentClick({ target: document.body });
        expect(rows[0].querySelector(".roster-person-results").innerHTML).toBe(
            "",
        );
    });

    test("person add/init search/render/add/reindex/auto-select utility branches", async () => {
        const controller = await startController();

        controller.handlePersonAdded({});
        controller.handlePersonAdded({ newOption: { value: "", text: "x" } });
        controller.handlePersonAdded({
            newOption: { value: "5", text: "Five" },
        });

        expect(() =>
            controller.initPersonSearch(null, "/admin/people/search"),
        ).not.toThrow();
        expect(() =>
            controller.initPersonSearch(
                document.querySelector(".roster-row"),
                "",
            ),
        ).not.toThrow();

        const missingBits = document.createElement("div");
        missingBits.className = "roster-row";
        missingBits.innerHTML = '<input class="roster-person-search" />';
        controller.initPersonSearch(missingBits, "/admin/people/search");

        const boundRow = document.querySelector(".roster-row");
        const boundInput = boundRow.querySelector(".roster-person-search");
        boundInput.dataset.searchBound = "1";
        controller.initPersonSearch(boundRow, "/admin/people/search");

        const noSelectedRow = document.createElement("div");
        noSelectedRow.className = "roster-row";
        noSelectedRow.innerHTML =
            '<input class="roster-person-search" /><input class="roster-person-id" /><div class="roster-person-results"></div>';
        controller.initPersonSearch(noSelectedRow, "/admin/people/search");
        noSelectedRow.querySelector(".roster-person-search").value = "ab";
        globalThis.fetch.mockRejectedValueOnce({ name: "AbortError" });
        noSelectedRow
            .querySelector(".roster-person-search")
            .dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        await flushPromises();
        expect(
            noSelectedRow.querySelector(".roster-person-results").innerHTML,
        ).toBe("");

        controller.renderResults(
            [{ value: 1 }, { value: 2, text: "Beta" }],
            noSelectedRow.querySelector(".roster-person-results"),
            jest.fn(),
        );
        expect(
            noSelectedRow.querySelector(".roster-person-results").innerHTML,
        ).toContain('data-id="1" data-text=""');

        const rowsContainer = document.getElementById("roster-rows");
        const row = rowsContainer.querySelector(".roster-row");
        row.removeAttribute("data-row-index");
        row.querySelector(".roster-person-selected").remove();
        row.querySelector(".roster-person-results").remove();
        const nameless = document.createElement("input");
        row.appendChild(nameless);
        row.querySelector(".roster-person-search").remove();

        controller.addRow();
        const newRow = rowsContainer.querySelectorAll(".roster-row")[1];
        expect(newRow.dataset.rowIndex).toBe("1");

        controller.reindexRows();
        expect(() => controller.updateRemoveButtons()).not.toThrow();

        const selectSpy = jest.fn();
        const blankHidden = document.querySelector(".roster-person-id");
        blankHidden.value = "already";
        row._rosterSetSelected = selectSpy;
        controller.autoSelectNewPerson("99", "Name");
        expect(selectSpy).not.toHaveBeenCalled();

        blankHidden.value = "";
        controller.autoSelectNewPerson("99", "Name");
        expect(selectSpy).toHaveBeenCalledWith("99", "Name");

        const emptyRowsController = await startController({
            includeRow: false,
        });
        expect(() => emptyRowsController.addRow()).not.toThrow();

        expect(controller.escapeHtml("<tag>&\"'")).toBe("&lt;tag&gt;&amp;\"'");
    });
});
