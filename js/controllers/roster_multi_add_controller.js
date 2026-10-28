import { Controller } from "@hotwired/stimulus";

const DEBOUNCE_MS = 300;
const MIN_QUERY_LENGTH = 2;

export default class extends Controller {
    static targets = ["rows"];

    initialize() {
        this.searchTimers = new Map();
        this.searchRequests = new Map();
    }

    connect() {
        this.updateRemoveButtons();
    }

    disconnect() {
        this.cancelAllSearches();
    }

    handleRowsClick(event) {
        const target = event.target;
        const row = target.closest(".roster-row");

        if (!row) {
            return;
        }

        const clearButton = target.closest(".roster-clear-person");
        if (clearButton) {
            this.clearPersonSelection(row);
            return;
        }

        const resultButton = target.closest(".roster-search-result");
        if (resultButton) {
            this.selectPerson(
                row,
                resultButton.dataset.id,
                resultButton.dataset.text,
            );
            return;
        }

        const removeButton = target.closest(".remove-row-btn");
        if (!removeButton) {
            return;
        }

        row.querySelectorAll(".roster-person-search").forEach((input) => {
            this.cancelSearch(input);
        });
        row.remove();
        this.reindexRows();
        this.updateRemoveButtons();
    }

    handleDocumentClick(event) {
        const target = event.target;
        this.rowsTarget.querySelectorAll(".roster-row").forEach((row) => {
            if (!row.contains(target)) {
                row.querySelector(".roster-person-results")?.replaceChildren();
            }
        });
    }

    handlePersonAdded(event) {
        const option = event.detail?.newOption;
        if (!option?.value || !option?.text) {
            return;
        }

        this.autoSelectNewPerson(String(option.value), option.text);
    }

    searchPerson(event) {
        const searchInput = event.target.closest(".roster-person-search");
        if (!searchInput || !this.rowsTarget.contains(searchInput)) {
            return;
        }

        const row = searchInput.closest(".roster-row");
        if (!row) {
            return;
        }

        const resultsContainer = row.querySelector(".roster-person-results");
        if (!resultsContainer) {
            return;
        }

        this.cancelSearch(searchInput);
        const query = searchInput.value.trim();
        const searchUrl = this.rowsTarget.dataset.personSearchUrl;
        if (query.length < MIN_QUERY_LENGTH || !searchUrl) {
            resultsContainer.replaceChildren();
            return;
        }

        const timer = window.setTimeout(() => {
            this.searchTimers.delete(searchInput);
            this.runSearch(
                searchInput,
                row,
                resultsContainer,
                searchUrl,
                query,
            );
        }, DEBOUNCE_MS);
        this.searchTimers.set(searchInput, timer);
    }

    runSearch(searchInput, row, resultsContainer, searchUrl, query) {
        if (!this.element.isConnected || !row.isConnected) {
            return;
        }

        const request = new AbortController();
        this.searchRequests.set(searchInput, request);

        fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, {
            signal: request.signal,
            headers: { "X-Requested-With": "XMLHttpRequest" },
        })
            .then((response) => response.json())
            .then((data) => {
                if (
                    this.searchRequests.get(searchInput) !== request ||
                    searchInput.value.trim() !== query
                ) {
                    return;
                }

                if (!data?.success || !Array.isArray(data.results)) {
                    this.showSearchMessage(
                        resultsContainer,
                        "No results",
                        "text-muted",
                    );
                    return;
                }

                this.renderResults(data.results, resultsContainer);
            })
            .catch((error) => {
                if (
                    error?.name !== "AbortError" &&
                    this.searchRequests.get(searchInput) === request
                ) {
                    this.showSearchMessage(
                        resultsContainer,
                        "Network error",
                        "text-danger",
                    );
                }
            })
            .finally(() => {
                if (this.searchRequests.get(searchInput) === request) {
                    this.searchRequests.delete(searchInput);
                }
            });
    }

    cancelSearch(searchInput) {
        const timer = this.searchTimers.get(searchInput);
        if (timer !== undefined) {
            window.clearTimeout(timer);
            this.searchTimers.delete(searchInput);
        }

        const request = this.searchRequests.get(searchInput);
        if (request) {
            request.abort();
            this.searchRequests.delete(searchInput);
        }
    }

    cancelAllSearches() {
        if (!this.searchTimers || !this.searchRequests) {
            return;
        }

        const searchInputs = new Set([
            ...this.searchTimers.keys(),
            ...this.searchRequests.keys(),
        ]);
        searchInputs.forEach((searchInput) => this.cancelSearch(searchInput));
    }

    beforeCache() {
        this.cancelAllSearches();
        this.rowsTarget
            .querySelectorAll('.roster-row[data-transient="true"]')
            .forEach((row) => row.remove());
        this.rowsTarget
            .querySelectorAll(".roster-person-search")
            .forEach((input) => {
                input.value = "";
            });
        this.rowsTarget
            .querySelectorAll(".roster-person-results")
            .forEach((results) => results.replaceChildren());
        this.reindexRows();
        this.updateRemoveButtons();
    }

    clearPersonSelection(row) {
        const hiddenInput = row.querySelector(".roster-person-id");
        if (hiddenInput) {
            hiddenInput.value = "";
        }

        const selectedDisplay = row.querySelector(".roster-person-selected");
        if (selectedDisplay) {
            const message = document.createElement("span");
            message.className = "text-muted fst-italic";
            message.textContent = "None selected";
            selectedDisplay.replaceChildren(message);
        }

        row.querySelector(".roster-person-results")?.replaceChildren();
    }

    selectPerson(row, id, text) {
        const hiddenInput = row.querySelector(".roster-person-id");
        if (hiddenInput) {
            hiddenInput.value = id;
        }

        const selectedDisplay = row.querySelector(".roster-person-selected");
        if (selectedDisplay) {
            const badge = document.createElement("span");
            badge.className = "badge bg-primary me-1";
            badge.append(document.createTextNode(String(text ?? "")));

            const clearButton = document.createElement("button");
            clearButton.type = "button";
            clearButton.className =
                "btn-close btn-close-white ms-1 roster-clear-person";
            clearButton.setAttribute("aria-label", "Clear");
            badge.append(" ", clearButton);
            selectedDisplay.replaceChildren(badge);
        }

        const searchInput = row.querySelector(".roster-person-search");
        if (searchInput) {
            this.cancelSearch(searchInput);
            searchInput.value = "";
        }

        row.querySelector(".roster-person-results")?.replaceChildren();
    }

    showSearchMessage(container, message, textClass) {
        const element = document.createElement("div");
        element.className = `${textClass} small py-1`;
        element.textContent = message;
        container.replaceChildren(element);
    }

    renderResults(results, container) {
        if (results.length === 0) {
            this.showSearchMessage(container, "No results found", "text-muted");
            return;
        }

        const list = document.createElement("div");
        list.className =
            "list-group list-group-flush position-absolute top-100 start-0 z-3 w-100 overflow-auto shadow roster-search-results";
        list.style.maxHeight = "200px";

        results.forEach((item) => {
            const button = document.createElement("button");
            button.type = "button";
            button.className =
                "list-group-item list-group-item-action py-1 small roster-search-result";
            button.dataset.id = String(item.value ?? "");
            button.dataset.text = String(item.text ?? "");
            button.textContent = button.dataset.text;
            list.append(button);
        });

        container.replaceChildren(list);
    }

    addRow() {
        const rowsElement = this.rowsTarget;
        const rows = rowsElement.querySelectorAll(".roster-row");
        const template = rows[0];
        if (!template) {
            return;
        }

        const newRow = template.cloneNode(true);
        const newIndex = rows.length;
        newRow.setAttribute("data-row-index", String(newIndex));
        newRow.dataset.transient = "true";

        newRow.querySelectorAll("input").forEach((input) => {
            if (input.name?.match(/rows\[\d+\]\[id\]$/)) {
                input.remove();
                return;
            }

            input.value = "";
            if (input.name) {
                input.name = input.name.replace(
                    /rows\[\d+\]/,
                    `rows[${newIndex}]`,
                );
            }
        });

        const selected = newRow.querySelector(".roster-person-selected");
        if (selected) {
            const message = document.createElement("span");
            message.className = "text-muted fst-italic";
            message.textContent = "None selected";
            selected.replaceChildren(message);
        }

        const results = newRow.querySelector(".roster-person-results");
        if (results) {
            results.replaceChildren();
        }

        rowsElement.appendChild(newRow);
        this.updateRemoveButtons();

        const searchInput = newRow.querySelector(".roster-person-search");
        if (searchInput) {
            searchInput.focus();
        }
    }

    reindexRows() {
        this.rowsTarget.querySelectorAll(".roster-row").forEach((row, idx) => {
            row.setAttribute("data-row-index", String(idx));
            row.querySelectorAll("input").forEach((field) => {
                if (field.name) {
                    field.name = field.name.replace(
                        /rows\[\d+\]/,
                        `rows[${idx}]`,
                    );
                }
            });
        });
    }

    updateRemoveButtons() {
        const onlyOne =
            this.rowsTarget.querySelectorAll(".roster-row").length <= 1;
        this.rowsTarget
            .querySelectorAll(".remove-row-btn")
            .forEach((button) => {
                button.disabled = onlyOne;
            });
    }

    autoSelectNewPerson(id, label) {
        this.rowsTarget.querySelectorAll(".roster-row").forEach((row) => {
            const hiddenInput = row.querySelector(".roster-person-id");
            if (hiddenInput && !hiddenInput.value) {
                this.selectPerson(row, id, label);
            }
        });
    }
}
