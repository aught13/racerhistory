import { Controller } from "@hotwired/stimulus";

/**
 * Stimulus controller for tag selection form initialization.
 * Handles dynamic person/roster/game selection with API lookups.
 *
 * Data attributes:
 * - data-initial-persons-json: JSON array of {id, label} objects
 * - data-initial-roster-id: Initial roster ID to pre-select
 */
export default class extends Controller {
    static values = {
        initialPersonsJson: String,
        initialRosterId: Number,
    };

    connect() {
        console.log("[TagSelectionController] Stimulus controller connected");
        this.initialize();
    }

    disconnect() {
        console.log(
            "[TagSelectionController] Stimulus controller disconnected",
        );
    }

    initialize() {
        console.log("[TagSelectionController] initialize() called");

        // Parse initial data from attributes
        try {
            let initialPersons = [];
            if (this.initialPersonsJsonValue) {
                initialPersons = JSON.parse(this.initialPersonsJsonValue);
                console.log(
                    "[TagSelectionController] Parsed initial persons:",
                    initialPersons,
                );
            }

            // Get references to key elements (elements must be inside the controller element)
            this.selectedPersonsEl =
                this.element.querySelector("#selectedPersons");
            this.hiddenInputsContainer = this.element.querySelector(
                "#person_hidden_inputs",
            );
            this.rosterSelect = this.element.querySelector("#roster_select");

            if (
                !this.selectedPersonsEl ||
                !this.hiddenInputsContainer ||
                !this.rosterSelect
            ) {
                console.warn(
                    "[TagSelectionController] Missing required elements",
                );
                return;
            }

            // Render initial badges
            if (Array.isArray(initialPersons) && initialPersons.length > 0) {
                this.renderSelectedPersons(initialPersons);
            }
        } catch (error) {
            console.error(
                "[TagSelectionController] Error during initialization:",
                error,
            );
        }
    }

    renderSelectedPersons(selectedPersons) {
        console.log(
            "[TagSelectionController] renderSelectedPersons() called with:",
            selectedPersons,
        );

        if (!this.selectedPersonsEl || !this.hiddenInputsContainer) {
            console.warn(
                "[TagSelectionController] Missing elements for rendering",
            );
            return;
        }

        // Clear existing badges (keep hidden inputs to avoid form field loss)
        this.selectedPersonsEl.innerHTML = "";

        selectedPersons.forEach((person) => {
            console.log("[TagSelectionController] Processing person:", person);

            // Create badge
            const badge = document.createElement("span");
            badge.className = "badge bg-secondary";
            badge.textContent = person.label;
            badge.title = `Remove ${person.label}`;
            this.selectedPersonsEl.appendChild(badge);

            // If this person has a roster, fetch and pre-select it
            if (this.initialRosterIdValue && this.initialRosterIdValue > 0) {
                this.populateRostersForPerson(
                    person.id,
                    this.initialRosterIdValue,
                );
            }
        });
    }

    populateRostersForPerson(personId, preselectId) {
        const url = `/admin/tag-lookups/rosters?person_id=${personId}`;
        console.log("[TagSelectionController] Fetching rosters from:", url);

        fetch(url)
            .then((r) => r.json())
            .then((data) => {
                console.log(
                    "[TagSelectionController] Received roster data:",
                    data,
                );

                if (!this.rosterSelect) {
                    console.warn(
                        "[TagSelectionController] roster_select element missing",
                    );
                    return;
                }

                // Clear existing options except first
                while (this.rosterSelect.options.length > 1) {
                    this.rosterSelect.remove(1);
                }

                // Add new options
                if (data.rosters && Array.isArray(data.rosters)) {
                    data.rosters.forEach((roster) => {
                        const option = document.createElement("option");
                        option.value = roster.id;
                        option.textContent = roster.label;
                        if (roster.id === preselectId) {
                            console.log(
                                "[TagSelectionController] Pre-selecting roster:",
                                roster,
                            );
                            option.selected = true;
                        }
                        this.rosterSelect.appendChild(option);
                    });
                }
            })
            .catch((e) =>
                console.error(
                    "[TagSelectionController] Error fetching rosters:",
                    e,
                ),
            );
    }
}
