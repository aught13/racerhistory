/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import TagSelectionController from "../controllers/tag_selection_controller.js";

describe("tag-selection controller", () => {
    let application;
    let controllerElement;
    let consoleErrorSpy;
    let consoleWarnSpy;

    const flush = async () => {
        await Promise.resolve();
        await Promise.resolve();
    };

    const wait = (ms) =>
        new Promise((resolve) => {
            setTimeout(resolve, ms);
        });

    const buildMarkup = (prefix = "modal") => {
        const id = (name) => `${prefix}_${name}`;

        return `
            <div id="${id("selectedPersons")}" class="d-flex flex-wrap gap-2 mb-2"></div>
            <input type="text" name="person_search" id="${id("person_search")}" />
            <datalist id="${id("personsList")}"></datalist>
            <button type="button" id="${id("add_person_btn")}">Add Person</button>
            <button type="button" id="${id("clear_persons_btn")}">Clear</button>
            <div id="${id("person_hidden_inputs")}"></div>

            <select name="teamseason_select" id="${id("teamseason_select")}">
                <option value="">-- select team season --</option>
                <option value="110">Men's Basketball 2025-2026</option>
            </select>

            <input
                type="text"
                name="game_search"
                id="${id("game_search")}"
                disabled
            />
            <datalist id="${id("gamesList")}"></datalist>
            <input type="hidden" name="game_select" id="${id("game_select")}" />
            <div id="${id("selectedGame")}"></div>

            <input type="text" name="site_search" id="${id("site_search")}" />
            <datalist id="${id("sitesList")}"></datalist>
            <input type="hidden" name="site_select" id="${id("site_select")}" />

            <input type="text" name="opponent_search" id="${id("opponent_search")}" />
            <datalist id="${id("opponentsList")}"></datalist>
            <input type="hidden" name="opponent_select" id="${id("opponent_select")}" />

            <select name="roster_select" id="${id("roster_select")}" disabled>
                <option value="">-- select roster entry --</option>
            </select>
        `;
    };

    const mount = async ({
        prefix = "modal",
        initialPersons = [],
        initialRosterId = 0,
        initialPersonsJsonRaw = null,
    } = {}) => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";

        controllerElement = document.createElement("div");
        controllerElement.setAttribute("data-controller", "tag-selection");

        const initialPersonsJson = JSON.stringify(initialPersons);
        controllerElement.setAttribute(
            "data-tag-selection-initial-persons-json-value",
            initialPersonsJsonRaw ?? initialPersonsJson,
        );
        controllerElement.setAttribute(
            "data-tag-selection-initial-roster-id-value",
            String(initialRosterId),
        );

        controllerElement.innerHTML = buildMarkup(prefix);
        document.body.appendChild(controllerElement);

        application = Application.start();
        application.register("tag-selection", TagSelectionController);

        await flush();
    };

    beforeEach(() => {
        consoleErrorSpy = jest
            .spyOn(console, "error")
            .mockImplementation(() => {});
        consoleWarnSpy = jest
            .spyOn(console, "warn")
            .mockImplementation(() => {});
        window.fetch = jest.fn(async () => ({
            ok: true,
            json: async () => ({
                games: [],
                opponents: [],
                persons: [],
                rosters: [],
                sites: [],
            }),
        }));
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }
        consoleErrorSpy?.mockRestore();
        consoleWarnSpy?.mockRestore();
        delete window.fetch;
        document.body.innerHTML = "";
    });

    test("initializes without Stimulus lifecycle errors", async () => {
        await mount({ prefix: "uploadModalTag" });

        await flush();

        expect(consoleErrorSpy).not.toHaveBeenCalled();
    });

    test("supports prefixed ids and renders initial persons with roster preselect", async () => {
        window.fetch.mockImplementation(async (url) => {
            if (String(url).includes("/admin/tag-lookups/rosters")) {
                return {
                    ok: true,
                    json: async () => ({
                        rosters: [
                            {
                                id: 7,
                                label: "Men's Basketball 2025-2026 #7",
                            },
                        ],
                    }),
                };
            }

            return {
                ok: true,
                json: async () => ({
                    games: [],
                    opponents: [],
                    persons: [],
                    rosters: [],
                    sites: [],
                }),
            };
        });

        await mount({
            prefix: "uploadModalTag",
            initialPersons: [{ id: 42, label: "Test Person" }],
            initialRosterId: 7,
        });

        await wait(0);
        await flush();

        const hiddenPersonInputs = controllerElement.querySelectorAll(
            "input[name='person_select[]']",
        );
        const rosterSelect = controllerElement.querySelector(
            "select[name='roster_select']",
        );

        expect(hiddenPersonInputs).toHaveLength(1);
        expect(hiddenPersonInputs[0].value).toBe("42");
        expect(controllerElement.textContent).toContain("Test Person");
        expect(rosterSelect.disabled).toBe(false);
        expect(window.fetch).toHaveBeenCalledWith(
            "/admin/tag-lookups/rosters?person_id=42",
            expect.objectContaining({ credentials: "same-origin" }),
        );
        expect(
            Array.from(rosterSelect.options).some(
                (option) => option.value === "7" && option.selected,
            ),
        ).toBe(true);
    });

    test("enables game search after team season selection and sends teamseason_id filter", async () => {
        window.fetch.mockImplementation(async (url) => {
            if (String(url).includes("/admin/tag-lookups/games")) {
                return {
                    ok: true,
                    json: async () => ({
                        games: [
                            {
                                id: 1,
                                label: "State at Murray",
                                team_season_id: 110,
                            },
                        ],
                    }),
                };
            }

            return {
                ok: true,
                json: async () => ({
                    games: [],
                    opponents: [],
                    persons: [],
                    rosters: [],
                    sites: [],
                }),
            };
        });

        await mount({ prefix: "uploadModalTag" });

        const teamSeasonSelect = controllerElement.querySelector(
            "select[name='teamseason_select']",
        );
        const gameSearch = controllerElement.querySelector(
            "input[name='game_search']",
        );

        expect(gameSearch.disabled).toBe(true);

        teamSeasonSelect.value = "110";
        teamSeasonSelect.dispatchEvent(new Event("change", { bubbles: true }));

        expect(gameSearch.disabled).toBe(false);

        gameSearch.value = "St";
        gameSearch.dispatchEvent(new Event("input", { bubbles: true }));

        await wait(300);
        await flush();

        expect(window.fetch).toHaveBeenCalledWith(
            "/admin/tag-lookups/games?q=St&teamseason_id=110",
            expect.objectContaining({ credentials: "same-origin" }),
        );

        const gameOptions = controllerElement.querySelectorAll(
            "datalist[id$='gamesList'] option",
        );
        expect(gameOptions.length).toBeGreaterThan(0);
    });

    test("handles invalid initial persons json without lifecycle errors", async () => {
        await mount({
            prefix: "uploadModalTag",
            initialPersonsJsonRaw: "{broken-json",
        });

        await flush();

        expect(consoleErrorSpy).not.toHaveBeenCalled();
        expect(
            controllerElement.querySelectorAll("input[name='person_select[]']"),
        ).toHaveLength(0);
    });

    test("adds, deduplicates, and removes selected persons", async () => {
        await mount({ prefix: "uploadModalTag" });

        const controller = application.controllers.find(
            (c) => c.identifier === "tag-selection",
        );
        const personSearch = controllerElement.querySelector(
            "input[name='person_search']",
        );
        const addBtn = controllerElement.querySelector(
            "button[id$='add_person_btn']",
        );

        controller._maps.persons.set("Jane Doe", 77);

        personSearch.value = "jane doe";
        addBtn.dispatchEvent(new Event("click", { bubbles: true }));

        expect(
            controllerElement.querySelectorAll("input[name='person_select[]']"),
        ).toHaveLength(1);
        expect(controllerElement.textContent).toContain("jane doe");

        personSearch.value = "Jane Doe";
        addBtn.dispatchEvent(new Event("click", { bubbles: true }));
        expect(
            controllerElement.querySelectorAll("input[name='person_select[]']"),
        ).toHaveLength(1);
        expect(personSearch.value).toBe("");

        const badge = controllerElement.querySelector(
            "#uploadModalTag_selectedPersons .badge",
        );
        badge.dispatchEvent(new Event("click", { bubbles: true }));

        expect(
            controllerElement.querySelectorAll("input[name='person_select[]']"),
        ).toHaveLength(0);
    });

    test("clear persons removes badges and hidden person inputs", async () => {
        await mount({
            prefix: "uploadModalTag",
            initialPersons: [{ id: 5, label: "Clear Me" }],
        });

        const clearBtn = controllerElement.querySelector(
            "button[id$='clear_persons_btn']",
        );
        clearBtn.dispatchEvent(new Event("click", { bubbles: true }));

        expect(
            controllerElement.querySelectorAll("input[name='person_select[]']"),
        ).toHaveLength(0);
        expect(
            controllerElement.querySelector("#uploadModalTag_selectedPersons")
                .innerHTML,
        ).toBe("");
    });

    test("simple lookup selects hidden id and clears when query is too short", async () => {
        window.fetch.mockImplementation(async (url) => {
            if (String(url).includes("/admin/tag-lookups/opponents")) {
                return {
                    ok: true,
                    json: async () => ({
                        opponents: [{ id: 9, label: "Murray State" }],
                    }),
                };
            }

            return {
                ok: true,
                json: async () => ({
                    games: [],
                    opponents: [],
                    persons: [],
                    rosters: [],
                    sites: [],
                }),
            };
        });

        await mount({ prefix: "uploadModalTag" });

        const opponentSearch = controllerElement.querySelector(
            "input[name='opponent_search']",
        );
        const opponentHidden = controllerElement.querySelector(
            "input[name='opponent_select']",
        );

        opponentSearch.value = "Murray State";
        opponentSearch.dispatchEvent(new Event("input", { bubbles: true }));
        await wait(260);
        await flush();

        expect(opponentHidden.value).toBe("9");

        opponentSearch.value = "M";
        opponentSearch.dispatchEvent(new Event("input", { bubbles: true }));
        await flush();

        expect(opponentHidden.value).toBe("");
    });

    test("simple lookup failure clears datalist options", async () => {
        window.fetch.mockImplementation(async (url) => {
            if (String(url).includes("/admin/tag-lookups/sites")) {
                throw new Error("lookup failed");
            }

            return {
                ok: true,
                json: async () => ({
                    games: [],
                    opponents: [],
                    persons: [],
                    rosters: [],
                    sites: [],
                }),
            };
        });

        await mount({ prefix: "uploadModalTag" });

        const siteSearch = controllerElement.querySelector(
            "input[name='site_search']",
        );
        const sitesList = controllerElement.querySelector(
            "datalist[id$='sitesList']",
        );

        siteSearch.value = "Mu";
        siteSearch.dispatchEvent(new Event("input", { bubbles: true }));
        await wait(260);
        await flush();

        expect(sitesList.querySelectorAll("option")).toHaveLength(0);
    });

    test("fetchLookup populates options and throws on non-ok responses", async () => {
        await mount({ prefix: "uploadModalTag" });
        const controller = application.controllers.find(
            (c) => c.identifier === "tag-selection",
        );

        window.fetch = jest.fn().mockResolvedValueOnce({
            ok: true,
            json: async () => ({ persons: [{ id: 12 }] }),
        });
        await controller.fetchLookup("persons", "ab");

        const options = controllerElement.querySelectorAll(
            "datalist[id$='personsList'] option",
        );
        expect(options).toHaveLength(1);
        expect(options[0].value).toBe("12");

        window.fetch = jest.fn().mockResolvedValueOnce({
            ok: false,
            status: 500,
        });
        await expect(controller.fetchLookup("persons", "ab")).rejects.toThrow(
            "Lookup persons failed status 500",
        );
    });

    test("renders selected game badge and clear button resets game selection", async () => {
        window.fetch.mockImplementation(async (url) => {
            if (String(url).includes("/admin/tag-lookups/games")) {
                return {
                    ok: true,
                    json: async () => ({
                        games: [
                            {
                                id: 22,
                                label: "State at Murray",
                                team_season_id: 110,
                            },
                        ],
                    }),
                };
            }

            return {
                ok: true,
                json: async () => ({
                    games: [],
                    opponents: [],
                    persons: [],
                    rosters: [],
                    sites: [],
                }),
            };
        });

        await mount({ prefix: "uploadModalTag" });

        const teamSeasonSelect = controllerElement.querySelector(
            "select[name='teamseason_select']",
        );
        const gameSearch = controllerElement.querySelector(
            "input[name='game_search']",
        );
        const gameHidden = controllerElement.querySelector(
            "input[name='game_select']",
        );

        teamSeasonSelect.value = "110";
        teamSeasonSelect.dispatchEvent(new Event("change", { bubbles: true }));

        gameSearch.value = "State";
        gameSearch.dispatchEvent(new Event("input", { bubbles: true }));
        await wait(260);
        await flush();

        gameSearch.value = "State at Murray";
        gameSearch.dispatchEvent(new Event("change", { bubbles: true }));

        expect(gameHidden.value).toBe("22");

        const clearBtn = controllerElement.querySelector(
            "#uploadModalTag_selectedGame button",
        );
        clearBtn.dispatchEvent(new Event("click", { bubbles: true }));

        expect(gameHidden.value).toBe("");
        expect(gameSearch.value).toBe("");
    });

    test("populateRostersForPerson clears options on request failure", async () => {
        await mount({ prefix: "uploadModalTag" });
        const controller = application.controllers.find(
            (c) => c.identifier === "tag-selection",
        );
        const rosterSelect = controllerElement.querySelector(
            "select[name='roster_select']",
        );

        const seeded = document.createElement("option");
        seeded.value = "99";
        seeded.textContent = "Seeded";
        rosterSelect.appendChild(seeded);

        window.fetch = jest.fn().mockRejectedValue(new Error("boom"));
        controller.populateRostersForPerson(42);
        await wait(0);
        await flush();

        expect(rosterSelect.options).toHaveLength(1);
        expect(rosterSelect.value).toBe("");
    });

    test("disconnect clears debounce timers", async () => {
        await mount({ prefix: "uploadModalTag" });
        const controller = application.controllers.find(
            (c) => c.identifier === "tag-selection",
        );

        const clearTimeoutSpy = jest.spyOn(global, "clearTimeout");
        controller.personDebounce = setTimeout(() => {}, 1000);
        controller.gameDebounce = setTimeout(() => {}, 1000);

        controller.disconnect();

        expect(clearTimeoutSpy).toHaveBeenCalledTimes(2);
        expect(controller.personDebounce).toBeNull();
        expect(controller.gameDebounce).toBeNull();
    });
});
