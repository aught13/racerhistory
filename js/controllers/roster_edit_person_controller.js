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
        if (!this.hasSelectTarget) {
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
        if (this.searchInput && this.boundInput) {
            this.searchInput.removeEventListener("input", this.boundInput);
        }

        if (this.debounceTimer) {
            window.clearTimeout(this.debounceTimer);
            this.debounceTimer = null;
        }
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

    handleSearchInput(event) {
        const query = event.target.value.trim();
        if (query === this.lastQuery) {
            return;
        }

        this.lastQuery = query;
        if (this.debounceTimer) {
            window.clearTimeout(this.debounceTimer);
        }

        this.debounceTimer = window.setTimeout(() => {
            this.search(query);
        }, DEBOUNCE_MS);
    }

    search(query) {
        if (!this.hasSearchUrlValue || query.length < MIN_QUERY_LENGTH) {
            return;
        }

        fetch(`${this.searchUrlValue}?q=${encodeURIComponent(query)}`, {
            credentials: "same-origin",
            headers: { "X-Requested-With": "XMLHttpRequest" },
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success || !Array.isArray(data.results)) {
                    return;
                }

                this.replaceOptions(data.results);
            })
            .catch(() => {
                // Keep existing options on transient network errors.
            });
    }

    replaceOptions(results) {
        const currentValue = this.selectTarget.value;
        const preserved = currentValue
            ? Array.from(this.selectTarget.options).find(
                  (option) => option.value === currentValue,
              )
            : null;

        this.selectTarget.innerHTML = "";

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
