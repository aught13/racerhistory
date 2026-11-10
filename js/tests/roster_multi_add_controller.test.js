/* global MouseEvent, afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import RosterMultiAddController from "../controllers/roster_multi_add_controller.js";

const flushPromises = async () => {
    await Promise.resolve();
    await Promise.resolve();
    await Promise.resolve();
};

const renderFixture = () => {
    document.body.innerHTML = `
        <div
            data-controller="roster-multi-add"
            data-action="
                click@document->roster-multi-add#handleDocumentClick
                admin:person-added@document->roster-multi-add#handlePersonAdded
                turbo:before-cache@document->roster-multi-add#beforeCache
            "
        >
            <div
                id="roster-rows"
                data-roster-multi-add-target="rows"
                data-person-search-url="/admin/people/search"
                data-action="
                    click->roster-multi-add#handleRowsClick
                    input->roster-multi-add#searchPerson
                "
            >
                <div class="roster-row" data-row-index="0">
                    <input
                        class="roster-person-search"
                        name="rows[0][person_search]"
                    />
                    <input
                        type="hidden"
                        class="roster-person-id"
                        name="rows[0][person_id]"
                        value="12"
                    />
                    <div class="roster-person-selected">
                        <span class="badge bg-primary me-1">
                            Existing Person
                            <button
                                type="button"
                                class="btn-close btn-close-white ms-1 roster-clear-person"
                                aria-label="Clear"
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
                data-action="click->roster-multi-add#addRow"
            >
                Add row
            </button>
        </div>
    `;
};

describe("roster-multi-add controller", () => {
    let application;

    const getController = () => {
        const root = document.querySelector(
            '[data-controller="roster-multi-add"]',
        );
        return application.getControllerForElementAndIdentifier(
            root,
            "roster-multi-add",
        );
    };

    const startController = async () => {
        renderFixture();
        application = Application.start();
        application.register("roster-multi-add", RosterMultiAddController);
        await flushPromises();
        return getController();
    };

    beforeEach(() => {
        jest.useFakeTimers();
        globalThis.fetch = jest.fn();
    });

    afterEach(() => {
        application?.stop();
        application = null;
        delete globalThis.fetch;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
        jest.useRealTimers();
    });

    test("adds and removes rows while preserving indexed form fields", async () => {
        await startController();

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
        expect(newRow.dataset.transient).toBe("true");
        expect(newRow.dataset.rowIndex).toBe("1");
        expect(
            newRow.querySelector('[name="rows[1][person_search]"]'),
        ).not.toBeNull();
        expect(newRow.querySelector('[name="rows[1][person_id]"]').value).toBe(
            "",
        );
        expect(newRow.querySelector('[name="rows[1][id]"]')).toBeNull();
        expect(
            newRow.querySelector(".roster-person-selected").textContent,
        ).toBe("None selected");
        expect(document.activeElement).toBe(
            newRow.querySelector(".roster-person-search"),
        );

        newRow.querySelector(".remove-row-btn").click();

        const remainingRows = rowsContainer.querySelectorAll(".roster-row");
        expect(remainingRows).toHaveLength(1);
        expect(remainingRows[0].dataset.rowIndex).toBe("0");
        expect(remainingRows[0].querySelector(".remove-row-btn").disabled).toBe(
            true,
        );
    });

    test("searches, selects, and handles empty, invalid, and failed responses", async () => {
        await startController();

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
                json: async () => ({ success: true, results: [] }),
            })
            .mockResolvedValueOnce({
                json: async () => ({ success: false, results: [] }),
            })
            .mockRejectedValueOnce(new Error("network"));

        searchInput.value = "Al";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        await flushPromises();

        expect(fetch).toHaveBeenCalledWith("/admin/people/search?q=Al", {
            signal: expect.anything(),
            headers: { "X-Requested-With": "XMLHttpRequest" },
        });
        expect(results.textContent).toContain("Alice");

        results.querySelector("button").click();
        expect(hiddenInput.value).toBe("99");
        expect(
            document.querySelector(".roster-person-selected").textContent,
        ).toContain("Alice");
        expect(results.innerHTML).toBe("");
        expect(searchInput.value).toBe("");

        searchInput.value = "No";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        await flushPromises();
        expect(results.textContent).toContain("No results found");

        searchInput.value = "Bad";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        await flushPromises();
        expect(results.textContent).toBe("No results");

        searchInput.value = "Er";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        await flushPromises();
        expect(results.textContent).toBe("Network error");
    });

    test("aborts stale searches and ignores abort errors", async () => {
        const controller = await startController();
        const searchInput = document.querySelector(".roster-person-search");
        const results = document.querySelector(".roster-person-results");

        fetch
            .mockImplementationOnce(
                (_url, { signal }) =>
                    new Promise((_resolve, reject) => {
                        signal.addEventListener(
                            "abort",
                            () =>
                                reject(
                                    Object.assign(new Error("aborted"), {
                                        name: "AbortError",
                                    }),
                                ),
                            { once: true },
                        );
                    }),
            )
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

        searchInput.value = "Abe";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        expect(firstSignal.aborted).toBe(true);
        jest.advanceTimersByTime(300);
        await flushPromises();
        expect(results.textContent).toContain("Abe");

        fetch.mockRejectedValueOnce(
            Object.assign(new Error("aborted"), { name: "AbortError" }),
        );
        controller.runSearch(
            searchInput,
            searchInput.closest(".roster-row"),
            results,
            "/admin/people/search",
            "abort",
        );
        await flushPromises();
        expect(results.textContent).toContain("Abe");
    });

    test("dispatches person-added events, clears selections, and closes outside results", async () => {
        await startController();

        document.getElementById("add-row-btn").click();
        const rows = document.querySelectorAll(".roster-row");
        const firstHidden = rows[0].querySelector(".roster-person-id");
        const secondHidden = rows[1].querySelector(".roster-person-id");

        document.dispatchEvent(
            new CustomEvent("admin:person-added", {
                detail: {
                    newOption: { value: "321", text: "Injected Player" },
                },
            }),
        );
        expect(firstHidden.value).toBe("12");
        expect(secondHidden.value).toBe("321");

        document.dispatchEvent(
            new CustomEvent("admin:person-added", {
                detail: { newOption: { value: "", text: "Invalid" } },
            }),
        );
        expect(secondHidden.value).toBe("321");

        rows[0].querySelector(".roster-clear-person").click();
        expect(firstHidden.value).toBe("");
        expect(
            rows[0].querySelector(".roster-person-selected").textContent,
        ).toBe("None selected");

        const results = rows[0].querySelector(".roster-person-results");
        results.textContent = "Visible";
        rows[0].dispatchEvent(new MouseEvent("click", { bubbles: true }));
        expect(results.textContent).toBe("Visible");

        document.body.dispatchEvent(new MouseEvent("click", { bubbles: true }));
        expect(results.textContent).toBe("");
    });

    test("cleans transient searches and rows before Turbo caches the page", async () => {
        const controller = await startController();
        const searchInput = document.querySelector(".roster-person-search");
        const firstRow = searchInput.closest(".roster-row");
        const results = firstRow.querySelector(".roster-person-results");

        fetch.mockImplementationOnce(
            (_url, { signal }) =>
                new Promise((_resolve, reject) => {
                    signal.addEventListener(
                        "abort",
                        () =>
                            reject(
                                Object.assign(new Error("aborted"), {
                                    name: "AbortError",
                                }),
                            ),
                        { once: true },
                    );
                }),
        );

        searchInput.value = "Ab";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        const signal = fetch.mock.calls[0][1].signal;
        results.textContent = "Transient results";

        document.getElementById("add-row-btn").click();
        document.dispatchEvent(new Event("turbo:before-cache"));
        await flushPromises();

        expect(signal.aborted).toBe(true);
        expect(document.querySelectorAll(".roster-row")).toHaveLength(1);
        expect(searchInput.value).toBe("");
        expect(results.textContent).toBe("");
        expect(firstRow.querySelector(".roster-person-id").value).toBe("12");
        expect(
            firstRow.querySelector(".roster-person-selected").textContent,
        ).toContain("Existing Person");
        expect(firstRow.querySelector(".remove-row-btn").disabled).toBe(true);
        expect(controller.searchTimers.size).toBe(0);
        expect(controller.searchRequests.size).toBe(0);
    });

    test("cancels pending work on disconnect and reconnects without duplicate actions", async () => {
        const controller = await startController();
        const searchInput = document.querySelector(".roster-person-search");
        const root = document.querySelector(
            '[data-controller="roster-multi-add"]',
        );

        searchInput.value = "Ab";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        expect(controller.searchTimers.size).toBe(1);

        root.remove();
        await flushPromises();
        expect(controller.searchTimers.size).toBe(0);
        jest.advanceTimersByTime(300);
        expect(fetch).not.toHaveBeenCalled();

        document.body.append(root);
        await flushPromises();
        const reconnectedController = getController();
        const autoSelect = jest.spyOn(
            reconnectedController,
            "autoSelectNewPerson",
        );

        document.getElementById("add-row-btn").click();
        document.dispatchEvent(
            new CustomEvent("admin:person-added", {
                detail: { newOption: { value: "77", text: "Reconnected" } },
            }),
        );

        expect(document.querySelectorAll(".roster-row")).toHaveLength(2);
        expect(autoSelect).toHaveBeenCalledTimes(1);
    });

    test("guards malformed rows and renders result text without HTML injection", async () => {
        const controller = await startController();
        const rows = controller.rowsTarget;

        controller.searchPerson({ target: document.body });
        const orphanInput = document.createElement("input");
        orphanInput.className = "roster-person-search";
        rows.append(orphanInput);
        controller.searchPerson({ target: orphanInput });
        expect(fetch).not.toHaveBeenCalled();

        const noResultsRow = document.createElement("div");
        noResultsRow.className = "roster-row";
        const noResultsInput = document.createElement("input");
        noResultsInput.className = "roster-person-search";
        noResultsRow.append(noResultsInput);
        rows.append(noResultsRow);
        controller.searchPerson({ target: noResultsInput });

        const detachedRow = document.createElement("div");
        const detachedInput = document.createElement("input");
        const detachedResults = document.createElement("div");
        detachedRow.append(detachedInput, detachedResults);
        controller.runSearch(
            detachedInput,
            detachedRow,
            detachedResults,
            "/admin/people/search",
            "query",
        );
        expect(fetch).not.toHaveBeenCalled();

        const visibleResults = document.querySelector(".roster-person-results");
        controller.renderResults(
            [{ value: 1, text: '<img src=x onerror="alert(1)">' }],
            visibleResults,
        );
        expect(visibleResults.querySelector("img")).toBeNull();
        expect(visibleResults.textContent).toContain("<img");

        controller.renderResults([], visibleResults);
        expect(visibleResults.textContent).toBe("No results found");

        const noUrlRows = controller.rowsTarget;
        noUrlRows.dataset.personSearchUrl = "";
        const searchInput = document.querySelector(".roster-person-search");
        visibleResults.textContent = "Old result";
        searchInput.value = "Al";
        controller.searchPerson({ target: searchInput });
        expect(visibleResults.textContent).toBe("");

        controller.handlePersonAdded({ detail: {} });
        controller.addRow();
        rows.replaceChildren();
        expect(() => controller.addRow()).not.toThrow();
        controller.updateRemoveButtons();

        expect(() =>
            RosterMultiAddController.prototype.cancelAllSearches.call({
                searchTimers: null,
                searchRequests: null,
            }),
        ).not.toThrow();
    });
});
