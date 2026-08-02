/* eslint-disable security/detect-object-injection */
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        initialPersonsJson: String,
        initialRosterId: Number,
    };

    connect() {
        this.cleanupCallbacks = [];
        this._maps = {
            persons: new Map(),
            opponents: new Map(),
            sites: new Map(),
            games: new Map(),
        };

        this.setupSelectionUi();
    }

    disconnect() {
        this.cleanupCallbacks.forEach((cleanup) => cleanup());
        this.cleanupCallbacks = [];

        if (this.personDebounce) {
            clearTimeout(this.personDebounce);
            this.personDebounce = null;
        }
        if (this.gameDebounce) {
            clearTimeout(this.gameDebounce);
            this.gameDebounce = null;
        }
    }

    setupSelectionUi() {
        const initialPersons = this.parseInitialPersons();

        this.selectedPersonsEl = this.findByIdSuffix("selectedPersons");
        this.hiddenInputsContainer = this.findByIdSuffix(
            "person_hidden_inputs",
        );
        this.rosterSelect = this.findByName("roster_select");

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
        this.personSearch = this.findByName("person_search");
        this.personsList = this.findByIdSuffix("personsList");
        this.addPersonBtn = this.findByIdSuffix("add_person_btn");
        this.clearPersonsBtn = this.findByIdSuffix("clear_persons_btn");

        this.opponentSearch = this.findByName("opponent_search");
        this.opponentsList = this.findByIdSuffix("opponentsList");
        this.opponentHidden = this.findByName("opponent_select");

        this.siteSearch = this.findByName("site_search");
        this.sitesList = this.findByIdSuffix("sitesList");
        this.siteHidden = this.findByName("site_select");

        this.gameSearch = this.findByName("game_search");
        this.gamesList = this.findByIdSuffix("gamesList");
        this.gameHidden = this.findByName("game_select");
        this.selectedGameEl = this.findByIdSuffix("selectedGame");

        this.teamSeasonSelect = this.findByName("teamseason_select");

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
                this.opponentSearch,
                this.opponentsList,
                "/admin/tag-lookups/opponents",
                this._maps.opponents,
                (id) => {
                    this.opponentHidden.value = id ? String(id) : "";
                },
            );
        }

        if (this.siteSearch && this.sitesList && this.siteHidden) {
            this.bindSimpleLookup(
                this.siteSearch,
                this.sitesList,
                "/admin/tag-lookups/sites",
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
                clearTimeout(this.personDebounce);
            }

            if (q.length < 2) {
                this.personsList.innerHTML = "";

                return;
            }

            this.personDebounce = setTimeout(() => {
                this.fetchLookup("persons", q).catch(() => {
                    this.personsList.innerHTML = "";
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

            this.selectedPersonsEl.innerHTML = "";
            this.hiddenInputsContainer.innerHTML = "";
            this.updateRosterSelectionState();
        });
    }

    bindSimpleLookup(inputEl, datalistEl, urlBase, map, onSelectId) {
        let debounce = null;

        this.bindEvent(inputEl, "input", () => {
            const q = String(inputEl.value || "").trim();

            if (debounce) {
                clearTimeout(debounce);
            }

            if (q.length < 2) {
                datalistEl.innerHTML = "";
                map.clear();
                onSelectId("");

                return;
            }

            debounce = setTimeout(() => {
                fetch(`${urlBase}?q=${encodeURIComponent(q)}`, {
                    credentials: "same-origin",
                })
                    .then((response) => response.json())
                    .then((data) => {
                        const key = Object.keys(data).find((k) =>
                            Array.isArray(data[k]),
                        );
                        const items = key ? data[key] : [];

                        datalistEl.innerHTML = "";
                        map.clear();

                        items.forEach((item) => {
                            const label = item.label || "";
                            const option = document.createElement("option");
                            option.value = label;
                            datalistEl.appendChild(option);
                            map.set(label, item.id);
                        });

                        const selectedId = this.findIdForLabel(
                            map,
                            inputEl.value.trim(),
                        );
                        onSelectId(selectedId || "");
                    })
                    .catch(() => {
                        datalistEl.innerHTML = "";
                        map.clear();
                    });
            }, 220);
        });
    }

    bindGameLookup() {
        this.bindEvent(this.gameSearch, "input", () => {
            const q = String(this.gameSearch.value || "").trim();

            if (this.gameDebounce) {
                clearTimeout(this.gameDebounce);
            }

            if (q.length < 2) {
                this.clearGameResults();
                this.clearGameSelection({ clearInput: false });

                return;
            }

            this.gameDebounce = setTimeout(() => {
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
        const response = await fetch(url, { credentials: "same-origin" });
        if (!response.ok) {
            throw new Error(`Lookup ${kind} failed status ${response.status}`);
        }

        const payload = await response.json();
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
            datalist.innerHTML = "";
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

        const response = await fetch(url, { credentials: "same-origin" });
        if (!response.ok) {
            throw new Error(`Game lookup failed status ${response.status}`);
        }

        const data = await response.json();
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
            this.gamesList.innerHTML = "";
        }
        this._maps.games.clear();
    }

    clearGameSelection({ clearInput }) {
        this.gameHidden.value = "";
        if (this.selectedGameEl) {
            this.selectedGameEl.innerHTML = "";
        }
        if (clearInput) {
            this.gameSearch.value = "";
        }
    }

    renderSelectedGameBadge(label) {
        if (!this.selectedGameEl) {
            return;
        }

        this.selectedGameEl.innerHTML = "";

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
        this.selectedPersonsEl.innerHTML = "";

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

        fetch(url, { credentials: "same-origin" })
            .then((response) => response.json())
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

    findByName(name) {
        return (
            this.element.querySelector(`[name="${name}"]`) ||
            this.findByIdSuffix(name)
        );
    }

    findByIdSuffix(suffix) {
        return (
            this.element.querySelector(`#${suffix}`) ||
            this.element.querySelector(`[id$="_${suffix}"]`) ||
            this.element.querySelector(`[id$="${suffix}"]`)
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
