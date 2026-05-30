import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["input", "results", "selected", "hidden"];

    static values = {
        searchUrl: String,
    };

    connect() {
        this.debounceTimer = null;
        this.handleDocumentClickBound = this.handleDocumentClick.bind(this);
        document.addEventListener("click", this.handleDocumentClickBound);

        this.handleBirthPlaceAdded = (data) => {
            const place = data?.place;
            if (!place?.id) {
                return;
            }

            const label = `${place.place_city || ""}${place.place_state ? `, ${place.place_state}` : ""}`;
            this.setSelected(place.id, label);
        };

        window.handleBirthPlaceAdded = this.handleBirthPlaceAdded;
        this.bindClearButton();
    }

    disconnect() {
        document.removeEventListener("click", this.handleDocumentClickBound);

        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = null;
        }

        if (window.handleBirthPlaceAdded === this.handleBirthPlaceAdded) {
            delete window.handleBirthPlaceAdded;
        }
    }

    search() {
        if (!this.hasInputTarget || !this.hasResultsTarget) {
            return;
        }

        const query = this.inputTarget.value.trim();
        if (query.length < 2) {
            this.resultsTarget.innerHTML = "";
            return;
        }

        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        this.debounceTimer = setTimeout(async () => {
            try {
                const response = await fetch(
                    `${this.searchUrlValue}?q=${encodeURIComponent(query)}`,
                    {
                        headers: {
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    },
                );

                const data = await response.json();
                if (!data.success || !data.results?.length) {
                    this.resultsTarget.innerHTML =
                        '<div class="text-muted small">No results</div>';
                    return;
                }

                this.renderResults(data.results);
            } catch {
                this.resultsTarget.innerHTML =
                    '<div class="text-danger small">Error</div>';
            }
        }, 300);
    }

    renderResults(results) {
        const html = [
            '<div class="list-group list-group-flush" style="max-height:200px;overflow-y:auto;box-shadow:0 2px 8px rgba(0,0,0,.15)">',
            ...results.map((result) => {
                const label = `${result.place_city}${result.place_state ? `, ${result.place_state}` : ""}`;
                const escapedLabel = label.replace(/"/g, "&quot;");
                return `<button type="button" class="list-group-item list-group-item-action py-1 small" data-id="${result.id}" data-text="${escapedLabel}">${label}</button>`;
            }),
            "</div>",
        ].join("");

        this.resultsTarget.innerHTML = html;
        this.resultsTarget.querySelectorAll("button").forEach((button) => {
            button.addEventListener("click", () => {
                this.setSelected(button.dataset.id, button.dataset.text);
            });
        });
    }

    setSelected(id, text) {
        if (!this.hasHiddenTarget || !this.hasSelectedTarget) {
            return;
        }

        this.hiddenTarget.value = id;
        this.selectedTarget.innerHTML = `<span class="badge bg-primary me-1">${text} <button type="button" class="btn-close btn-close-white ms-1 clear-birth-place" aria-label="Clear" style="font-size:.5em;vertical-align:middle"></button></span>`;

        if (this.hasResultsTarget) {
            this.resultsTarget.innerHTML = "";
        }
        if (this.hasInputTarget) {
            this.inputTarget.value = "";
        }

        this.bindClearButton();
    }

    bindClearButton() {
        if (!this.hasSelectedTarget || !this.hasHiddenTarget) {
            return;
        }

        const clearButton =
            this.selectedTarget.querySelector(".clear-birth-place");
        if (!clearButton) {
            return;
        }

        clearButton.addEventListener("click", () => {
            this.hiddenTarget.value = "";
            this.selectedTarget.innerHTML =
                '<span class="text-muted fst-italic">None selected</span>';
        });
    }

    handleDocumentClick(event) {
        if (!this.hasInputTarget || !this.hasResultsTarget) {
            return;
        }

        if (
            this.inputTarget.contains(event.target) ||
            this.resultsTarget.contains(event.target)
        ) {
            return;
        }

        this.resultsTarget.innerHTML = "";
    }
}
