/** @jest-environment jsdom */
import { jest } from "@jest/globals";

// Helper to build minimal DOM for a single lookup
function setupLookupDom(prefix) {
    document.body.innerHTML += `
    <input type="text" id="${prefix}-search" />
    <input type="hidden" id="${prefix}-id" />
    <div id="${prefix}-results"></div>
    <div id="${prefix}-selected"></div>
  `;
}

// Helper to build the full game form DOM
function setupFullFormDom() {
    document.body.innerHTML = `
    <div id="game-form-card"
         data-opponent-search-url="/admin/opponents/ajax-search"
         data-place-search-url="/admin/places/ajax-search"
         data-site-search-url="/admin/sites/ajax-search"
         data-game-type-search-url="/admin/game-types/ajax-search"
         data-opponent-display=""
         data-place-display=""
         data-site-display=""
         data-game-type-display="">
    </div>
    <input type="text" id="game-type-search" />
    <input type="hidden" id="game-type-id" />
    <div id="game-type-results"></div>
    <div id="game-type-selected"></div>

    <input type="text" id="opponent-search" />
    <input type="hidden" id="opponent-id" />
    <div id="opponent-results"></div>
    <div id="opponent-selected"></div>

    <input type="text" id="place-search" />
    <input type="hidden" id="place-id" />
    <div id="place-results"></div>
    <div id="place-selected"></div>

    <input type="text" id="site-search" />
    <input type="hidden" id="site-id" />
    <div id="site-results"></div>
    <div id="site-selected"></div>

    <button id="add-site-btn">Add New Site</button>
    <input type="hidden" id="add-site-modal-place_id" />

    <!-- Opponent popup place lookup elements -->
    <div id="add-opponent-modal" class="modal">
      <input type="text" id="opponent-place-search" />
      <input type="hidden" id="add-opponent-modal-place_id" />
      <div id="opponent-place-results"></div>
      <div id="opponent-place-selected"></div>
      <button id="opponent-add-place-btn">New Place</button>
    </div>
    <div id="add-opponent-place-modal" class="modal"></div>
  `;
}

describe("game-form-lookups", () => {
    let initLookup;
    let initGameFormLookups;

    beforeEach(async () => {
        jest.restoreAllMocks();
        jest.resetModules();
        document.body.innerHTML = "";
        // Mock global fetch
        global.fetch = jest.fn();

        // Mock Bootstrap Modal for nested modal tests
        const mockModalInstance = {
            hide: jest.fn(),
            show: jest.fn(),
        };
        global.bootstrap = {
            Modal: {
                getOrCreateInstance: jest.fn(() => mockModalInstance),
                _mockInstance: mockModalInstance,
            },
        };

        const mod = await import("../game-form-lookups.js");
        initLookup = mod.initLookup;
        initGameFormLookups = mod.initGameFormLookups;
    });

    afterEach(() => {
        document.body.innerHTML = "";
        delete global.fetch;
        delete global.bootstrap;
        delete window.handleOpponentAdded;
        delete window.handlePlaceAdded;
        delete window.handleSiteAdded;
        delete window.handleGameTypeAdded;
        delete window.handleOpponentPlaceAdded;
    });

    describe("initLookup", () => {
        test("returns noop functions when DOM elements are missing", () => {
            const result = initLookup({
                searchInputId: "nonexistent-search",
                hiddenInputId: "nonexistent-id",
                resultsTableId: "nonexistent-results",
                selectedDisplayId: "nonexistent-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
            });
            // Should not throw
            expect(result.setSelected).toBeDefined();
            expect(result.clearSelection).toBeDefined();
            expect(result.refresh).toBeDefined();
            result.setSelected(1, "test");
            result.clearSelection();
        });

        test("sets hidden input and shows badge on setSelected", () => {
            setupLookupDom("test");
            const lookup = initLookup({
                searchInputId: "test-search",
                hiddenInputId: "test-id",
                resultsTableId: "test-results",
                selectedDisplayId: "test-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
            });

            lookup.setSelected("42", "Test Entity");
            expect(document.getElementById("test-id").value).toBe("42");
            expect(
                document.getElementById("test-selected").innerHTML,
            ).toContain("Test Entity");
            expect(
                document
                    .getElementById("test-selected")
                    .querySelector(".badge"),
            ).not.toBeNull();
        });

        test("clearSelection resets hidden input and display", () => {
            setupLookupDom("test");
            const lookup = initLookup({
                searchInputId: "test-search",
                hiddenInputId: "test-id",
                resultsTableId: "test-results",
                selectedDisplayId: "test-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
            });

            lookup.setSelected("42", "Test Entity");
            lookup.clearSelection();
            expect(document.getElementById("test-id").value).toBe("");
            expect(
                document.getElementById("test-selected").innerHTML,
            ).toContain("None selected");
        });

        test("calls onSelect callback when item is selected", () => {
            setupLookupDom("test");
            const onSelect = jest.fn();
            const lookup = initLookup({
                searchInputId: "test-search",
                hiddenInputId: "test-id",
                resultsTableId: "test-results",
                selectedDisplayId: "test-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
                onSelect,
            });

            lookup.setSelected("42", "Test Entity");
            expect(onSelect).toHaveBeenCalledWith("42", "Test Entity");
        });

        test("calls onSelect with null on clearSelection", () => {
            setupLookupDom("test");
            const onSelect = jest.fn();
            const lookup = initLookup({
                searchInputId: "test-search",
                hiddenInputId: "test-id",
                resultsTableId: "test-results",
                selectedDisplayId: "test-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
                onSelect,
            });

            lookup.clearSelection();
            expect(onSelect).toHaveBeenCalledWith(null, null);
        });

        test("clear button in badge triggers clearSelection", () => {
            setupLookupDom("test");
            const onSelect = jest.fn();
            const lookup = initLookup({
                searchInputId: "test-search",
                hiddenInputId: "test-id",
                resultsTableId: "test-results",
                selectedDisplayId: "test-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
                onSelect,
            });

            lookup.setSelected("42", "Test Entity");
            const clearBtn = document
                .getElementById("test-selected")
                .querySelector(".btn-close");
            expect(clearBtn).not.toBeNull();
            clearBtn.click();
            expect(document.getElementById("test-id").value).toBe("");
        });

        test("shows initial display when hiddenInput has value", () => {
            setupLookupDom("test");
            document.getElementById("test-id").value = "5";

            initLookup({
                searchInputId: "test-search",
                hiddenInputId: "test-id",
                resultsTableId: "test-results",
                selectedDisplayId: "test-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
                initialDisplay: "Pre-selected",
            });

            expect(
                document.getElementById("test-selected").innerHTML,
            ).toContain("Pre-selected");
        });

        test("debounced search fires fetch after input", () => {
            jest.useFakeTimers();
            setupLookupDom("test");

            global.fetch.mockResolvedValue({
                json: () =>
                    Promise.resolve({
                        success: true,
                        results: [{ id: 1, name: "Result One" }],
                    }),
            });

            initLookup({
                searchInputId: "test-search",
                hiddenInputId: "test-id",
                resultsTableId: "test-results",
                selectedDisplayId: "test-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
            });

            const input = document.getElementById("test-search");
            input.value = "hello";
            input.dispatchEvent(new Event("input"));

            // Should not have called fetch yet (debounce)
            expect(global.fetch).not.toHaveBeenCalled();

            jest.advanceTimersByTime(300);
            expect(global.fetch).toHaveBeenCalledTimes(1);
            expect(global.fetch.mock.calls[0][0]).toContain(
                "/admin/test/ajax-search?q=hello",
            );

            jest.useRealTimers();
        });

        test("search does not fire for short queries", () => {
            jest.useFakeTimers();
            setupLookupDom("test");

            initLookup({
                searchInputId: "test-search",
                hiddenInputId: "test-id",
                resultsTableId: "test-results",
                selectedDisplayId: "test-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
            });

            const input = document.getElementById("test-search");
            input.value = "";
            input.dispatchEvent(new Event("input"));

            jest.advanceTimersByTime(500);
            expect(global.fetch).not.toHaveBeenCalled();

            jest.useRealTimers();
        });

        test("builds results table from search response", async () => {
            jest.useFakeTimers();
            setupLookupDom("test");

            global.fetch.mockResolvedValue({
                json: () =>
                    Promise.resolve({
                        success: true,
                        results: [
                            { id: 1, name: "Alpha" },
                            { id: 2, name: "Beta" },
                        ],
                    }),
            });

            initLookup({
                searchInputId: "test-search",
                hiddenInputId: "test-id",
                resultsTableId: "test-results",
                selectedDisplayId: "test-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
            });

            const input = document.getElementById("test-search");
            input.value = "alp";
            input.dispatchEvent(new Event("input"));
            jest.advanceTimersByTime(300);

            // Wait for async fetch
            jest.useRealTimers();
            await new Promise((r) => setTimeout(r, 50));

            const results = document.getElementById("test-results");
            expect(results.querySelectorAll(".lookup-result-row").length).toBe(
                2,
            );
            expect(results.innerHTML).toContain("Alpha");
            expect(results.innerHTML).toContain("Beta");
        });

        test("shows no results message for empty results", async () => {
            jest.useFakeTimers();
            setupLookupDom("test");

            global.fetch.mockResolvedValue({
                json: () =>
                    Promise.resolve({
                        success: true,
                        results: [],
                    }),
            });

            initLookup({
                searchInputId: "test-search",
                hiddenInputId: "test-id",
                resultsTableId: "test-results",
                selectedDisplayId: "test-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
            });

            const input = document.getElementById("test-search");
            input.value = "xyz";
            input.dispatchEvent(new Event("input"));
            jest.advanceTimersByTime(300);
            jest.useRealTimers();
            await new Promise((r) => setTimeout(r, 50));

            expect(document.getElementById("test-results").innerHTML).toContain(
                "No results found",
            );
        });

        test("clicking a result row selects it", async () => {
            jest.useFakeTimers();
            setupLookupDom("test");

            global.fetch.mockResolvedValue({
                json: () =>
                    Promise.resolve({
                        success: true,
                        results: [{ id: 7, name: "Clicked Item" }],
                    }),
            });

            initLookup({
                searchInputId: "test-search",
                hiddenInputId: "test-id",
                resultsTableId: "test-results",
                selectedDisplayId: "test-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
            });

            const input = document.getElementById("test-search");
            input.value = "click";
            input.dispatchEvent(new Event("input"));
            jest.advanceTimersByTime(300);
            jest.useRealTimers();
            await new Promise((r) => setTimeout(r, 50));

            const row = document
                .getElementById("test-results")
                .querySelector(".lookup-result-row");
            row.click();

            expect(document.getElementById("test-id").value).toBe("7");
            expect(
                document.getElementById("test-selected").innerHTML,
            ).toContain("Clicked Item");
        });

        test("includes extra params from getExtraParams", () => {
            jest.useFakeTimers();
            setupLookupDom("test");

            global.fetch.mockResolvedValue({
                json: () => Promise.resolve({ success: true, results: [] }),
            });

            initLookup({
                searchInputId: "test-search",
                hiddenInputId: "test-id",
                resultsTableId: "test-results",
                selectedDisplayId: "test-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
                getExtraParams: () => ({ place_id: "5" }),
            });

            const input = document.getElementById("test-search");
            input.value = "test";
            input.dispatchEvent(new Event("input"));
            jest.advanceTimersByTime(300);

            expect(global.fetch.mock.calls[0][0]).toContain("place_id=5");

            jest.useRealTimers();
        });

        test("shows error on failed search response", async () => {
            jest.useFakeTimers();
            setupLookupDom("test");

            global.fetch.mockResolvedValue({
                json: () => Promise.resolve({ success: false }),
            });

            initLookup({
                searchInputId: "test-search",
                hiddenInputId: "test-id",
                resultsTableId: "test-results",
                selectedDisplayId: "test-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
            });

            const input = document.getElementById("test-search");
            input.value = "test";
            input.dispatchEvent(new Event("input"));
            jest.advanceTimersByTime(300);
            jest.useRealTimers();
            await new Promise((r) => setTimeout(r, 50));

            expect(document.getElementById("test-results").innerHTML).toContain(
                "Search error",
            );
        });

        test("shows network error on fetch failure", async () => {
            jest.useFakeTimers();
            setupLookupDom("test");

            global.fetch.mockRejectedValue(new Error("Network failure"));

            initLookup({
                searchInputId: "test-search",
                hiddenInputId: "test-id",
                resultsTableId: "test-results",
                selectedDisplayId: "test-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
            });

            const input = document.getElementById("test-search");
            input.value = "test";
            input.dispatchEvent(new Event("input"));
            jest.advanceTimersByTime(300);
            jest.useRealTimers();
            await new Promise((r) => setTimeout(r, 50));

            expect(document.getElementById("test-results").innerHTML).toContain(
                "Network error",
            );
        });

        test("aborted requests do not show network error", async () => {
            jest.useFakeTimers();
            setupLookupDom("test");

            const abortError = new DOMException("Aborted", "AbortError");
            global.fetch.mockRejectedValue(abortError);

            initLookup({
                searchInputId: "test-search",
                hiddenInputId: "test-id",
                resultsTableId: "test-results",
                selectedDisplayId: "test-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
            });

            const input = document.getElementById("test-search");
            input.value = "test";
            input.dispatchEvent(new Event("input"));
            jest.advanceTimersByTime(300);
            jest.useRealTimers();
            await new Promise((r) => setTimeout(r, 50));

            expect(
                document.getElementById("test-results").innerHTML,
            ).not.toContain("Network error");
        });

        test("clicking outside closes results", async () => {
            jest.useFakeTimers();
            setupLookupDom("test");

            global.fetch.mockResolvedValue({
                json: () =>
                    Promise.resolve({
                        success: true,
                        results: [{ id: 1, name: "Item" }],
                    }),
            });

            initLookup({
                searchInputId: "test-search",
                hiddenInputId: "test-id",
                resultsTableId: "test-results",
                selectedDisplayId: "test-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
            });

            const input = document.getElementById("test-search");
            input.value = "item";
            input.dispatchEvent(new Event("input"));
            jest.advanceTimersByTime(300);
            jest.useRealTimers();
            await new Promise((r) => setTimeout(r, 50));

            expect(document.getElementById("test-results").innerHTML).toContain(
                "Item",
            );

            // Click outside
            document.body.click();

            expect(document.getElementById("test-results").innerHTML).toBe("");
        });

        test("escapes HTML in display text to prevent XSS", () => {
            setupLookupDom("test");
            const lookup = initLookup({
                searchInputId: "test-search",
                hiddenInputId: "test-id",
                resultsTableId: "test-results",
                selectedDisplayId: "test-selected",
                searchUrl: "/admin/test/ajax-search",
                columns: ["name"],
                columnLabels: ["Name"],
                displayField: "name",
            });

            lookup.setSelected("1", '<script>alert("xss")</script>');
            const html = document.getElementById("test-selected").innerHTML;
            expect(html).not.toContain("<script>");
            expect(html).toContain("&lt;script&gt;");
        });
    });

    describe("initGameFormLookups", () => {
        test("returns early when game-form-card is not present", () => {
            document.body.innerHTML = "<div>no form</div>";
            // Should not throw
            initGameFormLookups();
        });

        test("initializes all four lookups", () => {
            setupFullFormDom();
            initGameFormLookups();

            // All hidden inputs should exist and have empty values
            expect(document.getElementById("game-type-id").value).toBe("");
            expect(document.getElementById("opponent-id").value).toBe("");
            expect(document.getElementById("place-id").value).toBe("");
            expect(document.getElementById("site-id").value).toBe("");

            // All selected displays should show "None selected"
            expect(
                document.getElementById("game-type-selected").innerHTML,
            ).toContain("None selected");
            expect(
                document.getElementById("opponent-selected").innerHTML,
            ).toContain("None selected");
            expect(
                document.getElementById("place-selected").innerHTML,
            ).toContain("None selected");
            expect(
                document.getElementById("site-selected").innerHTML,
            ).toContain("None selected");
        });

        test("disables add-site button when no place is selected", () => {
            setupFullFormDom();
            initGameFormLookups();

            const btn = document.getElementById("add-site-btn");
            expect(btn.disabled).toBe(true);
            expect(btn.title).toBe("Select a place first");
        });

        test("registers window callback handlers", () => {
            setupFullFormDom();
            initGameFormLookups();

            expect(typeof window.handleOpponentAdded).toBe("function");
            expect(typeof window.handlePlaceAdded).toBe("function");
            expect(typeof window.handleSiteAdded).toBe("function");
            expect(typeof window.handleGameTypeAdded).toBe("function");
        });

        test("handleOpponentAdded sets opponent selection", () => {
            setupFullFormDom();
            initGameFormLookups();

            window.handleOpponentAdded({
                newOption: { value: 10, text: "New Opponent" },
            });

            expect(document.getElementById("opponent-id").value).toBe("10");
            expect(
                document.getElementById("opponent-selected").innerHTML,
            ).toContain("New Opponent");
        });

        test("handlePlaceAdded sets place selection", () => {
            setupFullFormDom();
            initGameFormLookups();

            window.handlePlaceAdded({
                newOption: { value: 3, text: "Nashville, TN" },
            });

            expect(document.getElementById("place-id").value).toBe("3");
            expect(
                document.getElementById("place-selected").innerHTML,
            ).toContain("Nashville, TN");
        });

        test("handleSiteAdded sets site selection", () => {
            setupFullFormDom();
            initGameFormLookups();

            window.handleSiteAdded({
                newOption: { value: 5, text: "New Arena" },
            });

            expect(document.getElementById("site-id").value).toBe("5");
            expect(
                document.getElementById("site-selected").innerHTML,
            ).toContain("New Arena");
        });

        test("handleGameTypeAdded sets game type selection", () => {
            setupFullFormDom();
            initGameFormLookups();

            window.handleGameTypeAdded({
                newOption: { value: 8, text: "Exhibition" },
            });

            expect(document.getElementById("game-type-id").value).toBe("8");
            expect(
                document.getElementById("game-type-selected").innerHTML,
            ).toContain("Exhibition");
        });

        test("selecting a place enables add-site button and injects place_id", () => {
            setupFullFormDom();
            initGameFormLookups();

            // Simulate place being added via popup
            window.handlePlaceAdded({
                newOption: { value: 7, text: "Clarksville, TN" },
            });

            const btn = document.getElementById("add-site-btn");
            expect(btn.disabled).toBe(false);
            expect(btn.title).toBe("Add New Site");

            const sitePopupPlaceId = document.getElementById(
                "add-site-modal-place_id",
            );
            expect(sitePopupPlaceId.value).toBe("7");
        });

        test("clearing place disables add-site button and clears site", () => {
            setupFullFormDom();
            initGameFormLookups();

            // First select a place
            window.handlePlaceAdded({
                newOption: { value: 7, text: "Clarksville, TN" },
            });
            // Then select a site
            window.handleSiteAdded({
                newOption: { value: 2, text: "Arena" },
            });

            expect(document.getElementById("site-id").value).toBe("2");

            // Clear place via the place lookup's clear button
            const clearBtn = document
                .getElementById("place-selected")
                .querySelector(".btn-close");
            clearBtn.click();

            expect(document.getElementById("place-id").value).toBe("");
            // Site should be cleared when place is cleared
            expect(document.getElementById("site-id").value).toBe("");
            expect(document.getElementById("add-site-btn").disabled).toBe(true);
        });

        test("handles popup callbacks with missing newOption gracefully", () => {
            setupFullFormDom();
            initGameFormLookups();

            // Should not throw
            window.handleOpponentAdded({});
            window.handlePlaceAdded({});
            window.handleSiteAdded({});
            window.handleGameTypeAdded({});
        });

        test("initializes opponent place lookup inside opponent popup", () => {
            setupFullFormDom();
            initGameFormLookups();

            // The opponent place lookup hidden input should exist
            const hiddenInput = document.getElementById(
                "add-opponent-modal-place_id",
            );
            expect(hiddenInput).not.toBeNull();

            // The opponent place selected display should show "None selected"
            expect(
                document.getElementById("opponent-place-selected").innerHTML,
            ).toContain("None selected");
        });

        test("registers handleOpponentPlaceAdded callback", () => {
            setupFullFormDom();
            initGameFormLookups();

            expect(typeof window.handleOpponentPlaceAdded).toBe("function");
        });

        test("handleOpponentPlaceAdded sets place in opponent popup", () => {
            setupFullFormDom();
            initGameFormLookups();

            window.handleOpponentPlaceAdded({
                newOption: { value: 42, text: "Lexington, KY" },
            });

            expect(
                document.getElementById("add-opponent-modal-place_id").value,
            ).toBe("42");
            expect(
                document.getElementById("opponent-place-selected").innerHTML,
            ).toContain("Lexington, KY");
        });

        test("handleOpponentPlaceAdded with missing newOption does not throw", () => {
            setupFullFormDom();
            initGameFormLookups();

            // Should not throw
            window.handleOpponentPlaceAdded({});
        });

        test("opponent place search triggers fetch to place search URL", () => {
            jest.useFakeTimers();
            setupFullFormDom();
            initGameFormLookups();

            global.fetch.mockResolvedValue({
                json: () =>
                    Promise.resolve({
                        success: true,
                        results: [
                            {
                                id: 1,
                                place_city: "Murray",
                                place_state: "KY",
                            },
                        ],
                    }),
            });

            const input = document.getElementById("opponent-place-search");
            input.value = "Murray";
            input.dispatchEvent(new Event("input"));
            jest.advanceTimersByTime(300);

            expect(global.fetch).toHaveBeenCalledWith(
                expect.stringContaining("/admin/places/ajax-search?q=Murray"),
                expect.any(Object),
            );

            jest.useRealTimers();
        });

        test("clicking opponent-add-place-btn hides opponent modal and shows place modal", () => {
            setupFullFormDom();
            initGameFormLookups();

            const btn = document.getElementById("opponent-add-place-btn");
            btn.click();

            // Should have called hide on the opponent modal
            expect(
                global.bootstrap.Modal.getOrCreateInstance,
            ).toHaveBeenCalledWith(
                document.getElementById("add-opponent-modal"),
            );
            expect(
                global.bootstrap.Modal._mockInstance.hide,
            ).toHaveBeenCalled();
        });

        test("opponent modal hidden event after place toggle shows place modal", () => {
            setupFullFormDom();
            initGameFormLookups();

            // Click the add place button to start the toggle
            const btn = document.getElementById("opponent-add-place-btn");
            btn.click();

            // Simulate the opponent modal's hidden.bs.modal event
            const opponentModal = document.getElementById("add-opponent-modal");
            opponentModal.dispatchEvent(new Event("hidden.bs.modal"));

            // Should show the place modal
            expect(
                global.bootstrap.Modal.getOrCreateInstance,
            ).toHaveBeenCalledWith(
                document.getElementById("add-opponent-place-modal"),
            );
            expect(
                global.bootstrap.Modal._mockInstance.show,
            ).toHaveBeenCalled();
        });

        test("opponent place modal hidden event re-opens opponent modal", () => {
            setupFullFormDom();
            initGameFormLookups();

            // Simulate the place modal being hidden (after user finishes with it)
            const placeModal = document.getElementById(
                "add-opponent-place-modal",
            );
            placeModal.dispatchEvent(new Event("hidden.bs.modal"));

            // Should re-open the opponent modal
            expect(
                global.bootstrap.Modal.getOrCreateInstance,
            ).toHaveBeenCalledWith(
                document.getElementById("add-opponent-modal"),
            );
            expect(
                global.bootstrap.Modal._mockInstance.show,
            ).toHaveBeenCalled();
        });

        test("opponent place lookup does NOT clear when toggling to place modal", () => {
            setupFullFormDom();
            initGameFormLookups();

            // First set a place in the opponent popup
            window.handleOpponentPlaceAdded({
                newOption: { value: 42, text: "Lexington, KY" },
            });
            expect(
                document.getElementById("add-opponent-modal-place_id").value,
            ).toBe("42");

            // Click add place button (toggle to place modal)
            const btn = document.getElementById("opponent-add-place-btn");
            btn.click();

            // Simulate the opponent modal hidden event (during toggle)
            const opponentModal = document.getElementById("add-opponent-modal");
            opponentModal.dispatchEvent(new Event("hidden.bs.modal"));

            // Place selection should NOT be cleared (toggle flag prevents it)
            expect(
                document.getElementById("add-opponent-modal-place_id").value,
            ).toBe("42");
        });

        test("opponent place lookup clears on normal modal dismiss", () => {
            setupFullFormDom();
            initGameFormLookups();

            // Set a place in the opponent popup
            window.handleOpponentPlaceAdded({
                newOption: { value: 42, text: "Lexington, KY" },
            });
            expect(
                document.getElementById("add-opponent-modal-place_id").value,
            ).toBe("42");

            // Simulate normal modal dismiss (NOT toggle to place)
            const opponentModal = document.getElementById("add-opponent-modal");
            opponentModal.dispatchEvent(new Event("hidden.bs.modal"));

            // Place selection should be cleared
            expect(
                document.getElementById("add-opponent-modal-place_id").value,
            ).toBe("");
        });

        test("with initial display values shows pre-selected badges", () => {
            document.body.innerHTML = `
        <div id="game-form-card"
             data-opponent-search-url="/admin/opponents/ajax-search"
             data-place-search-url="/admin/places/ajax-search"
             data-site-search-url="/admin/sites/ajax-search"
             data-game-type-search-url="/admin/game-types/ajax-search"
             data-opponent-display="Belmont"
             data-place-display="Murray, KY"
             data-site-display="CFSB Center"
             data-game-type-display="Conference">
        </div>
        <input type="text" id="game-type-search" />
        <input type="hidden" id="game-type-id" value="1" />
        <div id="game-type-results"></div>
        <div id="game-type-selected"></div>

        <input type="text" id="opponent-search" />
        <input type="hidden" id="opponent-id" value="1" />
        <div id="opponent-results"></div>
        <div id="opponent-selected"></div>

        <input type="text" id="place-search" />
        <input type="hidden" id="place-id" value="1" />
        <div id="place-results"></div>
        <div id="place-selected"></div>

        <input type="text" id="site-search" />
        <input type="hidden" id="site-id" value="1" />
        <div id="site-results"></div>
        <div id="site-selected"></div>

        <button id="add-site-btn">Add New Site</button>
        <input type="hidden" id="add-site-modal-place_id" />
      `;

            initGameFormLookups();

            expect(
                document.getElementById("game-type-selected").innerHTML,
            ).toContain("Conference");
            expect(
                document.getElementById("opponent-selected").innerHTML,
            ).toContain("Belmont");
            expect(
                document.getElementById("place-selected").innerHTML,
            ).toContain("Murray, KY");
            expect(
                document.getElementById("site-selected").innerHTML,
            ).toContain("CFSB Center");
        });
    });
});
