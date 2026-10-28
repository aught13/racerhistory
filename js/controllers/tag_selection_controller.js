/* eslint-disable security/detect-object-injection */
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = [
        "selectedPersons",
        "personSearch",
        "personsList",
        "addPersonButton",
        "clearPersonsButton",
        "personHiddenInputs",
        "teamSeasonSelect",
        "gameSearch",
        "gamesList",
        "gameSelect",
        "selectedGame",
        "siteSearch",
        "sitesList",
        "siteSelect",
        "opponentSearch",
        "opponentsList",
        "opponentSelect",
        "rosterSelect",
    ];

    static values = {
        initialPersonsJson: String,
        initialRosterId: Number,
    };

    connect() {
        this.cleanupCallbacks = [];
        this.timers = new Set();
        this.activeRequests = new Map();
        this._maps = {
            persons: new Map(),
            opponents: new Map(),
            sites: new Map(),
            games: new Map(),
        };
        this.boundBeforeCache = () => this.resetEphemeralLookups();
        document.addEventListener("turbo:before-cache", this.boundBeforeCache);

        this.setupSelectionUi();
    }

    disconnect() {
        this.cleanupCallbacks.forEach((cleanup) => cleanup());
        this.cleanupCallbacks = [];

        document.removeEventListener(
            "turbo:before-cache",
            this.boundBeforeCache,
        );
        this.timers.forEach((timer) => window.clearTimeout(timer));
        this.timers.clear();
        this.personDebounce = null;
        this.gameDebounce = null;
        this.activeRequests.forEach((request) => request.abort());
        this.activeRequests.clear();
        Object.values(this._maps).forEach((map) => map.clear());
    }

    setupSelectionUi() {
        const initialPersons = this.parseInitialPersons();

        this.selectedPersonsEl = this.hasSelectedPersonsTarget
            ? this.selectedPersonsTarget
            : null;
        this.hiddenInputsContainer = this.hasPersonHiddenInputsTarget
            ? this.personHiddenInputsTarget
            : null;
        this.rosterSelect = this.hasRosterSelectTarget
            ? this.rosterSelectTarget
            : null;

        if (
            !this.selectedPersonsEl ||
            !this.hiddenInputsContainer ||
            !this.rosterSelect
        ) {
            return;
        }

        if (initialPersons.length > 0) {
            this.renderSelectedPersons(initialPersons);
        }

        this.connectLookups();
    }

    parseInitialPersons() {
        if (!this.initialPersonsJsonValue) {
            return [];
        }

        try {
            const parsed = JSON.parse(this.initialPersonsJsonValue);

            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    }

    connectLookups() {
        this.personSearch = this.targetOrNull("personSearch");
        this.personsList = this.targetOrNull("personsList");
        this.addPersonBtn = this.targetOrNull("addPersonButton");
        this.clearPersonsBtn = this.targetOrNull("clearPersonsButton");
        this.opponentSearch = this.targetOrNull("opponentSearch");
        this.opponentsList = this.targetOrNull("opponentsList");
        this.opponentHidden = this.targetOrNull("opponentSelect");
        this.siteSearch = this.targetOrNull("siteSearch");
        this.sitesList = this.targetOrNull("sitesList");
        this.siteHidden = this.targetOrNull("siteSelect");
        this.gameSearch = this.targetOrNull("gameSearch");
        this.gamesList = this.targetOrNull("gamesList");
        this.gameHidden = this.targetOrNull("gameSelect");
        this.selectedGameEl = this.targetOrNull("selectedGame");
        this.teamSeasonSelect = this.targetOrNull("teamSeasonSelect");

        if (
            this.personSearch &&
            this.personsList &&
            this.addPersonBtn &&
            this.clearPersonsBtn
        ) {
            this.bindPersonHandlers();
        }

        if (this.opponentSearch && this.opponentsList && this.opponentHidden) {
            this.bindSimpleLookup(
                "opponents",
                this.opponentSearch,
                this.opponentsList,
                this._maps.opponents,
                (id) => {
                    this.opponentHidden.value = id ? String(id) : "";
                },
            );
        }

        if (this.siteSearch && this.sitesList && this.siteHidden) {
            this.bindSimpleLookup(
                "sites",
                this.siteSearch,
                this.sitesList,
                this._maps.sites,
                (id) => {
                    this.siteHidden.value = id ? String(id) : "";
                },
            );
        }

        if (this.gameSearch && this.gamesList && this.gameHidden) {
            this.bindGameLookup();
        }

        if (this.teamSeasonSelect && this.gameSearch && this.gameHidden) {
            this.bindTeamSeasonChange();
        }
    }

    bindPersonHandlers() {
        this.bindEvent(this.personSearch, "input", () => {
            const q = String(this.personSearch.value || "").trim();

            if (this.personDebounce) {
                this.clearTimer(this.personDebounce);
            }

            if (q.length < 2) {
                this.personsList.replaceChildren();

                return;
            }

            this.personDebounce = this.setTimer(() => {
                this.fetchLookup("persons", q).catch(() => {
                    this.personsList.replaceChildren();
                });
            }, 250);
        });

        this.bindEvent(this.addPersonBtn, "click", (event) => {
            event.preventDefault();

            const label = String(this.personSearch.value || "").trim();
            if (!label) {
                return;
            }

            const id = this.findIdForLabel(this._maps.persons, label);
            if (!id) {
                return;
            }

            const exists = this.getSelectedPersonInputs().some(
                (input) => String(input.value) === String(id),
            );
            if (exists) {
                this.personSearch.value = "";

                return;
            }

            this.appendHiddenPersonInput(id);
            this.appendPersonBadge({ id, label });
            this.personSearch.value = "";

            this.updateRosterSelectionState();
        });

        this.bindEvent(this.clearPersonsBtn, "click", (event) => {
            event.preventDefault();

            this.selectedPersonsEl.replaceChildren();
            this.hiddenInputsContainer.replaceChildren();
            this.updateRosterSelectionState();
        });
    }

    bindSimpleLookup(kind, inputEl, datalistEl, map, onSelectId) {
        let debounce = null;

        this.bindEvent(inputEl, "input", () => {
            const q = String(inputEl.value || "").trim();

            if (debounce) {
                this.clearTimer(debounce);
            }

            if (q.length < 2) {
                datalistEl.replaceChildren();
                map.clear();
                onSelectId("");

                return;
            }

            debounce = this.setTimer(() => {
                this.fetchLookup(kind, q)
                    .then(() => {
                        const selectedId = this.findIdForLabel(
                            map,
                            inputEl.value.trim(),
                        );
                        onSelectId(selectedId || "");
                    })
                    .catch(() => {
                        datalistEl.replaceChildren();
                        map.clear();
                    });
            }, 220);
        });
    }

    bindGameLookup() {
        this.bindEvent(this.gameSearch, "input", () => {
            const q = String(this.gameSearch.value || "").trim();

            if (this.gameDebounce) {
                this.clearTimer(this.gameDebounce);
            }

            if (q.length < 2) {
                this.clearGameResults();
                this.clearGameSelection({ clearInput: false });

                return;
            }

            this.gameDebounce = this.setTimer(() => {
                this.searchGames(q).catch(() => {
                    this.clearGameResults();
                });
            }, 220);
        });

        this.bindEvent(this.gameSearch, "change", () => {
            this.applySelectedGameLabel(
                String(this.gameSearch.value || "").trim(),
            );
        });

        if (this.gameHidden.value && this.gameSearch.value) {
            this.renderSelectedGameBadge(String(this.gameSearch.value));
        }
    }

    bindTeamSeasonChange() {
        const sync = () => {
            const teamSeasonId = String(
                this.teamSeasonSelect.value || "",
            ).trim();
            const hasTeamSeason = teamSeasonId !== "";

            this.gameSearch.disabled = !hasTeamSeason;

            if (!hasTeamSeason) {
                this.clearGameResults();
                this.clearGameSelection({ clearInput: true });

                return;
            }

            const q = String(this.gameSearch.value || "").trim();
            if (q.length >= 2) {
                this.searchGames(q).catch(() => {
                    this.clearGameResults();
                });
            }
        };

        this.bindEvent(this.teamSeasonSelect, "change", sync);
        sync();
    }

    async fetchLookup(kind, q) {
        const url = `/admin/tag-lookups/${encodeURIComponent(kind)}?q=${encodeURIComponent(q)}`;
        const payload = await this.requestJson(url, kind);
        const items = payload[kind] || [];
        const datalist =
            kind === "persons"
                ? this.personsList
                : kind === "opponents"
                  ? this.opponentsList
                  : kind === "sites"
                    ? this.sitesList
                    : kind === "games"
                      ? this.gamesList
                      : null;

        if (datalist) {
            datalist.replaceChildren();
        }

        const map = this._maps[kind];
        if (map) {
            map.clear();
        }

        items.forEach((item) => {
            const label = item.label || String(item.id);
            if (datalist) {
                const option = document.createElement("option");
                option.value = label;
                datalist.appendChild(option);
            }
            if (map) {
                map.set(label, item.id);
            }
        });
    }

    async searchGames(query) {
        const teamSeasonId = String(this.teamSeasonSelect?.value || "").trim();
        let url = `/admin/tag-lookups/games?q=${encodeURIComponent(query)}`;
        if (teamSeasonId !== "") {
            url += `&teamseason_id=${encodeURIComponent(teamSeasonId)}`;
        }

        const data = await this.requestJson(url, "games");
        const items = Array.isArray(data.games) ? data.games : [];

        this.clearGameResults();
        items.forEach((item) => {
            const label = item.label || "";
            if (!label) {
                return;
            }

            const option = document.createElement("option");
            option.value = label;
            this.gamesList.appendChild(option);
            this._maps.games.set(label, {
                id: item.id,
                team_season_id: item.team_season_id,
            });
        });

        this.applySelectedGameLabel(String(this.gameSearch.value || "").trim());
    }

    applySelectedGameLabel(label) {
        if (!label) {
            return;
        }

        const match = this._maps.games.get(label);
        if (!match) {
            return;
        }

        this.gameHidden.value = String(match.id);
        this.renderSelectedGameBadge(label);
    }

    clearGameResults() {
        if (this.gamesList) {
            this.gamesList.replaceChildren();
        }
        this._maps.games.clear();
    }

    clearGameSelection({ clearInput }) {
        this.gameHidden.value = "";
        if (this.selectedGameEl) {
            this.selectedGameEl.replaceChildren();
        }
        if (clearInput) {
            this.gameSearch.value = "";
        }
    }

    renderSelectedGameBadge(label) {
        if (!this.selectedGameEl) {
            return;
        }

        this.selectedGameEl.replaceChildren();

        const badge = document.createElement("span");
        badge.className = "badge bg-primary me-1";
        badge.append(document.createTextNode(`${label} `));

        const clearButton = document.createElement("button");
        clearButton.type = "button";
        clearButton.className = "btn-close btn-close-white ms-1";
        clearButton.setAttribute("aria-label", "Clear");
        clearButton.addEventListener("click", (event) => {
            event.preventDefault();
            this.clearGameSelection({ clearInput: true });
        });

        badge.appendChild(clearButton);
        this.selectedGameEl.appendChild(badge);
    }

    renderSelectedPersons(selectedPersons) {
        this.selectedPersonsEl.replaceChildren();

        selectedPersons.forEach((person) => {
            const personId = Number(person.id);
            const personLabel = String(person.label || "").trim();
            if (!personId || !personLabel) {
                return;
            }

            const exists = this.getSelectedPersonInputs().some(
                (input) => String(input.value) === String(personId),
            );
            if (!exists) {
                this.appendHiddenPersonInput(personId);
            }

            this.appendPersonBadge({ id: personId, label: personLabel });
        });

        const preselectRosterId =
            this.initialRosterIdValue && this.initialRosterIdValue > 0
                ? this.initialRosterIdValue
                : null;
        this.updateRosterSelectionState(preselectRosterId);
    }

    appendHiddenPersonInput(id) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "person_select[]";
        input.value = String(id);
        this.hiddenInputsContainer.appendChild(input);
    }

    appendPersonBadge(person) {
        const badge = document.createElement("span");
        badge.className = "badge bg-secondary me-1 mb-1";
        badge.textContent = person.label;
        badge.title = `Remove ${person.label}`;
        badge.style.cursor = "pointer";

        badge.addEventListener("click", () => {
            const input = this.getSelectedPersonInputs().find(
                (item) => String(item.value) === String(person.id),
            );
            if (input) {
                input.remove();
            }
            badge.remove();

            this.updateRosterSelectionState();
        });

        this.selectedPersonsEl.appendChild(badge);
    }

    updateRosterSelectionState(preselectId = null) {
        const selectedInputs = this.getSelectedPersonInputs();

        if (selectedInputs.length === 1) {
            const personId = selectedInputs[0].value;
            this.rosterSelect.disabled = false;
            this.populateRostersForPerson(personId, preselectId || null);

            return;
        }

        this.rosterSelect.disabled = true;
        this.clearRosterOptions();
    }

    clearRosterOptions() {
        while (this.rosterSelect.options.length > 1) {
            this.rosterSelect.remove(1);
        }
        this.rosterSelect.value = "";
    }

    populateRostersForPerson(personId, preselectId = null) {
        const url = `/admin/tag-lookups/rosters?person_id=${encodeURIComponent(personId)}`;

        this.requestJson(url, "rosters")
            .then((data) => {
                this.clearRosterOptions();

                const rosters = Array.isArray(data.rosters) ? data.rosters : [];
                rosters.forEach((roster) => {
                    const option = document.createElement("option");
                    option.value = String(roster.id);
                    option.textContent = String(roster.label || "");
                    if (
                        preselectId &&
                        String(roster.id) === String(preselectId)
                    ) {
                        option.selected = true;
                    }
                    this.rosterSelect.appendChild(option);
                });
            })
            .catch(() => {
                this.clearRosterOptions();
            });
    }

    targetOrNull(name) {
        const hasTarget = `has${name[0].toUpperCase()}${name.slice(1)}Target`;
        const target = `${name}Target`;

        return this[hasTarget] ? this[target] : null;
    }

    async requestJson(url, key) {
        this.activeRequests.get(key)?.abort();
        const request = new AbortController();
        this.activeRequests.set(key, request);

        try {
            const response = await fetch(url, {
                credentials: "same-origin",
                signal: request.signal,
            });
            if (!response.ok) {
                const message =
                    key === "games"
                        ? "Game lookup"
                        : key === "rosters"
                          ? "Roster lookup"
                          : `Lookup ${key}`;
                throw new Error(`${message} failed status ${response.status}`);
            }

            return await response.json();
        } finally {
            if (this.activeRequests.get(key) === request) {
                this.activeRequests.delete(key);
            }
        }
    }

    setTimer(callback, delay) {
        const timer = window.setTimeout(() => {
            this.timers.delete(timer);
            callback();
        }, delay);
        this.timers.add(timer);

        return timer;
    }

    clearTimer(timer) {
        window.clearTimeout(timer);
        this.timers.delete(timer);
    }

    resetEphemeralLookups() {
        [
            ["personsList", "persons"],
            ["opponentsList", "opponents"],
            ["sitesList", "sites"],
            ["gamesList", "games"],
        ].forEach(([target, mapName]) => {
            const element = this.targetOrNull(target);
            element?.replaceChildren();
            this._maps[mapName].clear();
        });

        [this.personSearch, this.opponentSearch, this.siteSearch].forEach(
            (input) => {
                if (input) {
                    input.value = "";
                }
            },
        );
    }

    findIdForLabel(map, label) {
        if (!label) {
            return null;
        }

        if (map.has(label)) {
            return map.get(label);
        }

        const lowerLabel = label.toLowerCase();
        for (const [key, value] of map.entries()) {
            if (String(key).toLowerCase() === lowerLabel) {
                return value;
            }
        }

        return null;
    }

    getSelectedPersonInputs() {
        return Array.from(
            this.hiddenInputsContainer.querySelectorAll(
                "input[name='person_select[]']",
            ),
        );
    }

    bindEvent(element, eventName, handler) {
        element.addEventListener(eventName, handler);
        this.cleanupCallbacks.push(() => {
            element.removeEventListener(eventName, handler);
        });
    }
}
