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
            initialPersonsJson,
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
});
