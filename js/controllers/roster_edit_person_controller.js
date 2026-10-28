import { Controller } from "@hotwired/stimulus";

const DEBOUNCE_MS = 300;
const MIN_QUERY_LENGTH = 2;

export default class extends Controller {
    static targets = ["select"];

    static values = {
        searchUrl: String,
        currentId: Number,
        currentLabel: String,
    };

    connect() {
        this.isConnected = true;
        this.initialize();
    }

    initialize() {
        if (!this.hasSelectTarget || this.boundInput) {
            return;
        }
        this.debounceTimer = null;
        this.lastQuery = "";
        this.boundInput = (event) => this.handleSearchInput(event);

        this.injectSearchInput();
        this.ensureCurrentOption();

        if (this.searchInput) {
            this.searchInput.addEventListener("input", this.boundInput);
        }
    }

    disconnect() {
        this.isConnected = false;
        this.cleanup(true);
    }

    beforeCache() {
        this.cleanup(true);
    }

    afterCache() {
        if (this.isConnected) {
            this.initialize();
        }
    }

    cleanup(removeSearchWrapper) {
        if (this.searchInput && this.boundInput) {
            this.searchInput.removeEventListener("input", this.boundInput);
        }
        this.boundInput = null;

        if (this.debounceTimer !== null) {
            window.clearTimeout(this.debounceTimer);
            this.debounceTimer = null;
        }
        this.activeRequest?.abort();
        this.activeRequest = null;

        if (removeSearchWrapper && this.searchInput) {
            const wrapper = this.searchInput.closest(".dynamic-person-wrapper");
            if (wrapper?.contains(this.selectTarget)) {
                wrapper.parentNode.insertBefore(this.selectTarget, wrapper);
                wrapper.remove();
            }
            this.searchInput = null;
        }
        this.lastQuery = "";
    }

    injectSearchInput() {
        const parent = this.selectTarget.parentNode;
        if (!parent) {
            return;
        }

        const existingWrapper = parent.querySelector(".dynamic-person-wrapper");
        if (existingWrapper) {
            this.searchInput = existingWrapper.querySelector(
                ".roster-person-filter",
            );
            return;
        }

        const wrapper = document.createElement("div");
        wrapper.className = "dynamic-person-wrapper";

        const input = document.createElement("input");
        input.type = "text";
        input.className = "form-control mb-1 roster-person-filter";
        input.placeholder = "Search persons...";

        parent.insertBefore(wrapper, this.selectTarget);
        wrapper.appendChild(input);
        wrapper.appendChild(this.selectTarget);

        this.searchInput = input;
    }

    ensureCurrentOption() {
        const currentId =
            this.hasCurrentIdValue && this.currentIdValue > 0
                ? String(this.currentIdValue)
                : "";
        const currentLabel = this.hasCurrentLabelValue
            ? this.currentLabelValue
            : "";

        if (!currentId || !currentLabel) {
            return;
        }

        const existing = Array.from(this.selectTarget.options).find(
            (option) => option.value === currentId,
        );
        if (!existing) {
            const option = new window.Option(
                currentLabel,
                currentId,
                true,
                true,
            );
            this.selectTarget.add(option);
        }

        this.selectTarget.value = currentId;
    }

    popupOptionAdded(event) {
        const detail = event.detail;
        if (
            detail?.targetSelectId !== this.selectTarget.id ||
            detail.newOption?.value == null
        ) {
            return;
        }

        const value = String(detail.newOption.value);
        let option = Array.from(this.selectTarget.options).find(
            (candidate) => candidate.value === value,
        );
        if (!option) {
            option = this.selectTarget.ownerDocument.createElement("option");
            option.value = value;
            this.selectTarget.add(option);
        }
        option.textContent = String(detail.newOption.text ?? value);
        this.selectTarget.value = value;
        this.selectTarget.dispatchEvent(new Event("change", { bubbles: true }));
    }

    handleSearchInput(event) {
        const query = event.target.value.trim();
        if (query === this.lastQuery) {
            return;
        }

        this.lastQuery = query;
        if (this.debounceTimer) {
            window.clearTimeout(this.debounceTimer);
        }
        this.activeRequest?.abort();
        this.activeRequest = null;

        this.debounceTimer = window.setTimeout(() => {
            this.search(query);
        }, DEBOUNCE_MS);
    }

    search(query) {
        if (!this.hasSearchUrlValue) {
            return;
        }
        if (query.length < MIN_QUERY_LENGTH) {
            this.activeRequest?.abort();
            this.activeRequest = null;
            return;
        }

        const request = new AbortController();
        this.activeRequest = request;

        fetch(`${this.searchUrlValue}?q=${encodeURIComponent(query)}`, {
            credentials: "same-origin",
            headers: { "X-Requested-With": "XMLHttpRequest" },
            signal: request.signal,
        })
            .then((response) => response.json())
            .then((data) => {
                if (this.activeRequest !== request) {
                    return;
                }
                if (!data.success || !Array.isArray(data.results)) {
                    return;
                }

                this.replaceOptions(data.results);
            })
            .catch((error) => {
                if (
                    error?.name !== "AbortError" &&
                    this.activeRequest === request
                ) {
                    console.warn("Roster person search failed", error);
                }
            })
            .finally(() => {
                if (this.activeRequest === request) {
                    this.activeRequest = null;
                }
            });
    }

    replaceOptions(results) {
        const currentValue = this.selectTarget.value;
        const preserved = currentValue
            ? Array.from(this.selectTarget.options).find(
                  (option) => option.value === currentValue,
              )
            : null;

        this.selectTarget.replaceChildren();

        const emptyOption = new window.Option("(Select a person)", "");
        this.selectTarget.add(emptyOption);

        if (preserved && currentValue !== "") {
            this.selectTarget.add(
                new window.Option(preserved.text, preserved.value),
            );
        }

        results.forEach((result) => {
            this.selectTarget.add(
                new window.Option(result.text, String(result.value)),
            );
        });

        if (currentValue) {
            this.selectTarget.value = currentValue;
        }
    }
}
