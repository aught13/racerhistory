import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["input", "results", "selected", "hidden"];

    static values = {
        searchUrl: String,
    };

    initialize() {
        this.debounceTimer = null;
        this.searchRequest = null;
        this.isCaching = false;
    }

    connect() {
        this.isCaching = false;
    }

    disconnect() {
        this.cancelSearch();
    }

    search() {
        if (this.isCaching || !this.hasInputTarget || !this.hasResultsTarget) {
            return;
        }

        this.cancelSearch();
        const query = this.inputTarget.value.trim();
        if (query.length < 2) {
            this.resultsTarget.replaceChildren();
            return;
        }

        this.debounceTimer = window.setTimeout(() => {
            this.debounceTimer = null;
            this.runSearch(query);
        }, 300);
    }

    runSearch(query) {
        if (
            this.isCaching ||
            !this.hasInputTarget ||
            !this.hasResultsTarget ||
            !this.element.isConnected
        ) {
            return;
        }

        const request = new AbortController();
        this.searchRequest = request;
        fetch(`${this.searchUrlValue}?q=${encodeURIComponent(query)}`, {
            signal: request.signal,
            headers: { "X-Requested-With": "XMLHttpRequest" },
        })
            .then((response) => response.json())
            .then((data) => {
                if (
                    this.searchRequest !== request ||
                    this.inputTarget.value.trim() !== query
                ) {
                    return;
                }

                if (
                    !data?.success ||
                    !Array.isArray(data.results) ||
                    data.results.length === 0
                ) {
                    this.showMessage("No results", "text-muted");
                    return;
                }

                this.renderResults(data.results);
            })
            .catch((error) => {
                if (
                    error?.name !== "AbortError" &&
                    this.searchRequest === request
                ) {
                    this.showMessage("Error", "text-danger");
                }
            })
            .finally(() => {
                if (this.searchRequest === request) {
                    this.searchRequest = null;
                }
            });
    }

    cancelSearch() {
        if (this.debounceTimer !== null) {
            window.clearTimeout(this.debounceTimer);
            this.debounceTimer = null;
        }

        if (this.searchRequest) {
            this.searchRequest.abort();
            this.searchRequest = null;
        }
    }

    beforeCache() {
        this.isCaching = true;
        this.cancelSearch();
        if (this.hasInputTarget) {
            this.inputTarget.value = "";
        }
        if (this.hasResultsTarget) {
            this.resultsTarget.replaceChildren();
        }
    }

    handleBirthPlaceAdded(event) {
        const place = event.detail?.place;
        if (!place?.id) {
            return;
        }

        const label = `${place.place_city || ""}${place.place_state ? `, ${place.place_state}` : ""}`;
        this.setSelected(place.id, label);
    }

    renderResults(results) {
        if (!this.hasResultsTarget) {
            return;
        }

        if (results.length === 0) {
            this.showMessage("No results", "text-muted");
            return;
        }

        const list = document.createElement("div");
        list.className =
            "list-group list-group-flush overflow-auto shadow place-search-results";
        list.style.maxHeight = "200px";

        results.forEach((result) => {
            const city = String(result.place_city ?? "");
            const state = String(result.place_state ?? "");
            const label = `${city}${state ? `, ${state}` : ""}`;
            const button = document.createElement("button");
            button.type = "button";
            button.className =
                "list-group-item list-group-item-action py-1 small place-search-result";
            button.dataset.id = String(result.id ?? "");
            button.dataset.text = label;
            button.textContent = label;
            list.append(button);
        });

        this.resultsTarget.replaceChildren(list);
    }

    setSelected(id, text) {
        if (!this.hasHiddenTarget || !this.hasSelectedTarget) {
            return;
        }

        this.hiddenTarget.value = String(id);
        const badge = document.createElement("span");
        badge.className = "badge bg-primary me-1";
        badge.append(document.createTextNode(String(text ?? "")));

        const clearButton = document.createElement("button");
        clearButton.type = "button";
        clearButton.className =
            "btn-close btn-close-white ms-1 clear-birth-place";
        clearButton.setAttribute("aria-label", "Clear birth place");
        badge.append(" ", clearButton);
        this.selectedTarget.replaceChildren(badge);

        if (this.hasResultsTarget) {
            this.resultsTarget.replaceChildren();
        }
        if (this.hasInputTarget) {
            this.inputTarget.value = "";
        }

        this.cancelSearch();
    }

    clearSelection() {
        if (!this.hasSelectedTarget || !this.hasHiddenTarget) {
            return;
        }

        this.hiddenTarget.value = "";
        const message = document.createElement("span");
        message.className = "text-muted fst-italic";
        message.textContent = "None selected";
        this.selectedTarget.replaceChildren(message);
    }

    handleClick(event) {
        const target = event.target;
        if (target.closest(".clear-birth-place")) {
            this.clearSelection();
            return;
        }

        const resultButton = target.closest(".place-search-result");
        if (resultButton) {
            this.setSelected(
                resultButton.dataset.id,
                resultButton.dataset.text,
            );
        }
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

        this.resultsTarget.replaceChildren();
    }

    showMessage(message, textClass) {
        const element = document.createElement("div");
        element.className = `${textClass} small`;
        element.textContent = message;
        this.resultsTarget.replaceChildren(element);
    }
}
